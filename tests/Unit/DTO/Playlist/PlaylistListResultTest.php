<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Playlist;

use Kinescope\DTO\Playlist\PlaylistDTO;
use Kinescope\DTO\Playlist\PlaylistListResult;
use Kinescope\Enum\PrivacyType;
use PHPUnit\Framework\TestCase;

final class PlaylistListResultTest extends TestCase
{
    public function testFromArrayCreatesPaginatedPlaylistList(): void
    {
        $result = PlaylistListResult::fromArray($this->response());

        self::assertCount(3, $result);
        self::assertContainsOnlyInstancesOf(PlaylistDTO::class, $result->getData());
        self::assertSame(3, $result->getTotal());
    }

    public function testCurrentHelpersUsePrivacyParentAndName(): void
    {
        $result = PlaylistListResult::fromArray($this->response());

        self::assertCount(1, $result->getByPrivacyType(PrivacyType::ANYWHERE));
        self::assertCount(1, $result->getPublic());
        self::assertCount(2, $result->getPrivate());
        self::assertCount(1, $result->getWithDomainRestrictions());
        self::assertCount(2, $result->getByParentId('parent-a'));
        self::assertSame('playlist-b', $result->findByName('Beta')?->id);
        self::assertSame(['playlist-a', 'playlist-b', 'playlist-c'], $result->getIds());
    }

    public function testSortingAndLookup(): void
    {
        $result = PlaylistListResult::fromArray($this->response());

        self::assertSame('Beta', $result->findById('playlist-b')?->name);
        self::assertNull($result->findById('missing'));
        self::assertSame(['Alpha', 'Beta', 'Gamma'], array_map(
            static fn (PlaylistDTO $playlist): string => $playlist->name,
            $result->getSortedByName(),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function response(): array
    {
        return [
            'data' => [
                $this->playlist('playlist-a', 'Alpha', 'parent-a', 'anywhere'),
                $this->playlist('playlist-b', 'Beta', 'parent-a', 'custom'),
                $this->playlist('playlist-c', 'Gamma', 'parent-b', 'nowhere'),
            ],
            'meta' => [
                'pagination' => ['page' => 1, 'per_page' => 20, 'total' => 3],
                'order' => ['name' => 'asc'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function playlist(string $id, string $name, string $parentId, string $privacyType): array
    {
        return [
            'id' => $id,
            'workspace_id' => 'workspace-id',
            'player_id' => 'player-id',
            'parent_id' => $parentId,
            'name' => $name,
            'description' => '',
            'privacy_type' => $privacyType,
            'privacy_domains' => $privacyType === 'custom' ? ['learn.rarus.ru'] : [],
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
