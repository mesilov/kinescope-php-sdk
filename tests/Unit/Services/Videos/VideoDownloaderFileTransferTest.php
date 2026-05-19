<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Videos;

use Kinescope\Enum\QualityPreference;
use Kinescope\Event\Download\DownloadCompletedEvent;
use Kinescope\Exception\KinescopeException;
use Kinescope\Services\Videos\Download\FileTransferInterface;
use Kinescope\Services\Videos\VideoDownloader;
use Kinescope\Services\Videos\Videos;
use Kinescope\Tests\Unit\FakeApiClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

final class VideoDownloaderFileTransferTest extends TestCase
{
    public function testConstructorUsesDownloaderDependenciesWithLoggerLast(): void
    {
        $constructor = new ReflectionMethod(VideoDownloader::class, '__construct');
        $parameters = $constructor->getParameters();

        $this->assertSame([
            'videos',
            'filesystem',
            'fileTransfer',
            'eventDispatcher',
            'assetSelector',
            'logger',
        ], array_map(static fn ($parameter): string => $parameter->getName(), $parameters));

        $this->assertFalse($parameters[0]->isDefaultValueAvailable());
        $this->assertSame(FileTransferInterface::class, (string) $parameters[2]->getType());
        $this->assertSame(EventDispatcherInterface::class, (string) $parameters[3]->getType());
        $this->assertSame(LoggerInterface::class, (string) $parameters[5]->getType());
    }

    public function testSuccessfulTransferUsesPartPathThenCommitsFinalFile(): void
    {
        $filesystem = new Filesystem();
        $destinationDir = sys_get_temp_dir() . '/kinescope-sdk-unit-' . uniqid('', true);
        $fileTransfer = new FakeFileTransfer(byte: 's');
        $downloader = $this->createDownloader(
            videoId: 'video-success',
            videoStreamSize: 42,
            fileTransfer: $fileTransfer,
            filesystem: $filesystem,
        );

        try {
            $filePath = $downloader->downloadVideo('video-success', $destinationDir);

            $this->assertSame($destinationDir . '/video-success.mp4', $filePath);
            $this->assertFileExists($filePath);
            $this->assertSame(str_repeat('s', 42), file_get_contents($filePath));
            $this->assertFileDoesNotExist($filePath . '.part');

            $request = $fileTransfer->requestAt(0);
            $this->assertSame('https://example.test/videos/video-success.mp4', $request->url);
            $this->assertSame($filePath . '.part', $request->outputPath);
            $this->assertSame(42, $request->expectedBytes);
        } finally {
            $filesystem->remove($destinationDir);
        }
    }

    public function testIncompleteTransferFailsAndDeletesPartFile(): void
    {
        $filesystem = new Filesystem();
        $destinationDir = sys_get_temp_dir() . '/kinescope-sdk-unit-' . uniqid('', true);
        $downloader = $this->createDownloader(
            videoId: 'video-incomplete',
            videoStreamSize: 42,
            fileTransfer: new FakeFileTransfer(bytesWritten: 41),
            filesystem: $filesystem,
        );

        $this->expectException(KinescopeException::class);
        $this->expectExceptionMessage('Completed transfer size mismatch');

        try {
            $downloader->downloadVideo('video-incomplete', $destinationDir);
        } finally {
            $this->assertFileDoesNotExist($destinationDir . '/video-incomplete.mp4');
            $this->assertFileDoesNotExist($destinationDir . '/video-incomplete.mp4.part');
            $filesystem->remove($destinationDir);
        }
    }

    public function testTransferReportedBytesCanOverrideStaleAssetVideoStreamSize(): void
    {
        $filesystem = new Filesystem();
        $destinationDir = sys_get_temp_dir() . '/kinescope-sdk-unit-' . uniqid('', true);
        $downloader = $this->createDownloader(
            videoId: 'video-stale-file-size',
            videoStreamSize: 42,
            fileTransfer: new FakeFileTransfer(bytesWritten: 50, reportedBytes: 50, byte: 'r'),
            filesystem: $filesystem,
        );

        try {
            $filePath = $downloader->downloadVideo('video-stale-file-size', $destinationDir);

            $this->assertFileExists($filePath);
            $this->assertSame(50, filesize($filePath));
            $this->assertSame(str_repeat('r', 50), file_get_contents($filePath));
        } finally {
            $filesystem->remove($destinationDir);
        }
    }

