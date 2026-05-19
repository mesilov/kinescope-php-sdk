<?php

declare(strict_types=1);

namespace Kinescope\DTO\Playlist;

use Kinescope\DTO\Common\CollectionResponse;

/**
 * Unpaginated list of playlist entities.
 *
 * @extends CollectionResponse<PlaylistEntityDTO>
 */
final readonly class PlaylistEntityListResult extends CollectionResponse
{
    /**
     * @param array<string, mixed> $response Raw API response
     */
    public static function fromArray(array $response): self
    {
        $data = [];

        if (isset($response['data']) && is_array($response['data'])) {
            $data = array_map(
                PlaylistEntityDTO::fromArray(...),
                array_values(array_filter($response['data'], is_array(...))),
            );
        }

        return new self($data);
    }

    /**
     * @return list<PlaylistEntityDTO>
     */
    public function getSortedByPosition(): array
    {
        $sorted = $this->data;

        usort(
            $sorted,
            static fn (PlaylistEntityDTO $a, PlaylistEntityDTO $b): int => $a->position <=> $b->position,
        );

        return $sorted;
    }

    public function getAtPosition(int $position): ?PlaylistEntityDTO
    {
        return $this->find(
            static fn (PlaylistEntityDTO $entity): bool => $entity->position === $position,
        );
    }

    /**
     * @return list<PlaylistEntityDTO>
     */
    public function getReady(): array
    {
        return $this->filter(
            static fn (PlaylistEntityDTO $entity): bool => $entity->isReady(),
        );
    }

    /**
     * @return list<PlaylistEntityDTO>
     */
    public function getProcessing(): array
    {
        return $this->filter(
            static fn (PlaylistEntityDTO $entity): bool => $entity->isProcessing(),
        );
    }

    /**
     * @return list<PlaylistEntityDTO>
     */
    public function getWithErrors(): array
    {
        return $this->filter(
            static fn (PlaylistEntityDTO $entity): bool => $entity->hasError(),
        );
    }

    public function findById(string $id): ?PlaylistEntityDTO
    {
        return $this->find(
            static fn (PlaylistEntityDTO $entity): bool => $entity->id === $id,
        );
    }

    public function getTotalDuration(): float
    {
        return array_reduce(
            $this->data,
            static fn (float $total, PlaylistEntityDTO $entity): float => $total + $entity->duration,
            0.0,
        );
    }

    /**
     * @return list<string>
     */
    public function getIds(): array
    {
        return $this->map(
            static fn (PlaylistEntityDTO $entity): string => $entity->id,
        );
    }
}
