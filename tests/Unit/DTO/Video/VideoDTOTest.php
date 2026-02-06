<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Video;

use Kinescope\DTO\Video\VideoDTO;
use Kinescope\Enum\VideoStatus;
use PHPUnit\Framework\TestCase;

class VideoDTOTest extends TestCase
{
    public function testFromArrayCreatesValidVideoDTO(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'Test Video',
            'description' => 'Test description',
            'status' => 'done',
            'duration' => 120,
            'project_id' => 'project-uuid',
            'folder_id' => 'folder-uuid',
            'embed_code' => '<iframe></iframe>',
            'hls_link' => 'https://example.com/hls.m3u8',
            'dash_link' => 'https://example.com/manifest.mpd',
            'poster_url' => 'https://example.com/poster.jpg',
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'views_count' => 100,
            'plays_count' => 200,
            'assets' => [],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
        ];

        $video = VideoDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $video->id);
        $this->assertEquals('Test Video', $video->title);
        $this->assertEquals('Test description', $video->description);
        $this->assertEquals(VideoStatus::DONE, $video->status);
        $this->assertEquals(120, $video->duration);
        $this->assertEquals('project-uuid', $video->projectId);
        $this->assertEquals('folder-uuid', $video->folderId);
        $this->assertEquals('<iframe></iframe>', $video->embedCode);
        $this->assertEquals('https://example.com/hls.m3u8', $video->hlsLink);
        $this->assertEquals('https://example.com/manifest.mpd', $video->dashLink);
        $this->assertEquals('https://example.com/poster.jpg', $video->posterUrl);
        $this->assertEquals('https://example.com/thumb.jpg', $video->thumbnailUrl);
        $this->assertEquals(100, $video->viewsCount);
        $this->assertEquals(200, $video->playsCount);
        $this->assertEmpty($video->assets);
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'Test Video',
            'status' => 'pending',
            'duration' => 0,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ];

        $video = VideoDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $video->id);
        $this->assertEquals('Test Video', $video->title);
        $this->assertEquals(VideoStatus::PENDING, $video->status);
        $this->assertEquals(0, $video->duration);
        $this->assertNull($video->description);
        $this->assertEmpty($video->assets);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'Test Video',
            'status' => 'done',
            'duration' => 120,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
            'assets' => [],
        ];

        $video = VideoDTO::fromArray($data);
        $array = $video->toArray();

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $array['id']);
        $this->assertEquals('Test Video', $array['title']);
        $this->assertEquals('done', $array['status']);
        $this->assertEquals(120, $array['duration']);
    }

    public function testIsReadyReturnsTrueWhenStatusIsDone(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 120,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($video->isReady());
    }

    public function testIsReadyReturnsFalseWhenStatusIsProcessing(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'processing',
            'duration' => 120,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($video->isReady());
    }

    public function testIsProcessingReturnsTrueWhenStatusIsProcessing(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'processing',
            'duration' => 120,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($video->isProcessing());
    }

    public function testHasErrorReturnsTrueWhenStatusIsError(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'error',
            'duration' => 120,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($video->hasError());
    }

    public function testGetFormattedDurationReturnsHumanReadable(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 3661,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals('1:01:01', $video->getFormattedDuration());
    }

    public function testGetFormattedDurationReturnsHHMMSS(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 125,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals('2:05', $video->getFormattedDuration());
    }

    public function testGetFormattedDurationWithZero(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 0,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals('0:00', $video->getFormattedDuration());
    }

    public function testGetHighestQualityAssetReturnsBestQuality(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 120,
            'assets' => [
                [
                    'id' => 'a1',
                    'video_id' => 'video-1',
                    'height' => 480,
                ],
                [
                    'id' => 'a2',
                    'video_id' => 'video-1',
                    'height' => 720,
                ],
                [
                    'id' => 'a3',
                    'video_id' => 'video-1',
                    'height' => 1080,
                ],
            ],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $best = $video->getHighestQualityAsset();

        $this->assertNotNull($best);
        $this->assertEquals('a3', $best->id);
    }

    public function testGetHighestQualityAssetReturnsNullWhenNoAssets(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 120,
            'assets' => [],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertNull($video->getHighestQualityAsset());
    }

    public function testGetLowestQualityAssetReturnsLowestQuality(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 120,
            'assets' => [
                [
                    'id' => 'a1',
                    'video_id' => 'video-1',
                    'height' => 480,
                ],
                [
                    'id' => 'a2',
                    'video_id' => 'video-1',
                    'height' => 720,
                ],
                [
                    'id' => 'a3',
                    'video_id' => 'video-1',
                    'height' => 1080,
                ],
            ],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $lowest = $video->getLowestQualityAsset();

        $this->assertNotNull($lowest);
        $this->assertEquals('a1', $lowest->id);
    }

    public function testGetLowestQualityAssetReturnsNullWhenNoAssets(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 120,
            'assets' => [],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertNull($video->getLowestQualityAsset());
    }

    public function testHasHlsLinkReturnsTrueWhenHasLink(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 120,
            'hls_link' => 'https://example.com/hls.m3u8',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($video->hasHlsLink());
    }

    public function testHasHlsLinkReturnsFalseWhenNull(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 120,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($video->hasHlsLink());
    }

    public function testHasEmbedCodeReturnsTrueWhenHasCode(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 120,
            'embed_code' => '<iframe></iframe>',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($video->hasEmbedCode());
    }

    public function testHasEmbedCodeReturnsFalseWhenNull(): void
    {
        $video = VideoDTO::fromArray([
            'id' => '1',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 120,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($video->hasEmbedCode());
    }
}
