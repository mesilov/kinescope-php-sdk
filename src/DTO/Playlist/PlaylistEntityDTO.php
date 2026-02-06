<?php

declare(strict_types=1);

namespace Kinescope\DTO\Playlist;

use DateTimeImmutable;
use DateTimeInterface;
use Kinescope\Enum\VideoStatus;

/**
 * Playlist entity (item) data transfer object.
 *
 * Represents an item (video) within a playlist.
 */
final readonly class PlaylistEntityDTO
{
    /**
     * Create a new PlaylistEntityDTO instance.
     *
     * @param string $id Entity unique identifier
     * @param string $playlistId Parent playlist identifier
     * @param string $videoId Associated video identifier
     * @param string $title Item title (may differ from video title)
     * @param string|null $description Item description
     * @param int $position Position in playlist (0-indexed)
     * @param int $duration Duration in seconds
     * @param VideoStatus|null $videoStatus Status of associated video
     * @param string|null $posterUrl Poster/thumbnail URL
     * @param DateTimeImmutable|null $addedAt When item was added to playlist
     */
    public function __construct(
        public string $id,
        public string $playlistId,
        public string $videoId,
        public string $title,
        public ?string $description,
        public int $position,
        public int $duration,
        public ?VideoStatus $videoStatus,
        public ?string $posterUrl,
        public ?DateTimeImmutable $addedAt,
    ) {
    }

    /**
     * Create a PlaylistEntityDTO from API response array.
     *
     * @param array<string, mixed> $data Raw API response data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $videoStatus = null;

        if (isset($data['video_status'])) {
            $videoStatus = VideoStatus::tryFrom((string) $data['video_status']);
        }

        return new self(
            id: (string) $data['id'],
            playlistId: (string) ($data['playlist_id'] ?? ''),
            videoId: (string) ($data['video_id'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            position: (int) ($data['position'] ?? 0),
            duration: (int) ($data['duration'] ?? 0),
            videoStatus: $videoStatus,
            posterUrl: isset($data['poster_url']) ? (string) $data['poster_url'] : null,
            addedAt: isset($data['added_at'])
                ? new DateTimeImmutable($data['added_at'])
                : null,
        );
    }

    /**
     * Get duration formatted as HH:MM:SS or MM:SS.
     *
     * @return string
     */
    public function getFormattedDuration(): string
    {
        $hours = (int) floor($this->duration / 3600);
        $minutes = (int) floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Check if associated video is ready for playback.
     *
     * @return bool
     */
    public function isVideoReady(): bool
    {
        return $this->videoStatus?->isReady() ?? false;
    }

    /**
     * Check if associated video is still processing.
     *
     * @return bool
     */
    public function isVideoProcessing(): bool
    {
        return $this->videoStatus?->isProcessing() ?? false;
    }

    /**
     * Check if associated video has an error.
     *
     * @return bool
     */
    public function hasVideoError(): bool
    {
        return $this->videoStatus?->hasError() ?? false;
    }

    /**
     * Check if item has a poster.
     *
     * @return bool
     */
    public function hasPoster(): bool
    {
        return $this->posterUrl !== null && $this->posterUrl !== '';
    }

    /**
     * Get position as 1-indexed (human-friendly).
     *
     * @return int
     */
    public function getHumanPosition(): int
    {
        return $this->position + 1;
    }

    /**
     * Check if this is first item in playlist.
     *
     * @return bool
     */
    public function isFirst(): bool
    {
        return $this->position === 0;
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
            'playlist_id' => $this->playlistId,
            'video_id' => $this->videoId,
            'title' => $this->title,
            'description' => $this->description,
            'position' => $this->position,
            'duration' => $this->duration,
            'video_status' => $this->videoStatus?->value,
            'poster_url' => $this->posterUrl,
            'added_at' => $this->addedAt?->format(DateTimeInterface::ATOM),
        ];
    }
}
