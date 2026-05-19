<?php

declare(strict_types=1);

namespace Kinescope\DTO\Folder;

use Carbon\CarbonImmutable;
use Kinescope\DTO\Common\ApiDate;

/**
 * Folder data transfer object.
 *
 * Mirrors the current Kinescope folder payload.
 */
final readonly class FolderDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $projectId,
        public ?string $parentId,
        public int $size,
        public int $itemsCount,
        public ?CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data Raw API response data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            name: (string) ($data['name'] ?? ''),
            projectId: (string) ($data['project_id'] ?? ''),
            parentId: isset($data['parent_id']) ? (string) $data['parent_id'] : null,
            size: (int) ($data['size'] ?? 0),
            itemsCount: (int) ($data['items_count'] ?? 0),
            createdAt: ApiDate::from($data['created_at'] ?? null),
            updatedAt: ApiDate::from($data['updated_at'] ?? null),
            deletedAt: ApiDate::from($data['deleted_at'] ?? null),
        );
    }

    public function isRoot(): bool
    {
        return $this->parentId === null || $this->parentId === $this->projectId;
    }

    public function hasParent(): bool
    {
        return $this->parentId !== null && $this->parentId !== $this->projectId;
    }

    public function hasItems(): bool
    {
        return $this->itemsCount > 0;
    }

    public function isEmpty(): bool
    {
        return $this->itemsCount === 0;
    }

    public function isChildOf(string $parentId): bool
    {
        return $this->parentId === $parentId;
    }

    public function getHumanSize(): ?string
    {
        if ($this->size === 0) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            ++$unitIndex;
        }

        return sprintf('%.2f %s', $size, $units[$unitIndex]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'project_id' => $this->projectId,
            'parent_id' => $this->parentId,
            'size' => $this->size,
            'items_count' => $this->itemsCount,
            'created_at' => ApiDate::toString($this->createdAt),
            'updated_at' => ApiDate::toString($this->updatedAt),
            'deleted_at' => ApiDate::toString($this->deletedAt),
        ];
    }
}
