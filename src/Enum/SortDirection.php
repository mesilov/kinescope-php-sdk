<?php

declare(strict_types=1);

namespace Kinescope\Enum;

/**
 * Sort direction for list queries.
 */
enum SortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';

    /**
     * Get the opposite direction.
     */
    public function reversed(): self
    {
        return match ($this) {
            self::ASC => self::DESC,
            self::DESC => self::ASC,
        };
    }
}
