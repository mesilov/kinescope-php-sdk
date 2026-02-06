<?php

declare(strict_types=1);

namespace Kinescope\DTO\Project;

use DateTimeImmutable;
use DateTimeInterface;
use Kinescope\Enum\PrivacyType;

/**
 * Project data transfer object.
 *
 * Represents a project in Kinescope system.
 * Projects are containers for organizing videos.
 */
final readonly class ProjectDTO
{
    /**
     * Create a new ProjectDTO instance.
     *
     * @param string $id Project unique identifier (UUID)
     * @param string $name Project name
     * @param string|null $description Project description
     * @param PrivacyType|null $privacyType Privacy/playback restrictions
     * @param string|null $privacyTypeRaw Raw privacy type from API (if not in enum)
     * @param int $videosCount Number of videos in project
     * @param int $foldersCount Number of folders in project
     * @param int|null $storageUsed Storage used in bytes
     * @param bool $isDefault Whether this is default project
     * @param array<string> $allowedDomains Allowed domains for playback (if privacy is custom)
     * @param array<string, mixed> $settings Project settings
     * @param DateTimeImmutable $createdAt Creation timestamp
     * @param DateTimeImmutable $updatedAt Last update timestamp
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ?PrivacyType $privacyType,
        public ?string $privacyTypeRaw,
        public int $videosCount,
        public int $foldersCount,
        public ?int $storageUsed,
        public bool $isDefault,
        public array $allowedDomains,
        public array $settings,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * Create a ProjectDTO from API response array.
     *
     * @param array<string, mixed> $data Raw API response data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $privacyTypeRaw = isset($data['privacy_type']) ? (string) $data['privacy_type'] : null;
        $privacyType = $privacyTypeRaw !== null
            ? PrivacyType::tryFrom($privacyTypeRaw)
            : null;

        return new self(
            id: (string) $data['id'],
            name: (string) ($data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            privacyType: $privacyType,
            privacyTypeRaw: $privacyTypeRaw,
            videosCount: (int) ($data['videos_count'] ?? 0),
            foldersCount: (int) ($data['folders_count'] ?? 0),
            storageUsed: isset($data['storage_used']) ? (int) $data['storage_used'] : null,
            isDefault: (bool) ($data['is_default'] ?? false),
            allowedDomains: isset($data['allowed_domains']) && is_array($data['allowed_domains'])
                ? array_map('strval', $data['allowed_domains'])
                : [],
            settings: isset($data['settings']) && is_array($data['settings'])
                ? $data['settings']
                : [],
            createdAt: new DateTimeImmutable($data['created_at'] ?? 'now'),
            updatedAt: new DateTimeImmutable($data['updated_at'] ?? 'now'),
        );
    }

    /**
     * Check if playback is allowed anywhere.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->privacyType?->isPublic() ?? false;
    }

    /**
     * Check if playback has domain restrictions.
     *
     * @return bool
     */
    public function hasDomainRestrictions(): bool
    {
        return $this->privacyType?->hasDomainRestrictions() ?? false;
    }

    /**
     * Check if playback is disabled.
     *
     * @return bool
     */
    public function isPlaybackDisabled(): bool
    {
        return $this->privacyType?->isDisabled() ?? false;
    }

    /**
     * Check if a domain is allowed for playback.
     *
     * @param string $domain Domain to check
     *
     * @return bool
     */
    public function isDomainAllowed(string $domain): bool
    {
        if ($this->isPublic()) {
            return true;
        }

        if ($this->isPlaybackDisabled()) {
            return false;
        }

        if (! $this->hasDomainRestrictions()) {
            return false;
        }

        $normalizedDomain = strtolower(trim($domain));

        foreach ($this->allowedDomains as $allowed) {
            $normalizedAllowed = strtolower(trim($allowed));

            if ($normalizedDomain === $normalizedAllowed) {
                return true;
            }

            if (str_starts_with($normalizedAllowed, '*.')) {
                $baseDomain = substr($normalizedAllowed, 2);

                if (str_ends_with($normalizedDomain, $baseDomain)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if project has any videos.
     *
     * @return bool
     */
    public function hasVideos(): bool
    {
        return $this->videosCount > 0;
    }

    /**
     * Check if project has any folders.
     *
     * @return bool
     */
    public function hasFolders(): bool
    {
        return $this->foldersCount > 0;
    }

    /**
     * Get human-readable storage used.
     *
     * @return string|null
     */
    public function getHumanStorageUsed(): ?string
    {
        if ($this->storageUsed === null) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->storageUsed;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return sprintf('%.2f %s', $size, $units[$unitIndex]);
    }

    /**
     * Get a setting value by key.
     *
     * @param string $key Setting key
     * @param mixed $default Default value if key not found
     *
     * @return mixed
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
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
            'name' => $this->name,
            'description' => $this->description,
            'privacy_type' => $this->privacyType !== null ? $this->privacyType->value : $this->privacyTypeRaw,
            'videos_count' => $this->videosCount,
            'folders_count' => $this->foldersCount,
            'storage_used' => $this->storageUsed,
            'is_default' => $this->isDefault,
            'allowed_domains' => $this->allowedDomains,
            'settings' => $this->settings,
            'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
