<?php

declare(strict_types=1);

namespace Kinescope\DTO\Common;

use Carbon\CarbonImmutable;

final readonly class ApiDate
{
    private function __construct()
    {
    }

    public static function from(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonImmutable) {
            return $value;
        }

        return CarbonImmutable::parse((string) $value);
    }

    public static function toString(?CarbonImmutable $value): ?string
    {
        return $value?->toJSON();
    }
}
