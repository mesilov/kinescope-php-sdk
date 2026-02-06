<?php

declare(strict_types=1);

namespace Kinescope\Exception;

use Throwable;

/**
 * Exception for HTTP 404 Not Found errors.
 *
 * Thrown when requested resource (video, project, folder, etc.) doesn't exist.
 */
class NotFoundException extends KinescopeException
{
    /**
     * Default HTTP status code for this exception.
     */
    private const int STATUS_CODE = 404;

    /**
     * Create a new NotFoundException.
     *
     * @param string $message Error message (defaults to standard HTTP message)
     * @param int $code HTTP status code (defaults to 404)
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'Not Found',
        int $code = self::STATUS_CODE,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
