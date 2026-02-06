<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Enum;

use Kinescope\Enum\QualityPreference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QualityPreference::class)]
final class QualityPreferenceTest extends TestCase
{
    public function testBestCaseExists(): void
    {
        $this->assertSame('BEST', QualityPreference::BEST->name);
    }

    public function testWorstCaseExists(): void
    {
        $this->assertSame('WORST', QualityPreference::WORST->name);
    }

    public function testCasesReturnsAllValues(): void
    {
        $cases = QualityPreference::cases();

        $this->assertCount(2, $cases);
        $this->assertSame(QualityPreference::BEST, $cases[0]);
        $this->assertSame(QualityPreference::WORST, $cases[1]);
    }
}
