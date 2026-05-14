<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit;

use Kinescope\Contracts\ApiClientInterface;
use Kinescope\Enum\HttpMethod;
use Kinescope\Exception\KinescopeException;
use RuntimeException;

final class FakeApiClient implements ApiClientInterface
{
    /**
     * @var list<array<string, mixed>|KinescopeException>
     */
    private array $outcomes = [];

    /**
     * @var list<array{method: HttpMethod, endpoint: string, query: array<string, mixed>, body: array<string, mixed>}>
     */
    private array $requests = [];

    /**
     * @param array<string, mixed> $response
     */
    public function queueResponse(array $response): self
    {
        $this->outcomes[] = $response;

        return $this;
    }

    public function queueException(KinescopeException $exception): self
    {
        $this->outcomes[] = $exception;

        return $this;
    }

    /**
     * @return list<array{method: HttpMethod, endpoint: string, query: array<string, mixed>, body: array<string, mixed>}>
     */
    public function requests(): array
    {
        return $this->requests;
    }

    /**
     * @return array{method: HttpMethod, endpoint: string, query: array<string, mixed>, body: array<string, mixed>}
     */
    public function requestAt(int $index): array
    {
        return $this->requests[$index] ?? throw new RuntimeException(
            sprintf('No request recorded at index %d.', $index)
        );
    }

    public function requestCount(): int
    {
        return count($this->requests);
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request(HttpMethod::GET, $endpoint, ['query' => $query]);
    }

    public function post(string $endpoint, array $data = [], array $query = []): array
    {
        return $this->request(HttpMethod::POST, $endpoint, [
            'body' => $data,
            'query' => $query,
        ]);
    }

    public function put(string $endpoint, array $data = [], array $query = []): array
    {
        return $this->request(HttpMethod::PUT, $endpoint, [
            'body' => $data,
            'query' => $query,
        ]);
    }

    public function patch(string $endpoint, array $data = [], array $query = []): array
    {
        return $this->request(HttpMethod::PATCH, $endpoint, [
            'body' => $data,
            'query' => $query,
        ]);
    }

    public function delete(string $endpoint, array $query = []): array
    {
        return $this->request(HttpMethod::DELETE, $endpoint, ['query' => $query]);
    }

    public function request(HttpMethod $method, string $endpoint, array $options = []): array
    {
        $query = $options['query'] ?? [];
        $body = $options['body'] ?? [];

        if (! is_array($query) || ! is_array($body)) {
            throw new RuntimeException('FakeApiClient expects array query and body options.');
        }

        $this->requests[] = [
            'method' => $method,
            'endpoint' => $endpoint,
            'query' => $query,
            'body' => $body,
        ];

        if ($this->outcomes === []) {
            throw new RuntimeException(sprintf(
                'Unexpected %s request to %s.',
                $method->value,
                $endpoint
            ));
        }

        $outcome = array_shift($this->outcomes);

        if ($outcome instanceof KinescopeException) {
            throw $outcome;
        }

        return $outcome;
    }
}
