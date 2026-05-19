<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos;

use Carbon\CarbonImmutable;
use Kinescope\Core\Pagination;
use Kinescope\Enum\QualityPreference;
use Kinescope\Event\Download\DownloadCompletedEvent;
use Kinescope\Event\Download\DownloadFailedEvent;
use Kinescope\Event\Download\DownloadProgressEvent;
use Kinescope\Event\Download\DownloadStartedEvent;
use Kinescope\Exception\KinescopeException;
use Kinescope\Services\Videos\Download\CurlFileTransfer;
use Kinescope\Services\Videos\Download\FileTransferInterface;
use Kinescope\Services\Videos\Download\FileTransferProgress;
use Kinescope\Services\Videos\Download\FileTransferRequest;
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
    private const int PROGRESS_REPORT_INTERVAL_BYTES = 10_485_760; // 10 MiB

    public function __construct(
        private Videos $videos,
        private Filesystem $filesystem = new Filesystem(),
        private FileTransferInterface $fileTransfer = new CurlFileTransfer(),
        private EventDispatcherInterface $eventDispatcher = new EventDispatcher(),
        private AssetSelector $assetSelector = new AssetSelector(),
        private LoggerInterface $logger = new NullLogger(),
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

        $asset = $this->assetSelector->select($video->assets, $quality);

        if ($asset === null) {
            throw new KinescopeException(
                sprintf('No downloadable assets found for video "%s"', $videoId),
            );
        }

        /** @var string $downloadLink */
        $downloadLink = $asset->downloadLink;
        $streamSizeBytes = $asset->videoStreamSize;
        $selectedHeight = $asset->resolution === null ? 0 : $asset->resolution->height;

        if ($streamSizeBytes <= 0) {
            throw new KinescopeException(sprintf(
                'Selected asset has invalid video stream size for video "%s": %d',
                $videoId,
                $streamSizeBytes,
            ));
        }

        $this->logger->info('Selected asset for download', [
            'videoId' => $videoId,
            'quality' => $selectedHeight,
            'videoStreamSize' => $streamSizeBytes,
            'downloadLink' => $downloadLink,
        ]);

        $this->filesystem->mkdir($destinationDir);

        $filePath = rtrim($destinationDir, '/') . '/' . $videoId . '.mp4';
        $partPath = $filePath . '.part';
        $bytesWritten = 0;
        $nextProgressReportAt = self::PROGRESS_REPORT_INTERVAL_BYTES;

        $this->eventDispatcher->dispatch(new DownloadStartedEvent(
            videoId: $videoId,
            downloadUrl: $downloadLink,
            sizeBytes: $streamSizeBytes,
            qualityPreference: $quality,
            selectedHeight: $selectedHeight,
            occurredAt: $startedAt,
        ));

        try {
            $result = $this->fileTransfer->transfer(
                request: new FileTransferRequest(
                    url: $downloadLink,
                    outputPath: $partPath,
                    expectedBytes: $streamSizeBytes,
                ),
                onProgress: function (FileTransferProgress $progress) use (
                    $videoId,
                    $filePath,
                    $streamSizeBytes,
                    &$bytesWritten,
                    &$nextProgressReportAt,
                ): void {
                    $bytesWritten = $progress->bytesWritten;

                    if ($progress->bytesWritten < $nextProgressReportAt) {
                        return;
                    }

                    while ($progress->bytesWritten >= $nextProgressReportAt) {
                        $nextProgressReportAt += self::PROGRESS_REPORT_INTERVAL_BYTES;
                    }

                    $progressTotalBytes = $progress->totalBytes !== null && $progress->totalBytes > 0
                        ? $progress->totalBytes
                        : $streamSizeBytes;
                    $percent = $progress->percent() ?? round($progress->bytesWritten / $progressTotalBytes * 100, 1);

                    $this->logger->debug('Download progress', [
                        'filePath' => $filePath,
                        'bytesWritten' => $progress->bytesWritten,
                        'totalBytes' => $progressTotalBytes,
                        'percent' => $percent,
                    ]);

                    $this->eventDispatcher->dispatch(new DownloadProgressEvent(
                        videoId: $videoId,
                        filePath: $filePath,
                        bytesWritten: $progress->bytesWritten,
                        sizeBytes: $progressTotalBytes,
                        percent: $percent,
                        occurredAt: CarbonImmutable::now('UTC'),
                    ));
                },
            );

            $bytesWritten = $result->bytesWritten;
            $validationBytes = $result->reportedBytes ?? $streamSizeBytes;

            if ($result->reportedBytes !== null && $result->reportedBytes !== $streamSizeBytes) {
                $this->logger->warning('Transfer reported size differs from selected asset stream metadata', [
                    'videoId' => $videoId,
                    'assetVideoStreamSize' => $streamSizeBytes,
                    'reportedBytes' => $result->reportedBytes,
                ]);
            }

            if ($result->bytesWritten !== $validationBytes) {
                throw new KinescopeException(sprintf(
                    'Completed transfer size mismatch for video "%s": expected %d bytes, got %d bytes.',
                    $videoId,
                    $validationBytes,
                    $result->bytesWritten,
                ));
            }

            $this->filesystem->rename($partPath, $filePath, true);
        } catch (Throwable $exception) {
            $this->cleanupPartFile($partPath);

            $this->eventDispatcher->dispatch(new DownloadFailedEvent(
                videoId: $videoId,
                filePath: $filePath,
                totalBytes: $streamSizeBytes,
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

    private function cleanupPartFile(string $partPath): void
    {
        try {
            $this->filesystem->remove($partPath);
        } catch (Throwable $cleanupException) {
            $this->logger->warning('Failed to remove incomplete download part file', [
                'partPath' => $partPath,
                'exception' => $cleanupException,
            ]);
        }
    }
}
