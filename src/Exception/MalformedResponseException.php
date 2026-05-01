<?php

declare(strict_types=1);

namespace Kinescope\Exception;

use Throwable;

/**
 * Exception for malformed API responses.
 *
 * Thrown when an API response is missing fields required by the SDK contract.
 */
class MalformedResponseException extends KinescopeException
{
    /**
     * Create a new MalformedResponseException.
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'Malformed API response',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
