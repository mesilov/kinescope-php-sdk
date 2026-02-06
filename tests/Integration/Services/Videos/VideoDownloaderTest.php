<?php

declare(strict_types=1);

namespace Kinescope\Tests\Integration\Services\Videos;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Kinescope\Core\ApiClientFactory;
use Kinescope\Core\Credentials;
use Kinescope\Enum\QualityPreference;
use Kinescope\Services\Videos\VideoDownloader;
use Kinescope\Services\Videos\Videos;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryUsageProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Integration tests for VideoDownloader service.
 *
 * These tests verify that VideoDownloader works correctly
 * against the real Kinescope API.
 *
 * Requires KINESCOPE_API_KEY, TESTS_VIDEO_DOWNLOADER_VIDEO_ID,
 * and TESTS_VIDEO_DOWNLOADER_FOLDER_ID environment variables.
 *
 * @group integration
 */
class VideoDownloaderTest extends TestCase
{
    private VideoDownloader $downloader;

    private Filesystem $filesystem;

    private string $tempDir;

    private string $videoId;

    private string $folderId;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = getenv('KINESCOPE_API_KEY');
        $videoId = getenv('TESTS_VIDEO_DOWNLOADER_VIDEO_ID');
        $folderId = getenv('TESTS_VIDEO_DOWNLOADER_FOLDER_ID');

        if ($apiKey === false || $apiKey === '') {
            $this->markTestSkipped('KINESCOPE_API_KEY environment variable not set');
        }

        if ($videoId === false || $videoId === '') {
            $this->markTestSkipped('TESTS_VIDEO_DOWNLOADER_VIDEO_ID environment variable not set');
        }

        if ($folderId === false || $folderId === '') {
            $this->markTestSkipped('TESTS_VIDEO_DOWNLOADER_FOLDER_ID environment variable not set');
        }

        $this->videoId = $videoId;
        $this->folderId = $folderId;

        $credentials = Credentials::fromString($apiKey);
        $apiClient = ApiClientFactory::create()
            ->withCredentials($credentials)
            ->build();

        $videos = new Videos($apiClient);
        $this->filesystem = new Filesystem();

        $this->tempDir = dirname(__DIR__, 4) . '/var/temp/kinescope-sdk-test-' . uniqid();

        $logDir = dirname(__DIR__, 4) . '/var/logs';

        if (! is_dir($logDir)) {
            mkdir($logDir, 0o755, true);
        }
        $logger = new Logger('kinescope-test');
        $logger->pushHandler(new StreamHandler($logDir . '/video-downloader-test.log'));
        $logger->pushProcessor(new MemoryUsageProcessor());

        $this->downloader = new VideoDownloader(
            $videos,
            Psr18ClientDiscovery::find(),
            Psr17FactoryDiscovery::findRequestFactory(),
            $this->filesystem,
            $logger,
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->tempDir) && $this->filesystem->exists($this->tempDir)) {
            //  $this->filesystem->remove($this->tempDir);
        }

        parent::tearDown();
    }

    public function testDownloadVideoSavesFileToDestination(): void
    {
        $filePath = $this->downloader->downloadVideo($this->videoId, $this->tempDir, QualityPreference::WORST);

        $this->assertFileExists($filePath);
        $this->assertStringEndsWith($this->videoId . '.mp4', $filePath);
        $this->assertGreaterThan(0, filesize($filePath));
    }

    public function testDownloadVideoWithBestQuality(): void
    {
        $filePath = $this->downloader->downloadVideo($this->videoId, $this->tempDir, QualityPreference::BEST);

        $this->assertFileExists($filePath);
        $this->assertGreaterThan(0, filesize($filePath));
    }

    public function testDownloadVideoCreatesDestinationDirectory(): void
    {
        $nestedDir = $this->tempDir . '/nested/sub/dir';
        $this->assertDirectoryDoesNotExist($nestedDir);

        $filePath = $this->downloader->downloadVideo($this->videoId, $nestedDir, QualityPreference::WORST);

        $this->assertDirectoryExists($nestedDir);
        $this->assertFileExists($filePath);
    }

    public function testDownloadFolderDownloadsAllVideos(): void
    {
        $paths = $this->downloader->downloadFolder($this->folderId, $this->tempDir, QualityPreference::WORST);

        $this->assertNotEmpty($paths);

        foreach ($paths as $path) {
            $this->assertFileExists($path);
            $this->assertStringEndsWith('.mp4', $path);
            $this->assertGreaterThan(0, filesize($path));
        }
    }
}
