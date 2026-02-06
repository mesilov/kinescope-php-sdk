<?php

declare(strict_types=1);

namespace Kinescope\DTO\Playlist;

use Kinescope\DTO\Common\MetaDTO;
use Kinescope\DTO\Common\PaginatedResponse;

/**
 * Paginated list of playlists.
 *
 * @extends PaginatedResponse<PlaylistDTO>
 */
final readonly class PlaylistListResult extends PaginatedResponse
{
    /**
     * Create a PlaylistListResult from API response array.
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
                static fn (array $item): PlaylistDTO => PlaylistDTO::fromArray($item),
                $response['data']
            );
        }

        $meta = MetaDTO::fromArray($response['meta'] ?? []);

        return new self($data, $meta);
    }

    /**
     * Get public playlists.
     *
     * @return array<PlaylistDTO>
     */
    public function getPublic(): array
    {
        return $this->filter(
            static fn (PlaylistDTO $playlist): bool => $playlist->isPublic
        );
    }

    /**
     * Get private playlists.
     *
     * @return array<PlaylistDTO>
     */
    public function getPrivate(): array
    {
        return $this->filter(
            static fn (PlaylistDTO $playlist): bool => ! $playlist->isPublic
        );
    }

    /**
     * Get playlists with items.
     *
     * @return array<PlaylistDTO>
     */
    public function getWithItems(): array
    {
        return $this->filter(
            static fn (PlaylistDTO $playlist): bool => $playlist->hasItems()
        );
    }

    /**
     * Get empty playlists.
     *
     * @return array<PlaylistDTO>
     */
    public function getEmpty(): array
    {
        return $this->filter(
            static fn (PlaylistDTO $playlist): bool => $playlist->isEmpty()
        );
    }

    /**
     * Get playlists by project.
     *
     * @param string $projectId Project identifier
     *
     * @return array<PlaylistDTO>
     */
    public function getByProject(string $projectId): array
    {
        return $this->filter(
            static fn (PlaylistDTO $playlist): bool =>
                $playlist->projectId === $projectId
        );
    }

    /**
     * Get playlists sorted by items count.
     *
     * @param bool $ascending Sort direction
     *
     * @return array<PlaylistDTO>
     */
    public function getSortedByItemsCount(bool $ascending = true): array
    {
        $sorted = $this->data;

        usort(
            $sorted,
            static fn (PlaylistDTO $a, PlaylistDTO $b): int =>
            $ascending
                ? $a->itemsCount <=> $b->itemsCount
                : $b->itemsCount <=> $a->itemsCount
        );

        return $sorted;
    }

    /**
     * Get playlists sorted by total duration.
     *
     * @param bool $ascending Sort direction
     *
     * @return array<PlaylistDTO>
     */
    public function getSortedByDuration(bool $ascending = true): array
    {
        $sorted = $this->data;

        usort(
            $sorted,
            static fn (PlaylistDTO $a, PlaylistDTO $b): int =>
            $ascending
                ? $a->totalDuration <=> $b->totalDuration
                : $b->totalDuration <=> $a->totalDuration
        );

        return $sorted;
    }

    /**
     * Find playlist by ID.
     *
     * @param string $id Playlist identifier
     *
     * @return PlaylistDTO|null
     */
    public function findById(string $id): ?PlaylistDTO
    {
        return $this->find(
            static fn (PlaylistDTO $playlist): bool => $playlist->id === $id
        );
    }

    /**
     * Find playlist by title.
     *
     * @param string $title Playlist title
     *
     * @return PlaylistDTO|null
     */
    public function findByTitle(string $title): ?PlaylistDTO
    {
        return $this->find(
            static fn (PlaylistDTO $playlist): bool => $playlist->title === $title
        );
    }

    /**
     * Get total items count across all playlists.
     *
     * @return int
     */
    public function getTotalItemsCount(): int
    {
        return array_reduce(
            $this->data,
            static fn (int $total, PlaylistDTO $playlist): int =>
                $total + $playlist->itemsCount,
            0
        );
    }

    /**
     * Get total duration across all playlists in seconds.
     *
     * @return int
     */
    public function getTotalDuration(): int
    {
        return array_reduce(
            $this->data,
            static fn (int $total, PlaylistDTO $playlist): int =>
                $total + $playlist->totalDuration,
            0
        );
    }

    /**
     * Get IDs of all playlists.
     *
     * @return array<string>
     */
    public function getIds(): array
    {
        return $this->map(static fn (PlaylistDTO $playlist): string => $playlist->id);
    }
}
