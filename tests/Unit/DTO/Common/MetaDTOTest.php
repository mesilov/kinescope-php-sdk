<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Common;

use Kinescope\DTO\Common\MetaDTO;
use PHPUnit\Framework\TestCase;

class MetaDTOTest extends TestCase
{
    public function testFromArrayCreatesValidMetaDTO(): void
    {
        $data = [
            'total' => 100,
            'page' => 2,
            'per_page' => 20,
        ];

        $meta = MetaDTO::fromArray($data);

        $this->assertEquals(100, $meta->total);
        $this->assertEquals(2, $meta->pagination->page);
        $this->assertEquals(20, $meta->pagination->perPage);
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'total' => 0,
            'page' => 1,
            'per_page' => 10,
        ];

        $meta = MetaDTO::fromArray($data);

        $this->assertEquals(0, $meta->total);
        $this->assertEquals(1, $meta->pagination->page);
        $this->assertEquals(10, $meta->pagination->perPage);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 50,
            'page' => 3,
            'per_page' => 25,
        ]);

        $array = $meta->toArray();

        $this->assertEquals([
            'total' => 50,
            'page' => 3,
            'per_page' => 25,
            'last_page' => 2,
        ], $array);
    }

    public function testGetLastPageCalculatesCorrectly(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 100,
            'page' => 1,
            'per_page' => 20,
        ]);

        $this->assertEquals(5, $meta->getLastPage());
    }

    public function testGetLastPageWithPartialPage(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 95,
            'page' => 1,
            'per_page' => 20,
        ]);

        $this->assertEquals(5, $meta->getLastPage());
    }

    public function testGetLastPageWithZeroTotal(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 0,
            'page' => 1,
            'per_page' => 20,
        ]);

        $this->assertEquals(0, $meta->getLastPage());
    }

    public function testHasNextPageReturnsTrueWhenMorePagesExist(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 100,
            'page' => 1,
            'per_page' => 20,
        ]);

        $this->assertTrue($meta->hasNextPage());
    }

    public function testHasNextPageReturnsFalseOnLastPage(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 100,
            'page' => 5,
            'per_page' => 20,
        ]);

        $this->assertFalse($meta->hasNextPage());
    }

    public function testHasPreviousPageReturnsTrueWhenNotOnFirstPage(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 100,
            'page' => 2,
            'per_page' => 20,
        ]);

        $this->assertTrue($meta->hasPreviousPage());
    }

    public function testHasPreviousPageReturnsFalseOnFirstPage(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 100,
            'page' => 1,
            'per_page' => 20,
        ]);

        $this->assertFalse($meta->hasPreviousPage());
    }

    public function testIsEmptyReturnsTrueWhenTotalIsZero(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 0,
            'page' => 1,
            'per_page' => 20,
        ]);

        $this->assertTrue($meta->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenTotalIsPositive(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 1,
            'page' => 1,
            'per_page' => 20,
        ]);

        $this->assertFalse($meta->isEmpty());
    }

    public function testIsFirstPageReturnsTrue(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 100,
            'page' => 1,
            'per_page' => 20,
        ]);

        $this->assertTrue($meta->isFirstPage());
    }

    public function testIsFirstPageReturnsFalse(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 100,
            'page' => 2,
            'per_page' => 20,
        ]);

        $this->assertFalse($meta->isFirstPage());
    }

    public function testIsLastPageReturnsTrueOnLastPage(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 100,
            'page' => 5,
            'per_page' => 20,
        ]);

        $this->assertTrue($meta->isLastPage());
    }

    public function testGetOffsetReturnsCorrectValue(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 100,
            'page' => 3,
            'per_page' => 20,
        ]);

        $this->assertEquals(40, $meta->getOffset());
    }
}