    public function testFailedTransferDeletesPartFileAndDoesNotDispatchCompletedEvent(): void
    {
        $filesystem = new Filesystem();
        $destinationDir = sys_get_temp_dir() . '/kinescope-sdk-unit-' . uniqid('', true);
        $downloader = $this->createDownloader(
            videoId: 'video-failure',
            videoStreamSize: 42,
            fileTransfer: new FakeFileTransfer(
                exception: new RuntimeException('transfer exploded'),
                partialBytesBeforeFailure: 20,
            ),
            filesystem: $filesystem,
        );
        $completed = [];

        $downloader->on(DownloadCompletedEvent::class, static function (DownloadCompletedEvent $event) use (&$completed): void {
            $completed[] = $event;
        });

        try {
            $downloader->downloadVideo('video-failure', $destinationDir);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('transfer exploded', $exception->getMessage());
        } finally {
            $this->assertSame([], $completed);
            $this->assertFileDoesNotExist($destinationDir . '/video-failure.mp4');
            $this->assertFileDoesNotExist($destinationDir . '/video-failure.mp4.part');
            $filesystem->remove($destinationDir);
        }
    }

    public function testDownloadFolderReusesSameInjectedTransfer(): void
    {
        $filesystem = new Filesystem();
        $destinationDir = sys_get_temp_dir() . '/kinescope-sdk-unit-' . uniqid('', true);
        $fileTransfer = new FakeFileTransfer();
        $apiClient = new FakeApiClient()
            ->queueResponse([
                'data' => [
                    $this->videoPayload(videoId: 'video-1', videoStreamSize: 10),
                    $this->videoPayload(videoId: 'video-2', videoStreamSize: 20),
                ],
                'meta' => ['pagination' => ['total' => 2, 'page' => 1, 'per_page' => 20]],
            ])
            ->queueResponse($this->videoResponse(videoId: 'video-1', videoStreamSize: 10))
            ->queueResponse($this->videoResponse(videoId: 'video-2', videoStreamSize: 20));
        $downloader = new VideoDownloader(
            videos: new Videos($apiClient),
            filesystem: $filesystem,
            fileTransfer: $fileTransfer,
        );

        try {
            $paths = $downloader->downloadFolder('folder-1', $destinationDir, QualityPreference::BEST);

            $this->assertSame([
                $destinationDir . '/video-1.mp4',
                $destinationDir . '/video-2.mp4',
            ], $paths);
            $this->assertSame(2, $fileTransfer->requestCount());
            $this->assertSame('https://example.test/videos/video-1.mp4', $fileTransfer->requestAt(0)->url);
            $this->assertSame('https://example.test/videos/video-2.mp4', $fileTransfer->requestAt(1)->url);
        } finally {
            $filesystem->remove($destinationDir);
        }
    }

    private function createDownloader(
        string $videoId,
        int $videoStreamSize,
        FakeFileTransfer $fileTransfer,
        Filesystem $filesystem,
    ): VideoDownloader {
        return new VideoDownloader(
            videos: new Videos(new FakeApiClient()->queueResponse($this->videoResponse(
                videoId: $videoId,
                videoStreamSize: $videoStreamSize,
            ))),
            filesystem: $filesystem,
            fileTransfer: $fileTransfer,
        );
    }

    /**
     * @return array{data: array<string, mixed>}
     */
    private function videoResponse(string $videoId, int $videoStreamSize): array
    {
        return ['data' => $this->videoPayload(videoId: $videoId, videoStreamSize: $videoStreamSize)];
    }

    /**
     * @return array<string, mixed>
     */
    private function videoPayload(string $videoId, int $videoStreamSize): array
    {
        return [
            'id' => $videoId,
            'title' => 'Test Video',
            'status' => 'done',
            'duration' => 120,
            'folder_id' => 'folder-1',
            'assets' => [
                [
                    'id' => $videoId . '-asset',
                    'video_id' => $videoId,
                    'resolution' => '1920x1080',
                    'file_size' => $videoStreamSize,
                    'download_link' => 'https://example.test/videos/' . $videoId . '.mp4',
                ],
            ],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ];
    }
}
