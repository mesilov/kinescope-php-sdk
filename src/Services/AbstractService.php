<?php

declare(strict_types=1);

namespace Kinescope\Services;

use Kinescope\Contracts\ApiClientInterface;
use Kinescope\Contracts\ServiceInterface;

/**
 * Abstract base class for all Kinescope API services.
 *
 * Provides common functionality for interacting with API,
 * including request building and response handling.
 */
abstract class AbstractService implements ServiceInterface
{
    /**
     * Create a new service instance.
     *
     * @param ApiClientInterface $apiClient The API client for making requests
     */
    public function __construct(
        protected readonly ApiClientInterface $apiClient
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getApiClient(): ApiClientInterface
    {
        return $this->apiClient;
    }

    /**
     * Build pagination query parameters.
     *
     * @param int|null $page Page number (1-indexed)
     * @param int|null $perPage Items per page
     *
     * @return array<string, int>
     */
    protected function buildPaginationQuery(?int $page, ?int $perPage): array
    {
        $query = [];

        if ($page !== null) {
            $query['page'] = $page;
        }

        if ($perPage !== null) {
            $query['per_page'] = $perPage;
        }

        return $query;
    }

    /**
     * Build filter query parameters.
     *
     * Removes null values from filters array.
     *
     * @param array<string, mixed> $filters Filter parameters
     *
     * @return array<string, mixed>
     */
    protected function buildFilterQuery(array $filters): array
    {
        return array_filter($filters, static fn ($value): bool => $value !== null);
    }

    /**
     * Merge multiple query parameter arrays.
     *
     * @param array<string, mixed> ...$queries Query parameter arrays to merge
     *
     * @return array<string, mixed>
     */
    protected function mergeQueries(array ...$queries): array
    {
        return array_merge(...$queries);
    }

    /**
     * Extract data from a single-item response.
     *
     * @param array<string, mixed> $response API response
     *
     * @return array<string, mixed>
     */
    protected function extractData(array $response): array
    {
        return $response['data'] ?? $response;
    }

    /**
     * Build endpoint URL with path parameters.
     *
     * @param string $template URL template with placeholders (e.g., "/v1/videos/{video_id}")
     * @param array<string, string> $params Path parameters
     *
     * @return string
     */
    protected function buildEndpoint(string $template, array $params = []): string
    {
        $endpoint = $template;

        foreach ($params as $key => $value) {
            $endpoint = str_replace('{' . $key . '}', $value, $endpoint);
        }

        return $endpoint;
    }
}
