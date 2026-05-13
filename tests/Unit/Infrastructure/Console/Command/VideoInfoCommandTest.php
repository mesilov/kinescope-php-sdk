<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Infrastructure\Console\Command;

use Kinescope\Core\ApiClientFactory;
use Kinescope\Infrastructure\Console\Command\VideoInfoCommand;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class VideoInfoCommandTest extends TestCase
{
    public function testSuccessOutputsVideoJson(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(
            new Response(200, ['Content-Type' => 'application/json'], $this->fakeVideoResponse())
        );

        $tester = $this->registerAndGetTester($this->buildCommand($httpClient));
        $exitCode = $tester->execute(['video-id' => 'test-video-id', '--api-key' => 'test-key']);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $decoded = json_decode($tester->getDisplay(), true);
        $this->assertSame('test-video-id', $decoded['id']);
        $this->assertSame('Test Video', $decoded['title']);
    }

    public function testFailsWhenApiKeyIsEmpty(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $tester = $this->registerAndGetTester($this->buildCommand($httpClient));

        $exitCode = $tester->execute(['video-id' => 'test-video-id', '--api-key' => '']);

        $this->assertSame(Command::FAILURE, $exitCode);
    }

    public function testFailsWhenNoApiKeyAndEnvNotSet(): void
    {
        // phpunit.xml sets KINESCOPE_API_KEY='' so buildFromEnvironment() throws InvalidArgumentException
        $httpClient = $this->createMock(ClientInterface::class);
        $tester = $this->registerAndGetTester($this->buildCommand($httpClient));

        $exitCode = $tester->execute(['video-id' => 'test-video-id']);

        $this->assertSame(Command::FAILURE, $exitCode);
    }

    public function testFailsWhenVideoNotFound(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(
            new Response(404, ['Content-Type' => 'application/json'], '{"message":"Not Found"}')
        );

        $tester = $this->registerAndGetTester($this->buildCommand($httpClient));
        $exitCode = $tester->execute(['video-id' => 'nonexistent-id', '--api-key' => 'test-key']);

        $this->assertSame(Command::FAILURE, $exitCode);
    }

    public function testFailsOnApiError(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(
            new Response(500, ['Content-Type' => 'application/json'], '{"message":"Server Error"}')
        );

        $tester = $this->registerAndGetTester($this->buildCommand($httpClient));
        $exitCode = $tester->execute(['video-id' => 'test-video-id', '--api-key' => 'test-key']);

        $this->assertSame(Command::FAILURE, $exitCode);
    }

    private function buildCommand(ClientInterface $httpClient): VideoInfoCommand
    {
        $psr17 = new Psr17Factory();
        $apiClientFactory = ApiClientFactory::create()
            ->withHttpClient($httpClient)
            ->withRequestFactory($psr17)
            ->withStreamFactory($psr17);

        return new VideoInfoCommand($apiClientFactory, new NullLogger());
    }

    private function registerAndGetTester(VideoInfoCommand $command): CommandTester
    {
        // Commands with #[Argument]/#[Option] on __invoke need to be registered
        // with an Application so that the input definition is fully resolved.
        $app = new Application('test', '1.0.0');
        $app->addCommand($command);

        return new CommandTester($command);
    }

    private function fakeVideoResponse(): string
    {
        return (string) json_encode([
            'data' => [
                'id' => 'test-video-id',
                'title' => 'Test Video',
                'status' => 'done',
                'duration' => 120,
                'created_at' => '2024-01-01T00:00:00+00:00',
                'updated_at' => '2024-01-01T00:00:00+00:00',
            ],
        ]);
    }
}
