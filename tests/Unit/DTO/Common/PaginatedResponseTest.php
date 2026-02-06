<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Common;

use Kinescope\DTO\Common\MetaDTO;
use PHPUnit\Framework\TestCase;

class PaginatedResponseTest extends TestCase
{
    public function testMetaPaginationMethods(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 100,
            'page' => 1,
            'per_page' => 20,
        ]);

        $this->assertEquals(5, $meta->getLastPage());
        $this->assertTrue($meta->hasNextPage());
        $this->assertFalse($meta->hasPreviousPage());
        $this->assertTrue($meta->isFirstPage());
        $this->assertFalse($meta->isEmpty());
        $this->assertEquals(0, $meta->getOffset());
    }

    public function testMetaLastPage(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 100,
            'page' => 5,
            'per_page' => 20,
        ]);

        $this->assertEquals(5, $meta->getLastPage());
        $this->assertFalse($meta->hasNextPage());
        $this->assertTrue($meta->isLastPage());
    }

    public function testMetaEmpty(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 0,
            'page' => 1,
            'per_page' => 20,
        ]);

        $this->assertEquals(0, $meta->getLastPage());
        $this->assertTrue($meta->isEmpty());
        $this->assertFalse($meta->hasNextPage());
    }

    public function testMetaWithCustomLastPage(): void
    {
        $meta = MetaDTO::fromArray([
            'total' => 100,
            'page' => 1,
            'per_page' => 20,
            'last_page' => 3,
        ]);

        $this->assertEquals(3, $meta->getLastPage());
    }
}
