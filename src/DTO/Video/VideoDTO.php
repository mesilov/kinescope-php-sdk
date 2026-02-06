<?php

declare(strict_types=1);

namespace Kinescope\DTO\Video;

use DateTimeImmutable;
use DateTimeInterface;
use Kinescope\Enum\VideoStatus;

/**
 * Video data transfer object.
 *
 * Represents a video in the Kinescope system with all its properties.
 */
final readonly class VideoDTO
{
    /**
     * Create a new VideoDTO instance.
     *
     * @param string $id Video unique identifier (UUID)
     * @param string $title Video title
     * @param string|null $description Video description
     * @param VideoStatus $status Current processing status
     * @param int $duration Duration in seconds
     * @param string|null $projectId Parent project identifier
     * @param string|null $folderId Parent folder identifier
     * @param string|null $embedCode HTML embed code
     * @param string|null $hlsLink HLS streaming URL
     * @param string|null $dashLink DASH streaming URL
     * @param string|null $posterUrl Poster image URL
     * @param string|null $thumbnailUrl Thumbnail image URL
     * @param int|null $viewsCount Number of views
     * @param int|null $playsCount Number of plays
     * @param array<AssetDTO> $assets Available quality variants
     * @param array<string, mixed> $additionalData Additional data from API
     * @param DateTimeImmutable $createdAt Creation timestamp
     * @param DateTimeImmutable $updatedAt Last update timestamp
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public VideoStatus $status,
        public int $duration,
        public ?string $projectId,
        public ?string $folderId,
        public ?string $embedCode,
        public ?string $hlsLink,
        public ?string $dashLink,
        public ?string $posterUrl,
        public ?string $thumbnailUrl,
        public ?int $viewsCount,
        public ?int $playsCount,
        public array $assets,
        public array $additionalData,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * Create a VideoDTO from API response array.
     *
     * @param array<string, mixed> $data Raw API response data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $assets = [];

        if (isset($data['assets']) && is_array($data['assets'])) {
            $assets = array_map(
                static fn (array $asset): AssetDTO => AssetDTO::fromArray($asset),
                $data['assets']
            );
        }

        $knownFields = [
            'id', 'title', 'description', 'status', 'duration',
            'project_id', 'folder_id', 'embed_code', 'hls_link', 'dash_link',
            'poster_url', 'thumbnail_url', 'views_count', 'plays_count',
            'assets', 'created_at', 'updated_at',
        ];
        $additionalData = array_diff_key($data, array_flip($knownFields));

        return new self(
            id: (string) $data['id'],
            title: (string) ($data['title'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            status: VideoStatus::from((string) ($data['status'] ?? 'pending')),
            duration: (int) ($data['duration'] ?? 0),
            projectId: isset($data['project_id']) ? (string) $data['project_id'] : null,
            folderId: isset($data['folder_id']) ? (string) $data['folder_id'] : null,
            embedCode: isset($data['embed_code']) ? (string) $data['embed_code'] : null,
            hlsLink: isset($data['hls_link']) ? (string) $data['hls_link'] : null,
            dashLink: isset($data['dash_link']) ? (string) $data['dash_link'] : null,
            posterUrl: isset($data['poster_url']) ? (string) $data['poster_url'] : null,
            thumbnailUrl: isset($data['thumbnail_url']) ? (string) $data['thumbnail_url'] : null,
            viewsCount: isset($data['views_count']) ? (int) $data['views_count'] : null,
            playsCount: isset($data['plays_count']) ? (int) $data['plays_count'] : null,
            assets: $assets,
            additionalData: $additionalData,
            createdAt: new DateTimeImmutable($data['created_at'] ?? 'now'),
            updatedAt: new DateTimeImmutable($data['updated_at'] ?? 'now'),
        );
    }

    /**
     * Check if video is ready for playback.
     *
     * @return bool
     */
    public function isReady(): bool
    {
        return $this->status->isReady();
    }

    /**
     * Check if video is still processing.
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return $this->status->isProcessing();
    }

    /**
     * Check if video processing failed.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->status->hasError();
    }

    /**
     * Get duration formatted as HH:MM:SS.
     *
     * @return string
     */
    public function getFormattedDuration(): string
    {
        $hours = (int) floor($this->duration / 3600);
        $minutes = (int) floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get the highest quality asset.
     *
     * @return AssetDTO|null
     */
    public function getHighestQualityAsset(): ?AssetDTO
    {
        if ($this->assets === []) {
            return null;
        }

        $sorted = $this->assets;
        usort(
            $sorted,
            static fn (AssetDTO $a, AssetDTO $b): int =>
            ($b->height ?? 0) <=> ($a->height ?? 0)
        );

        return $sorted[0];
    }

    /**
     * Get the lowest quality asset.
     *
     * @return AssetDTO|null
     */
    public function getLowestQualityAsset(): ?AssetDTO
    {
        if ($this->assets === []) {
            return null;
        }

        $sorted = $this->assets;
        usort(
            $sorted,
            static fn (AssetDTO $a, AssetDTO $b): int =>
            ($a->height ?? 0) <=> ($b->height ?? 0)
        );

        return $sorted[0];
    }

    /**
     * Check if video has HLS link.
     *
     * @return bool
     */
    public function hasHlsLink(): bool
    {
        return $this->hlsLink !== null && $this->hlsLink !== '';
    }

    /**
     * Check if video has embed code.
     *
     * @return bool
     */
    public function hasEmbedCode(): bool
    {
        return $this->embedCode !== null && $this->embedCode !== '';
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
            'status' => $this->status->value,
            'duration' => $this->duration,
            'project_id' => $this->projectId,
            'folder_id' => $this->folderId,
            'embed_code' => $this->embedCode,
            'hls_link' => $this->hlsLink,
            'dash_link' => $this->dashLink,
            'poster_url' => $this->posterUrl,
            'thumbnail_url' => $this->thumbnailUrl,
            'views_count' => $this->viewsCount,
            'plays_count' => $this->playsCount,
            'assets' => array_map(
                static fn (AssetDTO $asset): array => $asset->toArray(),
                $this->assets
            ),
            'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
