<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Statistics;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Kinescope\DTO\Statistics\StatisticsDTO;
use PHPUnit\Framework\TestCase;

final class StatisticsDTOTest extends TestCase
{
    public function testZeroDurationReportsCleanly(): void
    {
        $dto = new StatisticsDTO(0, CarbonInterval::seconds(0), CarbonImmutable::parse('2026-05-14T00:00:00+00:00'));

        $this->assertSame(0, $dto->videosCount);
        $this->assertSame(0, $dto->getTotalSeconds());
        $this->assertSame(0, $dto->getTotalMinutes());
        $this->assertSame(0, $dto->getTotalHours());
    }

    public function testMinuteTotalsRoundToNearestInteger(): void
    {
        $dto = new StatisticsDTO(1, CarbonInterval::seconds(90), CarbonImmutable::now('UTC'));

        $this->assertSame(90, $dto->getTotalSeconds());
        $this->assertSame(2, $dto->getTotalMinutes());
        $this->assertSame(0, $dto->getTotalHours());
    }

    public function testExactHourReportsExactIntegers(): void
    {
        $dto = new StatisticsDTO(1, CarbonInterval::seconds(3600), CarbonImmutable::now('UTC'));

        $this->assertSame(3600, $dto->getTotalSeconds());
        $this->assertSame(60, $dto->getTotalMinutes());
        $this->assertSame(1, $dto->getTotalHours());
    }

    public function testHalfHourTotalsRoundUp(): void
    {
        $dto = new StatisticsDTO(1, CarbonInterval::seconds(5400), CarbonImmutable::now('UTC'));

        $this->assertSame(5400, $dto->getTotalSeconds());
        $this->assertSame(90, $dto->getTotalMinutes());
        $this->assertSame(2, $dto->getTotalHours());
    }

    public function testForHumansDelegatesToCarbonInterval(): void
    {
        $dto = new StatisticsDTO(1, CarbonInterval::seconds(3600), CarbonImmutable::now('UTC'));

        $this->assertSame($dto->totalDuration->forHumans(), $dto->forHumans());
        $this->assertNotSame('', $dto->forHumans());
    }

    public function testToArrayEmitsExpectedShape(): void
    {
        $generatedAt = CarbonImmutable::parse('2026-05-14T12:34:56+00:00');
        $dto = new StatisticsDTO(7, CarbonInterval::seconds(5400), $generatedAt);

        $this->assertSame([
            'videos_count' => 7,
            'total_duration_seconds' => 5400,
            'total_minutes' => 90,
            'total_hours' => 2,
            'generated_at' => '2026-05-14T12:34:56.000000Z',
        ], $dto->toArray());
    }
}
