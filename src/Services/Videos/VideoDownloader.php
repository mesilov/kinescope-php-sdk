<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos;

use Carbon\CarbonImmutable;
use Kinescope\Core\Pagination;
use Kinescope\DTO\Video\AssetDTO;
use Kinescope\Enum\QualityPreference;
use Kinescope\Event\Download\DownloadCompletedEvent;
use Kinescope\Event\Download\DownloadFailedEvent;
use Kinescope\Event\Download\DownloadProgressEvent;
use Kinescope\Event\Download\DownloadStartedEvent;
use Kinescope\Exception\KinescopeException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

/**
 * Service for downloading video files from Kinescope.
 */
final readonly class VideoDownloader
{
    private const int CHUNK_SIZE = 262_144; // 256 KiB
    private const int PROGRESS_REPORT_INTERVAL_BYTES = 1_048_576; // 1 MiB

    public function __construct(
        private Videos $videos,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private Filesystem $filesystem,
        private LoggerInterface $logger = new NullLogger(),
        private EventDispatcherInterface $eventDispatcher = new EventDispatcher(),
    ) {
    }

    /**
     * Register an event listener on the internal dispatcher.
     *
     * @param class-string $eventName Event class name
     * @param callable $listener Listener callback
     * @param int $priority Listener priority (higher runs earlier)
     */
    public function on(string $eventName, callable $listener, int $priority = 0): self
    {
        $this->eventDispatcher->addListener($eventName, $listener, $priority);

        return $this;
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
        $startedAt = CarbonImmutable::now('UTC');

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
        $sizeBytes = $asset->fileSize;
        $selectedHeight = $asset->height ?? 0;

        if ($sizeBytes <= 0) {
            throw new KinescopeException(sprintf(
                'Selected asset has invalid file size for video "%s": %d',
                $videoId,
                $sizeBytes,
            ));
        }

        $this->logger->info('Selected asset for download', [
            'videoId' => $videoId,
            'quality' => $selectedHeight,
            'fileSize' => $sizeBytes,
            'downloadLink' => $downloadLink,
        ]);

        $this->filesystem->mkdir($destinationDir);

        $filePath = rtrim($destinationDir, '/') . '/' . $videoId . '.mp4';
        $bytesWritten = 0;

        $this->eventDispatcher->dispatch(new DownloadStartedEvent(
            videoId: $videoId,
            downloadUrl: $downloadLink,
            sizeBytes: $sizeBytes,
            qualityPreference: $quality,
            selectedHeight: $selectedHeight,
            occurredAt: $startedAt,
        ));

        try {
            $request = $this->requestFactory->createRequest('GET', $downloadLink);
            $response = $this->httpClient->sendRequest($request);

            $bytesWritten = $this->writeStreamToFile(
                stream: $response->getBody(),
                filePath: $filePath,
                sizeBytes: $sizeBytes,
                onProgress: function (int $writtenBytes, float $percent) use ($videoId, $filePath, $sizeBytes): void {
                    $this->eventDispatcher->dispatch(new DownloadProgressEvent(
                        videoId: $videoId,
                        filePath: $filePath,
                        bytesWritten: $writtenBytes,
                        sizeBytes: $sizeBytes,
                        percent: $percent,
                        occurredAt: CarbonImmutable::now('UTC'),
                    ));
                },
            );
        } catch (Throwable $exception) {
            $this->eventDispatcher->dispatch(new DownloadFailedEvent(
                videoId: $videoId,
                filePath: $filePath,
                totalBytes: $sizeBytes,
                bytesWritten: $bytesWritten,
                exception: $exception,
                occurredAt: CarbonImmutable::now('UTC'),
            ));

            throw $exception;
        }

        $completedAt = CarbonImmutable::now('UTC');
        $durationMs = (int) round($startedAt->diffInMilliseconds($completedAt));
        $actualFileSize = filesize($filePath);
        $fileSize = $actualFileSize === false ? $bytesWritten : $actualFileSize;

        $this->logger->info('Video download completed', [
            'videoId' => $videoId,
            'filePath' => $filePath,
            'fileSize' => $fileSize,
        ]);

        $this->eventDispatcher->dispatch(new DownloadCompletedEvent(
            videoId: $videoId,
            filePath: $filePath,
            fileSize: $fileSize,
            durationMs: $durationMs,
            occurredAt: $completedAt,
        ));

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
    private function writeStreamToFile(
        StreamInterface $stream,
        string $filePath,
        int $sizeBytes,
        ?callable $onProgress = null,
    ): int {
        $this->logger->info('Writing stream to file', [
            'filePath' => $filePath,
            'totalBytes' => $sizeBytes,
        ]);

        $fileHandle = @fopen($filePath, 'wb');

        if ($fileHandle === false) {
            throw new KinescopeException(sprintf('Failed to open file for writing: "%s"', $filePath));
        }

        try {
            $bytesWritten = 0;
            $nextProgressReportAt = self::PROGRESS_REPORT_INTERVAL_BYTES;

            while (! $stream->eof()) {
                $chunk = $stream->read(self::CHUNK_SIZE);

                if ($chunk !== '') {
                    fwrite($fileHandle, $chunk);
                    $bytesWritten += strlen($chunk);

                    if ($bytesWritten >= $nextProgressReportAt) {
                        while ($bytesWritten >= $nextProgressReportAt) {
                            $nextProgressReportAt += self::PROGRESS_REPORT_INTERVAL_BYTES;
                        }

                        $percent = round($bytesWritten / $sizeBytes * 100, 1);
                        $context = [
                            'filePath' => $filePath,
                            'bytesWritten' => $bytesWritten,
                            'totalBytes' => $sizeBytes,
                            'percent' => $percent,
                        ];

                        $this->logger->debug('Download progress', $context);
                        $onProgress?->__invoke($bytesWritten, $percent);
                    }
                }
            }

            $this->logger->info('File write completed', [
                'filePath' => $filePath,
                'bytesWritten' => $bytesWritten,
            ]);

            return $bytesWritten;
        } finally {
            fclose($fileHandle);
        }
    }
}
