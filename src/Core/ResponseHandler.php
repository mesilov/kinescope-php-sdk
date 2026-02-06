<?php

declare(strict_types=1);

namespace Kinescope\Core;

use Kinescope\Exception\AuthenticationException;
use Kinescope\Exception\BadRequestException;
use Kinescope\Exception\ForbiddenException;
use Kinescope\Exception\KinescopeException;
use Kinescope\Exception\NotFoundException;
use Kinescope\Exception\PaymentRequiredException;
use Kinescope\Exception\RateLimitException;
use Kinescope\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;

/**
 * Handles API responses and maps errors to appropriate exceptions.
 *
 * This class is responsible for:
 * - Parsing successful responses
 * - Mapping HTTP error codes to SDK exceptions
 * - Extracting error messages from response bodies
 */
final class ResponseHandler
{
    /**
     * JSON decoder instance.
     */
    private readonly JsonDecoder $jsonDecoder;

    /**
     * Create a new ResponseHandler.
     *
     * @param JsonDecoder|null $jsonDecoder Optional custom JSON decoder
     */
    public function __construct(?JsonDecoder $jsonDecoder = null)
    {
        $this->jsonDecoder = $jsonDecoder ?? new JsonDecoder();
    }

    /**
     * Handle an API response.
     *
     * @param ResponseInterface $response The PSR-7 response
     *
     * @throws KinescopeException On API errors
     *
     * @return array<string, mixed> Decoded response body
     */
    public function handle(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($this->isSuccessful($statusCode)) {
            return $this->jsonDecoder->decode($body);
        }

        $this->throwException($statusCode, $body, $response);
    }

    /**
     * Check if status code indicates success.
     *
     * @param int $statusCode HTTP status code
     *
     * @return bool
     */
    public function isSuccessful(int $statusCode): bool
    {
        return $statusCode >= 200 && $statusCode < 300;
    }

    /**
     * Throw appropriate exception for status code.
     *
     * @param int $statusCode HTTP status code
     * @param string $body Response body
     * @param ResponseInterface $response The original response
     *
     * @throws KinescopeException
     *
     * @return never
     */
    private function throwException(int $statusCode, string $body, ResponseInterface $response): never
    {
        $message = $this->extractErrorMessage($body, $statusCode);
        $headers = $this->extractHeaders($response);

        $exception = match ($statusCode) {
            400 => BadRequestException::withResponse($message, $statusCode, $body, $headers),
            401 => AuthenticationException::withResponse($message, $statusCode, $body, $headers),
            402 => PaymentRequiredException::withResponse($message, $statusCode, $body, $headers),
            403 => ForbiddenException::withResponse($message, $statusCode, $body, $headers),
            404 => NotFoundException::withResponse($message, $statusCode, $body, $headers),
            422 => $this->createValidationException($body, $statusCode, $headers),
            429 => $this->createRateLimitException($body, $statusCode, $headers, $response),
            default => KinescopeException::withResponse($message, $statusCode, $body, $headers),
        };

        throw $exception;
    }

    /**
     * Extract error message from response body.
     *
     * @param string $body Response body
     * @param int $statusCode HTTP status code
     *
     * @return string Error message
     */
    private function extractErrorMessage(string $body, int $statusCode): string
    {
        $decoded = $this->jsonDecoder->decodeOrNull($body);

        if ($decoded === null) {
            return $this->getDefaultMessage($statusCode);
        }

        $message = $this->jsonDecoder->extractErrorMessage($decoded);

        return $message ?? $this->getDefaultMessage($statusCode);
    }

    /**
     * Get the default error message for a status code.
     *
     * @param int $statusCode HTTP status code
     *
     * @return string Default message
     */
    private function getDefaultMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Validation Error',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            default => sprintf('HTTP Error %d', $statusCode),
        };
    }

    /**
     * Create a validation exception with field errors.
     *
     * @param string $body Response body
     * @param int $statusCode HTTP status code
     * @param array<string, string|array<string>> $headers Response headers
     *
     * @return ValidationException
     */
    private function createValidationException(
        string $body,
        int $statusCode,
        array $headers
    ): ValidationException {
        $decoded = $this->jsonDecoder->decodeOrNull($body);
        $errors = [];
        $message = 'Validation Error';

        if ($decoded !== null) {
            if (isset($decoded['errors']) && is_array($decoded['errors'])) {
                $errors = $decoded['errors'];
            }

            $extracted = $this->jsonDecoder->extractErrorMessage($decoded);

            if ($extracted !== null) {
                $message = $extracted;
            }
        }

        $exception = ValidationException::withErrors($errors, $message, $statusCode);

        // Add response details
        return ValidationException::withResponse($message, $statusCode, $body, $headers);
    }

    /**
     * Create a rate limit exception with retry information.
     *
     * @param string $body Response body
     * @param int $statusCode HTTP status code
     * @param array<string, string|array<string>> $headers Response headers
     * @param ResponseInterface $response Original response
     *
     * @return RateLimitException
     */
    private function createRateLimitException(
        string $body,
        int $statusCode,
        array $headers,
        ResponseInterface $response
    ): RateLimitException {
        $message = $this->extractErrorMessage($body, $statusCode);
        $retryAfter = $this->extractRetryAfter($response);

        if ($retryAfter !== null) {
            $exception = RateLimitException::withRetryAfter($retryAfter, $message, $statusCode);
        } else {
            $exception = new RateLimitException($message, $statusCode);
        }

        return RateLimitException::withResponse($message, $statusCode, $body, $headers);
    }

    /**
     * Extract Retry-After header value.
     *
     * @param ResponseInterface $response The response
     *
     * @return int|null Seconds to wait, or null if not specified
     */
    private function extractRetryAfter(ResponseInterface $response): ?int
    {
        if (! $response->hasHeader('Retry-After')) {
            return null;
        }

        $value = $response->getHeaderLine('Retry-After');

        // Retry-After can be a number of seconds or an HTTP date
        if (is_numeric($value)) {
            return (int) $value;
        }

        // Try to parse as HTTP date
        $timestamp = strtotime($value);

        if ($timestamp !== false) {
            $seconds = $timestamp - time();

            return max(0, $seconds);
        }

        return null;
    }

    /**
     * Extract headers from response.
     *
     * @param ResponseInterface $response The response
     *
     * @return array<string, string|array<string>>
     */
    private function extractHeaders(ResponseInterface $response): array
    {
        $headers = [];

        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = count($values) === 1 ? $values[0] : $values;
        }

        return $headers;
    }
}
