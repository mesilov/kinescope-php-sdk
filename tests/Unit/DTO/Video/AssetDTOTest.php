<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Video;

use InvalidArgumentException;
use Kinescope\DTO\Video\AssetDTO;
use Kinescope\DTO\Video\Resolution;
use PHPUnit\Framework\TestCase;

class AssetDTOTest extends TestCase
{
    public function testFromArrayCreatesValidAssetDTO(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'video_id' => 'video-uuid',
            'quality' => '1080p',
            'width' => 1920,
            'height' => 1080,
            'bitrate' => 5000000,
            'file_size' => 104857600,
            'codec' => 'h264',
            'url' => 'https://example.com/video.mp4',
            'download_link' => 'https://example.com/download/video.mp4',
            'created_at' => '2024-01-01T00:00:00Z',
        ];

        $asset = AssetDTO::fromArray($data);

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $asset->id);
        $this->assertSame('video-uuid', $asset->videoId);
        $this->assertSame('1080p', $asset->quality);
        $this->assertInstanceOf(Resolution::class, $asset->resolution);
        $this->assertSame(1920, $asset->resolution->width);
        $this->assertSame(1080, $asset->resolution->height);
        $this->assertSame(5000000, $asset->bitrate);
        $this->assertSame(104857600, $asset->fileSize);
        $this->assertSame('h264', $asset->codec);
        $this->assertSame('https://example.com/video.mp4', $asset->url);
        $this->assertSame('https://example.com/download/video.mp4', $asset->downloadLink);
    }

    public function testFromArrayParsesResolutionStringWhenNumericFieldsAreAbsent(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'file_size' => 1024,
            'resolution' => '1920x1080',
        ]);

        $this->assertInstanceOf(Resolution::class, $asset->resolution);
        $this->assertSame(1920, $asset->resolution->width);
        $this->assertSame(1080, $asset->resolution->height);
    }

    public function testFromArrayPrefersNumericFieldsOverResolutionString(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'file_size' => 1024,
            'width' => 1280,
            'height' => 720,
            'resolution' => '1920x1080',
        ]);

        $this->assertInstanceOf(Resolution::class, $asset->resolution);
        $this->assertSame(1280, $asset->resolution->width);
        $this->assertSame(720, $asset->resolution->height);
    }

    public function testFromArrayIgnoresMalformedResolutionString(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'file_size' => 1024,
            'resolution' => '1080p',
        ]);

        $this->assertNull($asset->resolution);
    }

    public function testFromArrayIgnoresPartialNumericDimensions(): void
    {
        $assetWidthOnly = AssetDTO::fromArray([
            'id' => '1', 'video_id' => 'v1', 'file_size' => 1024, 'width' => 1920,
        ]);

        $assetHeightOnly = AssetDTO::fromArray([
            'id' => '2', 'video_id' => 'v1', 'file_size' => 1024, 'height' => 1080,
        ]);

        $this->assertNull($assetWidthOnly->resolution);
        $this->assertNull($assetHeightOnly->resolution);
    }

    public function testFromArrayRequiresFileSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Asset "file_size" is required.');

        AssetDTO::fromArray([
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'video_id' => 'video-uuid',
            'quality' => '720p',
        ]);
    }

    public function testToArrayEmitsResolutionStringAndDropsLegacyKeys(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => 'asset-1',
            'video_id' => 'video-1',
            'quality' => '1080p',
            'width' => 1920,
            'height' => 1080,
            'bitrate' => 5000000,
            'file_size' => 1073741824,
            'codec' => 'h264',
            'url' => 'https://example.com/video.mp4',
            'download_link' => 'https://example.com/download/video.mp4',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $array = $asset->toArray();

        $this->assertSame('asset-1', $array['id']);
        $this->assertSame('video-1', $array['video_id']);
        $this->assertSame('1080p', $array['quality']);
        $this->assertSame('1920x1080', $array['resolution']);
        $this->assertArrayNotHasKey('width', $array);
        $this->assertArrayNotHasKey('height', $array);
        $this->assertSame(5000000, $array['bitrate']);
        $this->assertSame(1073741824, $array['file_size']);
        $this->assertSame('h264', $array['codec']);
        $this->assertSame('https://example.com/video.mp4', $array['url']);
        $this->assertSame('https://example.com/download/video.mp4', $array['download_link']);
        $this->assertSame('2024-01-01T00:00:00+00:00', $array['created_at']);
    }

    public function testToArrayEmitsNullResolutionWhenMissing(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'file_size' => 1024,
        ]);

        $array = $asset->toArray();

        $this->assertArrayHasKey('resolution', $array);
        $this->assertNull($array['resolution']);
    }

    public function testResolutionIsNullWhenAbsent(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'video-1',
            'file_size' => 1024,
        ]);

        $this->assertNull($asset->resolution);
    }

    public function testGetAspectRatioReturnsNullWhenResolutionMissing(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'video-1',
            'file_size' => 1024,
        ]);

        $this->assertNull($asset->getAspectRatio());
    }

    public function testGetAspectRatioReturnsCorrectValue(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'video-1',
            'file_size' => 1024,
            'width' => 16,
            'height' => 9,
        ]);

        $this->assertEqualsWithDelta(16 / 9, $asset->getAspectRatio(), 0.0001);
    }

    public function testIsHdReturnsTrueFor720pAndAbove(): void
    {
        $asset720 = AssetDTO::fromArray([
            'id' => '1', 'video_id' => 'v1', 'file_size' => 1024, 'width' => 1280, 'height' => 720,
        ]);
        $asset1080 = AssetDTO::fromArray([
            'id' => '2', 'video_id' => 'v1', 'file_size' => 1024, 'width' => 1920, 'height' => 1080,
        ]);
        $asset4k = AssetDTO::fromArray([
            'id' => '3', 'video_id' => 'v1', 'file_size' => 1024, 'width' => 3840, 'height' => 2160,
        ]);
        $asset480 = AssetDTO::fromArray([
            'id' => '4', 'video_id' => 'v1', 'file_size' => 1024, 'width' => 852, 'height' => 480,
        ]);
        $assetUnknown = AssetDTO::fromArray([
            'id' => '5', 'video_id' => 'v1', 'file_size' => 1024,
        ]);

        $this->assertTrue($asset720->isHd());
        $this->assertTrue($asset1080->isHd());
        $this->assertTrue($asset4k->isHd());
        $this->assertFalse($asset480->isHd());
        $this->assertFalse($assetUnknown->isHd());
    }

    public function testIsFullHdReturnsTrueFor1080pAndAbove(): void
    {
        $asset1080 = AssetDTO::fromArray([
            'id' => '1', 'video_id' => 'v1', 'file_size' => 1024, 'width' => 1920, 'height' => 1080,
        ]);
        $asset4k = AssetDTO::fromArray([
            'id' => '2', 'video_id' => 'v1', 'file_size' => 1024, 'width' => 3840, 'height' => 2160,
        ]);
        $asset720 = AssetDTO::fromArray([
            'id' => '3', 'video_id' => 'v1', 'file_size' => 1024, 'width' => 1280, 'height' => 720,
        ]);

        $this->assertTrue($asset1080->isFullHd());
        $this->assertTrue($asset4k->isFullHd());
        $this->assertFalse($asset720->isFullHd());
    }

    public function testIs4KReturnsTrueFor2160pAndAbove(): void
    {
        $asset4k = AssetDTO::fromArray([
            'id' => '1', 'video_id' => 'v1', 'file_size' => 1024, 'width' => 3840, 'height' => 2160,
        ]);

        $this->assertTrue($asset4k->is4K());
    }

    public function testIs4KReturnsFalseForBelow4K(): void
    {
        $asset1080 = AssetDTO::fromArray([
            'id' => '1', 'video_id' => 'v1', 'file_size' => 1024, 'width' => 1920, 'height' => 1080,
        ]);

        $this->assertFalse($asset1080->is4K());
    }

    public function testGetHumanFileSizeReturnsFormattedString(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'file_size' => 1073741824,
        ]);

        $this->assertSame('1.00 GB', $asset->getHumanFileSize());
    }

    public function testFromArrayRejectsNonPositiveFileSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Asset "file_size" must be greater than 0.');

        AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'file_size' => 0,
        ]);
    }

    public function testGetHumanFileSizeForSmallFiles(): void
    {
        $assetKB = AssetDTO::fromArray([
            'id' => '1', 'video_id' => 'v1', 'file_size' => 1024,
        ]);

        $assetMB = AssetDTO::fromArray([
            'id' => '2', 'video_id' => 'v1', 'file_size' => 1048576,
        ]);

        $this->assertSame('1.00 KB', $assetKB->getHumanFileSize());
        $this->assertSame('1.00 MB', $assetMB->getHumanFileSize());
    }
}
