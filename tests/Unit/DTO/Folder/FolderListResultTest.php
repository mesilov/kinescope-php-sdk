<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Folder;

use Kinescope\DTO\Folder\FolderDTO;
use Kinescope\DTO\Folder\FolderListResult;
use PHPUnit\Framework\TestCase;

final class FolderListResultTest extends TestCase
{
    public function testFromArrayCreatesPaginatedFolderList(): void
    {
        $result = FolderListResult::fromArray($this->response());

        self::assertCount(3, $result);
        self::assertContainsOnlyInstancesOf(FolderDTO::class, $result->getData());
        self::assertSame(3, $result->getTotal());
    }

    public function testCurrentHelpersUseItemsAndSize(): void
    {
        $result = FolderListResult::fromArray($this->response());

        self::assertCount(2, $result->getRoots());
        self::assertCount(1, $result->getChildren('folder-a'));
        self::assertCount(2, $result->getWithItems());
        self::assertCount(1, $result->getEmpty());
        self::assertSame(3, $result->getTotalItemsCount());
        self::assertSame(300, $result->getTotalSize());
        self::assertSame(['folder-a', 'folder-b', 'folder-c'], $result->getIds());
    }

    public function testLookupAndSorting(): void
    {
        $result = FolderListResult::fromArray($this->response());

        self::assertSame('Beta', $result->findById('folder-b')?->name);
        self::assertSame('folder-c', $result->findByName('Gamma')?->id);
        self::assertNull($result->findById('missing'));
        self::assertSame(['folder-a', 'folder-b', 'folder-c'], array_map(
            static fn (FolderDTO $folder): string => $folder->id,
            $result->getSortedByName(),
        ));
    }

    public function testBuildTreeUsesParentIds(): void
    {
        $tree = FolderListResult::fromArray($this->response())->buildTree();

        self::assertCount(2, $tree);
        self::assertSame('folder-a', $tree[0]['folder']->id);
        self::assertCount(1, $tree[0]['children']);
        self::assertSame('folder-b', $tree[0]['children'][0]['folder']->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function response(): array
    {
        return [
            'data' => [
                $this->folder('folder-a', 'Alpha', 'project-id', 100, 1),
                $this->folder('folder-b', 'Beta', 'folder-a', 200, 2),
                $this->folder('folder-c', 'Gamma', 'project-id', 0, 0),
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
    private function folder(string $id, string $name, string $parentId, int $size, int $itemsCount): array
    {
        return [
            'id' => $id,
            'name' => $name,
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
