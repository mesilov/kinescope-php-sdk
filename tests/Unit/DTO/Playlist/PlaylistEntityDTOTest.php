<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Playlist;

use Kinescope\DTO\Playlist\PlaylistEntityDTO;
use Kinescope\Enum\VideoStatus;
use PHPUnit\Framework\TestCase;

class PlaylistEntityDTOTest extends TestCase
{
    public function testFromArrayCreatesValidPlaylistEntityDTO(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'title' => 'Video Title',
            'description' => 'Video description',
            'position' => 1,
            'duration' => 120,
            'video_status' => 'done',
            'poster_url' => 'https://example.com/thumb.jpg',
            'added_at' => '2024-01-01T00:00:00Z',
        ];

        $entity = PlaylistEntityDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $entity->id);
        $this->assertEquals('playlist-uuid', $entity->playlistId);
        $this->assertEquals('video-uuid', $entity->videoId);
        $this->assertEquals('Video Title', $entity->title);
        $this->assertEquals('Video description', $entity->description);
        $this->assertEquals(1, $entity->position);
        $this->assertEquals(120, $entity->duration);
        $this->assertEquals(VideoStatus::DONE, $entity->videoStatus);
        $this->assertEquals('https://example.com/thumb.jpg', $entity->posterUrl);
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
        ];

        $entity = PlaylistEntityDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $entity->id);
        $this->assertEquals('playlist-uuid', $entity->playlistId);
        $this->assertEquals('video-uuid', $entity->videoId);
        $this->assertEquals('', $entity->title);
        $this->assertNull($entity->description);
        $this->assertEquals(0, $entity->position);
        $this->assertEquals(0, $entity->duration);
        $this->assertNull($entity->videoStatus);
        $this->assertNull($entity->posterUrl);
        $this->assertNull($entity->addedAt);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'title' => 'Video Title',
            'description' => 'Video description',
            'position' => 1,
            'duration' => 120,
            'video_status' => 'done',
            'poster_url' => 'https://example.com/thumb.jpg',
            'added_at' => '2024-01-01T00:00:00Z',
        ];

        $entity = PlaylistEntityDTO::fromArray($data);
        $array = $entity->toArray();

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $array['id']);
        $this->assertEquals('playlist-uuid', $array['playlist_id']);
        $this->assertEquals('video-uuid', $array['video_id']);
        $this->assertEquals('Video Title', $array['title']);
        $this->assertEquals('Video description', $array['description']);
        $this->assertEquals(1, $array['position']);
        $this->assertEquals(120, $array['duration']);
        $this->assertEquals('done', $array['video_status']);
        $this->assertEquals('https://example.com/thumb.jpg', $array['poster_url']);
    }

    public function testGetFormattedDurationReturnsHHMMSS(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'duration' => 3665,
        ]);

        $this->assertEquals('01:01:05', $entity->getFormattedDuration());
    }

    public function testGetFormattedDurationReturnsMMSSWhenUnderHour(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'duration' => 125,
        ]);

        $this->assertEquals('02:05', $entity->getFormattedDuration());
    }

    public function testGetFormattedDurationWithZero(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'duration' => 0,
        ]);

        $this->assertEquals('00:00', $entity->getFormattedDuration());
    }

    public function testIsVideoReadyReturnsTrueWhenDone(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'video_status' => 'done',
        ]);

        $this->assertTrue($entity->isVideoReady());
    }

    public function testIsVideoReadyReturnsFalseWhenProcessing(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'video_status' => 'processing',
        ]);

        $this->assertFalse($entity->isVideoReady());
    }

    public function testIsVideoReadyReturnsFalseWhenNull(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
        ]);

        $this->assertFalse($entity->isVideoReady());
    }

    public function testIsVideoProcessingReturnsTrueWhenProcessing(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'video_status' => 'processing',
        ]);

        $this->assertTrue($entity->isVideoProcessing());
    }

    public function testIsVideoProcessingReturnsFalseWhenDone(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'video_status' => 'done',
        ]);

        $this->assertFalse($entity->isVideoProcessing());
    }

    public function testHasVideoErrorReturnsTrueWhenError(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'video_status' => 'error',
        ]);

        $this->assertTrue($entity->hasVideoError());
    }

    public function testHasVideoErrorReturnsFalseWhenDone(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'video_status' => 'done',
        ]);

        $this->assertFalse($entity->hasVideoError());
    }

    public function testHasPosterReturnsTrueWhenPosterUrlExists(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'poster_url' => 'https://example.com/thumb.jpg',
        ]);

        $this->assertTrue($entity->hasPoster());
    }

    public function testHasPosterReturnsFalseWhenNoPosterUrl(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
        ]);

        $this->assertFalse($entity->hasPoster());
    }

    public function testGetHumanPositionReturns1Indexed(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'position' => 0,
        ]);

        $this->assertEquals(1, $entity->getHumanPosition());
    }

    public function testGetHumanPositionReturnsCorrectValue(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'position' => 5,
        ]);

        $this->assertEquals(6, $entity->getHumanPosition());
    }

    public function testIsFirstReturnsTrueWhenPositionIsZero(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'position' => 0,
        ]);

        $this->assertTrue($entity->isFirst());
    }

    public function testIsFirstReturnsFalseWhenNotFirst(): void
    {
        $entity = PlaylistEntityDTO::fromArray([
            'id' => '1',
            'playlist_id' => 'playlist-uuid',
            'video_id' => 'video-uuid',
            'position' => 1,
        ]);

        $this->assertFalse($entity->isFirst());
    }
}
