<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Folder;

use Kinescope\DTO\Folder\FolderDTO;
use PHPUnit\Framework\TestCase;

class FolderDTOTest extends TestCase
{
    public function testFromArrayCreatesValidFolderDTO(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'project_id' => 'project-uuid',
            'name' => 'My Folder',
            'description' => 'Folder description',
            'parent_id' => 'parent-uuid',
            'videos_count' => 10,
            'depth' => 1,
            'path' => 'parent/My Folder',
            'position' => 5,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
        ];

        $folder = FolderDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $folder->id);
        $this->assertEquals('project-uuid', $folder->projectId);
        $this->assertEquals('My Folder', $folder->name);
        $this->assertEquals('Folder description', $folder->description);
        $this->assertEquals('parent-uuid', $folder->parentId);
        $this->assertEquals(10, $folder->videosCount);
        $this->assertEquals(1, $folder->depth);
        $this->assertEquals('parent/My Folder', $folder->path);
        $this->assertEquals(5, $folder->position);
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'project_id' => 'project-uuid',
            'name' => 'My Folder',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ];

        $folder = FolderDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $folder->id);
        $this->assertEquals('project-uuid', $folder->projectId);
        $this->assertEquals('My Folder', $folder->name);
        $this->assertNull($folder->description);
        $this->assertNull($folder->parentId);
        $this->assertEquals(0, $folder->videosCount);
        $this->assertEquals(0, $folder->depth);
        $this->assertNull($folder->path);
        $this->assertEquals(0, $folder->position);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'project_id' => 'project-uuid',
            'name' => 'My Folder',
            'description' => 'Folder description',
            'parent_id' => 'parent-uuid',
            'videos_count' => 10,
            'depth' => 1,
            'path' => 'parent/My Folder',
            'position' => 5,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
        ];

        $folder = FolderDTO::fromArray($data);
        $array = $folder->toArray();

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $array['id']);
        $this->assertEquals('project-uuid', $array['project_id']);
        $this->assertEquals('My Folder', $array['name']);
        $this->assertEquals('Folder description', $array['description']);
        $this->assertEquals('parent-uuid', $array['parent_id']);
        $this->assertEquals(10, $array['videos_count']);
        $this->assertEquals(1, $array['depth']);
        $this->assertEquals('parent/My Folder', $array['path']);
        $this->assertEquals(5, $array['position']);
    }

    public function testIsRootReturnsTrueWhenNoParent(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Root Folder',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($folder->isRoot());
    }

    public function testIsRootReturnsFalseWhenHasParent(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Child Folder',
            'parent_id' => 'parent-uuid',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($folder->isRoot());
    }

    public function testHasParentReturnsTrueWhenParentExists(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Child Folder',
            'parent_id' => 'parent-uuid',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($folder->hasParent());
    }

    public function testHasParentReturnsFalseWhenNoParent(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Root Folder',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($folder->hasParent());
    }

    public function testHasVideosReturnsTrueWhenVideosCountGreaterThanZero(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Folder',
            'videos_count' => 5,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($folder->hasVideos());
    }

    public function testHasVideosReturnsFalseWhenVideosCountIsZero(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Folder',
            'videos_count' => 0,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($folder->hasVideos());
    }

    public function testIsEmptyReturnsTrueWhenVideosCountIsZero(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Folder',
            'videos_count' => 0,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($folder->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenHasVideos(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Folder',
            'videos_count' => 5,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($folder->isEmpty());
    }

    public function testGetFullPathReturnsPathWhenSet(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Child',
            'path' => 'parent/Child',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals('parent/Child', $folder->getFullPath());
    }

    public function testGetFullPathReturnsNameWhenPathNotSet(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Folder',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals('Folder', $folder->getFullPath());
    }

    public function testGetPathSegmentsReturnsArrayOfSegments(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Grandchild',
            'path' => 'parent/child/Grandchild',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals(['parent', 'child', 'Grandchild'], $folder->getPathSegments());
    }

    public function testGetPathSegmentsReturnsNameWhenNoPath(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Root',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertEquals(['Root'], $folder->getPathSegments());
    }

    public function testIsChildOfReturnsTrueWhenParentMatches(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '2',
            'project_id' => 'project-uuid',
            'name' => 'Child',
            'parent_id' => '1',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($folder->isChildOf('1'));
    }

    public function testIsChildOfReturnsFalseWhenParentDoesNotMatch(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '2',
            'project_id' => 'project-uuid',
            'name' => 'Child',
            'parent_id' => '1',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($folder->isChildOf('3'));
    }

    public function testIsAtDepthReturnsTrueWhenDepthMatches(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Folder',
            'depth' => 2,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertTrue($folder->isAtDepth(2));
    }

    public function testIsAtDepthReturnsFalseWhenDepthDoesNotMatch(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => '1',
            'project_id' => 'project-uuid',
            'name' => 'Folder',
            'depth' => 2,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->assertFalse($folder->isAtDepth(0));
    }
}
