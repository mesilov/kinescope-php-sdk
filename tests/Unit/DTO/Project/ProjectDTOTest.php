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
            'description' => 'Project description',
            'privacy_type' => 'anywhere',
            'videos_count' => 10,
            'folders_count' => 3,
            'storage_used' => 1073741824,
            'is_default' => true,
            'allowed_domains' => ['example.com', '*.test.com'],
            'settings' => ['key' => 'value'],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
        ];

        $project = ProjectDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $project->id);
        $this->assertEquals('My Project', $project->name);
        $this->assertEquals('Project description', $project->description);
        $this->assertEquals(PrivacyType::ANYWHERE, $project->privacyType);
        $this->assertEquals('anywhere', $project->privacyTypeRaw);
        $this->assertEquals(10, $project->videosCount);
        $this->assertEquals(3, $project->foldersCount);
        $this->assertEquals(1073741824, $project->storageUsed);
        $this->assertTrue($project->isDefault);
        $this->assertEquals(['example.com', '*.test.com'], $project->allowedDomains);
        $this->assertEquals(['key' => 'value'], $project->settings);
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
        $this->assertNull($project->description);
        $this->assertNull($project->privacyType);
        $this->assertNull($project->privacyTypeRaw);
        $this->assertEquals(0, $project->videosCount);
        $this->assertEquals(0, $project->foldersCount);
        $this->assertNull($project->storageUsed);
        $this->assertFalse($project->isDefault);
        $this->assertEmpty($project->allowedDomains);
        $this->assertEmpty($project->settings);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'My Project',
            'description' => 'Project description',
            'privacy_type' => 'anywhere',
            'videos_count' => 10,
            'folders_count' => 3,
            'storage_used' => 1073741824,
            'is_default' => true,
            'allowed_domains' => ['example.com'],
            'settings' => ['key' => 'value'],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
        ];

        $project = ProjectDTO::fromArray($data);
        $array = $project->toArray();

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $array['id']);
        $this->assertEquals('My Project', $array['name']);
        $this->assertEquals('Project description', $array['description']);
        $this->assertEquals('anywhere', $array['privacy_type']);
        $this->assertEquals(10, $array['videos_count']);
        $this->assertEquals(3, $array['folders_count']);
        $this->assertEquals(1073741824, $array['storage_used']);
        $this->assertTrue($array['is_default']);
        $this->assertEquals(['example.com'], $array['allowed_domains']);
        $this->assertEquals(['key' => 'value'], $array['settings']);
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
            'allowed_domains' => ['example.com', '*.test.com'],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($project->isDomainAllowed('example.com'));
        $this->assertTrue($project->isDomainAllowed('sub.test.com'));
        $this->assertFalse($project->isDomainAllowed('other.com'));
    }

    public function testHasVideosReturnsTrueWhenVideosCountGreaterThanZero(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'videos_count' => 5,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($project->hasVideos());
    }

    public function testHasVideosReturnsFalseWhenVideosCountIsZero(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'videos_count' => 0,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($project->hasVideos());
    }

    public function testHasFoldersReturnsTrueWhenFoldersCountGreaterThanZero(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'folders_count' => 3,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($project->hasFolders());
    }

    public function testHasFoldersReturnsFalseWhenFoldersCountIsZero(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'folders_count' => 0,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($project->hasFolders());
    }

    public function testGetHumanStorageUsedReturnsFormattedString(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'storage_used' => 1073741824,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals('1.00 GB', $project->getHumanStorageUsed());
    }

    public function testGetHumanStorageUsedReturnsNullWhenNotSet(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertNull($project->getHumanStorageUsed());
    }

    public function testGetSettingReturnsValue(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'settings' => ['key1' => 'value1', 'key2' => 42],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals('value1', $project->getSetting('key1'));
        $this->assertEquals(42, $project->getSetting('key2'));
    }

    public function testGetSettingReturnsDefaultWhenNotFound(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => '1',
            'name' => 'Project',
            'settings' => [],
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertNull($project->getSetting('missing'));
        $this->assertEquals('default', $project->getSetting('missing', 'default'));
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
