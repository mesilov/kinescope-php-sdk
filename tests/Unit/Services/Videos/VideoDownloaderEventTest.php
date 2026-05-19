<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Videos;

use Kinescope\Enum\QualityPreference;
use Kinescope\Event\Download\DownloadCompletedEvent;
use Kinescope\Event\Download\DownloadFailedEvent;
use Kinescope\Event\Download\DownloadProgressEvent;
use Kinescope\Event\Download\DownloadStartedEvent;
use Kinescope\Services\Videos\VideoDownloader;
use Kinescope\Services\Videos\Videos;
use Kinescope\Tests\Unit\FakeApiClient;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

final class VideoDownloaderEventTest extends TestCase
{
    private const int PROGRESS_INTERVAL_BYTES = 10_485_760;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem();
    }

    public function testDownloadVideoDispatchesStartedProgressAndCompletedEvents(): void
    {
        $videoId = 'video-1';
        $sizeBytes = 12_000_000;
        $destinationDir = sys_get_temp_dir() . '/kinescope-sdk-unit-' . uniqid('', true);
        $fileTransfer = new FakeFileTransfer(progressBytes: [
            1_000_000,
            self::PROGRESS_INTERVAL_BYTES,
            self::PROGRESS_INTERVAL_BYTES + 1,
        ]);

        $downloader = $this->createDownloader(
            videoStreamSize: $sizeBytes,
            selectedHeight: 1080,
            fileTransfer: $fileTransfer,
        );

        $started = [];
        $progress = [];
        $completed = [];
        $failed = [];

        $downloader
            ->on(DownloadStartedEvent::class, static function (DownloadStartedEvent $event) use (&$started): void {
                $started[] = $event;
            })
            ->on(DownloadProgressEvent::class, static function (DownloadProgressEvent $event) use (&$progress): void {
                $progress[] = $event;
            })
            ->on(DownloadCompletedEvent::class, static function (DownloadCompletedEvent $event) use (&$completed): void {
                $completed[] = $event;
            })
            ->on(DownloadFailedEvent::class, static function (DownloadFailedEvent $event) use (&$failed): void {
                $failed[] = $event;
            });

        try {
            $filePath = $downloader->downloadVideo($videoId, $destinationDir, QualityPreference::BEST);

            $this->assertFileExists($filePath);
            $this->assertSame($destinationDir . '/' . $videoId . '.mp4', $filePath);
        } finally {
            $this->filesystem->remove($destinationDir);
        }

        $this->assertCount(1, $started);
        $this->assertCount(1, $progress);
        $this->assertCount(1, $completed);
        $this->assertCount(0, $failed);
        $this->assertSame(1, $fileTransfer->requestCount());

        $startedEvent = $started[0];
        $this->assertSame($videoId, $startedEvent->videoId);
        $this->assertSame($sizeBytes, $startedEvent->sizeBytes);
        $this->assertSame(QualityPreference::BEST, $startedEvent->qualityPreference);
        $this->assertSame(1080, $startedEvent->selectedHeight);

        $progressEvent = $progress[0];
        $this->assertSame($videoId, $progressEvent->videoId);
        $this->assertSame($destinationDir . '/' . $videoId . '.mp4', $progressEvent->filePath);
        $this->assertSame($sizeBytes, $progressEvent->sizeBytes);
        $this->assertSame(self::PROGRESS_INTERVAL_BYTES, $progressEvent->bytesWritten);
        $this->assertSame(87.4, $progressEvent->percent);

        $completedEvent = $completed[0];
        $this->assertSame($videoId, $completedEvent->videoId);
        $this->assertSame($destinationDir . '/' . $videoId . '.mp4', $completedEvent->filePath);
        $this->assertSame($sizeBytes, $completedEvent->fileSize);
        $this->assertGreaterThanOrEqual(0, $completedEvent->durationMs);
    }

    public function testDownloadVideoDispatchesFailedEventWithOriginalException(): void
    {
        $videoId = 'video-2';
        $sizeBytes = 2_000_000;
        $destinationDir = sys_get_temp_dir() . '/kinescope-sdk-unit-' . uniqid('', true);
        $exception = new RuntimeException('request failed');
        $fileTransfer = new FakeFileTransfer(
            exception: $exception,
            partialBytesBeforeFailure: 1024,
        );

        $downloader = $this->createDownloader(
            videoStreamSize: $sizeBytes,
            selectedHeight: 720,
            fileTransfer: $fileTransfer,
        );

        $started = [];
        $failed = [];

        $downloader
            ->on(DownloadStartedEvent::class, static function (DownloadStartedEvent $event) use (&$started): void {
                $started[] = $event;
            })
            ->on(DownloadFailedEvent::class, static function (DownloadFailedEvent $event) use (&$failed): void {
                $failed[] = $event;
            });

        try {
            $downloader->downloadVideo($videoId, $destinationDir, QualityPreference::WORST);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('request failed', $exception->getMessage());
        } finally {
            if ($this->filesystem->exists($destinationDir)) {
                $this->filesystem->remove($destinationDir);
            }
        }

        $this->assertCount(1, $started);
        $this->assertCount(1, $failed);
        $this->assertSame($videoId, $failed[0]->videoId);
        $this->assertSame($sizeBytes, $failed[0]->totalBytes);
        $this->assertSame(0, $failed[0]->bytesWritten);
        $this->assertSame('request failed', $failed[0]->exception->getMessage());
        $this->assertFileDoesNotExist($destinationDir . '/' . $videoId . '.mp4');
        $this->assertFileDoesNotExist($destinationDir . '/' . $videoId . '.mp4.part');
    }

    private function createDownloader(int $videoStreamSize, int $selectedHeight, FakeFileTransfer $fileTransfer): VideoDownloader
    {
        return new VideoDownloader(
            filesystem: $this->filesystem,
            videos: new Videos(new FakeApiClient()->queueResponse($this->videoResponse(
                videoId: 'video-' . ($selectedHeight === 1080 ? '1' : '2'),
                videoStreamSize: $videoStreamSize,
                selectedHeight: $selectedHeight,
            ))),
            fileTransfer: $fileTransfer,
        );
    }

    /**
     * @return array{data: array<string, mixed>}
     */
    private function videoResponse(string $videoId, int $videoStreamSize, int $selectedHeight): array
    {
        return [
            'data' => [
                'id' => $videoId,
                'title' => 'Test Video',
                'status' => 'done',
                'duration' => 120,
                'assets' => [
                    [
                        'id' => 'asset-1',
                        'video_id' => $videoId,
                        'resolution' => sprintf('1920x%d', $selectedHeight),
                        'file_size' => $videoStreamSize,
                        'download_link' => 'https://example.test/videos/' . $videoId . '.mp4',
                    ],
                ],
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
            ],
        ];
    }
}
