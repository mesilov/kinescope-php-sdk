<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Project;

use Kinescope\DTO\Project\ProjectDTO;
use Kinescope\Enum\PrivacyType;
use PHPUnit\Framework\TestCase;

class ProjectDTOTest extends TestCase
{
    public function testFromArrayCreatesValidProjectDTO(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'My Project',
            'privacy_type' => 'anywhere',
            'items_count' => 10,
            'folders' => [
                ['id' => 'folder-1'],
                ['id' => 'folder-2'],
                ['id' => 'folder-3'],
            ],
            'size' => 1073741824,
            'privacy_domains' => ['example.com', '*.test.com'],
            'privacy_email_domains' => ['example.org'],
            'privacy_share' => ['enabled' => true],
            'player_id' => 'player-uuid',
            'favorite' => true,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
            'encrypted' => true,
        ];

        $project = ProjectDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $project->id);
        $this->assertEquals('My Project', $project->name);
        $this->assertEquals(PrivacyType::ANYWHERE, $project->privacyType);
        $this->assertEquals('anywhere', $project->privacyTypeRaw);
        $this->assertEquals(10, $project->itemsCount);
        $this->assertCount(3, $project->folders);
        $this->assertEquals(1073741824, $project->size);
        $this->assertEquals(['example.com', '*.test.com'], $project->privacyDomains);
        $this->assertEquals(['example.org'], $project->privacyEmailDomains);
        $this->assertEquals(['enabled' => true], $project->privacyShare);
        $this->assertEquals('player-uuid', $project->playerId);
        $this->assertTrue($project->favorite);
        $this->assertTrue($project->encrypted);
    }

    public function testFromArrayMapsCurrentApiProjectPayload(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'Current API Project',
            'privacy_type' => 'custom',
            'privacy_domains' => ['learn.rarus.ru', 'demo2-learn.rarus.ru'],
            'privacy_email_domains' => [],
            'privacy_share' => [],
            'player_id' => '83073711-4967-48ab-b2a7-0c628f795e0e',
            'favorite' => false,
            'size' => 272661569450,
            'items_count' => 242,
            'folders' => [
                ['id' => 'folder-1'],
                ['id' => 'folder-2'],
                ['id' => 'folder-3'],
                ['id' => 'folder-4'],
            ],
            'created_at' => '2025-09-28T10:35:39.170997Z',
            'updated_at' => '2026-03-26T13:23:29.567978Z',
            'encrypted' => true,
        ];

        $project = ProjectDTO::fromArray($data);

        $this->assertEquals(242, $project->itemsCount);
        $this->assertCount(4, $project->folders);
        $this->assertEquals(272661569450, $project->size);
        $this->assertEquals(['learn.rarus.ru', 'demo2-learn.rarus.ru'], $project->privacyDomains);
        $this->assertEquals([], $project->privacyEmailDomains);
        $this->assertEquals([], $project->privacyShare);
        $this->assertEquals('83073711-4967-48ab-b2a7-0c628f795e0e', $project->playerId);
        $this->assertFalse($project->favorite);
        $this->assertTrue($project->encrypted);
    }

    public function testFromArrayDoesNotUseLegacyCounterAliases(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'Legacy-shaped Project',
            'videos_count' => 242,
            'folders_count' => 4,
            'storage_used' => 272661569450,
            'allowed_domains' => ['learn.rarus.ru'],
            'created_at' => '2025-09-28T10:35:39.170997Z',
            'updated_at' => '2026-03-26T13:23:29.567978Z',
        ];

        $project = ProjectDTO::fromArray($data);

        $this->assertSame(0, $project->itemsCount);
        $this->assertSame([], $project->folders);
        $this->assertSame(0, $project->size);
        $this->assertSame([], $project->privacyDomains);
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'My Project',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ];

        $project = ProjectDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $project->id);
        $this->assertEquals('My Project', $project->name);
        $this->assertNull($project->privacyType);
        $this->assertNull($project->privacyTypeRaw);
        $this->assertEquals(0, $project->itemsCount);
        $this->assertEmpty($project->folders);
        $this->assertEquals(0, $project->size);
        $this->assertEmpty($project->privacyDomains);
        $this->assertEmpty($project->privacyEmailDomains);
        $this->assertEmpty($project->privacyShare);
        $this->assertNull($project->playerId);
        $this->assertFalse($project->favorite);
        $this->assertFalse($project->encrypted);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'My Project',
            'privacy_type' => 'anywhere',
            'items_count' => 10,
            'folders' => [
                ['id' => 'folder-1'],
                ['id' => 'folder-2'],
                ['id' => 'folder-3'],
            ],
            'size' => 1073741824,
            'privacy_domains' => ['example.com'],
            'privacy_email_domains' => ['example.org'],
            'privacy_share' => ['enabled' => true],
            'player_id' => 'player-uuid',
            'favorite' => true,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
            'encrypted' => true,
        ];

        $project = ProjectDTO::fromArray($data);
        $array = $project->toArray();

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $array['id']);
        $this->assertEquals('My Project', $array['name']);
        $this->assertEquals('anywhere', $array['privacy_type']);
        $this->assertEquals(10, $array['items_count']);
        $this->assertCount(3, $array['folders']);
        $this->assertEquals(1073741824, $array['size']);
        $this->assertEquals(['example.com'], $array['privacy_domains']);
        $this->assertEquals(['example.org'], $array['privacy_email_domains']);
        $this->assertEquals(['enabled' => true], $array['privacy_share']);
        $this->assertEquals('player-uuid', $array['player_id']);
        $this->assertTrue($array['favorite']);
        $this->assertTrue($array['encrypted']);
        $this->assertArrayNotHasKey('videos_count', $array);
        $this->assertArrayNotHasKey('folders_count', $array);
        $this->assertArrayNotHasKey('storage_used', $array);
        $this->assertArrayNotHasKey('allowed_domains', $array);
    }

    public function testIsPublicReturnsTrueWhenPrivacyTypeIsAnywhere(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Public Project',
            'privacy_type' => 'anywhere',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($project->isPublic());
    }

    public function testIsPublicReturnsFalseWhenPrivacyTypeIsCustom(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Custom Project',
            'privacy_type' => 'custom',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($project->isPublic());
    }

    public function testHasDomainRestrictionsReturnsTrueWhenPrivacyTypeIsCustom(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Custom Project',
            'privacy_type' => 'custom',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($project->hasDomainRestrictions());
    }

    public function testIsPlaybackDisabledReturnsTrueWhenPrivacyTypeIsNowhere(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Disabled Project',
            'privacy_type' => 'nowhere',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($project->isPlaybackDisabled());
    }

    public function testAllPrivacyTypes(): void
    {
        $anywhereProject = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Anywhere',
            'privacy_type' => 'anywhere',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $customProject = ProjectDTO::fromArray([
            'id' => '2',
            'name' => 'Custom',
            'privacy_type' => 'custom',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $nowhereProject = ProjectDTO::fromArray([
            'id' => '3',
            'name' => 'Nowhere',
            'privacy_type' => 'nowhere',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals(PrivacyType::ANYWHERE, $anywhereProject->privacyType);
        $this->assertEquals(PrivacyType::CUSTOM, $customProject->privacyType);
        $this->assertEquals(PrivacyType::NOWHERE, $nowhereProject->privacyType);
    }

    public function testIsDomainAllowedReturnsTrueForPublicProject(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Public Project',
            'privacy_type' => 'anywhere',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($project->isDomainAllowed('example.com'));
        $this->assertTrue($project->isDomainAllowed('any-domain.com'));
    }

    public function testIsDomainAllowedReturnsFalseForDisabledPlayback(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Disabled Project',
            'privacy_type' => 'nowhere',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($project->isDomainAllowed('example.com'));
    }

    public function testIsDomainAllowedChecksAllowedDomainsForCustomPrivacy(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Custom Project',
            'privacy_type' => 'custom',
            'privacy_domains' => ['example.com', '*.test.com'],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($project->isDomainAllowed('example.com'));
        $this->assertTrue($project->isDomainAllowed('sub.test.com'));
        $this->assertFalse($project->isDomainAllowed('other.com'));
    }

    public function testHasVideosReturnsTrueWhenItemsCountGreaterThanZero(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'items_count' => 5,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($project->hasVideos());
    }

    public function testHasVideosReturnsFalseWhenItemsCountIsZero(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'items_count' => 0,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($project->hasVideos());
    }

    public function testHasFoldersReturnsTrueWhenFoldersArePresent(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'folders' => [
                ['id' => 'folder-1'],
                ['id' => 'folder-2'],
                ['id' => 'folder-3'],
            ],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($project->hasFolders());
    }

    public function testHasFoldersReturnsFalseWhenFoldersAreEmpty(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'folders' => [],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($project->hasFolders());
    }

    public function testGetHumanSizeReturnsFormattedString(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'size' => 1073741824,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals('1.00 GB', $project->getHumanSize());
    }

    public function testGetHumanSizeReturnsNullWhenNotSet(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertNull($project->getHumanSize());
    }

    public function testUnknownPrivacyTypeIsPreservedInRaw(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'privacy_type' => 'unknown_type',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertNull($project->privacyType);
        $this->assertEquals('unknown_type', $project->privacyTypeRaw);
    }
}
