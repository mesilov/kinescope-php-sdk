<?php

declare(strict_types=1);

namespace Kinescope\Exception;

use Throwable;

/**
 * Exception for HTTP 429 Too Many Requests errors.
 *
 * Thrown when API rate limit is exceeded.
 */
class RateLimitException extends KinescopeException
{
    /**
     * Default HTTP status code for this exception.
     */
    private const int STATUS_CODE = 429;

    /**
     * Seconds until rate limit resets.
     */
    private ?int $retryAfter = null;

    /**
     * Create a new RateLimitException.
     *
     * @param string $message Error message (defaults to standard HTTP message)
     * @param int $code HTTP status code (defaults to 429)
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'Too Many Requests',
        int $code = self::STATUS_CODE,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create a rate limit exception with retry information.
     *
     * @param int $retryAfter Seconds until rate limit resets
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param Throwable|null $previous Previous exception
     *
     * @return static
     */
    public static function withRetryAfter(
        int $retryAfter,
        string $message = 'Too Many Requests',
        int $code = self::STATUS_CODE,
        ?Throwable $previous = null
    ): static {
        /** @phpstan-ignore new.static */
        $exception = new static($message, $code, $previous);
        $exception->retryAfter = $retryAfter;

        return $exception;
    }

    /**
     * Get retry-after value in seconds.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Check if retry-after information is available.
     */
    public function hasRetryAfter(): bool
    {
        return $this->retryAfter !== null;
    }
}
