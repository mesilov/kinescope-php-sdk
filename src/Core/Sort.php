<?php

declare(strict_types=1);

namespace Kinescope\Core;

use Kinescope\Enum\SortDirection;

/**
 * Value object for sorting parameters.
 *
 * Used to specify sort field and direction when listing resources.
 *
 * @example
 * // Sort by created_at ascending
 * $sort = Sort::asc('created_at');
 *
 * // Sort by title descending
 * $sort = Sort::desc('title');
 *
 * // Reverse sort direction
 * $reversed = $sort->reversed();
 *
 * // Convert to query parameters
 * $query = $sort->toQueryParams();
 */
final readonly class Sort
{
    /**
     * Create a new Sort instance.
     *
     * @param string $field Field to sort by
     * @param SortDirection $direction Sort direction
     */
    public function __construct(
        public string $field,
        public SortDirection $direction = SortDirection::ASC
    ) {
    }

    /**
     * Create ascending sort for the given field.
     *
     * @param string $field Field to sort by
     */
    public static function asc(string $field): self
    {
        return new self($field, SortDirection::ASC);
    }

    /**
     * Create descending sort for the given field.
     *
     * @param string $field Field to sort by
     */
    public static function desc(string $field): self
    {
        return new self($field, SortDirection::DESC);
    }

    /**
     * Create a new Sort with the opposite direction.
     */
    public function reversed(): self
    {
        return new self($this->field, $this->direction->reversed());
    }

    /**
     * Convert to query parameters array.
     *
     * @return array{order: string, direction: string}
     */
    public function toQueryParams(): array
    {
        return [
            'order' => $this->field,
            'direction' => $this->direction->value,
        ];
    }
}
