<?php

declare(strict_types=1);

namespace Kinescope\DTO\Video;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Kinescope\DTO\Common\ApiDate;

/**
 * Video asset data transfer object.
 *
 * Mirrors the current Kinescope asset payload embedded in video responses.
 */
final readonly class AssetDTO
{
    public function __construct(
        public string $id,
        public string $videoId,
        public ?string $originalName,
        /**
         * Size of the video stream reported by Kinescope metadata.
         *
         * This is not guaranteed to be the size of the downloadable file on disk.
         * Downloaded MP4 files may also include audio streams and container overhead.
         * For download validation, disk checks, and storage accounting, use the real
         * transfer size / HTTP Content-Length / bytes written / final filesize().
         */
        public int $videoStreamSize,
        public ?string $md5,
        public ?string $filetype,
        public ?string $quality,
        public ?Resolution $resolution,
        public ?CarbonImmutable $createdAt,
        public ?string $url,
        public ?string $downloadLink,
    ) {
        if ($this->videoStreamSize <= 0) {
            throw new InvalidArgumentException('Asset "file_size" video stream size must be greater than 0.');
        }
    }

    /**
     * @param array<string, mixed> $data Raw API response data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['file_size'])) {
            throw new InvalidArgumentException('Asset "file_size" is required.');
        }

        $videoStreamSize = (int) $data['file_size'];

        if ($videoStreamSize <= 0) {
            throw new InvalidArgumentException('Asset "file_size" video stream size must be greater than 0.');
        }

        return new self(
            id: (string) $data['id'],
            videoId: (string) ($data['video_id'] ?? ''),
            originalName: isset($data['original_name']) ? (string) $data['original_name'] : null,
            videoStreamSize: $videoStreamSize,
            md5: isset($data['md5']) ? (string) $data['md5'] : null,
            filetype: isset($data['filetype']) ? (string) $data['filetype'] : null,
            quality: isset($data['quality']) ? (string) $data['quality'] : null,
            resolution: self::resolutionFromPayload($data),
            createdAt: ApiDate::from($data['created_at'] ?? null),
            url: isset($data['url']) ? (string) $data['url'] : null,
            downloadLink: isset($data['download_link']) ? (string) $data['download_link'] : null,
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

    public function getHumanVideoStreamSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->videoStreamSize;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            ++$unitIndex;
        }

        return sprintf('%.2f %s', $size, $units[$unitIndex]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'video_id' => $this->videoId,
            'original_name' => $this->originalName,
            'video_stream_size' => $this->videoStreamSize,
            'md5' => $this->md5,
            'filetype' => $this->filetype,
            'quality' => $this->quality,
            'resolution' => $this->resolution === null ? null : (string) $this->resolution,
            'created_at' => ApiDate::toString($this->createdAt),
            'url' => $this->url,
            'download_link' => $this->downloadLink,
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
