<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Common;

use Kinescope\DTO\Common\MetaDTO;
use PHPUnit\Framework\TestCase;

class PaginatedResponseTest extends TestCase
{
    public function testMetaPaginationMethods(): void
    {
        $meta = self::meta(total: 100, page: 1, perPage: 20);

        $this->assertEquals(5, $meta->getLastPage());
        $this->assertTrue($meta->hasNextPage());
        $this->assertFalse($meta->hasPreviousPage());
        $this->assertTrue($meta->isFirstPage());
        $this->assertFalse($meta->isEmpty());
        $this->assertEquals(0, $meta->getOffset());
    }

    public function testMetaLastPage(): void
    {
        $meta = self::meta(total: 100, page: 5, perPage: 20);

        $this->assertEquals(5, $meta->getLastPage());
        $this->assertFalse($meta->hasNextPage());
        $this->assertTrue($meta->isLastPage());
    }

    public function testMetaEmpty(): void
    {
        $meta = self::meta(total: 0, page: 1, perPage: 20);

        $this->assertEquals(0, $meta->getLastPage());
        $this->assertTrue($meta->isEmpty());
        $this->assertFalse($meta->hasNextPage());
    }

    public function testMetaCalculatesLastPageWithoutCustomLastPage(): void
    {
        $meta = self::meta(total: 100, page: 1, perPage: 20);

        $this->assertEquals(5, $meta->getLastPage());
    }

    private static function meta(int $total, int $page, int $perPage): MetaDTO
    {
        return MetaDTO::fromArray([
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
            ],
        ]);
    }
}
