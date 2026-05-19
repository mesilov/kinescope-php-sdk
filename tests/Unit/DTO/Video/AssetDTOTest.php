<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Video;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Kinescope\DTO\Video\AssetDTO;
use PHPUnit\Framework\TestCase;

final class AssetDTOTest extends TestCase
{
    public function testFromArrayMapsCurrentApiPayload(): void
    {
        $asset = AssetDTO::fromArray($this->payload());

        self::assertSame('asset-id', $asset->id);
        self::assertSame('video-id', $asset->videoId);
        self::assertSame('original', $asset->originalName);
        self::assertSame(17267227, $asset->fileSize);
        self::assertSame('md5-hash', $asset->md5);
        self::assertSame('mp4', $asset->filetype);
        self::assertSame('720p', $asset->quality);
        self::assertNotNull($asset->resolution);
        self::assertSame(1280, $asset->resolution->width);
        self::assertSame(720, $asset->resolution->height);
        self::assertInstanceOf(CarbonImmutable::class, $asset->createdAt);
        self::assertSame('2025-07-30T19:01:15.692226Z', $asset->createdAt->toJSON());
    }

    public function testToArrayUsesRawApiFieldNames(): void
    {
        $array = AssetDTO::fromArray($this->payload())->toArray();

        self::assertSame('original', $array['original_name']);
        self::assertSame(17267227, $array['file_size']);
        self::assertSame('md5-hash', $array['md5']);
        self::assertSame('mp4', $array['filetype']);
        self::assertSame('1280x720', $array['resolution']);
        self::assertArrayNotHasKey('bitrate', $array);
        self::assertArrayNotHasKey('codec', $array);
    }

    public function testResolutionHelpers(): void
    {
        $asset = AssetDTO::fromArray($this->payload(resolution: '3840x2160'));
        $unknown = AssetDTO::fromArray($this->payload(resolution: 'not-a-resolution'));

        self::assertTrue($asset->isHd());
        self::assertTrue($asset->isFullHd());
        self::assertTrue($asset->is4K());
        self::assertSame(3840 / 2160, $asset->getAspectRatio());
        self::assertNull($unknown->resolution);
        self::assertNull($unknown->getAspectRatio());
    }

    public function testFileSizeIsRequiredAndPositive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AssetDTO::fromArray($this->payload(fileSize: 0));
    }

    public function testHumanFileSize(): void
    {
        self::assertSame('1.00 MB', AssetDTO::fromArray($this->payload(fileSize: 1024 * 1024))->getHumanFileSize());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(int $fileSize = 17267227, string $resolution = '1280x720'): array
    {
        return [
            'id' => 'asset-id',
            'video_id' => 'video-id',
            'original_name' => 'original',
            'file_size' => $fileSize,
            'md5' => 'md5-hash',
            'filetype' => 'mp4',
            'quality' => '720p',
            'resolution' => $resolution,
            'created_at' => '2025-07-30T19:01:15.692226Z',
            'url' => 'https://example.com/video.mp4',
            'download_link' => 'https://example.com/download.mp4',
        ];
    }
}
