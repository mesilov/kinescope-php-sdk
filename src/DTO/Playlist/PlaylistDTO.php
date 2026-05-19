<?php

declare(strict_types=1);

namespace Kinescope\DTO\Playlist;

use Kinescope\Enum\PrivacyType;

/**
 * Playlist data transfer object.
 *
 * Mirrors the current Kinescope playlist payload.
 */
final readonly class PlaylistDTO
{
    /**
     * @param list<string> $privacyDomains
     * @param list<string> $privacyEmailDomains
     * @param array<string, mixed> $privacyShare
     * @param list<array<string, mixed>> $tags
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $additionalData
     */
    public function __construct(
        public string $id,
        public ?string $workspaceId,
        public ?string $playerId,
        public ?string $parentId,
        public string $name,
        public ?string $description,
        public ?PrivacyType $privacyType,
        public ?string $privacyTypeRaw,
        public array $privacyDomains,
        public array $privacyEmailDomains,
        public array $privacyShare,
        public bool $uniqueCodesEnabled,
        public array $tags,
        public array $settings,
        public ?string $playLink,
        public ?string $embedLink,
        public array $additionalData = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data Raw API response data
     */
    public static function fromArray(array $data): self
    {
        $privacyTypeRaw = isset($data['privacy_type']) ? (string) $data['privacy_type'] : null;
        $knownFields = [
            'id', 'workspace_id', 'player_id', 'parent_id', 'name', 'description',
            'privacy_type', 'privacy_domains', 'privacy_email_domains',
            'privacy_share', 'unique_codes_enabled', 'tags', 'settings',
            'play_link', 'embed_link',
        ];

        return new self(
            id: (string) $data['id'],
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : null,
            playerId: isset($data['player_id']) ? (string) $data['player_id'] : null,
            parentId: isset($data['parent_id']) ? (string) $data['parent_id'] : null,
            name: (string) ($data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            privacyType: $privacyTypeRaw !== null ? PrivacyType::tryFrom($privacyTypeRaw) : null,
            privacyTypeRaw: $privacyTypeRaw,
            privacyDomains: isset($data['privacy_domains']) && is_array($data['privacy_domains'])
                ? array_values(array_map(strval(...), $data['privacy_domains']))
                : [],
            privacyEmailDomains: isset($data['privacy_email_domains']) && is_array($data['privacy_email_domains'])
                ? array_values(array_map(strval(...), $data['privacy_email_domains']))
                : [],
            privacyShare: isset($data['privacy_share']) && is_array($data['privacy_share']) ? $data['privacy_share'] : [],
            uniqueCodesEnabled: (bool) ($data['unique_codes_enabled'] ?? false),
            tags: isset($data['tags']) && is_array($data['tags'])
                ? array_values(array_filter($data['tags'], is_array(...)))
                : [],
            settings: isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : [],
            playLink: isset($data['play_link']) ? (string) $data['play_link'] : null,
            embedLink: isset($data['embed_link']) ? (string) $data['embed_link'] : null,
            additionalData: array_diff_key($data, array_flip($knownFields)),
        );
    }

    public function isPublic(): bool
    {
        return $this->privacyType?->isPublic() ?? false;
    }

    public function hasDomainRestrictions(): bool
    {
        return $this->privacyType?->hasDomainRestrictions() ?? false;
    }

    public function hasEmbedLink(): bool
    {
        return $this->embedLink !== null && $this->embedLink !== '';
    }

    public function hasPlayLink(): bool
    {
        return $this->playLink !== null && $this->playLink !== '';
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'workspace_id' => $this->workspaceId,
            'player_id' => $this->playerId,
            'parent_id' => $this->parentId,
            'name' => $this->name,
            'description' => $this->description,
            'privacy_type' => $this->privacyType !== null ? $this->privacyType->value : $this->privacyTypeRaw,
            'privacy_domains' => $this->privacyDomains,
            'privacy_email_domains' => $this->privacyEmailDomains,
            'privacy_share' => $this->privacyShare === [] ? (object) [] : $this->privacyShare,
            'unique_codes_enabled' => $this->uniqueCodesEnabled,
            'tags' => $this->tags,
            'settings' => $this->settings,
            'play_link' => $this->playLink,
            'embed_link' => $this->embedLink,
        ], $this->additionalData);
    }
}
