<?php

declare(strict_types=1);

namespace Kinescope\DTO\Playlist;

use Carbon\CarbonImmutable;
use Kinescope\DTO\Common\ApiDate;
use Kinescope\Enum\VideoStatus;

/**
 * Playlist entity data transfer object.
 *
 * Mirrors current items returned by `/v1/playlists/{playlist_id}/entities`.
 */
final readonly class PlaylistEntityDTO
{
    /**
     * @param array<string, mixed> $additionalData
     */
    public function __construct(
        public string $id,
        public int $position,
        public ?VideoStatus $status,
        public ?string $statusRaw,
        public string $title,
        public ?string $description,
        public float $duration,
        public ?CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt,
        public array $additionalData = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data Raw API response data
     */
    public static function fromArray(array $data): self
    {
        $statusRaw = isset($data['status']) ? (string) $data['status'] : null;
        $knownFields = [
            'id', 'position', 'status', 'title', 'description',
            'duration', 'created_at', 'updated_at',
        ];

        return new self(
            id: (string) $data['id'],
            position: (int) ($data['position'] ?? 0),
            status: $statusRaw !== null ? VideoStatus::tryFrom($statusRaw) : null,
            statusRaw: $statusRaw,
            title: (string) ($data['title'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            duration: (float) ($data['duration'] ?? 0),
            createdAt: ApiDate::from($data['created_at'] ?? null),
            updatedAt: ApiDate::from($data['updated_at'] ?? null),
            additionalData: array_diff_key($data, array_flip($knownFields)),
        );
    }

    public function getFormattedDuration(): string
    {
        $secondsTotal = (int) round($this->duration);
        $hours = (int) floor($secondsTotal / 3600);
        $minutes = (int) floor(($secondsTotal % 3600) / 60);
        $seconds = $secondsTotal % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function isReady(): bool
    {
        return $this->status?->isReady() ?? false;
    }

    public function isProcessing(): bool
    {
        return $this->status?->isProcessing() ?? false;
    }

    public function hasError(): bool
    {
        return $this->status?->hasError() ?? false;
    }

    public function isFirst(): bool
    {
        return $this->position === 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'position' => $this->position,
            'status' => $this->status !== null ? $this->status->value : $this->statusRaw,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'created_at' => ApiDate::toString($this->createdAt),
            'updated_at' => ApiDate::toString($this->updatedAt),
        ], $this->additionalData);
    }
}
