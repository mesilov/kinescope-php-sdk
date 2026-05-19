<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Videos;

use Kinescope\DTO\Video\AssetDTO;
use Kinescope\DTO\Video\Resolution;
use Kinescope\Enum\QualityPreference;
use Kinescope\Services\Videos\AssetSelector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetSelectorTest extends TestCase
{
    public function testReturnsNullWhenNoAssets(): void
    {
        $selector = new AssetSelector();

        self::assertNull($selector->select([], QualityPreference::WORST));
        self::assertNull($selector->select([], QualityPreference::BEST));
    }

    public function testReturnsNullWhenNoDownloadableAssets(): void
    {
        $selector = new AssetSelector();
        $assets = [
            self::asset(id: 'a', fileSize: 10, downloadLink: null),
            self::asset(id: 'b', fileSize: 20, downloadLink: null),
        ];

        self::assertNull($selector->select($assets, QualityPreference::WORST));
        self::assertNull($selector->select($assets, QualityPreference::BEST));
    }

    public function testReturnsSingleDownloadableAsset(): void
    {
        $selector = new AssetSelector();
        $only = self::asset(id: 'only', fileSize: 42, downloadLink: 'https://example.test/only.mp4');

        $result = $selector->select([$only], QualityPreference::WORST);

        self::assertSame($only, $result);
    }

    public function testIgnoresAssetsWithoutDownloadLink(): void
    {
        $selector = new AssetSelector();
        $downloadable = self::asset(id: 'good', fileSize: 100, downloadLink: 'https://example.test/good.mp4');
        $assets = [
            self::asset(id: 'bad', fileSize: 1, downloadLink: null),
            $downloadable,
            self::asset(id: 'also-bad', fileSize: 1, downloadLink: null),
        ];

        $result = $selector->select($assets, QualityPreference::WORST);

        self::assertSame($downloadable, $result);
    }

    /**
     * Reproduces issue #5: API returns assets with `height = null`, the first one is `original`,
     * and WORST must still pick the smallest fileSize.
     */
    public function testWorstSelectsSmallestFileSizeWhenHeightsAreMissing(): void
    {
        $selector = new AssetSelector();
        $original = self::asset(id: 'original', fileSize: 113_552_377, downloadLink: 'https://example.test/original.mp4');
        $tiny = self::asset(id: '360p', fileSize: 2_587_668, downloadLink: 'https://example.test/360p.mp4');

        $result = $selector->select([$original, $tiny], QualityPreference::WORST);

        self::assertSame($tiny, $result);
    }

    public function testWorstSelectsSmallestFileSize(): void
    {
        $selector = new AssetSelector();
        $assets = [
            self::asset(id: '1080p', fileSize: 50, height: 1080, downloadLink: 'https://example.test/1080p.mp4'),
            self::asset(id: '360p', fileSize: 5, height: 360, downloadLink: 'https://example.test/360p.mp4'),
            self::asset(id: '720p', fileSize: 20, height: 720, downloadLink: 'https://example.test/720p.mp4'),
        ];

        $result = $selector->select($assets, QualityPreference::WORST);

        self::assertNotNull($result);
        self::assertSame('360p', $result->id);
    }

    public function testWorstBreaksFileSizeTieByLowerKnownHeight(): void
    {
        $selector = new AssetSelector();
        $assets = [
            self::asset(id: 'a-1080p', fileSize: 10, height: 1080, downloadLink: 'https://example.test/a.mp4'),
            self::asset(id: 'b-360p', fileSize: 10, height: 360, downloadLink: 'https://example.test/b.mp4'),
        ];

        $result = $selector->select($assets, QualityPreference::WORST);

        self::assertNotNull($result);
        self::assertSame('b-360p', $result->id);
    }

    public function testWorstPrefersKnownHeightOverNullWhenFileSizesTie(): void
    {
        $selector = new AssetSelector();
        $knownHeight = self::asset(id: 'known', fileSize: 10, height: 720, downloadLink: 'https://example.test/known.mp4');
        $unknownHeight = self::asset(id: 'unknown', fileSize: 10, downloadLink: 'https://example.test/unknown.mp4');

        $result = $selector->select([$unknownHeight, $knownHeight], QualityPreference::WORST);

        self::assertSame($knownHeight, $result);
    }

    public function testBestSelectsHighestKnownHeight(): void
    {
        $selector = new AssetSelector();
        $assets = [
            self::asset(id: '720p', fileSize: 30, height: 720, downloadLink: 'https://example.test/720p.mp4'),
            self::asset(id: '1080p', fileSize: 60, height: 1080, downloadLink: 'https://example.test/1080p.mp4'),
            self::asset(id: '360p', fileSize: 10, height: 360, downloadLink: 'https://example.test/360p.mp4'),
        ];

        $result = $selector->select($assets, QualityPreference::BEST);

        self::assertNotNull($result);
        self::assertSame('1080p', $result->id);
    }

    public function testBestPrefersKnownHeightOverNull(): void
    {
        $selector = new AssetSelector();
        $knownHeight = self::asset(id: 'known', fileSize: 1, height: 360, downloadLink: 'https://example.test/known.mp4');
        $unknownHeight = self::asset(id: 'unknown', fileSize: 1, downloadLink: 'https://example.test/unknown.mp4');

        $result = $selector->select([$unknownHeight, $knownHeight], QualityPreference::BEST);

        self::assertSame($knownHeight, $result);
    }

    public function testBestKeepsFirstWhenAllHeightsAreMissing(): void
    {
        $selector = new AssetSelector();
        $first = self::asset(id: 'first', fileSize: 100, downloadLink: 'https://example.test/first.mp4');
        $second = self::asset(id: 'second', fileSize: 200, downloadLink: 'https://example.test/second.mp4');

        $result = $selector->select([$first, $second], QualityPreference::BEST);

        self::assertSame($first, $result);
    }

    /**
     * @param list<AssetDTO> $assets
     */
    #[DataProvider('realWorldPayloadCases')]
    public function testRealWorldKinescopePayload(QualityPreference $quality, array $assets, string $expectedId): void
    {
        $selector = new AssetSelector();

        $result = $selector->select($assets, $quality);

        self::assertNotNull($result);
        self::assertSame($expectedId, $result->id);
    }

    /**
     * Mirrors the Kinescope `GET /v1/videos/{id}` payload observed on 2026-05-13:
     * all heights are null, original comes first, file_size is the only discriminator.
     *
     * @return iterable<string, array{quality: QualityPreference, assets: list<AssetDTO>, expectedId: string}>
     */
    public static function realWorldPayloadCases(): iterable
    {
        $assets = [
            self::asset(id: 'original', fileSize: 113_552_377, downloadLink: 'https://example.test/original.mp4'),
            self::asset(id: '1080p', fileSize: 12_983_745, downloadLink: 'https://example.test/1080p.mp4'),
            self::asset(id: '720p', fileSize: 6_448_559, downloadLink: 'https://example.test/720p.mp4'),
            self::asset(id: '480p', fileSize: 3_646_570, downloadLink: 'https://example.test/480p.mp4'),
            self::asset(id: '360p', fileSize: 2_587_668, downloadLink: 'https://example.test/360p.mp4'),
        ];

        yield 'worst picks 360p' => [
            'quality' => QualityPreference::WORST,
            'assets' => $assets,
            'expectedId' => '360p',
        ];

        yield 'best keeps original when heights are unknown' => [
            'quality' => QualityPreference::BEST,
            'assets' => $assets,
            'expectedId' => 'original',
        ];
    }

    private static function asset(
        string $id,
        int $fileSize,
        ?string $downloadLink,
        ?int $height = null,
    ): AssetDTO {
        $resolution = $height === null ? null : new Resolution(width: 1920, height: $height);

        return new AssetDTO(
            id: $id,
            videoId: 'video-test',
            originalName: null,
            fileSize: $fileSize,
            md5: null,
            filetype: 'mp4',
            quality: null,
            resolution: $resolution,
            createdAt: null,
            url: null,
            downloadLink: $downloadLink,
        );
    }
}
