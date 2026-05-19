<?php

declare(strict_types=1);

namespace Kinescope\DTO\Common;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Base class for API responses that return a plain `data` collection without pagination metadata.
 *
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
abstract readonly class CollectionResponse implements IteratorAggregate, Countable
{
    /**
     * @param list<T> $data
     */
    public function __construct(
        protected array $data,
    ) {
    }

    /**
     * @return list<T>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return T|null
     */
    public function first(): mixed
    {
        return $this->data[0] ?? null;
    }

    /**
     * @return T|null
     */
    public function last(): mixed
    {
        if ($this->data === []) {
            return null;
        }

        return $this->data[array_key_last($this->data)];
    }

    public function isEmpty(): bool
    {
        return $this->data === [];
    }

    public function isNotEmpty(): bool
    {
        return $this->data !== [];
    }

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    /**
     * @template TResult
     *
     * @param callable(T): TResult $callback
     *
     * @return list<TResult>
     */
    public function map(callable $callback): array
    {
        return array_values(array_map($callback, $this->data));
    }

    /**
     * @param callable(T): bool $callback
     *
     * @return list<T>
     */
    public function filter(callable $callback): array
    {
        return array_values(array_filter($this->data, $callback));
    }

    /**
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
     * @param callable(T): bool $callback
     */
    public function contains(callable $callback): bool
    {
        return $this->find($callback) !== null;
    }

    /**
     * @return array{data: list<mixed>}
     */
    public function toArray(): array
    {
        return [
            'data' => array_values(array_map(
                self::normalizeItem(...),
                $this->data,
            )),
        ];
    }

    private static function normalizeItem(mixed $item): mixed
    {
        if (! is_object($item) || ! is_callable([$item, 'toArray'])) {
            return $item;
        }

        /** @var callable(): mixed $toArray */
        $toArray = [$item, 'toArray'];

        return $toArray();
    }
}
