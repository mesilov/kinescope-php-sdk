<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Video;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Kinescope\DTO\Video\AssetDTO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AssetDTOTest extends TestCase
{
    public function testFromArrayMapsCurrentApiPayload(): void
    {
        $asset = AssetDTO::fromArray($this->payload());

        self::assertSame('asset-id', $asset->id);
        self::assertSame('video-id', $asset->videoId);
        self::assertSame('original', $asset->originalName);
        self::assertSame(17267227, $asset->videoStreamSize);
        self::assertFalse(new ReflectionClass(AssetDTO::class)->hasProperty('fileSize'));
        self::assertSame('md5-hash', $asset->md5);
        self::assertSame('mp4', $asset->filetype);
        self::assertSame('720p', $asset->quality);
        self::assertNotNull($asset->resolution);
        self::assertSame(1280, $asset->resolution->width);
        self::assertSame(720, $asset->resolution->height);
        self::assertInstanceOf(CarbonImmutable::class, $asset->createdAt);
        self::assertSame('2025-07-30T19:01:15.692226Z', $asset->createdAt->toJSON());
    }

    public function testToArrayUsesSdkStreamSizeFieldName(): void
    {
        $array = AssetDTO::fromArray($this->payload())->toArray();

        self::assertSame('original', $array['original_name']);
        self::assertSame(17267227, $array['video_stream_size']);
        self::assertSame('md5-hash', $array['md5']);
        self::assertSame('mp4', $array['filetype']);
        self::assertSame('1280x720', $array['resolution']);
        self::assertArrayNotHasKey('file_size', $array);
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

    public function testVideoStreamSizeIsRequiredAndPositive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AssetDTO::fromArray($this->payload(videoStreamSize: 0));
    }

    public function testHumanVideoStreamSize(): void
    {
        self::assertSame('1.00 MB', AssetDTO::fromArray($this->payload(videoStreamSize: 1024 * 1024))->getHumanVideoStreamSize());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(int $videoStreamSize = 17267227, string $resolution = '1280x720'): array
    {
        return [
            'id' => 'asset-id',
            'video_id' => 'video-id',
            'original_name' => 'original',
            'file_size' => $videoStreamSize,
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
