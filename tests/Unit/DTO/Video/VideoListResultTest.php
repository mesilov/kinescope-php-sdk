<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Video;

use Kinescope\DTO\Video\VideoDTO;
use Kinescope\DTO\Video\VideoListResult;
use Kinescope\Enum\VideoStatus;
use PHPUnit\Framework\TestCase;

final class VideoListResultTest extends TestCase
{
    public function testFromArrayCreatesPaginatedVideoList(): void
    {
        $result = VideoListResult::fromArray($this->response());

        self::assertCount(3, $result);
        self::assertContainsOnlyInstancesOf(VideoDTO::class, $result->getData());
        self::assertSame(3, $result->getTotal());
    }

    public function testCurrentHelpers(): void
    {
        $result = VideoListResult::fromArray($this->response());

        self::assertCount(1, $result->getReady());
        self::assertCount(1, $result->getProcessing());
        self::assertCount(1, $result->getWithErrors());
        self::assertCount(3, $result->getByProject('project-id'));
        self::assertCount(1, $result->getByFolder('folder-a'));
        self::assertSame('video-b', $result->findById('video-b')?->id);
        self::assertSame(60.5 + 30.25 + 15.0, $result->getTotalDuration());
        self::assertSame(['video-a', 'video-b', 'video-c'], $result->getIds());
    }

    public function testFilterByStatus(): void
    {
        $result = VideoListResult::fromArray($this->response());

        self::assertSame('video-a', $result->getByStatus(VideoStatus::DONE)[0]->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function response(): array
    {
        return [
            'data' => [
                $this->video('video-a', 'done', 'folder-a', 60.5),
                $this->video('video-b', 'processing', 'folder-b', 30.25),
                $this->video('video-c', 'error', null, 15.0),
            ],
            'meta' => [
                'pagination' => ['page' => 1, 'per_page' => 20, 'total' => 3],
                'order' => ['title' => 'asc'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function video(string $id, string $status, ?string $folderId, float $duration): array
    {
        return [
            'id' => $id,
            'project_id' => 'project-id',
            'folder_id' => $folderId,
            'title' => $id,
            'status' => $status,
            'duration' => $duration,
            'assets' => [],
            'created_at' => '2025-07-30T19:01:12.323724Z',
            'updated_at' => null,
        ];
    }
}
