<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Playlist;

use Carbon\CarbonImmutable;
use Kinescope\DTO\Playlist\PlaylistEntityDTO;
use Kinescope\Enum\VideoStatus;
use PHPUnit\Framework\TestCase;

final class PlaylistEntityDTOTest extends TestCase
{
    public function testFromArrayMapsCurrentApiPayload(): void
    {
        $entity = PlaylistEntityDTO::fromArray($this->payload());

        self::assertSame('entity-id', $entity->id);
        self::assertSame(1, $entity->position);
        self::assertSame(VideoStatus::DONE, $entity->status);
        self::assertSame('Entity', $entity->title);
        self::assertSame(4830.9585, $entity->duration);
        self::assertInstanceOf(CarbonImmutable::class, $entity->createdAt);
        self::assertSame('2025-06-30T07:28:27.967490Z', $entity->createdAt->toJSON());
    }

    public function testToArrayUsesRawApiFieldNames(): void
    {
        $array = PlaylistEntityDTO::fromArray($this->payload())->toArray();

        self::assertSame('entity-id', $array['id']);
        self::assertSame(1, $array['position']);
        self::assertSame('done', $array['status']);
        self::assertSame(4830.9585, $array['duration']);
        self::assertArrayNotHasKey('playlist_id', $array);
        self::assertArrayNotHasKey('video_id', $array);
        self::assertArrayNotHasKey('video_status', $array);
    }

    public function testStatusAndDurationHelpers(): void
    {
        $done = PlaylistEntityDTO::fromArray($this->payload(status: 'done', position: 1, duration: 65.4));
        $processing = PlaylistEntityDTO::fromArray($this->payload(status: 'processing', position: 2));
        $error = PlaylistEntityDTO::fromArray($this->payload(status: 'error', position: 3));

        self::assertTrue($done->isReady());
        self::assertTrue($done->isFirst());
        self::assertSame('01:05', $done->getFormattedDuration());
        self::assertTrue($processing->isProcessing());
        self::assertTrue($error->hasError());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $status = 'done', int $position = 1, float $duration = 4830.9585): array
    {
        return [
            'id' => 'entity-id',
            'position' => $position,
            'status' => $status,
            'title' => 'Entity',
            'description' => '',
            'duration' => $duration,
            'created_at' => '2025-06-30T07:28:27.96749Z',
            'updated_at' => '2025-06-30T07:42:48.908293Z',
        ];
    }
}
