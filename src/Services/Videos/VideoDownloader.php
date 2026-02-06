<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos;

use Kinescope\Core\Pagination;
use Kinescope\DTO\Video\AssetDTO;
use Kinescope\Enum\QualityPreference;
use Kinescope\Exception\KinescopeException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Service for downloading video files from Kinescope.
 */
final readonly class VideoDownloader
{
    private const int CHUNK_SIZE = 8192;

    public function __construct(
        private Videos $videos,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private Filesystem $filesystem,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * Download a single video by ID.
     *
     * Saves the file to $destinationDir/{videoId}.mp4
     *
     * @param string $videoId Video UUID
     * @param string $destinationDir Directory to save the file
     * @param QualityPreference $quality Quality selection strategy
     *
     * @throws KinescopeException If no downloadable asset is found or download fails
     *
     * @return string Path to the saved file
     */
    public function downloadVideo(
        string $videoId,
        string $destinationDir,
        QualityPreference $quality = QualityPreference::BEST,
    ): string {
        $this->logger->info('Starting video download', [
            'videoId' => $videoId,
            'quality' => $quality->name,
        ]);

        $video = $this->videos->get($videoId);

        $downloadableAssets = array_filter(
            $video->assets,
            static fn (AssetDTO $asset): bool => $asset->downloadLink !== null,
        );

        if ($downloadableAssets === []) {
            throw new KinescopeException(
                sprintf('No downloadable assets found for video "%s"', $videoId),
            );
        }

        $downloadableAssets = array_values($downloadableAssets);

        usort(
            $downloadableAssets,
            static fn (AssetDTO $a, AssetDTO $b): int => $quality === QualityPreference::BEST
                ? ($b->height ?? 0) <=> ($a->height ?? 0)
                : ($a->height ?? 0) <=> ($b->height ?? 0),
        );

        $asset = $downloadableAssets[0];

        /** @var string $downloadLink */
        $downloadLink = $asset->downloadLink;

        $this->logger->info('Selected asset for download', [
            'videoId' => $videoId,
            'quality' => $asset->height,
            'downloadLink' => $downloadLink,
        ]);

        $this->filesystem->mkdir($destinationDir);

        $request = $this->requestFactory->createRequest('GET', $downloadLink);
        $response = $this->httpClient->sendRequest($request);

        $filePath = rtrim($destinationDir, '/') . '/' . $videoId . '.mp4';

        $contentLength = $response->getHeaderLine('Content-Length');
        $totalBytes = $contentLength !== '' ? (int) $contentLength : null;
        $this->writeStreamToFile($response->getBody(), $filePath, $totalBytes);

        $this->logger->info('Video download completed', [
            'videoId' => $videoId,
            'filePath' => $filePath,
            'fileSize' => filesize($filePath),
        ]);

        return $filePath;
    }

    /**
     * Download all videos from a folder.
     *
     * @param string $folderId Folder UUID
     * @param string $destinationDir Directory to save the files
     * @param QualityPreference $quality Quality selection strategy
     *
     * @throws KinescopeException On API or download errors
     *
     * @return array<string> Paths to saved files
     */
    public function downloadFolder(
        string $folderId,
        string $destinationDir,
        QualityPreference $quality = QualityPreference::BEST,
    ): array {
        $this->logger->info('Starting folder download', [
            'folderId' => $folderId,
            'quality' => $quality->name,
        ]);

        $this->filesystem->mkdir($destinationDir);

        $paths = [];
        $index = 0;
        $pagination = new Pagination();

        do {
            $result = $this->videos->listByFolder($folderId, $pagination);

            foreach ($result->getData() as $video) {
                $index++;
                $this->logger->debug('Downloading video from folder', [
                    'videoId' => $video->id,
                    'index' => $index,
                ]);
                $paths[] = $this->downloadVideo($video->id, $destinationDir, $quality);
            }

            if (! $result->hasNextPage()) {
                break;
            }

            $pagination = $pagination->nextPage();
        } while (true);

        $this->logger->info('Folder download completed', [
            'folderId' => $folderId,
            'totalVideos' => count($paths),
        ]);

        return $paths;
    }

    /**
     * @throws KinescopeException
     */
    private function writeStreamToFile(StreamInterface $stream, string $filePath, ?int $totalBytes = null): void
    {
        $this->logger->info('Writing stream to file', [
            'filePath' => $filePath,
            'totalBytes' => $totalBytes,
        ]);

        $fileHandle = @fopen($filePath, 'wb');

        if ($fileHandle === false) {
            throw new KinescopeException(sprintf('Failed to open file for writing: "%s"', $filePath));
        }

        try {
            $bytesWritten = 0;
            $chunkCount = 0;

            while (! $stream->eof()) {
                $chunk = $stream->read(self::CHUNK_SIZE);

                if ($chunk !== '') {
                    fwrite($fileHandle, $chunk);
                    $bytesWritten += strlen($chunk);
                    $chunkCount++;

                    if ($chunkCount % 128 === 0) {
                        $context = [
                            'filePath' => $filePath,
                            'bytesWritten' => $bytesWritten,
                            'totalBytes' => $totalBytes,
                        ];

                        if ($totalBytes !== null && $totalBytes > 0) {
                            $context['percent'] = round($bytesWritten / $totalBytes * 100, 1);
                        }

                        $this->logger->debug('Download progress', $context);
                    }
                }
            }

            $this->logger->info('File write completed', [
                'filePath' => $filePath,
                'bytesWritten' => $bytesWritten,
            ]);
        } finally {
            fclose($fileHandle);
        }
    }
}
