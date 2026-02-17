<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Videos;

use Kinescope\Contracts\ApiClientInterface;
use Kinescope\Enum\HttpMethod;
use Kinescope\Enum\QualityPreference;
use Kinescope\Event\Download\DownloadCompletedEvent;
use Kinescope\Event\Download\DownloadFailedEvent;
use Kinescope\Event\Download\DownloadProgressEvent;
use Kinescope\Event\Download\DownloadStartedEvent;
use Kinescope\Services\Videos\VideoDownloader;
use Kinescope\Services\Videos\Videos;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

class VideoDownloaderEventTest extends TestCase
{
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem();
    }

    public function testDownloadVideoDispatchesStartedProgressAndCompletedEvents(): void
    {
        $videoId = 'video-1';
        $sizeBytes = 1_500_000;
        $destinationDir = sys_get_temp_dir() . '/kinescope-sdk-unit-' . uniqid('', true);

        $requestFactory = new Psr17Factory();
        $response = new Response(
            status: 200,
            body: $requestFactory->createStream(str_repeat('a', $sizeBytes)),
        );

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $downloader = $this->createDownloader(
            httpClient: $httpClient,
            fileSize: $sizeBytes,
            selectedHeight: 1080,
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
        $this->assertGreaterThanOrEqual(1, count($progress));
        $this->assertCount(1, $completed);
        $this->assertCount(0, $failed);

        $startedEvent = $started[0];
        $this->assertSame($videoId, $startedEvent->videoId);
        $this->assertSame($sizeBytes, $startedEvent->sizeBytes);
        $this->assertSame(QualityPreference::BEST, $startedEvent->qualityPreference);
        $this->assertSame(1080, $startedEvent->selectedHeight);

        $progressEvent = $progress[0];
        $this->assertSame($videoId, $progressEvent->videoId);
        $this->assertSame($sizeBytes, $progressEvent->sizeBytes);
        $this->assertGreaterThan(0, $progressEvent->bytesWritten);
        $this->assertGreaterThanOrEqual(0.0, $progressEvent->percent);
        $this->assertLessThanOrEqual(100.0, $progressEvent->percent);

        $completedEvent = $completed[0];
        $this->assertSame($videoId, $completedEvent->videoId);
        $this->assertGreaterThan(0, $completedEvent->fileSize);
        $this->assertGreaterThanOrEqual(0, $completedEvent->durationMs);
    }

    public function testDownloadVideoDispatchesFailedEventWithOriginalException(): void
    {
        $videoId = 'video-2';
        $sizeBytes = 2_000_000;
        $destinationDir = sys_get_temp_dir() . '/kinescope-sdk-unit-' . uniqid('', true);

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(new RuntimeException('request failed'));

        $downloader = $this->createDownloader(
            httpClient: $httpClient,
            fileSize: $sizeBytes,
            selectedHeight: 720,
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
        $this->assertSame('request failed', $failed[0]->exception->getMessage());
    }

    private function createDownloader(ClientInterface $httpClient, int $fileSize, int $selectedHeight): VideoDownloader
    {
        $apiClient = new class ($fileSize, $selectedHeight) implements ApiClientInterface {
            public function __construct(
                private readonly int $fileSize,
                private readonly int $selectedHeight,
            ) {
            }

            public function get(string $endpoint, array $query = []): array
            {
                $videoId = basename($endpoint);

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
                                'height' => $this->selectedHeight,
                                'file_size' => $this->fileSize,
                                'download_link' => 'https://example.test/videos/' . $videoId . '.mp4',
                            ],
                        ],
                        'created_at' => '2024-01-01T00:00:00Z',
                        'updated_at' => '2024-01-01T00:00:00Z',
                    ],
                ];
            }

            public function post(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented in test stub.');
            }

            public function put(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented in test stub.');
            }

            public function patch(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented in test stub.');
            }

            public function delete(string $endpoint, array $query = []): array
            {
                throw new RuntimeException('Not implemented in test stub.');
            }

            public function request(HttpMethod $method, string $endpoint, array $options = []): array
            {
                throw new RuntimeException('Not implemented in test stub.');
            }
        };

        $videos = new Videos($apiClient);
        $requestFactory = new Psr17Factory();

        return new VideoDownloader(
            videos: $videos,
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            filesystem: $this->filesystem,
        );
    }
}
