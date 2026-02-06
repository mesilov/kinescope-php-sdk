<?php

declare(strict_types=1);

namespace Kinescope\DTO\Common;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Abstract base class for paginated API responses.
 *
 * Provides common functionality for list endpoints that return
 * paginated data with metadata.
 *
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
abstract readonly class PaginatedResponse implements IteratorAggregate, Countable
{
    /**
     * Create a new paginated response.
     *
     * @param array<T> $data The items on the current page
     * @param MetaDTO $meta Pagination metadata
     */
    public function __construct(
        protected array $data,
        protected MetaDTO $meta,
    ) {
    }

    /**
     * Get all items on the current page.
     *
     * @return array<T>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the pagination metadata.
     *
     * @return MetaDTO
     */
    public function getMeta(): MetaDTO
    {
        return $this->meta;
    }

    /**
     * Get the first item or null if empty.
     *
     * @return T|null
     */
    public function first(): mixed
    {
        return $this->data[0] ?? null;
    }

    /**
     * Get the last item or null if empty.
     *
     * @return T|null
     */
    public function last(): mixed
    {
        if ($this->data === []) {
            return null;
        }

        return $this->data[array_key_last($this->data)];
    }

    /**
     * Check if the response contains any items.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->data === [];
    }

    /**
     * Check if the response contains items.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return $this->data !== [];
    }

    /**
     * Get the number of items on the current page.
     *
     * @return int<0, max>
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Get the total number of items across all pages.
     *
     * @return int
     */
    public function getTotal(): int
    {
        return $this->meta->total;
    }

    /**
     * Check if there is a next page.
     *
     * @return bool
     */
    public function hasNextPage(): bool
    {
        return $this->meta->hasNextPage();
    }

    /**
     * Check if there is a previous page.
     *
     * @return bool
     */
    public function hasPreviousPage(): bool
    {
        return $this->meta->hasPreviousPage();
    }

    /**
     * Get the current page number.
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->meta->pagination->page;
    }

    /**
     * Get the last page number.
     *
     * @return int
     */
    public function getLastPage(): int
    {
        return $this->meta->getLastPage();
    }

    /**
     * Get the number of items per page.
     *
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->meta->pagination->perPage;
    }

    /**
     * Get an iterator for the items.
     *
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    /**
     * Map over the items with a callback.
     *
     * @template TResult
     *
     * @param callable(T): TResult $callback
     *
     * @return array<TResult>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->data);
    }

    /**
     * Filter items with a callback.
     *
     * @param callable(T): bool $callback
     *
     * @return array<T>
     */
    public function filter(callable $callback): array
    {
        return array_values(array_filter($this->data, $callback));
    }

    /**
     * Find first item matching a callback.
     *
     * @param callable(T): bool $callback
     *
     * @return T|null
     */
    public function find(callable $callback): mixed
    {
        foreach ($this->data as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Check if any item matches the callback.
     *
     * @param callable(T): bool $callback
     *
     * @return bool
     */
    public function contains(callable $callback): bool
    {
        return $this->find($callback) !== null;
    }

    /**
     * Convert to array representation.
     *
     * @return array{data: array<T>, meta: array{total: int, page: int, per_page: int, last_page: int}}
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'meta' => $this->meta->toArray(),
        ];
    }
}
