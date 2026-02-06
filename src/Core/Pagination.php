<?php

declare(strict_types=1);

namespace Kinescope\Core;

use InvalidArgumentException;

/**
 * Value object for pagination parameters.
 *
 * Used to specify pagination settings when listing resources.
 *
 * @example
 * // Create with defaults
 * $pagination = new Pagination();
 *
 * // Create with specific values
 * $pagination = new Pagination(page: 2, perPage: 50);
 *
 * // Convert to query parameters
 * $query = $pagination->toQueryParams();
 */
final readonly class Pagination
{
    /**
     * Default number of items per page.
     */
    public const int DEFAULT_PER_PAGE = 20;

    /**
     * Minimum items per page.
     */
    public const int MIN_PER_PAGE = 1;

    /**
     * Maximum items per page.
     */
    public const int MAX_PER_PAGE = 100;

    /**
     * Default page number.
     */
    public const int DEFAULT_PAGE = 1;

    /**
     * Create a new Pagination instance.
     *
     * @param int $page Page number (must be >= 1)
     * @param int $perPage Items per page (must be between 1 and 100)
     *
     * @throws InvalidArgumentException If parameters are out of valid range
     */
    public function __construct(
        public int $page = self::DEFAULT_PAGE,
        public int $perPage = self::DEFAULT_PER_PAGE
    ) {
        if ($page < 1) {
            throw new InvalidArgumentException(
                sprintf('Page must be at least 1, got %d', $page)
            );
        }

        if ($perPage < self::MIN_PER_PAGE || $perPage > self::MAX_PER_PAGE) {
            throw new InvalidArgumentException(
                sprintf(
                    'Items per page must be between %d and %d, got %d',
                    self::MIN_PER_PAGE,
                    self::MAX_PER_PAGE,
                    $perPage
                )
            );
        }
    }

    /**
     * Create pagination for the first page.
     *
     * @param int $perPage Items per page
     *
     * @return self
     */
    public static function firstPage(int $perPage = self::DEFAULT_PER_PAGE): self
    {
        return new self(page: 1, perPage: $perPage);
    }

    /**
     * Create a new Pagination for next page.
     *
     * @return self
     */
    public function nextPage(): self
    {
        return new self($this->page + 1, $this->perPage);
    }

    /**
     * Create a new Pagination for previous page.
     *
     * @throws InvalidArgumentException If already on first page
     *
     * @return self
     */
    public function previousPage(): self
    {
        if ($this->page <= 1) {
            throw new InvalidArgumentException('Already on first page');
        }

        return new self($this->page - 1, $this->perPage);
    }

    /**
     * Create a new Pagination with a different per page value.
     *
     * @param int $perPage New items per page value
     *
     * @return self
     */
    public function withPerPage(int $perPage): self
    {
        return new self(page: $this->page, perPage: $perPage);
    }

    /**
     * Create a new Pagination for a specific page.
     *
     * @param int $page Page number
     *
     * @return self
     */
    public function withPage(int $page): self
    {
        return new self(page: $page, perPage: $this->perPage);
    }

    /**
     * Calculate offset for database queries.
     *
     * @return int The offset value
     */
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /**
     * Check if this is first page.
     *
     * @return bool
     */
    public function isFirstPage(): bool
    {
        return $this->page === 1;
    }

    /**
     * Convert to query parameters array.
     *
     * @return array{page: int, per_page: int}
     */
    public function toQueryParams(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }
}
