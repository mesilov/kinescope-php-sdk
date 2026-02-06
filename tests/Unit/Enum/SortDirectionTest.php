<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Enum;

use Kinescope\Enum\SortDirection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SortDirection::class)]
final class SortDirectionTest extends TestCase
{
    public function testAscValue(): void
    {
        $this->assertSame('asc', SortDirection::ASC->value);
    }

    public function testDescValue(): void
    {
        $this->assertSame('desc', SortDirection::DESC->value);
    }

    public function testReversedAscToDesc(): void
    {
        $this->assertSame(SortDirection::DESC, SortDirection::ASC->reversed());
    }

    public function testReversedDescToAsc(): void
    {
        $this->assertSame(SortDirection::ASC, SortDirection::DESC->reversed());
    }

    public function testDoubleReversedReturnsSame(): void
    {
        $direction = SortDirection::ASC;
        $this->assertSame($direction, $direction->reversed()->reversed());
    }

    public function testFromValidString(): void
    {
        $this->assertSame(SortDirection::ASC, SortDirection::from('asc'));
        $this->assertSame(SortDirection::DESC, SortDirection::from('desc'));
    }

    public function testTryFromInvalidStringReturnsNull(): void
    {
        $this->assertNull(SortDirection::tryFrom('invalid'));
    }
}
