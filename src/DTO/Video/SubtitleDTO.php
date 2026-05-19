<?php

declare(strict_types=1);

namespace Kinescope\DTO\Video;

use Carbon\CarbonImmutable;
use Kinescope\DTO\Common\ApiDate;
use Kinescope\Enum\SubtitleLanguage;

/**
 * Video subtitle data transfer object.
 *
 * Mirrors current subtitle payloads returned both inline in video responses and
 * from `/v1/videos/{video_id}/subtitles`.
 */
final readonly class SubtitleDTO
{
    public function __construct(
        public string $id,
        public ?string $videoId,
        public ?string $description,
        public ?SubtitleLanguage $language,
        public ?string $languageRaw,
        public ?string $status,
        public ?int $position,
        /**
         * @var array<string, mixed>
         */
        public array $data,
        public bool $active,
        public ?string $url,
        public ?CarbonImmutable $updatedAt,
        public ?string $file,
        public ?string $fileName,
        public ?string $hlsFile,
        public ?string $transcribeUrl,
        public ?string $downloadFilename,
        public ?string $transcribeDownloadFilename,
    ) {
    }

    /**
     * @param array<string, mixed> $data Raw API response data
     */
    public static function fromArray(array $data): self
    {
        $languageRaw = isset($data['language']) ? (string) $data['language'] : null;

        return new self(
            id: (string) $data['id'],
            videoId: isset($data['video_id']) ? (string) $data['video_id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            language: $languageRaw !== null ? SubtitleLanguage::tryFrom($languageRaw) : null,
            languageRaw: $languageRaw,
            status: isset($data['status']) ? (string) $data['status'] : null,
            position: isset($data['position']) ? (int) $data['position'] : null,
            data: isset($data['data']) && is_array($data['data']) ? $data['data'] : [],
            active: (bool) ($data['active'] ?? false),
            url: isset($data['url']) ? (string) $data['url'] : null,
            updatedAt: ApiDate::from($data['updated_at'] ?? null),
            file: isset($data['file']) ? (string) $data['file'] : null,
            fileName: isset($data['file_name']) ? (string) $data['file_name'] : null,
            hlsFile: isset($data['hls_file']) ? (string) $data['hls_file'] : null,
            transcribeUrl: isset($data['transcribe_url']) ? (string) $data['transcribe_url'] : null,
            downloadFilename: isset($data['download_filename']) ? (string) $data['download_filename'] : null,
            transcribeDownloadFilename: isset($data['transcribe_download_filename'])
                ? (string) $data['transcribe_download_filename']
                : null,
        );
    }

    public function getLanguageName(): ?string
    {
        return $this->language?->getEnglishName();
    }

    public function getNativeLanguageName(): ?string
    {
        return $this->language?->getNativeName();
    }

    public function isRtl(): bool
    {
        return $this->language?->isRtl() ?? false;
    }

    public function hasKnownLanguage(): bool
    {
        return $this->language !== null;
    }

    public function getLanguageCode(): ?string
    {
        return $this->language !== null ? $this->language->value : $this->languageRaw;
    }

    public function hasUrl(): bool
    {
        return $this->url !== null && $this->url !== '';
    }

    public function hasTranscript(): bool
    {
        return $this->transcribeUrl !== null && $this->transcribeUrl !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'video_id' => $this->videoId,
            'description' => $this->description,
            'language' => $this->getLanguageCode(),
            'status' => $this->status,
            'position' => $this->position,
            'data' => $this->data === [] ? (object) [] : $this->data,
            'active' => $this->active,
            'url' => $this->url,
            'updated_at' => ApiDate::toString($this->updatedAt),
            'file' => $this->file,
            'file_name' => $this->fileName,
            'hls_file' => $this->hlsFile,
            'transcribe_url' => $this->transcribeUrl,
            'download_filename' => $this->downloadFilename,
            'transcribe_download_filename' => $this->transcribeDownloadFilename,
        ];
    }
}
