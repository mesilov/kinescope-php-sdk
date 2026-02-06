<?php

declare(strict_types=1);

namespace Kinescope\Contracts;

use Kinescope\Enum\HttpMethod;

/**
 * Interface for Kinescope API HTTP client.
 *
 * Provides methods for making HTTP requests to Kinescope API.
 * Implementations should handle authentication and error handling.
 */
interface ApiClientInterface
{
    /**
     * Make a GET request to API.
     *
     * @param string $endpoint API endpoint (e.g., '/v1/videos')
     * @param array<string, mixed> $query Query parameters
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     * @throws \Kinescope\Exception\NetworkException On network errors
     *
     * @return array<string, mixed> Decoded JSON response
     */
    public function get(string $endpoint, array $query = []): array;

    /**
     * Make a POST request to API.
     *
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $data Request body data
     * @param array<string, mixed> $query Query parameters
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     * @throws \Kinescope\Exception\NetworkException On network errors
     *
     * @return array<string, mixed> Decoded JSON response
     */
    public function post(string $endpoint, array $data = [], array $query = []): array;

    /**
     * Make a PUT request to API.
     *
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $data Request body data
     * @param array<string, mixed> $query Query parameters
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     * @throws \Kinescope\Exception\NetworkException On network errors
     *
     * @return array<string, mixed> Decoded JSON response
     */
    public function put(string $endpoint, array $data = [], array $query = []): array;

    /**
     * Make a PATCH request to API.
     *
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $data Request body data
     * @param array<string, mixed> $query Query parameters
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     * @throws \Kinescope\Exception\NetworkException On network errors
     *
     * @return array<string, mixed> Decoded JSON response
     */
    public function patch(string $endpoint, array $data = [], array $query = []): array;

    /**
     * Make a DELETE request to API.
     *
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $query Query parameters
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     * @throws \Kinescope\Exception\NetworkException On network errors
     *
     * @return array<string, mixed> Decoded JSON response
     */
    public function delete(string $endpoint, array $query = []): array;

    /**
     * Make a generic request to API.
     *
     * @param HttpMethod $method HTTP method
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $options Request options (query, body, headers)
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     * @throws \Kinescope\Exception\NetworkException On network errors
     *
     * @return array<string, mixed> Decoded JSON response
     */
    public function request(HttpMethod $method, string $endpoint, array $options = []): array;
}
