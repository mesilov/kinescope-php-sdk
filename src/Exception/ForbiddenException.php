<?php

declare(strict_types=1);

namespace Kinescope\Exception;

use Throwable;

/**
 * Exception for HTTP 403 Forbidden errors.
 *
 * Thrown when authenticated user doesn't have permission to access resource.
 */
class ForbiddenException extends KinescopeException
{
    /**
     * Default HTTP status code for this exception.
     */
    private const int STATUS_CODE = 403;

    /**
     * Create a new ForbiddenException.
     *
     * @param string $message Error message (defaults to standard HTTP message)
     * @param int $code HTTP status code (defaults to 403)
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'Forbidden',
        int $code = self::STATUS_CODE,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
