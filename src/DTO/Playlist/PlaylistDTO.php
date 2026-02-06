<?php

declare(strict_types=1);

namespace Kinescope\DTO\Playlist;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Playlist data transfer object.
 *
 * Represents a playlist containing multiple videos.
 */
final readonly class PlaylistDTO
{
    /**
     * Create a new PlaylistDTO instance.
     *
     * @param string $id Playlist unique identifier (UUID)
     * @param string $title Playlist title
     * @param string|null $description Playlist description
     * @param string|null $projectId Associated project ID
     * @param int $itemsCount Number of items in playlist
     * @param int $totalDuration Total duration of all items in seconds
     * @param string|null $posterUrl Playlist poster/thumbnail URL
     * @param string|null $embedCode HTML embed code
     * @param bool $isPublic Whether playlist is publicly accessible
     * @param array<string, mixed> $settings Playlist settings
     * @param DateTimeImmutable $createdAt Creation timestamp
     * @param DateTimeImmutable $updatedAt Last update timestamp
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public ?string $projectId,
        public int $itemsCount,
        public int $totalDuration,
        public ?string $posterUrl,
        public ?string $embedCode,
        public bool $isPublic,
        public array $settings,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * Create a PlaylistDTO from API response array.
     *
     * @param array<string, mixed> $data Raw API response data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            title: (string) ($data['title'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            projectId: isset($data['project_id']) ? (string) $data['project_id'] : null,
            itemsCount: (int) ($data['items_count'] ?? 0),
            totalDuration: (int) ($data['total_duration'] ?? 0),
            posterUrl: isset($data['poster_url']) ? (string) $data['poster_url'] : null,
            embedCode: isset($data['embed_code']) ? (string) $data['embed_code'] : null,
            isPublic: (bool) ($data['is_public'] ?? false),
            settings: isset($data['settings']) && is_array($data['settings'])
                ? $data['settings']
                : [],
            createdAt: new DateTimeImmutable($data['created_at'] ?? 'now'),
            updatedAt: new DateTimeImmutable($data['updated_at'] ?? 'now'),
        );
    }

    /**
     * Check if playlist has any items.
     *
     * @return bool
     */
    public function hasItems(): bool
    {
        return $this->itemsCount > 0;
    }

    /**
     * Check if playlist is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->itemsCount === 0;
    }

    /**
     * Get total duration formatted as HH:MM:SS.
     *
     * @return string
     */
    public function getFormattedDuration(): string
    {
        $hours = (int) floor($this->totalDuration / 3600);
        $minutes = (int) floor(($this->totalDuration % 3600) / 60);
        $seconds = $this->totalDuration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Get average item duration.
     *
     * @return int Duration in seconds (0 if empty)
     */
    public function getAverageItemDuration(): int
    {
        if ($this->itemsCount === 0) {
            return 0;
        }

        return (int) round($this->totalDuration / $this->itemsCount);
    }

    /**
     * Check if playlist has an embed code.
     *
     * @return bool
     */
    public function hasEmbedCode(): bool
    {
        return $this->embedCode !== null && $this->embedCode !== '';
    }

    /**
     * Check if playlist has a poster.
     *
     * @return bool
     */
    public function hasPoster(): bool
    {
        return $this->posterUrl !== null && $this->posterUrl !== '';
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
            'title' => $this->title,
            'description' => $this->description,
            'project_id' => $this->projectId,
            'items_count' => $this->itemsCount,
            'total_duration' => $this->totalDuration,
            'poster_url' => $this->posterUrl,
            'embed_code' => $this->embedCode,
            'is_public' => $this->isPublic,
            'settings' => $this->settings,
            'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
