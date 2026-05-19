<?php

declare(strict_types=1);

namespace Kinescope\DTO\Playlist;

use Kinescope\DTO\Common\MetaDTO;
use Kinescope\DTO\Common\PaginatedResponse;
use Kinescope\Enum\PrivacyType;

/**
 * Paginated list of playlists.
 *
 * @extends PaginatedResponse<PlaylistDTO>
 */
final readonly class PlaylistListResult extends PaginatedResponse
{
    /**
     * @param array<string, mixed> $response Raw API response
     */
    public static function fromArray(array $response): self
    {
        $data = [];

        if (isset($response['data']) && is_array($response['data'])) {
            $data = array_map(
                PlaylistDTO::fromArray(...),
                array_values(array_filter($response['data'], is_array(...))),
            );
        }

        return new self($data, MetaDTO::fromArray($response['meta'] ?? []));
    }

    /**
     * @return list<PlaylistDTO>
     */
    public function getByPrivacyType(PrivacyType $privacyType): array
    {
        return $this->filter(
            static fn (PlaylistDTO $playlist): bool => $playlist->privacyType === $privacyType,
        );
    }

    /**
     * @return list<PlaylistDTO>
     */
    public function getPublic(): array
    {
        return $this->filter(
            static fn (PlaylistDTO $playlist): bool => $playlist->isPublic(),
        );
    }

    /**
     * @return list<PlaylistDTO>
     */
    public function getPrivate(): array
    {
        return $this->filter(
            static fn (PlaylistDTO $playlist): bool => ! $playlist->isPublic(),
        );
    }

    /**
     * @return list<PlaylistDTO>
     */
    public function getWithDomainRestrictions(): array
    {
        return $this->filter(
            static fn (PlaylistDTO $playlist): bool => $playlist->hasDomainRestrictions(),
        );
    }

    /**
     * @return list<PlaylistDTO>
     */
    public function getByParentId(string $parentId): array
    {
        return $this->filter(
            static fn (PlaylistDTO $playlist): bool => $playlist->parentId === $parentId,
        );
    }

    /**
     * @return list<PlaylistDTO>
     */
    public function getSortedByName(): array
    {
        $sorted = $this->data;

        usort(
            $sorted,
            static fn (PlaylistDTO $a, PlaylistDTO $b): int => strcasecmp($a->name, $b->name),
        );

        return $sorted;
    }

    public function findById(string $id): ?PlaylistDTO
    {
        return $this->find(
            static fn (PlaylistDTO $playlist): bool => $playlist->id === $id,
        );
    }

    public function findByName(string $name): ?PlaylistDTO
    {
        return $this->find(
            static fn (PlaylistDTO $playlist): bool => $playlist->name === $name,
        );
    }

    /**
     * @return list<string>
     */
    public function getIds(): array
    {
        return $this->map(static fn (PlaylistDTO $playlist): string => $playlist->id);
    }
}
