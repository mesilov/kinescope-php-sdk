<?php

declare(strict_types=1);

namespace Kinescope\Exception;

use Throwable;

/**
 * Exception for network-related errors.
 *
 * Thrown when there are connection issues, timeouts, DNS resolution failures,
 * or other network-level problems that prevent communication with API.
 */
class NetworkException extends KinescopeException
{
    /**
     * The URL that was being accessed when error occurred.
     */
    private ?string $url = null;

    /**
     * The connection timeout in seconds (if applicable).
     */
    private ?int $timeout = null;

    /**
     * Create a new NetworkException.
     *
     * @param string $message Error message describing network issue
     * @param int $code Error code (typically 0 for network errors)
     * @param Throwable|null $previous Previous exception (usually from HTTP client)
     */
    public function __construct(
        string $message = 'Network Error',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create a network exception for a connection timeout.
     *
     * @param string $url The URL that timed out
     * @param int $timeout The timeout value in seconds
     * @param Throwable|null $previous Previous exception
     *
     * @return static
     */
    public static function connectionTimeout(
        string $url,
        int $timeout,
        ?Throwable $previous = null
    ): static {
        $message = sprintf('Connection timeout after %d seconds for URL: %s', $timeout, $url);
        /** @phpstan-ignore new.static */
        $exception = new static($message, 0, $previous);
        $exception->url = $url;
        $exception->timeout = $timeout;

        return $exception;
    }

    /**
     * Create a network exception for a DNS resolution failure.
     *
     * @param string $host The hostname that could not be resolved
     * @param Throwable|null $previous Previous exception
     *
     * @return static
     */
    public static function dnsResolutionFailed(
        string $host,
        ?Throwable $previous = null
    ): static {
        $message = sprintf('DNS resolution failed for host: %s', $host);

        /** @phpstan-ignore new.static */
        return new static($message, 0, $previous);
    }

    /**
     * Create a network exception for a connection refused error.
     *
     * @param string $url The URL that refused the connection
     * @param Throwable|null $previous Previous exception
     *
     * @return static
     */
    public static function connectionRefused(
        string $url,
        ?Throwable $previous = null
    ): static {
        $message = sprintf('Connection refused for URL: %s', $url);
        /** @phpstan-ignore new.static */
        $exception = new static($message, 0, $previous);
        $exception->url = $url;

        return $exception;
    }

    /**
     * Create a network exception for SSL/TLS errors.
     *
     * @param string $url The URL with SSL issues
     * @param string $sslError Description of SSL error
     * @param Throwable|null $previous Previous exception
     *
     * @return static
     */
    public static function sslError(
        string $url,
        string $sslError,
        ?Throwable $previous = null
    ): static {
        $message = sprintf('SSL error for URL %s: %s', $url, $sslError);
        /** @phpstan-ignore new.static */
        $exception = new static($message, 0, $previous);
        $exception->url = $url;

        return $exception;
    }

    /**
     * Create a network exception from an HTTP client exception.
     *
     * @param Throwable $clientException The original HTTP client exception
     * @param string|null $url The URL that was being accessed
     *
     * @return static
     */
    public static function fromClientException(
        Throwable $clientException,
        ?string $url = null
    ): static {
        $message = sprintf('HTTP client error: %s', $clientException->getMessage());
        /** @phpstan-ignore new.static */
        $exception = new static($message, 0, $clientException);
        $exception->url = $url;

        return $exception;
    }

    /**
     * Get the URL that was being accessed when error occurred.
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Get the timeout value in seconds.
     */
    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    /**
     * Check if this was a timeout error.
     */
    public function isTimeout(): bool
    {
        return $this->timeout !== null;
    }
}
