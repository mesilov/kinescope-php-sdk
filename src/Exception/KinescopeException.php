<?php

declare(strict_types=1);

namespace Kinescope\Exception;

use Exception;
use JsonException;
use Throwable;

/**
 * Base exception for all Kinescope SDK errors.
 *
 * All SDK-specific exceptions extend this class, allowing catch blocks
 * to handle all Kinescope errors with a single exception type.
 *
 * @example
 * try {
 *     $video = $factory->videos()->get('video-id');
 * } catch (KinescopeException $e) {
 *     echo "Kinescope error: " . $e->getMessage();
 *     echo "HTTP code: " . $e->getCode();
 *     if ($e->hasResponse()) {
 *         echo "Response: " . $e->getResponseBody();
 *     }
 * }
 */
class KinescopeException extends Exception
{
    /**
     * Raw response body from API (if available).
     */
    private ?string $responseBody = null;

    /**
     * Response headers from API (if available).
     *
     * @var array<string, string|array<string>>
     */
    private array $responseHeaders = [];

    /**
     * Create a new Kinescope exception.
     *
     * @param string $message Error message
     * @param int $code HTTP status code or error code
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create an exception with response details.
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param string|null $responseBody Raw response body
     * @param array<string, string|array<string>> $responseHeaders Response headers
     * @param Throwable|null $previous Previous exception
     *
     * @return static
     *
     * @phpstan-return static
     */
    public static function withResponse(
        string $message,
        int $code,
        ?string $responseBody = null,
        array $responseHeaders = [],
        ?Throwable $previous = null
    ): static {
        /** @phpstan-ignore new.static */
        $exception = new static($message, $code, $previous);
        $exception->responseBody = $responseBody;
        $exception->responseHeaders = $responseHeaders;

        return $exception;
    }

    /**
     * Check if response data is available.
     */
    public function hasResponse(): bool
    {
        return $this->responseBody !== null;
    }

    /**
     * Get raw response body.
     */
    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    /**
     * Get response headers.
     *
     * @return array<string, string|array<string>>
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * Get decoded response body as array.
     *
     * @return array<string, mixed>|null
     */
    public function getDecodedResponse(): ?array
    {
        if ($this->responseBody === null) {
            return null;
        }

        try {
            $decoded = json_decode($this->responseBody, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * Get HTTP status code.
     *
     * Alias for getCode() with proper int return type.
     */
    public function getStatusCode(): int
    {
        return $this->getCode();
    }
}
