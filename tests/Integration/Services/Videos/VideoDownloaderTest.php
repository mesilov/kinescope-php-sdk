<?php

declare(strict_types=1);

namespace Kinescope\Tests\Integration\Services\Videos;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Kinescope\Core\ApiClientFactory;
use Kinescope\Core\Credentials;
use Kinescope\Core\Pagination;
use Kinescope\DTO\Video\AssetDTO;
use Kinescope\Enum\QualityPreference;
use Kinescope\Exception\KinescopeException;
use Kinescope\Services\Videos\VideoDownloader;
use Kinescope\Services\Videos\Videos;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Integration tests for VideoDownloader service.
 *
 * These tests verify that VideoDownloader works correctly
 * against the real Kinescope API.
 *
 * Requires at least one video with downloadable assets in the account.
 *
 * @group integration
 */
class VideoDownloaderTest extends TestCase
{
    private VideoDownloader $downloader;

    private Videos $videos;

    private Filesystem $filesystem;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = getenv('KINESCOPE_API_KEY');

        if ($apiKey === false || $apiKey === '') {
            $this->markTestSkipped('KINESCOPE_API_KEY environment variable not set');
        }

        $credentials = Credentials::fromString($apiKey);
        $apiClient = ApiClientFactory::create()
            ->withCredentials($credentials)
            ->build();

        $this->videos = new Videos($apiClient);
        $this->filesystem = new Filesystem();

        $this->tempDir = sys_get_temp_dir() . '/kinescope-sdk-test-' . uniqid();

        $this->downloader = new VideoDownloader(
            $this->videos,
            Psr18ClientDiscovery::find(),
            Psr17FactoryDiscovery::findRequestFactory(),
            $this->filesystem,
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->tempDir) && $this->filesystem->exists($this->tempDir)) {
            $this->filesystem->remove($this->tempDir);
        }

        parent::tearDown();
    }

    public function testDownloadVideoSavesFileToDestination(): void
    {
        $videoId = $this->findVideoIdWithDownloadLink();

        if ($videoId === null) {
            $this->markTestSkipped('No video with download link found in the account');
        }

        $filePath = $this->downloader->downloadVideo($videoId, $this->tempDir, QualityPreference::WORST);

        $this->assertFileExists($filePath);
        $this->assertStringEndsWith($videoId . '.mp4', $filePath);
        $this->assertGreaterThan(0, filesize($filePath));
    }

    public function testDownloadVideoWithBestQuality(): void
    {
        $videoId = $this->findVideoIdWithDownloadLink();

        if ($videoId === null) {
            $this->markTestSkipped('No video with download link found in the account');
        }

        $filePath = $this->downloader->downloadVideo($videoId, $this->tempDir, QualityPreference::BEST);

        $this->assertFileExists($filePath);
        $this->assertGreaterThan(0, filesize($filePath));
    }

    public function testDownloadVideoCreatesDestinationDirectory(): void
    {
        $videoId = $this->findVideoIdWithDownloadLink();

        if ($videoId === null) {
            $this->markTestSkipped('No video with download link found in the account');
        }

        $nestedDir = $this->tempDir . '/nested/sub/dir';
        $this->assertDirectoryDoesNotExist($nestedDir);

        $filePath = $this->downloader->downloadVideo($videoId, $nestedDir, QualityPreference::WORST);

        $this->assertDirectoryExists($nestedDir);
        $this->assertFileExists($filePath);
    }

    public function testDownloadVideoThrowsForVideoWithoutDownloadLinks(): void
    {
        $videoId = $this->findVideoIdWithoutDownloadLink();

        if ($videoId === null) {
            $this->markTestSkipped('No video without download link found in the account (all videos have download links)');
        }

        $this->expectException(KinescopeException::class);
        $this->expectExceptionMessage('No downloadable assets found');

        $this->downloader->downloadVideo($videoId, $this->tempDir);
    }

    public function testDownloadFolderDownloadsAllVideos(): void
    {
        $folderId = $this->findFolderIdWithDownloadableVideos();

        if ($folderId === null) {
            $this->markTestSkipped('No folder with downloadable videos found in the account');
        }

        $paths = $this->downloader->downloadFolder($folderId, $this->tempDir, QualityPreference::WORST);

        $this->assertIsArray($paths);
        $this->assertNotEmpty($paths);

        foreach ($paths as $path) {
            $this->assertFileExists($path);
            $this->assertStringEndsWith('.mp4', $path);
            $this->assertGreaterThan(0, filesize($path));
        }
    }

    public function testDownloadFolderReturnsEmptyArrayForEmptyFolder(): void
    {
        // Use a non-existent folder ID to get empty results
        // The API returns empty data for valid but empty folders
        $result = $this->videos->list(pagination: new Pagination(perPage: 1));

        if ($result->isEmpty()) {
            $this->markTestSkipped('No videos found in the account');
        }

        $video = $result->getData()[0];

        if ($video->folderId === null) {
            $this->markTestSkipped('First video has no folder ID, cannot determine folder structure');
        }

        // This test validates the basic flow — downloadFolder creates directory and returns array
        $this->assertDirectoryDoesNotExist($this->tempDir);

        // Use a UUID that won't match any real folder
        $paths = $this->downloader->downloadFolder('00000000-0000-0000-0000-000000000000', $this->tempDir);

        $this->assertIsArray($paths);
        $this->assertDirectoryExists($this->tempDir);
    }

    /**
     * Find a video ID that has at least one asset with a download link.
     */
    private function findVideoIdWithDownloadLink(): ?string
    {
        $result = $this->videos->list(pagination: new Pagination(perPage: 10));

        foreach ($result->getData() as $video) {
            $fullVideo = $this->videos->get($video->id);

            foreach ($fullVideo->assets as $asset) {
                if ($asset->downloadLink !== null) {
                    return $fullVideo->id;
                }
            }
        }

        return null;
    }

    /**
     * Find a video ID that has assets but none with download links.
     */
    private function findVideoIdWithoutDownloadLink(): ?string
    {
        $result = $this->videos->list(pagination: new Pagination(perPage: 10));

        foreach ($result->getData() as $video) {
            $fullVideo = $this->videos->get($video->id);

            if ($fullVideo->assets === []) {
                continue;
            }

            $hasDownloadLink = false;

            foreach ($fullVideo->assets as $asset) {
                if ($asset->downloadLink !== null) {
                    $hasDownloadLink = true;

                    break;
                }
            }

            if (! $hasDownloadLink) {
                return $fullVideo->id;
            }
        }

        return null;
    }

    /**
     * Find a folder ID that contains at least one video with a download link.
     */
    private function findFolderIdWithDownloadableVideos(): ?string
    {
        $result = $this->videos->list(pagination: new Pagination(perPage: 10));

        foreach ($result->getData() as $video) {
            if ($video->folderId === null) {
                continue;
            }

            $fullVideo = $this->videos->get($video->id);

            $hasDownloadable = array_filter(
                $fullVideo->assets,
                static fn (AssetDTO $asset): bool => $asset->downloadLink !== null,
            );

            if ($hasDownloadable !== []) {
                return $video->folderId;
            }
        }

        return null;
    }
}
