<?php

declare(strict_types=1);

namespace Kinescope\Enum;

/**
 * HTTP methods used in API requests.
 *
 * This enum is used internally by the SDK for making HTTP requests.
 */
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';

    /**
     * Check if this method typically has a request body.
     */
    public function hasBody(): bool
    {
        return match ($this) {
            self::POST, self::PUT, self::PATCH => true,
            self::GET, self::DELETE => false,
        };
    }

    /**
     * Check if this method is safe (doesn't modify resources).
     */
    public function isSafe(): bool
    {
        return $this === self::GET;
    }

    /**
     * Check if this method is idempotent.
     */
    public function isIdempotent(): bool
    {
        return match ($this) {
            self::GET, self::PUT, self::DELETE => true,
            self::POST, self::PATCH => false,
        };
    }
}
