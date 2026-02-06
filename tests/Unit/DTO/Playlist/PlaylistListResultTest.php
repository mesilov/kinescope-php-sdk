<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Playlist;

use Kinescope\DTO\Playlist\PlaylistDTO;
use Kinescope\DTO\Playlist\PlaylistListResult;
use PHPUnit\Framework\TestCase;

class PlaylistListResultTest extends TestCase
{
    public function testFromArrayCreatesValidPlaylistListResult(): void
    {
        $data = [
            'data' => [
                [
                    'id' => '1',
                    'title' => 'Playlist 1',
                    'is_public' => true,
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-01T00:00:00Z',
                ],
                [
                    'id' => '2',
                    'title' => 'Playlist 2',
                    'is_public' => false,
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-01T00:00:00Z',
                ],
            ],
            'meta' => [
                'total' => 2,
                'page' => 1,
                'per_page' => 20,
            ],
        ];

        $result = PlaylistListResult::fromArray($data);

        $this->assertCount(2, $result->getData());
        $this->assertContainsOnlyInstancesOf(PlaylistDTO::class, $result->getData());
        $this->assertEquals(2, $result->getMeta()->total);
    }

    public function testGetPublicReturnsOnlyPublicPlaylists(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Public 1', 'is_public' => true, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Private', 'is_public' => false, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'title' => 'Public 2', 'is_public' => true, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);
        $public = $result->getPublic();

        $this->assertCount(2, $public);

        foreach ($public as $playlist) {
            $this->assertTrue($playlist->isPublic);
        }
    }

    public function testGetPrivateReturnsOnlyPrivatePlaylists(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Public', 'is_public' => true, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Private 1', 'is_public' => false, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'title' => 'Private 2', 'is_public' => false, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);
        $private = $result->getPrivate();

        $this->assertCount(2, $private);

        foreach ($private as $playlist) {
            $this->assertFalse($playlist->isPublic);
        }
    }

    public function testGetWithItemsReturnsPlaylistsWithItems(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Has Items', 'items_count' => 5, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Empty', 'items_count' => 0, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'title' => 'Also Has Items', 'items_count' => 3, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);
        $withItems = $result->getWithItems();

        $this->assertCount(2, $withItems);

        foreach ($withItems as $playlist) {
            $this->assertTrue($playlist->hasItems());
        }
    }

    public function testGetEmptyReturnsEmptyPlaylists(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Has Items', 'items_count' => 5, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Empty 1', 'items_count' => 0, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'title' => 'Empty 2', 'items_count' => 0, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);
        $empty = $result->getEmpty();

        $this->assertCount(2, $empty);

        foreach ($empty as $playlist) {
            $this->assertTrue($playlist->isEmpty());
        }
    }

    public function testGetByProjectFiltersCorrectly(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Playlist 1', 'project_id' => 'project-1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Playlist 2', 'project_id' => 'project-2', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'title' => 'Playlist 3', 'project_id' => 'project-1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);
        $projectPlaylists = $result->getByProject('project-1');

        $this->assertCount(2, $projectPlaylists);

        foreach ($projectPlaylists as $playlist) {
            $this->assertEquals('project-1', $playlist->projectId);
        }
    }

    public function testGetSortedByItemsCountSortsAscending(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Ten', 'items_count' => 10, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Five', 'items_count' => 5, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'title' => 'Twenty', 'items_count' => 20, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);
        $sorted = $result->getSortedByItemsCount(true);

        $this->assertEquals('Five', $sorted[0]->title);
        $this->assertEquals('Ten', $sorted[1]->title);
        $this->assertEquals('Twenty', $sorted[2]->title);
    }

    public function testGetSortedByItemsCountSortsDescending(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Ten', 'items_count' => 10, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Five', 'items_count' => 5, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'title' => 'Twenty', 'items_count' => 20, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);
        $sorted = $result->getSortedByItemsCount(false);

        $this->assertEquals('Twenty', $sorted[0]->title);
        $this->assertEquals('Ten', $sorted[1]->title);
        $this->assertEquals('Five', $sorted[2]->title);
    }

    public function testGetSortedByDurationSortsCorrectly(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Long', 'total_duration' => 3600, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Short', 'total_duration' => 60, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'title' => 'Medium', 'total_duration' => 600, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);
        $sorted = $result->getSortedByDuration(true);

        $this->assertEquals('Short', $sorted[0]->title);
        $this->assertEquals('Medium', $sorted[1]->title);
        $this->assertEquals('Long', $sorted[2]->title);
    }

    public function testFindByIdFindsCorrectPlaylist(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Alpha', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Beta', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);
        $playlist = $result->findById('2');

        $this->assertNotNull($playlist);
        $this->assertEquals('2', $playlist->id);
        $this->assertEquals('Beta', $playlist->title);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Alpha', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 1, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);

        $this->assertNull($result->findById('999'));
    }

    public function testFindByTitleFindsCorrectPlaylist(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Alpha', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Beta', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);
        $playlist = $result->findByTitle('Beta');

        $this->assertNotNull($playlist);
        $this->assertEquals('2', $playlist->id);
    }

    public function testFindByTitleReturnsNullWhenNotFound(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Alpha', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 1, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);

        $this->assertNull($result->findByTitle('NonExistent'));
    }

    public function testGetTotalItemsCountReturnsSum(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Playlist 1', 'items_count' => 10, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Playlist 2', 'items_count' => 20, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'title' => 'Playlist 3', 'items_count' => 5, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);

        $this->assertEquals(35, $result->getTotalItemsCount());
    }

    public function testGetTotalDurationReturnsSum(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Playlist 1', 'total_duration' => 100, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Playlist 2', 'total_duration' => 200, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'title' => 'Playlist 3', 'total_duration' => 150, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);

        $this->assertEquals(450, $result->getTotalDuration());
    }

    public function testGetIdsReturnsAllIds(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Playlist 1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Playlist 2', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistListResult::fromArray($data);

        $this->assertEquals(['1', '2'], $result->getIds());
    }
}
