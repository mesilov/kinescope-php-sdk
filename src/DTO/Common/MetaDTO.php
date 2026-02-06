<?php

declare(strict_types=1);

namespace Kinescope\DTO\Common;

use Kinescope\Core\Pagination;

/**
 * Metadata for paginated API responses.
 *
 * Contains pagination information returned by list endpoints.
 */
final readonly class MetaDTO
{
    /**
     * Create a new MetaDTO instance.
     *
     * @param int $total Total number of items across all pages
     * @param Pagination $pagination Pagination parameters (page and perPage)
     * @param int|null $lastPage Last page number (calculated if not provided)
     */
    public function __construct(
        public int $total,
        public Pagination $pagination,
        public ?int $lastPage = null,
    ) {
    }

    /**
     * Create a MetaDTO from API response array.
     *
     * @param array<string, mixed> $data Raw API response data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            total: (int) ($data['total'] ?? 0),
            pagination: new Pagination(
                page: (int) ($data['page'] ?? 1),
                perPage: (int) ($data['per_page'] ?? 20),
            ),
            lastPage: isset($data['last_page']) ? (int) $data['last_page'] : null,
        );
    }

    /**
     * Get the last page number.
     *
     * Calculates from total and perPage if not explicitly provided.
     *
     * @return int
     */
    public function getLastPage(): int
    {
        if ($this->lastPage !== null) {
            return $this->lastPage;
        }

        if ($this->pagination->perPage <= 0) {
            return 1;
        }

        return (int) ceil($this->total / $this->pagination->perPage);
    }

    /**
     * Check if there is a next page.
     *
     * @return bool
     */
    public function hasNextPage(): bool
    {
        return $this->pagination->page < $this->getLastPage();
    }

    /**
     * Check if there is a previous page.
     *
     * @return bool
     */
    public function hasPreviousPage(): bool
    {
        return $this->pagination->page > 1;
    }

    /**
     * Check if this is the first page.
     *
     * @return bool
     */
    public function isFirstPage(): bool
    {
        return $this->pagination->isFirstPage();
    }

    /**
     * Check if this is the last page.
     *
     * @return bool
     */
    public function isLastPage(): bool
    {
        return $this->pagination->page >= $this->getLastPage();
    }

    /**
     * Get the offset for the current page.
     *
     * @return int
     */
    public function getOffset(): int
    {
        return $this->pagination->getOffset();
    }

    /**
     * Check if the result set is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->total === 0;
    }

    /**
     * Convert to array representation.
     *
     * @return array{total: int, page: int, per_page: int, last_page: int}
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'page' => $this->pagination->page,
            'per_page' => $this->pagination->perPage,
            'last_page' => $this->getLastPage(),
        ];
    }
}
