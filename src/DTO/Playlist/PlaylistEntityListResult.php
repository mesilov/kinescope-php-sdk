<?php

declare(strict_types=1);

namespace Kinescope\DTO\Playlist;

use Kinescope\DTO\Common\MetaDTO;
use Kinescope\DTO\Common\PaginatedResponse;

/**
 * Paginated list of playlist entities.
 *
 * @extends PaginatedResponse<PlaylistEntityDTO>
 */
final readonly class PlaylistEntityListResult extends PaginatedResponse
{
    /**
     * Create a PlaylistEntityListResult from API response array.
     *
     * @param array<string, mixed> $response Raw API response
     *
     * @return self
     */
    public static function fromArray(array $response): self
    {
        $data = [];

        if (isset($response['data']) && is_array($response['data'])) {
            $data = array_map(
                static fn (array $item): PlaylistEntityDTO =>
                    PlaylistEntityDTO::fromArray($item),
                $response['data']
            );
        }

        $meta = MetaDTO::fromArray($response['meta'] ?? []);

        return new self($data, $meta);
    }

    /**
     * Get entities sorted by position.
     *
     * @return array<PlaylistEntityDTO>
     */
    public function getSortedByPosition(): array
    {
        $sorted = $this->data;

        usort(
            $sorted,
            static fn (PlaylistEntityDTO $a, PlaylistEntityDTO $b): int =>
            $a->position <=> $b->position
        );

        return $sorted;
    }

    /**
     * Get entity at a specific position.
     *
     * @param int $position Position (0-indexed)
     *
     * @return PlaylistEntityDTO|null
     */
    public function getAtPosition(int $position): ?PlaylistEntityDTO
    {
        return $this->find(
            static fn (PlaylistEntityDTO $entity): bool =>
                $entity->position === $position
        );
    }

    /**
     * Get entities with ready videos.
     *
     * @return array<PlaylistEntityDTO>
     */
    public function getReady(): array
    {
        return $this->filter(
            static fn (PlaylistEntityDTO $entity): bool => $entity->isVideoReady()
        );
    }

    /**
     * Get entities with processing videos.
     *
     * @return array<PlaylistEntityDTO>
     */
    public function getProcessing(): array
    {
        return $this->filter(
            static fn (PlaylistEntityDTO $entity): bool => $entity->isVideoProcessing()
        );
    }

    /**
     * Get entities with error videos.
     *
     * @return array<PlaylistEntityDTO>
     */
    public function getWithErrors(): array
    {
        return $this->filter(
            static fn (PlaylistEntityDTO $entity): bool => $entity->hasVideoError()
        );
    }

    /**
     * Find entity by ID.
     *
     * @param string $id Entity identifier
     *
     * @return PlaylistEntityDTO|null
     */
    public function findById(string $id): ?PlaylistEntityDTO
    {
        return $this->find(
            static fn (PlaylistEntityDTO $entity): bool => $entity->id === $id
        );
    }

    /**
     * Find entity by video ID.
     *
     * @param string $videoId Video identifier
     *
     * @return PlaylistEntityDTO|null
     */
    public function findByVideoId(string $videoId): ?PlaylistEntityDTO
    {
        return $this->find(
            static fn (PlaylistEntityDTO $entity): bool =>
                $entity->videoId === $videoId
        );
    }

    /**
     * Get total duration of all entities in seconds.
     *
     * @return int
     */
    public function getTotalDuration(): int
    {
        return array_reduce(
            $this->data,
            static fn (int $total, PlaylistEntityDTO $entity): int =>
                $total + $entity->duration,
            0
        );
    }

    /**
     * Get video IDs of all entities.
     *
     * @return array<string>
     */
    public function getVideoIds(): array
    {
        return $this->map(
            static fn (PlaylistEntityDTO $entity): string => $entity->videoId
        );
    }
}
