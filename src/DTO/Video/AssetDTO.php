<?php

declare(strict_types=1);

namespace Kinescope\DTO\Video;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Video asset (quality variant).
 *
 * Represents a specific quality/resolution variant of a video.
 */
final readonly class AssetDTO
{
    /**
     * Create a new AssetDTO instance.
     *
     * @param string $id Asset unique identifier
     * @param string $videoId Parent video identifier
     * @param string|null $quality Quality label (e.g., "1080p", "720p", "4k")
     * @param Resolution|null $resolution Pixel dimensions, or null when the API did not report them
     * @param int|null $bitrate Bitrate in bits per second
     * @param int $fileSize File size in bytes (must be > 0)
     * @param string|null $codec Video codec (e.g., "h264", "h265")
     * @param string|null $url Direct URL to the asset
     * @param string|null $downloadLink Download URL for the asset
     * @param DateTimeImmutable|null $createdAt Creation timestamp
     */
    public function __construct(
        public string $id,
        public string $videoId,
        public ?string $quality = null,
        public ?Resolution $resolution = null,
        public ?int $bitrate = null,
        public int $fileSize = 0,
        public ?string $codec = null,
        public ?string $url = null,
        public ?string $downloadLink = null,
        public ?DateTimeImmutable $createdAt = null,
    ) {
        if ($this->fileSize <= 0) {
            throw new InvalidArgumentException('Asset "file_size" must be greater than 0.');
        }
    }

    /**
     * Create an AssetDTO from API response array.
     *
     * @param array<string, mixed> $data Raw API response data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['file_size'])) {
            throw new InvalidArgumentException('Asset "file_size" is required.');
        }

        $fileSize = (int) $data['file_size'];

        if ($fileSize <= 0) {
            throw new InvalidArgumentException('Asset "file_size" must be greater than 0.');
        }

        return new self(
            id: (string) $data['id'],
            videoId: (string) ($data['video_id'] ?? ''),
            quality: isset($data['quality']) ? (string) $data['quality'] : null,
            resolution: self::resolutionFromPayload($data),
            bitrate: isset($data['bitrate']) ? (int) $data['bitrate'] : null,
            fileSize: $fileSize,
            codec: isset($data['codec']) ? (string) $data['codec'] : null,
            url: isset($data['url']) ? (string) $data['url'] : null,
            downloadLink: isset($data['download_link']) ? (string) $data['download_link'] : null,
            createdAt: isset($data['created_at'])
                ? new DateTimeImmutable($data['created_at'])
                : null,
        );
    }

    public function getAspectRatio(): ?float
    {
        return $this->resolution?->aspectRatio();
    }

    public function isHd(): bool
    {
        return $this->resolution?->isHd() ?? false;
    }

    public function isFullHd(): bool
    {
        return $this->resolution?->isFullHd() ?? false;
    }

    public function is4K(): bool
    {
        return $this->resolution?->is4K() ?? false;
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanFileSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->fileSize;
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
            'video_id' => $this->videoId,
            'quality' => $this->quality,
            'resolution' => $this->resolution === null ? null : (string) $this->resolution,
            'bitrate' => $this->bitrate,
            'file_size' => $this->fileSize,
            'codec' => $this->codec,
            'url' => $this->url,
            'download_link' => $this->downloadLink,
            'created_at' => $this->createdAt?->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function resolutionFromPayload(array $data): ?Resolution
    {
        if (isset($data['width'], $data['height'])) {
            $width = (int) $data['width'];
            $height = (int) $data['height'];

            if ($width > 0 && $height > 0) {
                return new Resolution(width: $width, height: $height);
            }
        }

        if (isset($data['resolution']) && is_string($data['resolution'])) {
            return Resolution::tryFromString($data['resolution']);
        }

        return null;
    }
}
