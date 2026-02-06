<?php

declare(strict_types=1);

namespace Kinescope\Tests\Integration\Core;

use Kinescope\Contracts\ApiClientInterface;
use Kinescope\Core\ApiClientFactory;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for ApiClientFactory.
 *
 * These tests verify that the factory creates working clients
 * that can communicate with the real Kinescope API.
 *
 * @group integration
 */
class ApiClientFactoryTest extends TestCase
{
    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = getenv('KINESCOPE_API_KEY');

        if ($apiKey === false || $apiKey === '') {
            $this->markTestSkipped('KINESCOPE_API_KEY environment variable not set');
        }

        $this->apiKey = $apiKey;
    }

    public function testBuildFromEnvironmentCreatesWorkingClient(): void
    {
        $client = ApiClientFactory::create()
            ->buildFromEnvironment();

        $this->assertInstanceOf(ApiClientInterface::class, $client);

        $response = $client->get('/v1/projects');
        $this->assertArrayHasKey('data', $response);
    }

    public function testBuildWithApiKeyCreatesWorkingClient(): void
    {
        $client = ApiClientFactory::create()
            ->withApiKey($this->apiKey)
            ->build();

        $this->assertInstanceOf(ApiClientInterface::class, $client);

        $response = $client->get('/v1/projects');
        $this->assertArrayHasKey('data', $response);
    }

    public function testFactoryWithCustomSettings(): void
    {
        $client = ApiClientFactory::create()
            ->withApiKey($this->apiKey)
            ->withTimeout(60)
            ->build();

        $this->assertInstanceOf(ApiClientInterface::class, $client);

        $response = $client->get('/v1/projects');
        $this->assertArrayHasKey('data', $response);
    }

    public function testClientCanListProjects(): void
    {
        $client = ApiClientFactory::create()
            ->withApiKey($this->apiKey)
            ->build();

        $response = $client->get('/v1/projects');

        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }
}
