<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Common;

use Kinescope\DTO\Common\MetaDTO;
use Kinescope\Exception\MalformedResponseException;
use PHPUnit\Framework\TestCase;

class MetaDTOTest extends TestCase
{
    public function testFromArrayParsesNestedPaginationMetadata(): void
    {
        $meta = MetaDTO::fromArray([
            'pagination' => [
                'total' => 100,
                'page' => 2,
                'per_page' => 20,
            ],
            'order' => [
                'name' => 'asc',
            ],
        ]);

        $this->assertEquals(100, $meta->total);
        $this->assertEquals(2, $meta->pagination->page);
        $this->assertEquals(20, $meta->pagination->perPage);
        $this->assertSame(['name' => 'asc'], $meta->order);
    }

    public function testFromArrayRejectsFlatMetadata(): void
    {
        $this->expectException(MalformedResponseException::class);
        $this->expectExceptionMessage('Missing required response metadata key: pagination');

        MetaDTO::fromArray([
            'total' => 100,
            'page' => 2,
            'per_page' => 20,
        ]);
    }

    public function testFromArrayRejectsMissingPaginationMetadata(): void
    {
        $this->expectException(MalformedResponseException::class);
        $this->expectExceptionMessage('Missing required response metadata key: pagination');

        MetaDTO::fromArray([]);
    }

    public function testFromArrayRejectsMissingPaginationTotal(): void
    {
        $this->expectException(MalformedResponseException::class);
        $this->expectExceptionMessage('Missing required response metadata key: pagination.total');

        MetaDTO::fromArray([
            'pagination' => [
                'page' => 1,
                'per_page' => 20,
            ],
        ]);
    }

    public function testFromArrayRejectsMissingPaginationPage(): void
    {
        $this->expectException(MalformedResponseException::class);
        $this->expectExceptionMessage('Missing required response metadata key: pagination.page');

        MetaDTO::fromArray([
            'pagination' => [
                'total' => 100,
                'per_page' => 20,
            ],
        ]);
    }

    public function testFromArrayRejectsMissingPaginationPerPage(): void
    {
        $this->expectException(MalformedResponseException::class);
        $this->expectExceptionMessage('Missing required response metadata key: pagination.per_page');

        MetaDTO::fromArray([
            'pagination' => [
                'total' => 100,
                'page' => 1,
            ],
        ]);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $meta = self::meta(total: 50, page: 3, perPage: 25);

        $array = $meta->toArray();

        $this->assertEquals([
            'pagination' => [
                'page' => 3,
                'per_page' => 25,
                'total' => 50,
            ],
            'order' => [],
        ], $array);
    }

    public function testGetLastPageCalculatesCorrectly(): void
    {
        $meta = self::meta(total: 100, page: 1, perPage: 20);

        $this->assertEquals(5, $meta->getLastPage());
    }

    public function testGetLastPageWithPartialPage(): void
    {
        $meta = self::meta(total: 95, page: 1, perPage: 20);

        $this->assertEquals(5, $meta->getLastPage());
    }

    public function testGetLastPageWithZeroTotal(): void
    {
        $meta = self::meta(total: 0, page: 1, perPage: 20);

        $this->assertEquals(0, $meta->getLastPage());
    }

    public function testHasNextPageReturnsTrueWhenMorePagesExist(): void
    {
        $meta = self::meta(total: 100, page: 1, perPage: 20);

        $this->assertTrue($meta->hasNextPage());
    }

    public function testHasNextPageReturnsFalseOnLastPage(): void
    {
        $meta = self::meta(total: 100, page: 5, perPage: 20);

        $this->assertFalse($meta->hasNextPage());
    }

    public function testHasPreviousPageReturnsTrueWhenNotOnFirstPage(): void
    {
        $meta = self::meta(total: 100, page: 2, perPage: 20);

        $this->assertTrue($meta->hasPreviousPage());
    }

    public function testHasPreviousPageReturnsFalseOnFirstPage(): void
    {
        $meta = self::meta(total: 100, page: 1, perPage: 20);

        $this->assertFalse($meta->hasPreviousPage());
    }

    public function testIsEmptyReturnsTrueWhenTotalIsZero(): void
    {
        $meta = self::meta(total: 0, page: 1, perPage: 20);

        $this->assertTrue($meta->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenTotalIsPositive(): void
    {
        $meta = self::meta(total: 1, page: 1, perPage: 20);

        $this->assertFalse($meta->isEmpty());
    }

    public function testIsFirstPageReturnsTrue(): void
    {
        $meta = self::meta(total: 100, page: 1, perPage: 20);

        $this->assertTrue($meta->isFirstPage());
    }

    public function testIsFirstPageReturnsFalse(): void
    {
        $meta = self::meta(total: 100, page: 2, perPage: 20);

        $this->assertFalse($meta->isFirstPage());
    }

    public function testIsLastPageReturnsTrueOnLastPage(): void
    {
        $meta = self::meta(total: 100, page: 5, perPage: 20);

        $this->assertTrue($meta->isLastPage());
    }

    public function testGetOffsetReturnsCorrectValue(): void
    {
        $meta = self::meta(total: 100, page: 3, perPage: 20);

        $this->assertEquals(40, $meta->getOffset());
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
