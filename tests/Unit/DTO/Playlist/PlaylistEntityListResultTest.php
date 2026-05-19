<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Playlist;

use Kinescope\DTO\Playlist\PlaylistEntityDTO;
use Kinescope\DTO\Playlist\PlaylistEntityListResult;
use PHPUnit\Framework\TestCase;

final class PlaylistEntityListResultTest extends TestCase
{
    public function testFromArrayCreatesUnpaginatedCollection(): void
    {
        $result = PlaylistEntityListResult::fromArray($this->response());

        self::assertCount(3, $result);
        self::assertContainsOnlyInstancesOf(PlaylistEntityDTO::class, $result->getData());
        self::assertSame('entity-a', $result->first()?->id);
    }

    public function testCurrentCollectionHelpers(): void
    {
        $result = PlaylistEntityListResult::fromArray($this->response());

        self::assertCount(1, $result->getReady());
        self::assertCount(1, $result->getProcessing());
        self::assertCount(1, $result->getWithErrors());
        self::assertSame('entity-b', $result->getAtPosition(2)?->id);
        self::assertSame('Beta', $result->findById('entity-b')?->title);
        self::assertSame(60.5 + 30.0 + 15.0, $result->getTotalDuration());
        self::assertSame(['entity-a', 'entity-b', 'entity-c'], $result->getIds());
    }

    public function testSortingByPosition(): void
    {
        $result = PlaylistEntityListResult::fromArray([
            'data' => [
                $this->entity('entity-c', 'Gamma', 3, 'error', 15.0),
                $this->entity('entity-a', 'Alpha', 1, 'done', 60.5),
                $this->entity('entity-b', 'Beta', 2, 'processing', 30.0),
            ],
        ]);

        self::assertSame(['entity-a', 'entity-b', 'entity-c'], array_map(
            static fn (PlaylistEntityDTO $entity): string => $entity->id,
            $result->getSortedByPosition(),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function response(): array
    {
        return [
            'data' => [
                $this->entity('entity-a', 'Alpha', 1, 'done', 60.5),
                $this->entity('entity-b', 'Beta', 2, 'processing', 30.0),
                $this->entity('entity-c', 'Gamma', 3, 'error', 15.0),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function entity(string $id, string $title, int $position, string $status, float $duration): array
    {
        return [
            'id' => $id,
            'position' => $position,
            'status' => $status,
            'title' => $title,
            'description' => '',
            'duration' => $duration,
            'created_at' => '2025-06-30T07:28:27.96749Z',
            'updated_at' => '2025-06-30T07:42:48.908293Z',
        ];
    }
}
