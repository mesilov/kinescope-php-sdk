<?php

declare(strict_types=1);

namespace Kinescope\DTO\Video;

use Carbon\CarbonImmutable;
use Kinescope\DTO\Common\ApiDate;
use Kinescope\Enum\PrivacyType;
use Kinescope\Enum\VideoStatus;

/**
 * Video data transfer object.
 *
 * Mirrors the current Kinescope video payload.
 */
final readonly class VideoDTO
{
    /**
     * @param list<AssetDTO> $assets
     * @param list<SubtitleDTO> $subtitles
     * @param list<array<string, mixed>> $audioTracks
     * @param list<array<string, mixed>> $tags
     * @param list<array<string, mixed>> $additionalMaterials
     * @param array<string, mixed> $chapters
     * @param list<string> $privacyDomains
     * @param list<string> $privacyEmailDomains
     * @param array<string, mixed> $privacyShare
     * @param array<string, mixed> $poster
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $additionalData
     */
    public function __construct(
        public string $id,
        public ?string $projectId,
        public ?string $folderId,
        public ?string $playerId,
        public ?int $version,
        public string $title,
        public ?string $subtitle,
        public ?string $description,
        public VideoStatus $status,
        public int $progress,
        public float $duration,
        public array $assets,
        public bool $hasAudio,
        public array $audioTracks,
        public array $chapters,
        public ?PrivacyType $privacyType,
        public ?string $privacyTypeRaw,
        public array $privacyDomains,
        public array $privacyEmailDomains,
        public array $privacyShare,
        public array $tags,
        public array $poster,
        public array $additionalMaterials,
        public bool $additionalMaterialsEnabled,
        public bool $annotationTextEnabled,
        public bool $annotationVideoEnabled,
        public ?string $playLink,
        public ?string $embedLink,
        public ?CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt,
        public array $subtitles,
        public bool $subtitlesEnabled,
        public ?string $hlsLink,
        public array $meta,
        public array $additionalData,
    ) {
    }

    /**
     * @param array<string, mixed> $data Raw API response data
     */
    public static function fromArray(array $data): self
    {
        $privacyTypeRaw = isset($data['privacy_type']) ? (string) $data['privacy_type'] : null;

        $knownFields = [
            'id', 'project_id', 'folder_id', 'player_id', 'version', 'title',
            'subtitle', 'description', 'status', 'progress', 'duration', 'assets',
            'has_audio', 'audio_tracks', 'chapters', 'privacy_type',
            'privacy_domains', 'privacy_email_domains', 'privacy_share', 'tags',
            'poster', 'additional_materials', 'additional_materials_enabled',
            'annotation_text_enabled', 'annotation_video_enabled', 'play_link',
            'embed_link', 'created_at', 'updated_at', 'subtitles',
            'subtitles_enabled', 'hls_link', 'meta',
        ];

        return new self(
            id: (string) $data['id'],
            projectId: isset($data['project_id']) ? (string) $data['project_id'] : null,
            folderId: isset($data['folder_id']) ? (string) $data['folder_id'] : null,
            playerId: isset($data['player_id']) ? (string) $data['player_id'] : null,
            version: isset($data['version']) ? (int) $data['version'] : null,
            title: (string) ($data['title'] ?? ''),
            subtitle: isset($data['subtitle']) ? (string) $data['subtitle'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            status: VideoStatus::from((string) ($data['status'] ?? 'pending')),
            progress: (int) ($data['progress'] ?? 0),
            duration: (float) ($data['duration'] ?? 0),
            assets: self::mapList($data['assets'] ?? [], AssetDTO::fromArray(...)),
            hasAudio: (bool) ($data['has_audio'] ?? false),
            audioTracks: self::arrayList($data['audio_tracks'] ?? []),
            chapters: isset($data['chapters']) && is_array($data['chapters']) ? $data['chapters'] : [],
            privacyType: $privacyTypeRaw !== null ? PrivacyType::tryFrom($privacyTypeRaw) : null,
            privacyTypeRaw: $privacyTypeRaw,
            privacyDomains: isset($data['privacy_domains']) && is_array($data['privacy_domains'])
                ? array_values(array_map(strval(...), $data['privacy_domains']))
                : [],
            privacyEmailDomains: isset($data['privacy_email_domains']) && is_array($data['privacy_email_domains'])
                ? array_values(array_map(strval(...), $data['privacy_email_domains']))
                : [],
            privacyShare: isset($data['privacy_share']) && is_array($data['privacy_share']) ? $data['privacy_share'] : [],
            tags: self::arrayList($data['tags'] ?? []),
            poster: isset($data['poster']) && is_array($data['poster']) ? $data['poster'] : [],
            additionalMaterials: self::arrayList($data['additional_materials'] ?? []),
            additionalMaterialsEnabled: (bool) ($data['additional_materials_enabled'] ?? false),
            annotationTextEnabled: (bool) ($data['annotation_text_enabled'] ?? false),
            annotationVideoEnabled: (bool) ($data['annotation_video_enabled'] ?? false),
            playLink: isset($data['play_link']) ? (string) $data['play_link'] : null,
            embedLink: isset($data['embed_link']) ? (string) $data['embed_link'] : null,
            createdAt: ApiDate::from($data['created_at'] ?? null),
            updatedAt: ApiDate::from($data['updated_at'] ?? null),
            subtitles: self::mapList($data['subtitles'] ?? [], SubtitleDTO::fromArray(...)),
            subtitlesEnabled: (bool) ($data['subtitles_enabled'] ?? false),
            hlsLink: isset($data['hls_link']) ? (string) $data['hls_link'] : null,
            meta: isset($data['meta']) && is_array($data['meta']) ? $data['meta'] : [],
            additionalData: array_diff_key($data, array_flip($knownFields)),
        );
    }

    public function isReady(): bool
    {
        return $this->status->isReady();
    }

    public function isProcessing(): bool
    {
        return $this->status->isProcessing();
    }

    public function hasError(): bool
    {
        return $this->status->hasError();
    }

    public function getFormattedDuration(): string
    {
        $secondsTotal = (int) round($this->duration);
        $hours = (int) floor($secondsTotal / 3600);
        $minutes = (int) floor(($secondsTotal % 3600) / 60);
        $seconds = $secondsTotal % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getHighestQualityAsset(): ?AssetDTO
    {
        if ($this->assets === []) {
            return null;
        }

        $sorted = $this->assets;
        usort(
            $sorted,
            static fn (AssetDTO $a, AssetDTO $b): int => ($b->resolution === null ? 0 : $b->resolution->height)
                <=> ($a->resolution === null ? 0 : $a->resolution->height),
        );

        return $sorted[0];
    }

    public function getLowestQualityAsset(): ?AssetDTO
    {
        if ($this->assets === []) {
            return null;
        }

        $sorted = $this->assets;
        usort(
            $sorted,
            static fn (AssetDTO $a, AssetDTO $b): int => ($a->resolution === null ? 0 : $a->resolution->height)
                <=> ($b->resolution === null ? 0 : $b->resolution->height),
        );

        return $sorted[0];
    }

    public function hasHlsLink(): bool
    {
        return $this->hlsLink !== null && $this->hlsLink !== '';
    }

    public function hasEmbedLink(): bool
    {
        return $this->embedLink !== null && $this->embedLink !== '';
    }

    public function hasPlayLink(): bool
    {
        return $this->playLink !== null && $this->playLink !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'project_id' => $this->projectId,
            'folder_id' => $this->folderId,
            'player_id' => $this->playerId,
            'version' => $this->version,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'status' => $this->status->value,
            'progress' => $this->progress,
            'duration' => $this->duration,
            'assets' => array_map(
                static fn (AssetDTO $asset): array => $asset->toArray(),
                $this->assets,
            ),
            'has_audio' => $this->hasAudio,
            'audio_tracks' => $this->audioTracks,
            'chapters' => $this->chapters === [] ? (object) [] : $this->chapters,
            'privacy_type' => $this->privacyType !== null ? $this->privacyType->value : $this->privacyTypeRaw,
            'privacy_domains' => $this->privacyDomains,
            'privacy_email_domains' => $this->privacyEmailDomains,
            'privacy_share' => $this->privacyShare === [] ? (object) [] : $this->privacyShare,
            'tags' => $this->tags,
            'poster' => $this->poster === [] ? null : $this->poster,
            'additional_materials' => $this->additionalMaterials,
            'additional_materials_enabled' => $this->additionalMaterialsEnabled,
            'annotation_text_enabled' => $this->annotationTextEnabled,
            'annotation_video_enabled' => $this->annotationVideoEnabled,
            'play_link' => $this->playLink,
            'embed_link' => $this->embedLink,
            'created_at' => ApiDate::toString($this->createdAt),
            'updated_at' => ApiDate::toString($this->updatedAt),
            'subtitles' => array_map(
                static fn (SubtitleDTO $subtitle): array => $subtitle->toArray(),
                $this->subtitles,
            ),
            'subtitles_enabled' => $this->subtitlesEnabled,
            'hls_link' => $this->hlsLink,
            'meta' => $this->meta === [] ? (object) [] : $this->meta,
        ], $this->additionalData);
    }

    /**
     * @template T
     *
     * @param mixed $items
     * @param callable(array<string, mixed>): T $mapper
     *
     * @return list<T>
     */
    private static function mapList(mixed $items, callable $mapper): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_map($mapper, array_values(array_filter($items, is_array(...))));
    }

    /**
     * @param mixed $items
     *
     * @return list<array<string, mixed>>
     */
    private static function arrayList(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, is_array(...)));
    }
}
