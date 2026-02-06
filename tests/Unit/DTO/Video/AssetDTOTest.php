<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Video;

use Kinescope\DTO\Video\AssetDTO;
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

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $asset->id);
        $this->assertEquals('video-uuid', $asset->videoId);
        $this->assertEquals('1080p', $asset->quality);
        $this->assertEquals(1920, $asset->width);
        $this->assertEquals(1080, $asset->height);
        $this->assertEquals(5000000, $asset->bitrate);
        $this->assertEquals(104857600, $asset->fileSize);
        $this->assertEquals('h264', $asset->codec);
        $this->assertEquals('https://example.com/video.mp4', $asset->url);
        $this->assertEquals('https://example.com/download/video.mp4', $asset->downloadLink);
    }

    public function testFromArrayWithNullableFields(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'video_id' => 'video-uuid',
            'quality' => '720p',
        ];

        $asset = AssetDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $asset->id);
        $this->assertEquals('video-uuid', $asset->videoId);
        $this->assertEquals('720p', $asset->quality);
        $this->assertNull($asset->width);
        $this->assertNull($asset->height);
        $this->assertNull($asset->bitrate);
        $this->assertNull($asset->fileSize);
        $this->assertNull($asset->codec);
        $this->assertNull($asset->url);
        $this->assertNull($asset->downloadLink);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $data = [
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
        ];

        $asset = AssetDTO::fromArray($data);
        $array = $asset->toArray();

        $this->assertEquals('asset-1', $array['id']);
        $this->assertEquals('video-1', $array['video_id']);
        $this->assertEquals('1080p', $array['quality']);
        $this->assertEquals(1920, $array['width']);
        $this->assertEquals(1080, $array['height']);
        $this->assertEquals(5000000, $array['bitrate']);
        $this->assertEquals(1073741824, $array['file_size']);
        $this->assertEquals('h264', $array['codec']);
        $this->assertEquals('https://example.com/video.mp4', $array['url']);
        $this->assertEquals('https://example.com/download/video.mp4', $array['download_link']);
        $this->assertEquals('2024-01-01T00:00:00+00:00', $array['created_at']);
    }

    public function testGetResolutionReturnsNullWhenNoDimensions(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'video-1',
        ]);

        $this->assertNull($asset->getResolution());
    }

    public function testGetResolutionReturnsFormattedString(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'video-1',
            'width' => 1920,
            'height' => 1080,
        ]);

        $this->assertEquals('1920x1080', $asset->getResolution());
    }

    public function testGetAspectRatioReturnsNullWhenNoDimensions(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'video-1',
        ]);

        $this->assertNull($asset->getAspectRatio());
    }

    public function testGetAspectRatioReturnsCorrectValue(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'video-1',
            'width' => 16,
            'height' => 9,
        ]);

        $this->assertEquals(16 / 9, $asset->getAspectRatio());
    }

    public function testIsHdReturnsTrueFor720pAndAbove(): void
    {
        $asset720 = AssetDTO::fromArray([
            'id' => '1', 'video_id' => 'v1', 'height' => 720,
        ]);
        $asset1080 = AssetDTO::fromArray([
            'id' => '2', 'video_id' => 'v1', 'height' => 1080,
        ]);
        $asset4k = AssetDTO::fromArray([
            'id' => '3', 'video_id' => 'v1', 'height' => 2160,
        ]);
        $asset480 = AssetDTO::fromArray([
            'id' => '4', 'video_id' => 'v1', 'height' => 480,
        ]);

        $this->assertTrue($asset720->isHd());
        $this->assertTrue($asset1080->isHd());
        $this->assertTrue($asset4k->isHd());
        $this->assertFalse($asset480->isHd());
    }

    public function testIsFullHdReturnsTrueFor1080pAndAbove(): void
    {
        $asset1080 = AssetDTO::fromArray([
            'id' => '1', 'video_id' => 'v1', 'height' => 1080,
        ]);
        $asset4k = AssetDTO::fromArray([
            'id' => '2', 'video_id' => 'v1', 'height' => 2160,
        ]);
        $asset720 = AssetDTO::fromArray([
            'id' => '3', 'video_id' => 'v1', 'height' => 720,
        ]);

        $this->assertTrue($asset1080->isFullHd());
        $this->assertTrue($asset4k->isFullHd());
        $this->assertFalse($asset720->isFullHd());
    }

    public function testIs4KReturnsTrueFor2160pAndAbove(): void
    {
        $asset4k = AssetDTO::fromArray([
            'id' => '1', 'video_id' => 'v1', 'height' => 2160,
        ]);

        $this->assertTrue($asset4k->is4K());
    }

    public function testIs4KReturnsFalseForBelow4K(): void
    {
        $asset1080 = AssetDTO::fromArray([
            'id' => '1', 'video_id' => 'v1', 'height' => 1080,
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

        $this->assertEquals('1.00 GB', $asset->getHumanFileSize());
    }

    public function testGetHumanFileSizeWithNullSize(): void
    {
        $asset = AssetDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
        ]);

        $this->assertNull($asset->getHumanFileSize());
    }

    public function testGetHumanFileSizeForSmallFiles(): void
    {
        $assetKB = AssetDTO::fromArray([
            'id' => '1', 'video_id' => 'v1', 'file_size' => 1024,
        ]);

        $assetMB = AssetDTO::fromArray([
            'id' => '2', 'video_id' => 'v1', 'file_size' => 1048576,
        ]);

        $this->assertEquals('1.00 KB', $assetKB->getHumanFileSize());
        $this->assertEquals('1.00 MB', $assetMB->getHumanFileSize());
    }
}
