<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Playlist;

use Kinescope\DTO\Playlist\PlaylistEntityDTO;
use Kinescope\DTO\Playlist\PlaylistEntityListResult;
use PHPUnit\Framework\TestCase;

class PlaylistEntityListResultTest extends TestCase
{
    public function testFromArrayCreatesValidPlaylistEntityListResult(): void
    {
        $data = [
            'data' => [
                [
                    'id' => '1',
                    'playlist_id' => 'playlist-uuid',
                    'video_id' => 'video-1',
                    'position' => 0,
                    'title' => 'Video 1',
                ],
                [
                    'id' => '2',
                    'playlist_id' => 'playlist-uuid',
                    'video_id' => 'video-2',
                    'position' => 1,
                    'title' => 'Video 2',
                ],
            ],
            'meta' => [
                'total' => 2,
                'page' => 1,
                'per_page' => 20,
            ],
        ];

        $result = PlaylistEntityListResult::fromArray($data);

        $this->assertCount(2, $result->getData());
        $this->assertContainsOnlyInstancesOf(PlaylistEntityDTO::class, $result->getData());
        $this->assertEquals(2, $result->getMeta()->total);
    }

    public function testGetSortedByPositionReturnsSortedEntities(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'playlist_id' => 'p', 'video_id' => 'v1', 'position' => 2, 'title' => 'Third'],
                ['id' => '2', 'playlist_id' => 'p', 'video_id' => 'v2', 'position' => 0, 'title' => 'First'],
                ['id' => '3', 'playlist_id' => 'p', 'video_id' => 'v3', 'position' => 1, 'title' => 'Second'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistEntityListResult::fromArray($data);
        $sorted = $result->getSortedByPosition();

        $this->assertEquals('First', $sorted[0]->title);
        $this->assertEquals('Second', $sorted[1]->title);
        $this->assertEquals('Third', $sorted[2]->title);
    }

    public function testGetAtPositionFindsCorrectEntity(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'playlist_id' => 'p', 'video_id' => 'v1', 'position' => 0, 'title' => 'First'],
                ['id' => '2', 'playlist_id' => 'p', 'video_id' => 'v2', 'position' => 1, 'title' => 'Second'],
                ['id' => '3', 'playlist_id' => 'p', 'video_id' => 'v3', 'position' => 2, 'title' => 'Third'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistEntityListResult::fromArray($data);
        $entity = $result->getAtPosition(1);

        $this->assertNotNull($entity);
        $this->assertEquals('Second', $entity->title);
    }

