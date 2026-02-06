<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Core;

use InvalidArgumentException;
use Kinescope\Core\Credentials;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Credentials value object.
 */
class CredentialsTest extends TestCase
{
    public function testFromStringCreatesCredentials(): void
    {
        $credentials = Credentials::fromString('test-api-key');

        $this->assertInstanceOf(Credentials::class, $credentials);
        $this->assertEquals('test-api-key', $credentials->apiKey);
    }

    public function testFromStringTrimsWhitespace(): void
    {
        $credentials = Credentials::fromString(' api-key-with-spaces  ');

        $this->assertEquals('api-key-with-spaces', $credentials->apiKey);
    }

    public function testFromStringThrowsOnEmptyKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API key cannot be empty');

        Credentials::fromString('');
    }

    public function testFromStringThrowsOnWhitespaceOnlyKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API key cannot be empty');

        Credentials::fromString('   ');
    }

    public function testFromEnvironmentCreatesCredentials(): void
    {
        $envVar = 'TEST_KINESCOPE_API_KEY';
        putenv("{$envVar}=env-api-key");

        try {
            $credentials = Credentials::fromEnvironment($envVar);
            $this->assertEquals('env-api-key', $credentials->apiKey);
        } finally {
            putenv($envVar);
        }
    }

    public function testFromEnvironmentThrowsOnMissingEnvVar(): void
    {
        $envVar = 'NONEXISTENT_API_KEY_VAR';
        putenv($envVar); // Ensure it's not set

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Environment variable {$envVar} is not set");

        Credentials::fromEnvironment($envVar);
    }

    public function testTryFromEnvironmentReturnsCredentialsWhenSet(): void
    {
        $envVar = 'TEST_KINESCOPE_API_KEY';
        putenv("{$envVar}=try-env-api-key");

        try {
            $credentials = Credentials::tryFromEnvironment($envVar);
            $this->assertNotNull($credentials);
            $this->assertEquals('try-env-api-key', $credentials->apiKey);
        } finally {
            putenv($envVar);
        }
    }

    public function testTryFromEnvironmentReturnsNullWhenNotSet(): void
    {
        $envVar = 'NONEXISTENT_API_KEY_VAR';
        putenv($envVar); // Ensure it's not set

        $credentials = Credentials::tryFromEnvironment($envVar);
        $this->assertNull($credentials);
    }

    public function testGetAuthorizationHeaderFormatsCorrectly(): void
    {
        $credentials = Credentials::fromString('my-secret-key');

        $this->assertEquals('Bearer my-secret-key', $credentials->getAuthorizationHeader());
    }

    public function testGetMaskedApiKeyMasksLongKey(): void
    {
        $credentials = Credentials::fromString('abcd1234567890wxyz');
        $masked = $credentials->getMaskedApiKey();

        $this->assertEquals('abcd**********wxyz', $masked);
        $this->assertStringStartsWith('abcd', $masked);
        $this->assertStringEndsWith('wxyz', $masked);
    }

    public function testGetMaskedApiKeyMasksShortKey(): void
    {
        $credentials = Credentials::fromString('abcd');
        $masked = $credentials->getMaskedApiKey();

        $this->assertEquals('****', $masked);
    }

    public function testGetMaskedApiKeyMasksExactlyEightCharKey(): void
    {
        $credentials = Credentials::fromString('12345678');
        $masked = $credentials->getMaskedApiKey();

        $this->assertEquals('********', $masked);
    }

    public function testSerializeRedactsApiKey(): void
    {
        $credentials = Credentials::fromString('secret-key');

        $serialized = $credentials->__serialize();

        $this->assertArrayHasKey('apiKey', $serialized);
        $this->assertEquals('[REDACTED]', $serialized['apiKey']);
    }

    public function testDebugInfoMasksApiKey(): void
    {
        $credentials = Credentials::fromString('debug-secret-key-here');
        $debugInfo = $credentials->__debugInfo();

        $this->assertArrayHasKey('apiKey', $debugInfo);
        $this->assertStringContainsString('****', $debugInfo['apiKey']);
        $this->assertStringNotContainsString('debug-secret-key-here', $debugInfo['apiKey']);
    }

    public function testDefaultEnvVarConstant(): void
    {
        $this->assertEquals('KINESCOPE_API_KEY', Credentials::DEFAULT_ENV_VAR);
    }
}
