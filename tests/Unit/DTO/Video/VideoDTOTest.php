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

    public function testFromArrayParsesEveryDocumentedStatus(): void
    {
        $this->assertEquals(VideoStatus::PENDING, VideoDTO::fromArray($this->minimalData('pending'))->status);
        $this->assertEquals(VideoStatus::UPLOADING, VideoDTO::fromArray($this->minimalData('uploading'))->status);
        $this->assertEquals(VideoStatus::PRE_PROCESSING, VideoDTO::fromArray($this->minimalData('pre-processing'))->status);
        $this->assertEquals(VideoStatus::PROCESSING, VideoDTO::fromArray($this->minimalData('processing'))->status);
        $this->assertEquals(VideoStatus::ABORTED, VideoDTO::fromArray($this->minimalData('aborted'))->status);
        $this->assertEquals(VideoStatus::DONE, VideoDTO::fromArray($this->minimalData('done'))->status);
        $this->assertEquals(VideoStatus::ERROR, VideoDTO::fromArray($this->minimalData('error'))->status);
    }

    public function testFromArrayRoundsFractionalDurationToNearestWholeSecond(): void
    {
        $this->assertEquals(60, VideoDTO::fromArray($this->minimalData('done', 59.96))->duration);
        $this->assertEquals(179, VideoDTO::fromArray($this->minimalData('done', 179.305))->duration);
        $this->assertEquals(180, VideoDTO::fromArray($this->minimalData('done', 179.5))->duration);
    }

    public function testFromArrayKeepsMissingDurationAsZero(): void
    {
        $data = $this->minimalData('done');
        unset($data['duration']);

        $this->assertEquals(0, VideoDTO::fromArray($data)->duration);
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
                    'file_size' => 1000,
                    'resolution' => '852x480',
                ],
                [
                    'id' => 'a2',
                    'video_id' => 'video-1',
                    'file_size' => 2000,
                    'resolution' => '1280x720',
                ],
                [
                    'id' => 'a3',
                    'video_id' => 'video-1',
                    'file_size' => 3000,
                    'resolution' => '1920x1080',
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
                    'file_size' => 1000,
                    'resolution' => '852x480',
                ],
                [
                    'id' => 'a2',
                    'video_id' => 'video-1',
                    'file_size' => 2000,
                    'resolution' => '1280x720',
                ],
                [
                    'id' => 'a3',
                    'video_id' => 'video-1',
                    'file_size' => 3000,
                    'resolution' => '1920x1080',
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

    /**
     * @return array<string, mixed>
     */
    private function minimalData(string $status, float|int $duration = 0): array
    {
        return [
            'id' => '1',
            'title' => 'Test',
            'status' => $status,
            'duration' => $duration,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ];
    }
}
