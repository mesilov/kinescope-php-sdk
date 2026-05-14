<?php

declare(strict_types=1);

namespace Kinescope\DTO\Statistics;

use Carbon\CarbonInterval;
use DateTimeImmutable;
use DateTimeInterface;

final readonly class StatisticsDTO
{
    public function __construct(
        public int $videosCount,
        public CarbonInterval $totalDuration,
        public DateTimeImmutable $generatedAt,
    ) {
    }

    public function getTotalSeconds(): int
    {
        return (int) $this->totalDuration->totalSeconds;
    }

    public function getTotalMinutes(): int
    {
        return (int) round($this->totalDuration->totalMinutes);
    }

    public function getTotalHours(): int
    {
        return (int) round($this->totalDuration->totalHours);
    }

    public function forHumans(): string
    {
        return $this->totalDuration->forHumans();
    }

    /**
     * @return array{
     *     videos_count: int,
     *     total_duration_seconds: int,
     *     total_minutes: int,
     *     total_hours: int,
     *     generated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'videos_count' => $this->videosCount,
            'total_duration_seconds' => $this->getTotalSeconds(),
            'total_minutes' => $this->getTotalMinutes(),
            'total_hours' => $this->getTotalHours(),
            'generated_at' => $this->generatedAt->format(DateTimeInterface::ATOM),
        ];
    }
}
