<?php

declare(strict_types=1);

namespace Kinescope\Core;

use InvalidArgumentException;
use Kinescope\Contracts\ApiClientInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Fluent builder for creating ApiClient instances.
 *
 * Provides a clean API for configuring a client with custom settings.
 *
 * @example
 * $client = ApiClientFactory::create()
 *     ->withCredentials(Credentials::fromString('api-key'))
 *     ->withTimeout(60)
 *     ->withLogger($logger)
 *     ->build();
 */
final class ApiClientFactory
{
    private ?Credentials $credentials = null;

    private string $baseUrl = ApiClient::DEFAULT_BASE_URL;

    private int $timeout = ApiClient::DEFAULT_TIMEOUT;

    private ?ClientInterface $httpClient = null;

    private ?RequestFactoryInterface $requestFactory = null;

    private ?StreamFactoryInterface $streamFactory = null;

    private ?LoggerInterface $logger = null;

    /**
     * Private constructor - use create() static method.
     */
    private function __construct()
    {
    }

    /**
     * Create a new factory instance.
     *
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Set the API credentials.
     *
     * @param Credentials $credentials The credentials to use
     *
     * @return self New instance with updated credentials
     */
    public function withCredentials(Credentials $credentials): self
    {
        $clone = clone $this;
        $clone->credentials = $credentials;

        return $clone;
    }

    /**
     * Set the API key directly (convenience method).
     *
     * @param string $apiKey The API key
     *
     * @return self New instance with updated credentials
     */
    public function withApiKey(string $apiKey): self
    {
        return $this->withCredentials(Credentials::fromString($apiKey));
    }

    /**
     * Set the base URL for the API.
     *
     * @param string $baseUrl The base URL
     *
     * @return self New instance with updated base URL
     */
    public function withBaseUrl(string $baseUrl): self
    {
        $clone = clone $this;
        $clone->baseUrl = rtrim($baseUrl, '/');

        return $clone;
    }

    /**
     * Set the request timeout.
     *
     * @param int $timeout Timeout in seconds
     *
     * @return self New instance with updated timeout
     */
    public function withTimeout(int $timeout): self
    {
        if ($timeout < 1) {
            throw new InvalidArgumentException('Timeout must be at least 1 second');
        }

        $clone = clone $this;
        $clone->timeout = $timeout;

        return $clone;
    }

    /**
     * Set a custom PSR-18 HTTP client.
     *
     * @param ClientInterface $httpClient The HTTP client
     *
     * @return self New instance with updated HTTP client
     */
    public function withHttpClient(ClientInterface $httpClient): self
    {
        $clone = clone $this;
        $clone->httpClient = $httpClient;

        return $clone;
    }

    /**
     * Set a custom PSR-17 request factory.
     *
     * @param RequestFactoryInterface $requestFactory The request factory
     *
     * @return self New instance with updated request factory
     */
    public function withRequestFactory(RequestFactoryInterface $requestFactory): self
    {
        $clone = clone $this;
        $clone->requestFactory = $requestFactory;

        return $clone;
    }

    /**
     * Set a custom PSR-17 stream factory.
     *
     * @param StreamFactoryInterface $streamFactory The stream factory
     *
     * @return self New instance with updated stream factory
     */
    public function withStreamFactory(StreamFactoryInterface $streamFactory): self
    {
        $clone = clone $this;
        $clone->streamFactory = $streamFactory;

        return $clone;
    }

    /**
     * Set a PSR-3 logger.
     *
     * @param LoggerInterface $logger The logger
     *
     * @return self New instance with updated logger
     */
    public function withLogger(LoggerInterface $logger): self
    {
        $clone = clone $this;
        $clone->logger = $logger;

        return $clone;
    }

    /**
     * Build the API client with the configured settings.
     *
     * @throws RuntimeException If credentials are not set
     *
     * @return ApiClientInterface The configured API client
     */
    public function build(): ApiClientInterface
    {
        if ($this->credentials === null) {
            throw new RuntimeException(
                'Credentials are required. Use withCredentials() or withApiKey() to set them.'
            );
        }

        return new ApiClient(
            credentials: $this->credentials,
            baseUrl: $this->baseUrl,
            httpClient: $this->httpClient,
            requestFactory: $this->requestFactory,
            streamFactory: $this->streamFactory,
            logger: $this->logger
        );
    }

    /**
     * Build an API client using credentials from environment variable.
     *
     * @param string $envVar Environment variable name (default: KINESCOPE_API_KEY)
     *
     * @return ApiClientInterface The configured API client
     */
    public function buildFromEnvironment(string $envVar = Credentials::DEFAULT_ENV_VAR): ApiClientInterface
    {
        return $this
            ->withCredentials(Credentials::fromEnvironment($envVar))
            ->build();
    }

    /**
     * Get the current configuration as an array (for debugging).
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return [
            'has_credentials' => $this->credentials !== null,
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'has_custom_http_client' => $this->httpClient !== null,
            'has_custom_request_factory' => $this->requestFactory !== null,
            'has_custom_stream_factory' => $this->streamFactory !== null,
            'has_logger' => $this->logger !== null,
        ];
    }
}
