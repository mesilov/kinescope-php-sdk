<?php

declare(strict_types=1);

namespace Kinescope\Exception;

use Throwable;

/**
 * Exception for HTTP 400 Bad Request errors.
 *
 * Thrown when API request contains invalid parameters or malformed data.
 */
class BadRequestException extends KinescopeException
{
    /**
     * Default HTTP status code for this exception.
     */
    private const int STATUS_CODE = 400;

    /**
     * Create a new BadRequestException.
     *
     * @param string $message Error message (defaults to standard HTTP message)
     * @param int $code HTTP status code (defaults to 400)
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'Bad Request',
        int $code = self::STATUS_CODE,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
