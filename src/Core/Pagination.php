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
 * $pagination = Pagination::create();
 *
 * // Create with specific values
 * $pagination = Pagination::create(page: 2, perPage: 50);
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
     * @param int $page Current page number (1-indexed)
     * @param int $perPage Number of items per page
     */
    private function __construct(
        public int $page = 1,
        public int $perPage = 20
    ) {
    }

    /**
     * Create pagination with specified parameters.
     *
     * @param int $page Page number (must be >= 1)
     * @param int $perPage Items per page (must be between 1 and 100)
     *
     * @return self
     * @throws InvalidArgumentException If parameters are out of valid range
     *
     */
    public static function create(
        int $page = self::DEFAULT_PAGE,
        int $perPage = self::DEFAULT_PER_PAGE
    ): self {
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

        return new self($page, $perPage);
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
        return self::create(page: 1, perPage: $perPage);
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
     * @return self
     * @throws InvalidArgumentException If already on first page
     *
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
        return self::create(page: $this->page, perPage: $perPage);
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
        return self::create(page: $page, perPage: $this->perPage);
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
