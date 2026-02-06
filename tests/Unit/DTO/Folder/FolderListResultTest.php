<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Folder;

use Kinescope\DTO\Folder\FolderDTO;
use Kinescope\DTO\Folder\FolderListResult;
use PHPUnit\Framework\TestCase;

class FolderListResultTest extends TestCase
{
    public function testFromArrayCreatesValidFolderListResult(): void
    {
        $data = [
            'data' => [
                [
                    'id' => '1',
                    'project_id' => 'project-uuid',
                    'name' => 'Folder 1',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-01T00:00:00Z',
                ],
                [
                    'id' => '2',
                    'project_id' => 'project-uuid',
                    'name' => 'Folder 2',
                    'parent_id' => '1',
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

        $result = FolderListResult::fromArray($data);

        $this->assertCount(2, $result->getData());
        $this->assertContainsOnlyInstancesOf(FolderDTO::class, $result->getData());
        $this->assertEquals(2, $result->getMeta()->total);
    }

    public function testGetRootsReturnsOnlyRootFolders(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Root 1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'Child', 'parent_id' => '1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'project_id' => 'project-uuid', 'name' => 'Root 2', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);
        $roots = $result->getRoots();

        $this->assertCount(2, $roots);

        foreach ($roots as $folder) {
            $this->assertTrue($folder->isRoot());
        }
    }

    public function testGetChildrenReturnsChildFolders(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Root', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'Child 1', 'parent_id' => '1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'project_id' => 'project-uuid', 'name' => 'Child 2', 'parent_id' => '1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '4', 'project_id' => 'project-uuid', 'name' => 'Other Child', 'parent_id' => '5', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 4, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);
        $children = $result->getChildren('1');

        $this->assertCount(2, $children);

        foreach ($children as $folder) {
            $this->assertEquals('1', $folder->parentId);
        }
    }

    public function testGetAtDepthReturnsFoldersAtSpecifiedDepth(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Root', 'depth' => 0, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'Level 1', 'depth' => 1, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'project_id' => 'project-uuid', 'name' => 'Level 1 again', 'depth' => 1, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '4', 'project_id' => 'project-uuid', 'name' => 'Level 2', 'depth' => 2, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 4, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);
        $atDepth1 = $result->getAtDepth(1);

        $this->assertCount(2, $atDepth1);

        foreach ($atDepth1 as $folder) {
            $this->assertTrue($folder->isAtDepth(1));
        }
    }

    public function testGetSortedByPositionReturnsSortedFolders(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Third', 'position' => 3, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'First', 'position' => 1, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'project_id' => 'project-uuid', 'name' => 'Second', 'position' => 2, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);
        $sorted = $result->getSortedByPosition();

        $this->assertEquals('First', $sorted[0]->name);
        $this->assertEquals('Second', $sorted[1]->name);
        $this->assertEquals('Third', $sorted[2]->name);
    }

    public function testGetSortedByNameReturnsSortedFolders(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Zulu', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'Alpha', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'project_id' => 'project-uuid', 'name' => 'Mike', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);
        $sorted = $result->getSortedByName();

        $this->assertEquals('Alpha', $sorted[0]->name);
        $this->assertEquals('Mike', $sorted[1]->name);
        $this->assertEquals('Zulu', $sorted[2]->name);
    }

    public function testGetWithVideosReturnsFoldersWithVideos(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'With Videos', 'videos_count' => 10, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'Empty', 'videos_count' => 0, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'project_id' => 'project-uuid', 'name' => 'With Videos 2', 'videos_count' => 5, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);
        $withVideos = $result->getWithVideos();

        $this->assertCount(2, $withVideos);

        foreach ($withVideos as $folder) {
            $this->assertTrue($folder->hasVideos());
        }
    }

    public function testGetEmptyReturnsEmptyFolders(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'With Videos', 'videos_count' => 10, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'Empty 1', 'videos_count' => 0, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'project_id' => 'project-uuid', 'name' => 'Empty 2', 'videos_count' => 0, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);
        $empty = $result->getEmpty();

        $this->assertCount(2, $empty);

        foreach ($empty as $folder) {
            $this->assertTrue($folder->isEmpty());
        }
    }

    public function testFindByIdFindsCorrectFolder(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Alpha', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'Beta', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);
        $folder = $result->findById('2');

        $this->assertNotNull($folder);
        $this->assertEquals('2', $folder->id);
        $this->assertEquals('Beta', $folder->name);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Alpha', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 1, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);

        $this->assertNull($result->findById('999'));
    }

    public function testFindByNameFindsCorrectFolder(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Alpha', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'Beta', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);
        $folder = $result->findByName('Beta');

        $this->assertNotNull($folder);
        $this->assertEquals('2', $folder->id);
    }

    public function testFindByNameReturnsNullWhenNotFound(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Alpha', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 1, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);

        $this->assertNull($result->findByName('NonExistent'));
    }

    public function testFindByPathFindsCorrectFolder(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Root', 'path' => 'root', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'Child', 'path' => 'root/child', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);
        $folder = $result->findByPath('root/child');

        $this->assertNotNull($folder);
        $this->assertEquals('2', $folder->id);
    }

    public function testFindByPathReturnsNullWhenNotFound(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Root', 'path' => 'root', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 1, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);

        $this->assertNull($result->findByPath('nonexistent/path'));
    }

    public function testGetTotalVideosCountReturnsSum(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Folder 1', 'videos_count' => 10, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'Folder 2', 'videos_count' => 20, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'project_id' => 'project-uuid', 'name' => 'Folder 3', 'videos_count' => 5, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);

        $this->assertEquals(35, $result->getTotalVideosCount());
    }

    public function testBuildTreeCreatesHierarchy(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Root', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'Child', 'parent_id' => '1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);
        $tree = $result->buildTree();

        $this->assertCount(1, $tree);
        $this->assertArrayHasKey('folder', $tree[0]);
        $this->assertArrayHasKey('children', $tree[0]);
        $this->assertEquals('Root', $tree[0]['folder']->name);
        $this->assertCount(1, $tree[0]['children']);
        $this->assertEquals('Child', $tree[0]['children'][0]['folder']->name);
    }

    public function testBuildTreeHandlesMultipleRoots(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Root 1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'Root 2', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '3', 'project_id' => 'project-uuid', 'name' => 'Child of Root 1', 'parent_id' => '1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 3, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);
        $tree = $result->buildTree();

        $this->assertCount(2, $tree);
    }

    public function testGetIdsReturnsAllIds(): void
    {
        $data = [
            'data' => [
                ['id' => '1', 'project_id' => 'project-uuid', 'name' => 'Folder 1', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                ['id' => '2', 'project_id' => 'project-uuid', 'name' => 'Folder 2', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
            ],
            'meta' => ['total' => 2, 'page' => 1, 'per_page' => 20],
        ];

        $result = FolderListResult::fromArray($data);

        $this->assertEquals(['1', '2'], $result->getIds());
    }
}
