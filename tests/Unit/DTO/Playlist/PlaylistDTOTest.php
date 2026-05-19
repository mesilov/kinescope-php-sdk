<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Playlist;

use Kinescope\DTO\Playlist\PlaylistDTO;
use Kinescope\Enum\PrivacyType;
use PHPUnit\Framework\TestCase;

final class PlaylistDTOTest extends TestCase
{
    public function testFromArrayMapsCurrentApiPayload(): void
    {
        $playlist = PlaylistDTO::fromArray($this->payload());

        self::assertSame('playlist-id', $playlist->id);
        self::assertSame('workspace-id', $playlist->workspaceId);
        self::assertSame('player-id', $playlist->playerId);
        self::assertSame('parent-id', $playlist->parentId);
        self::assertSame('Playlist', $playlist->name);
        self::assertSame(PrivacyType::ANYWHERE, $playlist->privacyType);
        self::assertFalse($playlist->uniqueCodesEnabled);
        self::assertSame('custom', $playlist->getSetting('sort_field'));
    }

    public function testToArrayUsesRawApiFieldNames(): void
    {
        $array = PlaylistDTO::fromArray($this->payload())->toArray();

        self::assertSame('Playlist', $array['name']);
        self::assertSame('workspace-id', $array['workspace_id']);
        self::assertSame('parent-id', $array['parent_id']);
        self::assertSame('https://kinescope.io/pl/slug', $array['play_link']);
        self::assertSame('https://kinescope.io/embed/pl/slug', $array['embed_link']);
        self::assertArrayNotHasKey('title', $array);
        self::assertArrayNotHasKey('project_id', $array);
        self::assertArrayNotHasKey('items_count', $array);
        self::assertArrayNotHasKey('is_public', $array);
    }

    public function testPrivacyAndLinkHelpers(): void
    {
        $public = PlaylistDTO::fromArray($this->payload(privacyType: 'anywhere'));
        $custom = PlaylistDTO::fromArray($this->payload(privacyType: 'custom'));

        self::assertTrue($public->isPublic());
        self::assertFalse($custom->isPublic());
        self::assertTrue($custom->hasDomainRestrictions());
        self::assertTrue($custom->hasPlayLink());
        self::assertTrue($custom->hasEmbedLink());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $privacyType = 'anywhere'): array
    {
        return [
            'id' => 'playlist-id',
            'workspace_id' => 'workspace-id',
            'player_id' => 'player-id',
            'parent_id' => 'parent-id',
            'name' => 'Playlist',
            'description' => '',
            'privacy_type' => $privacyType,
            'privacy_domains' => ['learn.rarus.ru'],
            'privacy_email_domains' => [],
            'privacy_share' => [],
            'unique_codes_enabled' => false,
            'tags' => [],
            'settings' => ['sort_field' => 'custom'],
            'play_link' => 'https://kinescope.io/pl/slug',
            'embed_link' => 'https://kinescope.io/embed/pl/slug',
        ];
    }
}
