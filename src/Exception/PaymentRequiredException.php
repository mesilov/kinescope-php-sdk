<?php

declare(strict_types=1);

namespace Kinescope\Exception;

use Throwable;

/**
 * Exception for HTTP 402 Payment Required errors.
 *
 * Thrown when account has insufficient funds or requires payment.
 */
class PaymentRequiredException extends KinescopeException
{
    /**
     * Default HTTP status code for this exception.
     */
    private const int STATUS_CODE = 402;

    /**
     * Create a new PaymentRequiredException.
     *
     * @param string $message Error message (defaults to standard HTTP message)
     * @param int $code HTTP status code (defaults to 402)
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'Payment Required',
        int $code = self::STATUS_CODE,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