    public function testGetAtPositionReturnsNullWhenNotFound(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'playlist_id' => 'p', 'video_id' => 'v1', 'position' => 0, 'title' => 'First'],
            ],
            'meta' => ['total' => 1, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistEntityListResult::fromArray($data);

        $this->assertNull($result->getAtPosition(99));
    }

    public function testGetReadyReturnsOnlyReadyEntities(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'playlist_id' => 'p', 'video_id' => 'v1', 'position' => 0, 'video_status' => 'done'],
                ['id' => '2', 'playlist_id' => 'p', 'video_id' => 'v2', 'position' => 1, 'video_status' => 'processing'],
                ['id' => '3', 'playlist_id' => 'p', 'video_id' => 'v3', 'position' => 2, 'video_status' => 'done'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistEntityListResult::fromArray($data);
        $ready = $result->getReady();

        $this->assertCount(2, $ready);

        foreach ($ready as $entity) {
            $this->assertTrue($entity->isVideoReady());
        }
    }

    public function testGetProcessingReturnsOnlyProcessingEntities(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'playlist_id' => 'p', 'video_id' => 'v1', 'position' => 0, 'video_status' => 'done'],
                ['id' => '2', 'playlist_id' => 'p', 'video_id' => 'v2', 'position' => 1, 'video_status' => 'processing'],
                ['id' => '3', 'playlist_id' => 'p', 'video_id' => 'v3', 'position' => 2, 'video_status' => 'processing'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistEntityListResult::fromArray($data);
        $processing = $result->getProcessing();

        $this->assertCount(2, $processing);

        foreach ($processing as $entity) {
            $this->assertTrue($entity->isVideoProcessing());
        }
    }

    public function testGetWithErrorsReturnsOnlyErrorEntities(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'playlist_id' => 'p', 'video_id' => 'v1', 'position' => 0, 'video_status' => 'done'],
                ['id' => '2', 'playlist_id' => 'p', 'video_id' => 'v2', 'position' => 1, 'video_status' => 'error'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistEntityListResult::fromArray($data);
        $errors = $result->getWithErrors();

        $this->assertCount(1, $errors);
        $this->assertTrue($errors[0]->hasVideoError());
    }

    public function testFindByIdFindsCorrectEntity(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'playlist_id' => 'p', 'video_id' => 'v1', 'position' => 0],
                ['id' => '2', 'playlist_id' => 'p', 'video_id' => 'v2', 'position' => 1],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistEntityListResult::fromArray($data);
        $entity = $result->findById('2');

        $this->assertNotNull($entity);
        $this->assertEquals('2', $entity->id);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'playlist_id' => 'p', 'video_id' => 'v1', 'position' => 0],
            ],
            'meta' => ['total' => 1, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistEntityListResult::fromArray($data);

        $this->assertNull($result->findById('999'));
    }

    public function testFindByVideoIdFindsCorrectEntity(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'playlist_id' => 'p', 'video_id' => 'video-aaa', 'position' => 0],
                ['id' => '2', 'playlist_id' => 'p', 'video_id' => 'video-bbb', 'position' => 1],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistEntityListResult::fromArray($data);
        $entity = $result->findByVideoId('video-bbb');

        $this->assertNotNull($entity);
        $this->assertEquals('2', $entity->id);
    }

    public function testFindByVideoIdReturnsNullWhenNotFound(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'playlist_id' => 'p', 'video_id' => 'video-aaa', 'position' => 0],
            ],
            'meta' => ['total' => 1, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistEntityListResult::fromArray($data);

        $this->assertNull($result->findByVideoId('non-existent'));
    }

    public function testGetTotalDurationCalculatesSum(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'playlist_id' => 'p', 'video_id' => 'v1', 'position' => 0, 'duration' => 100],
                ['id' => '2', 'playlist_id' => 'p', 'video_id' => 'v2', 'position' => 1, 'duration' => 200],
                ['id' => '3', 'playlist_id' => 'p', 'video_id' => 'v3', 'position' => 2, 'duration' => 150],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistEntityListResult::fromArray($data);

        $this->assertEquals(450, $result->getTotalDuration());
    }

    public function testGetTotalDurationWithZeroDurations(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'playlist_id' => 'p', 'video_id' => 'v1', 'position' => 0, 'duration' => 100],
                ['id' => '2', 'playlist_id' => 'p', 'video_id' => 'v2', 'position' => 1, 'duration' => 0],
                ['id' => '3', 'playlist_id' => 'p', 'video_id' => 'v3', 'position' => 2, 'duration' => 150],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistEntityListResult::fromArray($data);

        $this->assertEquals(250, $result->getTotalDuration());
    }

    public function testGetVideoIdsReturnsAllVideoIds(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'playlist_id' => 'p', 'video_id' => 'video-aaa', 'position' => 0],
                ['id' => '2', 'playlist_id' => 'p', 'video_id' => 'video-bbb', 'position' => 1],
                ['id' => '3', 'playlist_id' => 'p', 'video_id' => 'video-ccc', 'position' => 2],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = PlaylistEntityListResult::fromArray($data);
        $videoIds = $result->getVideoIds();

        $this->assertEquals(['video-aaa', 'video-bbb', 'video-ccc'], $videoIds);
    }
}
