<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Folder;

use Carbon\CarbonImmutable;
use Kinescope\DTO\Folder\FolderDTO;
use PHPUnit\Framework\TestCase;

final class FolderDTOTest extends TestCase
{
    public function testFromArrayMapsCurrentApiPayload(): void
    {
        $folder = FolderDTO::fromArray($this->payload());

        self::assertSame('folder-id', $folder->id);
        self::assertSame('Folder', $folder->name);
        self::assertSame('project-id', $folder->projectId);
        self::assertSame('project-id', $folder->parentId);
        self::assertSame(123456, $folder->size);
        self::assertSame(3, $folder->itemsCount);
        self::assertInstanceOf(CarbonImmutable::class, $folder->createdAt);
        self::assertSame('2025-07-30T17:11:12.905170Z', $folder->createdAt->toJSON());
        self::assertNull($folder->updatedAt);
        self::assertNull($folder->deletedAt);
    }

    public function testToArrayUsesRawApiFieldNames(): void
    {
        $array = FolderDTO::fromArray($this->payload())->toArray();

        self::assertSame('folder-id', $array['id']);
        self::assertSame('project-id', $array['project_id']);
        self::assertSame(123456, $array['size']);
        self::assertSame(3, $array['items_count']);
        self::assertArrayNotHasKey('videos_count', $array);
    }

    public function testRootAndParentChecksUseProjectParentConvention(): void
    {
        $root = FolderDTO::fromArray($this->payload(parentId: 'project-id'));
        $child = FolderDTO::fromArray($this->payload(parentId: 'folder-parent'));

        self::assertTrue($root->isRoot());
        self::assertFalse($root->hasParent());
        self::assertFalse($child->isRoot());
        self::assertTrue($child->hasParent());
        self::assertTrue($child->isChildOf('folder-parent'));
    }

    public function testItemAndSizeHelpers(): void
    {
        $folder = FolderDTO::fromArray($this->payload(size: 1024 * 1024, itemsCount: 1));
        $empty = FolderDTO::fromArray($this->payload(size: 0, itemsCount: 0));

        self::assertTrue($folder->hasItems());
        self::assertFalse($folder->isEmpty());
        self::assertSame('1.00 MB', $folder->getHumanSize());
        self::assertFalse($empty->hasItems());
        self::assertTrue($empty->isEmpty());
        self::assertNull($empty->getHumanSize());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        ?string $parentId = 'project-id',
        int $size = 123456,
        int $itemsCount = 3,
    ): array {
        return [
            'id' => 'folder-id',
            'name' => 'Folder',
            'project_id' => 'project-id',
            'parent_id' => $parentId,
            'size' => $size,
            'items_count' => $itemsCount,
            'created_at' => '2025-07-30T17:11:12.90517Z',
            'updated_at' => null,
            'deleted_at' => null,
        ];
    }
}
