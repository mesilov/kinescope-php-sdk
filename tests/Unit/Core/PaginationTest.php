<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Core;

use InvalidArgumentException;
use Kinescope\Core\Pagination;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Pagination value object.
 */
class PaginationTest extends TestCase
{
    public function testCreateWithDefaults(): void
    {
        $pagination = new Pagination();

        $this->assertEquals(1, $pagination->page);
        $this->assertEquals(20, $pagination->perPage);
    }

    public function testCreateWithCustomValues(): void
    {
        $pagination = new Pagination(page: 5, perPage: 50);

        $this->assertEquals(5, $pagination->page);
        $this->assertEquals(50, $pagination->perPage);
    }

    public function testCreateThrowsOnZeroPage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must be at least 1');

        new Pagination(page: 0);
    }

    public function testCreateThrowsOnNegativePage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must be at least 1');

        new Pagination(page: -1);
    }

    public function testCreateThrowsOnZeroPerPage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Items per page must be between 1 and 100');

        new Pagination(perPage: 0);
    }

    public function testCreateThrowsOnPerPageExceedingMax(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Items per page must be between 1 and 100');

        new Pagination(perPage: 101);
    }

    public function testCreateAcceptsMinPerPage(): void
    {
        $pagination = new Pagination(perPage: 1);

        $this->assertEquals(1, $pagination->perPage);
    }

    public function testCreateAcceptsMaxPerPage(): void
    {
        $pagination = new Pagination(perPage: 100);

        $this->assertEquals(100, $pagination->perPage);
    }

    public function testFirstPage(): void
    {
        $pagination = Pagination::firstPage(perPage: 25);

        $this->assertEquals(1, $pagination->page);
        $this->assertEquals(25, $pagination->perPage);
    }

    public function testNextPage(): void
    {
        $pagination = new Pagination(page: 3, perPage: 10);
        $next = $pagination->nextPage();

        $this->assertEquals(4, $next->page);
        $this->assertEquals(10, $next->perPage);
        $this->assertEquals(3, $pagination->page);
    }

    public function testPreviousPage(): void
    {
        $pagination = new Pagination(page: 5, perPage: 15);

        $previous = $pagination->previousPage();

        $this->assertEquals(4, $previous->page);
        $this->assertEquals(15, $previous->perPage);
        $this->assertEquals(5, $pagination->page);
    }

    public function testPreviousPageThrowsOnFirstPage(): void
    {
        $pagination = new Pagination(page: 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Already on first page');

        $pagination->previousPage();
    }

    public function testWithPerPage(): void
    {
        $pagination = new Pagination(page: 3, perPage: 10);

        $modified = $pagination->withPerPage(50);

        $this->assertEquals(3, $modified->page);
        $this->assertEquals(50, $modified->perPage);
        $this->assertEquals(10, $pagination->perPage);
    }

    public function testWithPage(): void
    {
        $pagination = new Pagination(page: 1, perPage: 20);
        $modified = $pagination->withPage(10);

        $this->assertEquals(10, $modified->page);
        $this->assertEquals(20, $modified->perPage);
        // Original unchanged
        $this->assertEquals(1, $pagination->page);
    }

    public function testGetOffset(): void
    {
        $pagination = new Pagination(page: 3, perPage: 20);

        $this->assertEquals(40, $pagination->getOffset());
    }

    public function testGetOffsetOnFirstPage(): void
    {
        $pagination = new Pagination(page: 1, perPage: 20);

        $this->assertEquals(0, $pagination->getOffset());
    }

    public function testIsFirstPageTrue(): void
    {
        $pagination = new Pagination(page: 1);

        $this->assertTrue($pagination->isFirstPage());
    }

    public function testIsFirstPageFalse(): void
    {
        $pagination = new Pagination(page: 2);

        $this->assertFalse($pagination->isFirstPage());
    }

    public function testToQueryParams(): void
    {
        $pagination = new Pagination(page: 5, perPage: 30);

        $expected = [
            'page' => 5,
            'per_page' => 30,
        ];

        $this->assertEquals($expected, $pagination->toQueryParams());
    }

    public function testConstants(): void
    {
        $this->assertEquals(20, Pagination::DEFAULT_PER_PAGE);
        $this->assertEquals(1, Pagination::MIN_PER_PAGE);
        $this->assertEquals(100, Pagination::MAX_PER_PAGE);
        $this->assertEquals(1, Pagination::DEFAULT_PAGE);
    }
}
