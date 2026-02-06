<?php

declare(strict_types=1);

namespace Kinescope\DTO\Folder;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Folder data transfer object.
 *
 * Represents a folder within a project for organizing videos.
 */
final readonly class FolderDTO
{
    /**
     * Create a new FolderDTO instance.
     *
     * @param string $id Folder unique identifier (UUID)
     * @param string $projectId Parent project identifier
     * @param string $name Folder name
     * @param string|null $description Folder description
     * @param string|null $parentId Parent folder ID (for nested folders)
     * @param int $videosCount Number of videos in folder
     * @param int $depth Nesting depth (0 = root level)
     * @param string|null $path Full path from root (e.g., "parent/child/folder")
     * @param int $position Sort position
     * @param DateTimeImmutable $createdAt Creation timestamp
     * @param DateTimeImmutable $updatedAt Last update timestamp
     */
    public function __construct(
        public string $id,
        public string $projectId,
        public string $name,
        public ?string $description,
        public ?string $parentId,
        public int $videosCount,
        public int $depth,
        public ?string $path,
        public int $position,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * Create a FolderDTO from API response array.
     *
     * @param array<string, mixed> $data Raw API response data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            projectId: (string) ($data['project_id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            parentId: isset($data['parent_id']) ? (string) $data['parent_id'] : null,
            videosCount: (int) ($data['videos_count'] ?? 0),
            depth: (int) ($data['depth'] ?? 0),
            path: isset($data['path']) ? (string) $data['path'] : null,
            position: (int) ($data['position'] ?? 0),
            createdAt: new DateTimeImmutable($data['created_at'] ?? 'now'),
            updatedAt: new DateTimeImmutable($data['updated_at'] ?? 'now'),
        );
    }

    /**
     * Check if this is a root-level folder.
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    /**
     * Check if this folder has a parent.
     *
     * @return bool
     */
    public function hasParent(): bool
    {
        return $this->parentId !== null;
    }

    /**
     * Check if folder has any videos.
     *
     * @return bool
     */
    public function hasVideos(): bool
    {
        return $this->videosCount > 0;
    }

    /**
     * Check if folder is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->videosCount === 0;
    }

    /**
     * Get full path or name if path not set.
     *
     * @return string
     */
    public function getFullPath(): string
    {
        return $this->path ?? $this->name;
    }

    /**
     * Get path segments as array.
     *
     * @return array<string>
     */
    public function getPathSegments(): array
    {
        if ($this->path === null || $this->path === '') {
            return [$this->name];
        }

        return explode('/', $this->path);
    }

    /**
     * Check if this folder is a child of another folder.
     *
     * @param string $parentId Potential parent folder ID
     *
     * @return bool
     */
    public function isChildOf(string $parentId): bool
    {
        return $this->parentId === $parentId;
    }

    /**
     * Check if folder is at a specific depth.
     *
     * @param int $depth Depth to check
     *
     * @return bool
     */
    public function isAtDepth(int $depth): bool
    {
        return $this->depth === $depth;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->projectId,
            'name' => $this->name,
            'description' => $this->description,
            'parent_id' => $this->parentId,
            'videos_count' => $this->videosCount,
            'depth' => $this->depth,
            'path' => $this->path,
            'position' => $this->position,
            'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
