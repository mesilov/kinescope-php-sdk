<?php

declare(strict_types=1);

namespace Kinescope\DTO\Project;

use Kinescope\DTO\Common\MetaDTO;
use Kinescope\DTO\Common\PaginatedResponse;
use Kinescope\Enum\PrivacyType;

/**
 * Paginated list of projects.
 *
 * @extends PaginatedResponse<ProjectDTO>
 */
final readonly class ProjectListResult extends PaginatedResponse
{
    /**
     * Create a ProjectListResult from API response array.
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
                static fn (array $item): ProjectDTO => ProjectDTO::fromArray($item),
                $response['data']
            );
        }

        $meta = MetaDTO::fromArray($response['meta'] ?? []);

        return new self($data, $meta);
    }

    /**
     * Get default project.
     *
     * @return ProjectDTO|null
     */
    public function getDefault(): ?ProjectDTO
    {
        return $this->find(
            static fn (ProjectDTO $project): bool => $project->isDefault
        );
    }

    /**
     * Get projects by privacy type.
     *
     * @param PrivacyType $privacyType Privacy type to filter by
     *
     * @return array<ProjectDTO>
     */
    public function getByPrivacyType(PrivacyType $privacyType): array
    {
        return $this->filter(
            static fn (ProjectDTO $project): bool =>
                $project->privacyType === $privacyType
        );
    }

    /**
     * Get all public projects.
     *
     * @return array<ProjectDTO>
     */
    public function getPublic(): array
    {
        return $this->filter(
            static fn (ProjectDTO $project): bool => $project->isPublic()
        );
    }

    /**
     * Get projects with domain restrictions.
     *
     * @return array<ProjectDTO>
     */
    public function getWithDomainRestrictions(): array
    {
        return $this->filter(
            static fn (ProjectDTO $project): bool => $project->hasDomainRestrictions()
        );
    }

    /**
     * Get projects with disabled playback.
     *
     * @return array<ProjectDTO>
     */
    public function getDisabled(): array
    {
        return $this->filter(
            static fn (ProjectDTO $project): bool => $project->isPlaybackDisabled()
        );
    }

    /**
     * Get projects that have videos.
     *
     * @return array<ProjectDTO>
     */
    public function getWithVideos(): array
    {
        return $this->filter(
            static fn (ProjectDTO $project): bool => $project->hasVideos()
        );
    }

    /**
     * Get empty projects (no videos).
     *
     * @return array<ProjectDTO>
     */
    public function getEmpty(): array
    {
        return $this->filter(
            static fn (ProjectDTO $project): bool => ! $project->hasVideos()
        );
    }

    /**
     * Find project by ID.
     *
     * @param string $id Project identifier
     *
     * @return ProjectDTO|null
     */
    public function findById(string $id): ?ProjectDTO
    {
        return $this->find(
            static fn (ProjectDTO $project): bool => $project->id === $id
        );
    }

    /**
     * Find project by name.
     *
     * @param string $name Project name
     *
     * @return ProjectDTO|null
     */
    public function findByName(string $name): ?ProjectDTO
    {
        return $this->find(
            static fn (ProjectDTO $project): bool => $project->name === $name
        );
    }

    /**
     * Get total video count across all projects.
     *
     * @return int
     */
    public function getTotalVideosCount(): int
    {
        return array_reduce(
            $this->data,
            static fn (int $total, ProjectDTO $project): int =>
                $total + $project->videosCount,
            0
        );
    }

    /**
     * Get total storage used across all projects in bytes.
     *
     * @return int
     */
    public function getTotalStorageUsed(): int
    {
        return array_reduce(
            $this->data,
            static fn (int $total, ProjectDTO $project): int =>
                $total + ($project->storageUsed ?? 0),
            0
        );
    }

    /**
     * Get IDs of all projects.
     *
     * @return array<string>
     */
    public function getIds(): array
    {
        return $this->map(static fn (ProjectDTO $project): string => $project->id);
    }
}
