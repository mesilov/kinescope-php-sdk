<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Videos;

use Kinescope\Enum\QualityPreference;
use Kinescope\Exception\KinescopeException;
use Kinescope\Services\Videos\VideoDownloader;
use Kinescope\Services\Videos\Videos;
use Kinescope\Tests\Unit\FakeApiClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class VideoDownloaderQualitySelectionTest extends TestCase
{
    /**
     * @return iterable<string, array{
     *     quality: QualityPreference,
     *     assets: list<array<string, mixed>>,
     *     expectedUrl: string,
     *     expectedSize: int,
     * }>
     */
    public static function assetSelectionCases(): iterable
    {
        yield 'worst selects smallest file when heights are missing' => [
            'quality' => QualityPreference::WORST,
            'assets' => [
                self::asset(id: 'asset-original', quality: 'original', fileSize: 100, downloadLink: 'https://example.test/original.mp4'),
                self::asset(id: 'asset-360p', quality: '360p', fileSize: 10, downloadLink: 'https://example.test/360p.mp4'),
            ],
            'expectedUrl' => 'https://example.test/360p.mp4',
            'expectedSize' => 10,
        ];

        yield 'best selects highest known height' => [
            'quality' => QualityPreference::BEST,
            'assets' => [
                self::asset(id: 'asset-720p', quality: '720p', height: 720, fileSize: 30, downloadLink: 'https://example.test/720p.mp4'),
                self::asset(id: 'asset-1080p', quality: '1080p', height: 1080, fileSize: 60, downloadLink: 'https://example.test/1080p.mp4'),
                self::asset(id: 'asset-360p', quality: '360p', height: 360, fileSize: 10, downloadLink: 'https://example.test/360p.mp4'),
            ],
            'expectedUrl' => 'https://example.test/1080p.mp4',
            'expectedSize' => 60,
        ];

        yield 'worst uses lower known height when file sizes tie' => [
            'quality' => QualityPreference::WORST,
            'assets' => [
                self::asset(id: 'asset-1080p', quality: '1080p', height: 1080, fileSize: 10, downloadLink: 'https://example.test/1080p.mp4'),
                self::asset(id: 'asset-360p', quality: '360p', height: 360, fileSize: 10, downloadLink: 'https://example.test/360p.mp4'),
            ],
            'expectedUrl' => 'https://example.test/360p.mp4',
            'expectedSize' => 10,
        ];

        yield 'assets without download links are ignored' => [
            'quality' => QualityPreference::WORST,
            'assets' => [
                self::asset(id: 'asset-undownloadable', quality: '360p', height: 360, fileSize: 1, downloadLink: null),
                self::asset(id: 'asset-downloadable', quality: '720p', height: 720, fileSize: 10, downloadLink: 'https://example.test/720p.mp4'),
            ],
            'expectedUrl' => 'https://example.test/720p.mp4',
            'expectedSize' => 10,
        ];
    }

    /**
     * @param list<array<string, mixed>> $assets
     */
    #[DataProvider('assetSelectionCases')]
    public function testDownloadVideoSelectsAssetByQualityPreference(
        QualityPreference $quality,
        array $assets,
        string $expectedUrl,
        int $expectedSize,
    ): void {
        $videoId = 'video-quality-selection';
        $destinationDir = sys_get_temp_dir() . '/kinescope-sdk-unit-' . uniqid('', true);
        $filesystem = new Filesystem();
        $fileTransfer = new FakeFileTransfer();

        $downloader = new VideoDownloader(
            videos: new Videos($this->createApiClient($assets)),
            filesystem: $filesystem,
            fileTransfer: $fileTransfer,
        );

        try {
            $downloader->downloadVideo($videoId, $destinationDir, $quality);
        } finally {
            $filesystem->remove($destinationDir);
        }

        $request = $fileTransfer->requestAt(0);

        $this->assertSame($expectedUrl, $request->url);
        $this->assertSame($destinationDir . '/' . $videoId . '.mp4.part', $request->outputPath);
        $this->assertSame($expectedSize, $request->expectedBytes);
        $this->assertSame([], $request->headers);
    }

    public function testDownloadVideoFailsWhenNoDownloadableAssetExists(): void
    {
        $videoId = 'video-quality-selection';
        $destinationDir = sys_get_temp_dir() . '/kinescope-sdk-unit-' . uniqid('', true);
        $filesystem = new Filesystem();
        $fileTransfer = new FakeFileTransfer();

        $downloader = new VideoDownloader(
            videos: new Videos($this->createApiClient([
                self::asset(id: 'asset-undownloadable', quality: '360p', height: 360, fileSize: 10, downloadLink: null),
            ])),
            filesystem: $filesystem,
            fileTransfer: $fileTransfer,
        );

        $this->expectException(KinescopeException::class);
        $this->expectExceptionMessage('No downloadable assets found for video "video-quality-selection"');

        try {
            $downloader->downloadVideo($videoId, $destinationDir, QualityPreference::WORST);
        } finally {
            $filesystem->remove($destinationDir);
        }

        $this->assertSame(0, $fileTransfer->requestCount());
    }

    /**
     * @return array{
     *     id: string,
     *     video_id: string,
     *     quality: string,
     *     resolution: string|null,
     *     file_size: int,
     *     download_link: string|null,
     * }
     */
    private static function asset(
        string $id,
        string $quality,
        int $fileSize,
        ?string $downloadLink,
        ?int $height = null,
    ): array {
        return [
            'id' => $id,
            'video_id' => 'video-quality-selection',
            'quality' => $quality,
            'resolution' => $height === null ? null : sprintf('1920x%d', $height),
            'file_size' => $fileSize,
            'download_link' => $downloadLink,
        ];
    }

    /**
     * @param list<array<string, mixed>> $assets
     */
    private function createApiClient(array $assets): FakeApiClient
    {
        return new FakeApiClient()->queueResponse([
            'data' => [
                'id' => 'video-quality-selection',
                'title' => 'Quality Selection Test Video',
                'status' => 'done',
                'duration' => 120,
                'assets' => $assets,
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
            ],
        ]);
    }
}
