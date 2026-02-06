<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Video;

use Kinescope\DTO\Video\VideoDTO;
use Kinescope\DTO\Video\VideoListResult;
use Kinescope\Enum\VideoStatus;
use PHPUnit\Framework\TestCase;

class VideoListResultTest extends TestCase
{
    public function testFromArrayCreatesValidVideoListResult(): void
    {
        $data = [
            'data' => [
                [
                    'id' => '1',
                    'title' => 'Video 1',
                    'status' => 'done',
                    'duration' => 120,
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-02T00:00:00Z',
                ],
                [
                    'id' => '2',
                    'title' => 'Video 2',
                    'status' => 'processing',
                    'duration' => 60,
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-02T00:00:00Z',
                ],
            ],
            'meta' => [
                'total' => 2,
                'page' => 1,
                'per_page' => 20,
            ],
        ];

        $result = VideoListResult::fromArray($data);

        $this->assertCount(2, $result->getData());
        $this->assertContainsOnlyInstancesOf(VideoDTO::class, $result->getData());
        $this->assertEquals(2, $result->getMeta()->total);
    }

    public function testGetDataReturnsItems(): void
    {
        $data = [
            'data' => [
                [
                    'id' => '1',
                    'title' => 'Video 1',
                    'status' => 'done',
                    'duration' => 120,
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-02T00:00:00Z',
                ],
            ],
            'meta' => [
                'total' => 1,
                'page' => 1,
                'per_page' => 20,
            ],
        ];

        $result = VideoListResult::fromArray($data);
        $items = $result->getData();

        $this->assertEquals('1', $items[0]->id);
        $this->assertEquals('Video 1', $items[0]->title);
    }

    public function testGetMetaReturnsMetaDTO(): void
    {
        $data = [
            'data' => [],
            'meta' => [
                'total' => 100,
                'page' => 2,
                'per_page' => 25,
            ],
        ];

        $result = VideoListResult::fromArray($data);
        $meta = $result->getMeta();

        $this->assertEquals(100, $meta->total);
        $this->assertEquals(2, $meta->pagination->page);
        $this->assertEquals(25, $meta->pagination->perPage);
    }

    public function testGetTotalReturnsTotal(): void
    {
        $data = [
            'data' => [],
            'meta' => [
                'total' => 100,
                'page' => 1,
                'per_page' => 20,
            ],
        ];

        $result = VideoListResult::fromArray($data);

        $this->assertEquals(100, $result->getTotal());
    }

    public function testGetIds(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Video 1', 'status' => 'done', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Video 2', 'status' => 'error', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => [
                'total' => 2,
                'page' => 1,
                'per_page' => 20,
            ],
        ];

        $result = VideoListResult::fromArray($data);

        $ids = $result->getIds();

        $this->assertEquals(['1', '2'], $ids);
    }

    public function testGetTotalDuration(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Video 1', 'duration' => 120, 'status' => 'done', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Video 2', 'duration' => 240, 'status' => 'done', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = VideoListResult::fromArray($data);

        $this->assertEquals(360, $result->getTotalDuration());
    }

    public function testGetReadyReturnsOnlyDoneVideos(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Video 1', 'status' => 'done', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Video 2', 'status' => 'processing', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'title' => 'Video 3', 'status' => 'done', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = VideoListResult::fromArray($data);
        $ready = $result->getReady();

        $this->assertCount(2, $ready);

        foreach ($ready as $video) {
            $this->assertEquals(VideoStatus::DONE, $video->status);
        }
    }

    public function testGetProcessingReturnsOnlyProcessingVideos(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Video 1', 'status' => 'processing', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Video 2', 'status' => 'error', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = VideoListResult::fromArray($data);
        $processing = $result->getProcessing();

        $this->assertCount(1, $processing);
        $this->assertEquals(VideoStatus::PROCESSING, $processing[0]->status);
    }

    public function testFilterByStatusFiltersCorrectly(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Video 1', 'status' => 'error', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Video 2', 'status' => 'error', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'title' => 'Video 3', 'status' => 'error', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = VideoListResult::fromArray($data);
        $errors = $result->getByStatus(VideoStatus::ERROR);

        $this->assertCount(3, $errors);

        foreach ($errors as $error) {
            $this->assertEquals(VideoStatus::ERROR, $error->status);
        }
    }

    public function testFilterByProjectIdFiltersCorrectly(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'title' => 'Video 1', 'status' => 'done', 'project_id' => 'project-1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'title' => 'Video 2', 'status' => 'done', 'project_id' => 'project-2', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'title' => 'Video 3', 'status' => 'done', 'project_id' => 'project-1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = VideoListResult::fromArray($data);
        $projectVideos = $result->getByProject('project-1');

        $this->assertCount(2, $projectVideos);
    }
}
