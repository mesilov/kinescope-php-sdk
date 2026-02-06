<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Project;

use Kinescope\DTO\Project\ProjectDTO;
use Kinescope\DTO\Project\ProjectListResult;
use Kinescope\Enum\PrivacyType;
use PHPUnit\Framework\TestCase;

class ProjectListResultTest extends TestCase
{
    public function testFromArrayCreatesValidProjectListResult(): void
    {
        $data = [
            'data' => [
                [
                    'id' => '1',
                    'name' => 'Project 1',
                    'is_default' => true,
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-01T00:00:00Z',
                ],
                [
                    'id' => '2',
                    'name' => 'Project 2',
                    'is_default' => false,
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-01T00:00:00Z',
                ],
            ],
            'meta' => [
                'total' => 2,
                'page' => 1,
                'per_page' => 20,
            ],
        ];

        $result = ProjectListResult::fromArray($data);

        $this->assertCount(2, $result->getData());
        $this->assertContainsOnlyInstancesOf(ProjectDTO::class, $result->getData());
        $this->assertEquals(2, $result->getMeta()->total);
    }

    public function testGetDefaultReturnsDefaultProject(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'Project 1', 'is_default' => false, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'name' => 'Default Project', 'is_default' => true, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'name' => 'Project 3', 'is_default' => false, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);
        $default = $result->getDefault();

        $this->assertNotNull($default);
        $this->assertEquals('2', $default->id);
        $this->assertTrue($default->isDefault);
    }

    public function testGetDefaultReturnsNullWhenNoDefault(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'Project 1', 'is_default' => false, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'name' => 'Project 2', 'is_default' => false, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);

        $this->assertNull($result->getDefault());
    }

    public function testGetByPrivacyTypeFiltersCorrectly(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'Public 1', 'privacy_type' => 'anywhere', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'name' => 'Custom', 'privacy_type' => 'custom', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'name' => 'Public 2', 'privacy_type' => 'anywhere', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);
        $anywhere = $result->getByPrivacyType(PrivacyType::ANYWHERE);

        $this->assertCount(2, $anywhere);

        foreach ($anywhere as $project) {
            $this->assertEquals(PrivacyType::ANYWHERE, $project->privacyType);
        }
    }

    public function testGetPublicReturnsOnlyPublicProjects(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'Public 1', 'privacy_type' => 'anywhere', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'name' => 'Custom', 'privacy_type' => 'custom', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'name' => 'Public 2', 'privacy_type' => 'anywhere', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);
        $public = $result->getPublic();

        $this->assertCount(2, $public);

        foreach ($public as $project) {
            $this->assertTrue($project->isPublic());
        }
    }

    public function testGetWithDomainRestrictionsReturnsCustomProjects(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'Public', 'privacy_type' => 'anywhere', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'name' => 'Custom 1', 'privacy_type' => 'custom', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'name' => 'Custom 2', 'privacy_type' => 'custom', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);
        $custom = $result->getWithDomainRestrictions();

        $this->assertCount(2, $custom);

        foreach ($custom as $project) {
            $this->assertTrue($project->hasDomainRestrictions());
        }
    }

    public function testGetDisabledReturnsNowhereProjects(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'Public', 'privacy_type' => 'anywhere', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'name' => 'Disabled', 'privacy_type' => 'nowhere', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);
        $disabled = $result->getDisabled();

        $this->assertCount(1, $disabled);
        $this->assertTrue($disabled[0]->isPlaybackDisabled());
    }

    public function testGetWithVideosReturnsProjectsWithVideos(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'With Videos', 'videos_count' => 10, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'name' => 'Empty', 'videos_count' => 0, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'name' => 'With Videos 2', 'videos_count' => 5, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);
        $withVideos = $result->getWithVideos();

        $this->assertCount(2, $withVideos);

        foreach ($withVideos as $project) {
            $this->assertTrue($project->hasVideos());
        }
    }

    public function testGetEmptyReturnsProjectsWithoutVideos(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'With Videos', 'videos_count' => 10, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'name' => 'Empty 1', 'videos_count' => 0, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'name' => 'Empty 2', 'videos_count' => 0, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);
        $empty = $result->getEmpty();

        $this->assertCount(2, $empty);

        foreach ($empty as $project) {
            $this->assertFalse($project->hasVideos());
        }
    }

    public function testFindByIdFindsCorrectProject(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'Alpha', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'name' => 'Beta', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'name' => 'Gamma', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);
        $project = $result->findById('2');

        $this->assertNotNull($project);
        $this->assertEquals('2', $project->id);
        $this->assertEquals('Beta', $project->name);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'Alpha', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 1, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);

        $this->assertNull($result->findById('999'));
    }

    public function testFindByNameFindsCorrectProject(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'Alpha', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'name' => 'Beta', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'name' => 'Gamma', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);
        $project = $result->findByName('Beta');

        $this->assertNotNull($project);
        $this->assertEquals('2', $project->id);
        $this->assertEquals('Beta', $project->name);
    }

    public function testFindByNameReturnsNullWhenNotFound(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'Alpha', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 1, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);

        $this->assertNull($result->findByName('NonExistent'));
    }

    public function testGetTotalVideosCountReturnsSum(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'Project 1', 'videos_count' => 10, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'name' => 'Project 2', 'videos_count' => 20, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'name' => 'Project 3', 'videos_count' => 5, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);

        $this->assertEquals(35, $result->getTotalVideosCount());
    }

    public function testGetTotalStorageUsedReturnsSum(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'Project 1', 'storage_used' => 1000, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'name' => 'Project 2', 'storage_used' => 2000, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'name' => 'Project 3', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);

        $this->assertEquals(3000, $result->getTotalStorageUsed());
    }

    public function testGetIdsReturnsAllIds(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'name' => 'Project 1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'name' => 'Project 2', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = ProjectListResult::fromArray($data);

        $this->assertEquals(['1', '2'], $result->getIds());
    }
}
