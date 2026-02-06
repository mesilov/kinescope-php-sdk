<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Core;

use Kinescope\Core\Sort;
use Kinescope\Enum\SortDirection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Sort::class)]
final class SortTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $sort = new Sort('created_at');

        $this->assertSame('created_at', $sort->field);
        $this->assertSame(SortDirection::ASC, $sort->direction);
    }

    public function testConstructorWithDirection(): void
    {
        $sort = new Sort('title', SortDirection::DESC);

        $this->assertSame('title', $sort->field);
        $this->assertSame(SortDirection::DESC, $sort->direction);
    }

    public function testAscFactory(): void
    {
        $sort = Sort::asc('created_at');

        $this->assertSame('created_at', $sort->field);
        $this->assertSame(SortDirection::ASC, $sort->direction);
    }

    public function testDescFactory(): void
    {
        $sort = Sort::desc('updated_at');

        $this->assertSame('updated_at', $sort->field);
        $this->assertSame(SortDirection::DESC, $sort->direction);
    }

    public function testReversedFromAsc(): void
    {
        $sort = Sort::asc('title');
        $reversed = $sort->reversed();

        $this->assertSame('title', $reversed->field);
        $this->assertSame(SortDirection::DESC, $reversed->direction);
    }

    public function testReversedFromDesc(): void
    {
        $sort = Sort::desc('title');
        $reversed = $sort->reversed();

        $this->assertSame('title', $reversed->field);
        $this->assertSame(SortDirection::ASC, $reversed->direction);
    }

    public function testDoubleReversedReturnsSameValues(): void
    {
        $sort = Sort::desc('created_at');
        $doubleReversed = $sort->reversed()->reversed();

        $this->assertSame($sort->field, $doubleReversed->field);
        $this->assertSame($sort->direction, $doubleReversed->direction);
    }

    public function testReversedReturnsNewInstance(): void
    {
        $sort = Sort::asc('title');
        $reversed = $sort->reversed();

        $this->assertNotSame($sort, $reversed);
    }

    public function testToQueryParams(): void
    {
        $sort = Sort::asc('created_at');

        $this->assertSame([
            'order' => 'created_at',
            'direction' => 'asc',
        ], $sort->toQueryParams());
    }

    public function testToQueryParamsDesc(): void
    {
        $sort = Sort::desc('title');

        $this->assertSame([
            'order' => 'title',
            'direction' => 'desc',
        ], $sort->toQueryParams());
    }
}
