<?php

declare(strict_types=1);

namespace Kinescope\DTO\Folder;

use Kinescope\DTO\Common\MetaDTO;
use Kinescope\DTO\Common\PaginatedResponse;

/**
 * Paginated list of folders.
 *
 * @extends PaginatedResponse<FolderDTO>
 */
final readonly class FolderListResult extends PaginatedResponse
{
    /**
     * Create a FolderListResult from API response array.
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
                static fn (array $item): FolderDTO => FolderDTO::fromArray($item),
                $response['data']
            );
        }

        $meta = MetaDTO::fromArray($response['meta'] ?? []);

        return new self($data, $meta);
    }

    /**
     * Get root folders (no parent).
     *
     * @return array<FolderDTO>
     */
    public function getRoots(): array
    {
        return $this->filter(
            static fn (FolderDTO $folder): bool => $folder->isRoot()
        );
    }

    /**
     * Get children of a specific folder.
     *
     * @param string $parentId Parent folder ID
     *
     * @return array<FolderDTO>
     */
    public function getChildren(string $parentId): array
    {
        return $this->filter(
            static fn (FolderDTO $folder): bool => $folder->isChildOf($parentId)
        );
    }

    /**
     * Get folders at a specific depth.
     *
     * @param int $depth Depth level (0 = root)
     *
     * @return array<FolderDTO>
     */
    public function getAtDepth(int $depth): array
    {
        return $this->filter(
            static fn (FolderDTO $folder): bool => $folder->isAtDepth($depth)
        );
    }

    /**
     * Get folders sorted by position.
     *
     * @return array<FolderDTO>
     */
    public function getSortedByPosition(): array
    {
        $sorted = $this->data;

        usort(
            $sorted,
            static fn (FolderDTO $a, FolderDTO $b): int =>
            $a->position <=> $b->position
        );

        return $sorted;
    }

    /**
     * Get folders sorted by name.
     *
     * @return array<FolderDTO>
     */
    public function getSortedByName(): array
    {
        $sorted = $this->data;

        usort(
            $sorted,
            static fn (FolderDTO $a, FolderDTO $b): int =>
            strcasecmp($a->name, $b->name)
        );

        return $sorted;
    }

    /**
     * Get folders with videos.
     *
     * @return array<FolderDTO>
     */
    public function getWithVideos(): array
    {
        return $this->filter(
            static fn (FolderDTO $folder): bool => $folder->hasVideos()
        );
    }

    /**
     * Get empty folders (no videos).
     *
     * @return array<FolderDTO>
     */
    public function getEmpty(): array
    {
        return $this->filter(
            static fn (FolderDTO $folder): bool => $folder->isEmpty()
        );
    }

    /**
     * Find folder by ID.
     *
     * @param string $id Folder identifier
     *
     * @return FolderDTO|null
     */
    public function findById(string $id): ?FolderDTO
    {
        return $this->find(
            static fn (FolderDTO $folder): bool => $folder->id === $id
        );
    }

    /**
     * Find folder by name.
     *
     * @param string $name Folder name
     *
     * @return FolderDTO|null
     */
    public function findByName(string $name): ?FolderDTO
    {
        return $this->find(
            static fn (FolderDTO $folder): bool => $folder->name === $name
        );
    }

    /**
     * Find folder by path.
     *
     * @param string $path Full path
     *
     * @return FolderDTO|null
     */
    public function findByPath(string $path): ?FolderDTO
    {
        return $this->find(
            static fn (FolderDTO $folder): bool => $folder->path === $path
        );
    }

    /**
     * Get total video count across all folders.
     *
     * @return int
     */
    public function getTotalVideosCount(): int
    {
        return array_reduce(
            $this->data,
            static fn (int $total, FolderDTO $folder): int =>
                $total + $folder->videosCount,
            0
        );
    }

    /**
     * Build a folder tree structure.
     *
     * Returns an array of root folders with nested children.
     *
     * @return array<array{folder: FolderDTO, children: array<mixed>}>
     */
    public function buildTree(): array
    {
        $tree = [];
        $byId = [];

        foreach ($this->data as $folder) {
            $byId[$folder->id] = [
                'folder' => $folder,
                'children' => [],
            ];
        }

        foreach ($byId as $id => &$node) {
            $folder = $node['folder'];

            if ($folder->parentId !== null && isset($byId[$folder->parentId])) {
                $byId[$folder->parentId]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }

        return $tree;
    }

    /**
     * Get IDs of all folders.
     *
     * @return array<string>
     */
    public function getIds(): array
    {
        return $this->map(static fn (FolderDTO $folder): string => $folder->id);
    }
}
