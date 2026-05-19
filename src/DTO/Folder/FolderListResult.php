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
     * @param array<string, mixed> $response Raw API response
     */
    public static function fromArray(array $response): self
    {
        $data = [];

        if (isset($response['data']) && is_array($response['data'])) {
            $data = array_map(
                FolderDTO::fromArray(...),
                array_values(array_filter($response['data'], is_array(...))),
            );
        }

        return new self($data, MetaDTO::fromArray($response['meta'] ?? []));
    }

    /**
     * @return list<FolderDTO>
     */
    public function getRoots(): array
    {
        return $this->filter(
            static fn (FolderDTO $folder): bool => $folder->isRoot(),
        );
    }

    /**
     * @return list<FolderDTO>
     */
    public function getChildren(string $parentId): array
    {
        return $this->filter(
            static fn (FolderDTO $folder): bool => $folder->isChildOf($parentId),
        );
    }

    /**
     * @return list<FolderDTO>
     */
    public function getSortedByName(): array
    {
        $sorted = $this->data;

        usort(
            $sorted,
            static fn (FolderDTO $a, FolderDTO $b): int => strcasecmp($a->name, $b->name),
        );

        return $sorted;
    }

    /**
     * @return list<FolderDTO>
     */
    public function getWithItems(): array
    {
        return $this->filter(
            static fn (FolderDTO $folder): bool => $folder->hasItems(),
        );
    }

    /**
     * @return list<FolderDTO>
     */
    public function getEmpty(): array
    {
        return $this->filter(
            static fn (FolderDTO $folder): bool => $folder->isEmpty(),
        );
    }

    public function findById(string $id): ?FolderDTO
    {
        return $this->find(
            static fn (FolderDTO $folder): bool => $folder->id === $id,
        );
    }

    public function findByName(string $name): ?FolderDTO
    {
        return $this->find(
            static fn (FolderDTO $folder): bool => $folder->name === $name,
        );
    }

    public function getTotalItemsCount(): int
    {
        return array_reduce(
            $this->data,
            static fn (int $total, FolderDTO $folder): int => $total + $folder->itemsCount,
            0,
        );
    }

    public function getTotalSize(): int
    {
        return array_reduce(
            $this->data,
            static fn (int $total, FolderDTO $folder): int => $total + $folder->size,
            0,
        );
    }

    /**
     * @return list<array{folder: FolderDTO, children: list<mixed>}>
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

        foreach ($byId as &$node) {
            $folder = $node['folder'];

            $parentId = $folder->parentId;

            if ($folder->hasParent() && $parentId !== null && isset($byId[$parentId])) {
                $byId[$parentId]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }

        return $tree;
    }

    /**
     * @return list<string>
     */
    public function getIds(): array
    {
        return $this->map(static fn (FolderDTO $folder): string => $folder->id);
    }
}
