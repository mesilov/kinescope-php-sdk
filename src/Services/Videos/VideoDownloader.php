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

        $this->filesystem->mkdir($destinationDir);

        /** @var string $downloadLink */
        $downloadLink = $asset->downloadLink;
        $request = $this->requestFactory->createRequest('GET', $downloadLink);
        $response = $this->httpClient->sendRequest($request);

        $filePath = rtrim($destinationDir, '/') . '/' . $videoId . '.mp4';

        $this->writeStreamToFile($response->getBody(), $filePath);

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
        $this->filesystem->mkdir($destinationDir);

        $paths = [];
        $pagination = new Pagination();

        do {
            $result = $this->videos->listByFolder($folderId, $pagination);

            foreach ($result->getData() as $video) {
                $paths[] = $this->downloadVideo($video->id, $destinationDir, $quality);
            }

            if (! $result->hasNextPage()) {
                break;
            }

            $pagination = $pagination->nextPage();
        } while (true);

        return $paths;
    }

    /**
     * @throws KinescopeException
     */
    private function writeStreamToFile(StreamInterface $stream, string $filePath): void
    {
        $fileHandle = @fopen($filePath, 'wb');

        if ($fileHandle === false) {
            throw new KinescopeException(sprintf('Failed to open file for writing: "%s"', $filePath));
        }

        try {
            while (! $stream->eof()) {
                $chunk = $stream->read(self::CHUNK_SIZE);

                if ($chunk !== '') {
                    fwrite($fileHandle, $chunk);
                }
            }
        } finally {
            fclose($fileHandle);
        }
    }
}
