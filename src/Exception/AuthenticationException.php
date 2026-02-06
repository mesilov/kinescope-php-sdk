<?php

declare(strict_types=1);

namespace Kinescope\Exception;

use Throwable;

/**
 * Exception for HTTP 401 Unauthorized errors.
 *
 * Thrown when API key is invalid, missing, or expired.
 */
class AuthenticationException extends KinescopeException
{
    /**
     * Default HTTP status code for this exception.
     */
    private const int STATUS_CODE = 401;

    /**
     * Create a new AuthenticationException.
     *
     * @param string $message Error message (defaults to standard HTTP message)
     * @param int $code HTTP status code (defaults to 401)
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'Unauthorized',
        int $code = self::STATUS_CODE,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
