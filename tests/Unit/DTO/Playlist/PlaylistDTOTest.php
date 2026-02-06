<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Playlist;

use Kinescope\DTO\Playlist\PlaylistDTO;
use PHPUnit\Framework\TestCase;

class PlaylistDTOTest extends TestCase
{
    public function testFromArrayCreatesValidPlaylistDTO(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'My Playlist',
            'description' => 'Playlist description',
            'project_id' => 'project-uuid',
            'items_count' => 10,
            'total_duration' => 3600,
            'poster_url' => 'https://example.com/poster.jpg',
            'embed_code' => '<iframe></iframe>',
            'is_public' => true,
            'settings' => ['autoplay' => true],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
        ];

        $playlist = PlaylistDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $playlist->id);
        $this->assertEquals('My Playlist', $playlist->title);
        $this->assertEquals('Playlist description', $playlist->description);
        $this->assertEquals('project-uuid', $playlist->projectId);
        $this->assertEquals(10, $playlist->itemsCount);
        $this->assertEquals(3600, $playlist->totalDuration);
        $this->assertEquals('https://example.com/poster.jpg', $playlist->posterUrl);
        $this->assertEquals('<iframe></iframe>', $playlist->embedCode);
        $this->assertTrue($playlist->isPublic);
        $this->assertEquals(['autoplay' => true], $playlist->settings);
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'My Playlist',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ];

        $playlist = PlaylistDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $playlist->id);
        $this->assertEquals('My Playlist', $playlist->title);
        $this->assertNull($playlist->description);
        $this->assertNull($playlist->projectId);
        $this->assertEquals(0, $playlist->itemsCount);
        $this->assertEquals(0, $playlist->totalDuration);
        $this->assertNull($playlist->posterUrl);
        $this->assertNull($playlist->embedCode);
        $this->assertFalse($playlist->isPublic);
        $this->assertEmpty($playlist->settings);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'My Playlist',
            'description' => 'Playlist description',
            'project_id' => 'project-uuid',
            'items_count' => 10,
            'total_duration' => 3600,
            'poster_url' => 'https://example.com/poster.jpg',
            'embed_code' => '<iframe></iframe>',
            'is_public' => true,
            'settings' => ['autoplay' => true],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
        ];

        $playlist = PlaylistDTO::fromArray($data);
        $array = $playlist->toArray();

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $array['id']);
        $this->assertEquals('My Playlist', $array['title']);
        $this->assertEquals('Playlist description', $array['description']);
        $this->assertEquals('project-uuid', $array['project_id']);
        $this->assertEquals(10, $array['items_count']);
        $this->assertEquals(3600, $array['total_duration']);
        $this->assertEquals('https://example.com/poster.jpg', $array['poster_url']);
        $this->assertEquals('<iframe></iframe>', $array['embed_code']);
        $this->assertTrue($array['is_public']);
        $this->assertEquals(['autoplay' => true], $array['settings']);
    }

    public function testHasItemsReturnsTrueWhenNotEmpty(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Filled Playlist',
            'items_count' => 5,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($playlist->hasItems());
    }

    public function testHasItemsReturnsFalseWhenEmpty(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Empty Playlist',
            'items_count' => 0,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($playlist->hasItems());
    }

    public function testIsEmptyReturnsTrueWhenNoItems(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Empty Playlist',
            'items_count' => 0,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($playlist->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenHasItems(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Filled Playlist',
            'items_count' => 5,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($playlist->isEmpty());
    }

    public function testGetFormattedDurationReturnsHHMMSS(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Playlist',
            'total_duration' => 3665,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals('01:01:05', $playlist->getFormattedDuration());
    }

    public function testGetFormattedDurationReturnsMMSSWhenUnderHour(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Playlist',
            'total_duration' => 125,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals('02:05', $playlist->getFormattedDuration());
    }

    public function testGetFormattedDurationWithZero(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Playlist',
            'total_duration' => 0,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals('00:00', $playlist->getFormattedDuration());
    }

    public function testGetAverageItemDurationReturnsCorrectValue(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Playlist',
            'items_count' => 10,
            'total_duration' => 1000,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals(100, $playlist->getAverageItemDuration());
    }

    public function testGetAverageItemDurationReturnsZeroWhenEmpty(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Playlist',
            'items_count' => 0,
            'total_duration' => 0,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals(0, $playlist->getAverageItemDuration());
    }

    public function testHasEmbedCodeReturnsTrueWhenSet(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Playlist',
            'embed_code' => '<iframe></iframe>',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($playlist->hasEmbedCode());
    }

    public function testHasEmbedCodeReturnsFalseWhenNull(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Playlist',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($playlist->hasEmbedCode());
    }

    public function testHasPosterReturnsTrueWhenSet(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Playlist',
            'poster_url' => 'https://example.com/poster.jpg',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($playlist->hasPoster());
    }

    public function testHasPosterReturnsFalseWhenNull(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Playlist',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($playlist->hasPoster());
    }

    public function testGetSettingReturnsValue(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Playlist',
            'settings' => ['autoplay' => true, 'loop' => false],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($playlist->getSetting('autoplay'));
        $this->assertFalse($playlist->getSetting('loop'));
    }

    public function testGetSettingReturnsDefaultWhenNotFound(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => '1',
            'title' => 'Playlist',
            'settings' => [],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertNull($playlist->getSetting('missing'));
        $this->assertEquals('default', $playlist->getSetting('missing', 'default'));
    }
}
