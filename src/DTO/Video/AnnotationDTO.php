<?php

declare(strict_types=1);

namespace Kinescope\DTO\Video;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Video annotation data transfer object.
 *
 * Represents an annotation/marker on a video timeline.
 */
final readonly class AnnotationDTO
{
    /**
     * Create a new AnnotationDTO instance.
     *
     * @param string $id Annotation unique identifier
     * @param string $videoId Parent video identifier
     * @param string $title Annotation title
     * @param string|null $description Annotation description
     * @param int $time Time position in seconds
     * @param int|null $duration Duration in seconds (for range annotations)
     * @param string|null $type Annotation type (marker, chapter, etc.)
     * @param string|null $url URL associated with annotation
     * @param array<string, mixed> $metadata Additional metadata
     * @param DateTimeImmutable|null $createdAt Creation timestamp
     * @param DateTimeImmutable|null $updatedAt Last update timestamp
     */
    public function __construct(
        public string $id,
        public string $videoId,
        public string $title,
        public ?string $description,
        public int $time,
        public ?int $duration,
        public ?string $type,
        public ?string $url,
        public array $metadata,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * Create an AnnotationDTO from API response array.
     *
     * @param array<string, mixed> $data Raw API response data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            videoId: (string) ($data['video_id'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            time: (int) ($data['time'] ?? 0),
            duration: isset($data['duration']) ? (int) $data['duration'] : null,
            type: isset($data['type']) ? (string) $data['type'] : null,
            url: isset($data['url']) ? (string) $data['url'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata'])
                ? $data['metadata']
                : [],
            createdAt: isset($data['created_at'])
                ? new DateTimeImmutable($data['created_at'])
                : null,
            updatedAt: isset($data['updated_at'])
                ? new DateTimeImmutable($data['updated_at'])
                : null,
        );
    }

    /**
     * Get time formatted as HH:MM:SS.
     *
     * @return string
     */
    public function getFormattedTime(): string
    {
        $hours = (int) floor($this->time / 3600);
        $minutes = (int) floor(($this->time % 3600) / 60);
        $seconds = $this->time % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get end time if this is a range annotation.
     *
     * @return int|null End time in seconds, or null if point annotation
     */
    public function getEndTime(): ?int
    {
        if ($this->duration === null) {
            return null;
        }

        return $this->time + $this->duration;
    }

    /**
     * Check if this is a range annotation (has duration).
     *
     * @return bool
     */
    public function isRange(): bool
    {
        return $this->duration !== null && $this->duration > 0;
    }

    /**
     * Check if this is a point annotation (no duration).
     *
     * @return bool
     */
    public function isPoint(): bool
    {
        return ! $this->isRange();
    }

    /**
     * Check if annotation has a URL.
     *
     * @return bool
     */
    public function hasUrl(): bool
    {
        return $this->url !== null && $this->url !== '';
    }

    /**
     * Check if given time falls within this annotation.
     *
     * @param int $timeInSeconds Time to check
     *
     * @return bool
     */
    public function containsTime(int $timeInSeconds): bool
    {
        if ($this->isPoint()) {
            return $this->time === $timeInSeconds;
        }

        $endTime = $this->getEndTime();

        return $timeInSeconds >= $this->time
            && $endTime !== null
            && $timeInSeconds <= $endTime;
    }

    /**
     * Get a metadata value by key.
     *
     * @param string $key Metadata key
     * @param mixed $default Default value if key not found
     *
     * @return mixed
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
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
            'video_id' => $this->videoId,
            'title' => $this->title,
            'description' => $this->description,
            'time' => $this->time,
            'duration' => $this->duration,
            'type' => $this->type,
            'url' => $this->url,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt?->format(DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(DateTimeInterface::ATOM),
        ];
    }
}
