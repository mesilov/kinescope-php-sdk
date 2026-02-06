<?php

declare(strict_types=1);

namespace Kinescope\Exception;

use Throwable;

/**
 * Exception for HTTP 422 Unprocessable Entity errors.
 *
 * Thrown when API request fails validation rules.
 */
class ValidationException extends KinescopeException
{
    /**
     * Default HTTP status code for this exception.
     */
    private const int STATUS_CODE = 422;

    /**
     * Validation errors from API response.
     *
     * @var array<string, array<string>>
     */
    private array $errors = [];

    /**
     * Create a new ValidationException.
     *
     * @param string $message Error message (defaults to standard HTTP message)
     * @param int $code HTTP status code (defaults to 422)
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'Validation Error',
        int $code = self::STATUS_CODE,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create a validation exception with field errors.
     *
     * @param array<string, array<string>> $errors Field validation errors
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param Throwable|null $previous Previous exception
     *
     * @return static
     */
    public static function withErrors(
        array $errors,
        string $message = 'Validation Error',
        int $code = self::STATUS_CODE,
        ?Throwable $previous = null
    ): static {
        /** @phpstan-ignore new.static */
        $exception = new static($message, $code, $previous);
        $exception->errors = $errors;

        return $exception;
    }

    /**
     * Get validation errors.
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are validation errors.
     */
    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Get errors for a specific field.
     *
     * @param string $field Field name
     *
     * @return array<string>
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
}
