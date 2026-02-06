<?php

declare(strict_types=1);

namespace Kinescope\Tests\Integration\Core;

use Kinescope\Contracts\ApiClientInterface;
use Kinescope\Core\ApiClientFactory;
use Kinescope\Core\Credentials;
use Kinescope\Enum\HttpMethod;
use Kinescope\Exception\AuthenticationException;
use Kinescope\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Integration tests for ApiClient.
 *
 * These tests verify that ApiClient works correctly
 * against the real Kinescope API.
 *
 * @group integration
 */
class ApiClientTest extends TestCase
{
    private ApiClientInterface $client;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = getenv('KINESCOPE_API_KEY');

        if ($apiKey === false || $apiKey === '') {
            $this->markTestSkipped('KINESCOPE_API_KEY environment variable not set');
        }

        $this->client = ApiClientFactory::create()
            ->withCredentials(Credentials::fromString($apiKey))
            ->build();
    }

    public function testGetReturnsProjectsList(): void
    {
        $response = $this->client->get('/v1/projects');

        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }

    public function testGetWithQueryParameters(): void
    {
        $response = $this->client->get('/v1/projects', ['per_page' => 1]);

        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
        $this->assertLessThanOrEqual(1, count($response['data']));

        $this->assertArrayHasKey('meta', $response);
        $this->assertIsArray($response['meta']);
    }

    public function testPostCreatesAndDeletesProject(): void
    {
        $projectName = 'SDK Integration Test ' . uniqid();

        $createResponse = $this->client->post('/v1/projects', [
            'name' => $projectName,
        ]);

        $this->assertArrayHasKey('data', $createResponse);
        $this->assertIsArray($createResponse['data']);
        $this->assertArrayHasKey('id', $createResponse['data']);
        $this->assertSame($projectName, $createResponse['data']['name']);

        $projectId = $createResponse['data']['id'];

        try {
            $deleteResponse = $this->client->delete('/v1/projects/' . $projectId);
            $this->assertIsArray($deleteResponse);
        } catch (Throwable $e) {
            $this->fail('Failed to delete project ' . $projectId . ': ' . $e->getMessage());
        }
    }

    public function testGetRetrievesProjectById(): void
    {
        $projectName = 'SDK Get Test ' . uniqid();
        $createResponse = $this->client->post('/v1/projects', [
            'name' => $projectName,
        ]);

        $projectId = $createResponse['data']['id'];

        try {
            $getResponse = $this->client->get('/v1/projects/' . $projectId);

            $this->assertArrayHasKey('data', $getResponse);
            $this->assertSame($projectId, $getResponse['data']['id']);
            $this->assertSame($projectName, $getResponse['data']['name']);
        } finally {
            $this->client->delete('/v1/projects/' . $projectId);
        }
    }

    public function testGetNonExistentResourceThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $this->client->get('/v1/projects/00000000-0000-0000-0000-000000000000');
    }

    public function testAuthenticationExceptionWithInvalidToken(): void
    {
        $this->expectException(AuthenticationException::class);

        $invalidClient = ApiClientFactory::create()
            ->withCredentials(Credentials::fromString('invalid-api-key'))
            ->build();
        $invalidClient->get('/v1/projects');
    }

    public function testRequestMethodWithHttpMethodEnum(): void
    {
        $response = $this->client->request(HttpMethod::GET, '/v1/projects');

        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }
}
