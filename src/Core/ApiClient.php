<?php

declare(strict_types=1);

namespace Kinescope\Core;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Kinescope\Contracts\ApiClientInterface;
use Kinescope\Enum\HttpMethod;
use Kinescope\Exception\NetworkException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * HTTP client for Kinescope API.
 *
 * This client handles:
 * - Authentication via bearer token
 * - JSON encoding/decoding
 * - Rate limit handling
 */
final class ApiClient implements ApiClientInterface
{
    /**
     * Default base URL for the API.
     */
    public const string DEFAULT_BASE_URL = 'https://api.kinescope.io';

    /**
     * Default request timeout in seconds.
     */
    public const int DEFAULT_TIMEOUT = 30;

    private readonly ClientInterface $httpClient;

    private readonly RequestFactoryInterface $requestFactory;

    private readonly StreamFactoryInterface $streamFactory;

    private readonly ResponseHandler $responseHandler;

    private readonly JsonDecoder $jsonDecoder;

    private readonly LoggerInterface $logger;

    /**
     * Create a new API client.
     *
     * @param Credentials $credentials API credentials
     * @param string $baseUrl Base URL for API
     * @param ClientInterface|null $httpClient PSR-18 HTTP client
     * @param RequestFactoryInterface|null $requestFactory PSR-17 request factory
     * @param StreamFactoryInterface|null $streamFactory PSR-17 stream factory
     * @param LoggerInterface|null $logger PSR-3 logger
     */
    public function __construct(
        private readonly Credentials $credentials,
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        $this->responseHandler = new ResponseHandler();
        $this->jsonDecoder = new JsonDecoder();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request(HttpMethod::GET, $endpoint, ['query' => $query]);
    }

    /**
     * {@inheritDoc}
     */
    public function post(string $endpoint, array $data = [], array $query = []): array
    {
        return $this->request(HttpMethod::POST, $endpoint, [
            'query' => $query,
            'body' => $data,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $endpoint, array $data = [], array $query = []): array
    {
        return $this->request(HttpMethod::PUT, $endpoint, [
            'query' => $query,
            'body' => $data,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function patch(string $endpoint, array $data = [], array $query = []): array
    {
        return $this->request(HttpMethod::PATCH, $endpoint, [
            'query' => $query,
            'body' => $data,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $endpoint, array $query = []): array
    {
        return $this->request(HttpMethod::DELETE, $endpoint, ['query' => $query]);
    }

    /**
     * {@inheritDoc}
     */
    public function request(HttpMethod $method, string $endpoint, array $options = []): array
    {
        $request = $this->buildRequest($method, $endpoint, $options);

        try {
            $this->logger->debug('Sending API request', [
                'method' => $request->getMethod(),
                'url' => (string) $request->getUri(),
            ]);

            $response = $this->httpClient->sendRequest($request);

            return $this->responseHandler->handle($response);
        } catch (ClientExceptionInterface $e) {
            throw NetworkException::fromClientException(
                $e,
                (string) $request->getUri()
            );
        }
    }

    /**
     * Build a PSR-7 request.
     *
     * @param HttpMethod $method HTTP method
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $options Request options
     *
     * @return RequestInterface
     */
    private function buildRequest(HttpMethod $method, string $endpoint, array $options): RequestInterface
    {
        $url = $this->buildUrl($endpoint, $options['query'] ?? []);
        $request = $this->requestFactory->createRequest($method->value, $url);

        // Add authentication header
        $request = $request->withHeader('Authorization', $this->credentials->getAuthorizationHeader());

        // Add common headers
        $request = $request->withHeader('Accept', 'application/json');
        $request = $request->withHeader('Content-Type', 'application/json');

        // Add body for methods that support it
        if ($method->hasBody() && isset($options['body']) && $options['body'] !== []) {
            $body = $this->jsonDecoder->encode($options['body']);
            $stream = $this->streamFactory->createStream($body);
            $request = $request->withBody($stream);
        }

        return $request;
    }

    /**
     * Build the full URL with query parameters.
     *
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $query Query parameters
     *
     * @return string Full URL
     */
    private function buildUrl(string $endpoint, array $query): string
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        if ($query !== []) {
            $filteredQuery = array_filter($query, static fn ($value) => $value !== null);

            if ($filteredQuery !== []) {
                $url .= '?' . http_build_query($filteredQuery);
            }
        }

        return $url;
    }
}
