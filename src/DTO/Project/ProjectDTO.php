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
     * @param PrivacyType|null $privacyType Privacy/playback restrictions
     * @param string|null $privacyTypeRaw Raw privacy type from API (if not in enum)
     * @param list<array<string, mixed>> $folders Raw project folders from API
     * @param array<string> $privacyDomains Allowed playback domains from API
     * @param array<string> $privacyEmailDomains Allowed playback email domains from API
     * @param array<string, mixed> $privacyShare Raw privacy share object from API
     * @param string|null $playerId Player identifier
     * @param bool $favorite Favorite flag
     * @param int $size Project size in bytes
     * @param int $itemsCount Project item count from API
     * @param DateTimeImmutable $createdAt Creation timestamp
     * @param DateTimeImmutable $updatedAt Last update timestamp
     * @param bool $encrypted Encrypted flag
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?PrivacyType $privacyType,
        public ?string $privacyTypeRaw,
        public array $folders,
        public array $privacyDomains,
        public array $privacyEmailDomains,
        public array $privacyShare,
        public ?string $playerId,
        public bool $favorite,
        public int $size,
        public int $itemsCount,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public bool $encrypted,
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
            privacyType: $privacyType,
            privacyTypeRaw: $privacyTypeRaw,
            folders: isset($data['folders']) && is_array($data['folders'])
                ? array_values(array_filter($data['folders'], is_array(...)))
                : [],
            privacyDomains: isset($data['privacy_domains']) && is_array($data['privacy_domains'])
                ? array_map(strval(...), $data['privacy_domains'])
                : [],
            privacyEmailDomains: isset($data['privacy_email_domains']) && is_array($data['privacy_email_domains'])
                ? array_map(strval(...), $data['privacy_email_domains'])
                : [],
            privacyShare: isset($data['privacy_share']) && is_array($data['privacy_share']) ? $data['privacy_share'] : [],
            playerId: isset($data['player_id']) ? (string) $data['player_id'] : null,
            favorite: (bool) ($data['favorite'] ?? false),
            size: (int) ($data['size'] ?? 0),
            itemsCount: (int) ($data['items_count'] ?? 0),
            createdAt: new DateTimeImmutable($data['created_at'] ?? 'now'),
            updatedAt: new DateTimeImmutable($data['updated_at'] ?? 'now'),
            encrypted: (bool) ($data['encrypted'] ?? false),
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

        foreach ($this->privacyDomains as $allowed) {
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
        return $this->itemsCount > 0;
    }

    /**
     * Check if project has any folders.
     *
     * @return bool
     */
    public function hasFolders(): bool
    {
        return $this->folders !== [];
    }

    /**
     * Get human-readable size.
     *
     * @return string|null
     */
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
            $unitIndex++;
        }

        return sprintf('%.2f %s', $size, $units[$unitIndex]);
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
            'folders' => $this->folders,
            'privacy_type' => $this->privacyType !== null ? $this->privacyType->value : $this->privacyTypeRaw,
            'privacy_domains' => $this->privacyDomains,
            'privacy_email_domains' => $this->privacyEmailDomains,
            'privacy_share' => $this->privacyShare === [] ? (object) [] : $this->privacyShare,
            'player_id' => $this->playerId,
            'favorite' => $this->favorite,
            'size' => $this->size,
            'items_count' => $this->itemsCount,
            'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt->format(DateTimeInterface::ATOM),
            'encrypted' => $this->encrypted,
        ];
    }
}
