<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Exception;

use Kinescope\Exception\AuthenticationException;
use Kinescope\Exception\BadRequestException;
use Kinescope\Exception\ForbiddenException;
use Kinescope\Exception\KinescopeException;
use Kinescope\Exception\NetworkException;
use Kinescope\Exception\NotFoundException;
use Kinescope\Exception\PaymentRequiredException;
use Kinescope\Exception\RateLimitException;
use Kinescope\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for exception hierarchy.
 */
class ExceptionsTest extends TestCase
{
    // =========================================================================
    // KinescopeException Tests
    // =========================================================================

    public function testKinescopeExceptionBasic(): void
    {
        $exception = new KinescopeException('Test message', 500);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals(500, $exception->getStatusCode());
    }

    public function testKinescopeExceptionWithResponse(): void
    {
        $exception = KinescopeException::withResponse(
            'Error message',
            400,
            '{"error": "details"}',
            ['Content-Type' => 'application/json']
        );

        $this->assertTrue($exception->hasResponse());
        $this->assertEquals('{"error": "details"}', $exception->getResponseBody());
        $this->assertArrayHasKey('Content-Type', $exception->getResponseHeaders());
    }

    public function testKinescopeExceptionGetDecodedResponse(): void
    {
        $exception = KinescopeException::withResponse(
            'Error',
            400,
            '{"error": "test", "code": 123}'
        );

        $decoded = $exception->getDecodedResponse();

        $this->assertIsArray($decoded);
        $this->assertEquals('test', $decoded['error']);
        $this->assertEquals(123, $decoded['code']);
    }

    public function testKinescopeExceptionGetDecodedResponseInvalidJson(): void
    {
        $exception = KinescopeException::withResponse('Error', 400, 'not json');

        $this->assertNull($exception->getDecodedResponse());
    }

    public function testKinescopeExceptionHasResponseFalse(): void
    {
        $exception = new KinescopeException('Error');

        $this->assertFalse($exception->hasResponse());
        $this->assertNull($exception->getResponseBody());
        $this->assertEquals([], $exception->getResponseHeaders());
    }

    // =========================================================================
    // HTTP Exception Tests
    // =========================================================================

    public function testBadRequestException(): void
    {
        $exception = new BadRequestException();

        $this->assertEquals('Bad Request', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertInstanceOf(KinescopeException::class, $exception);
    }

    public function testBadRequestExceptionCustomMessage(): void
    {
        $exception = new BadRequestException('Invalid parameter: title');

        $this->assertEquals('Invalid parameter: title', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
    }

    public function testAuthenticationException(): void
    {
        $exception = new AuthenticationException();

        $this->assertEquals('Unauthorized', $exception->getMessage());
        $this->assertEquals(401, $exception->getCode());
        $this->assertInstanceOf(KinescopeException::class, $exception);
    }

    public function testPaymentRequiredException(): void
    {
        $exception = new PaymentRequiredException();

        $this->assertEquals('Payment Required', $exception->getMessage());
        $this->assertEquals(402, $exception->getCode());
        $this->assertInstanceOf(KinescopeException::class, $exception);
    }

    public function testForbiddenException(): void
    {
        $exception = new ForbiddenException();

        $this->assertEquals('Forbidden', $exception->getMessage());
        $this->assertEquals(403, $exception->getCode());
        $this->assertInstanceOf(KinescopeException::class, $exception);
    }

    public function testNotFoundException(): void
    {
        $exception = new NotFoundException();

        $this->assertEquals('Not Found', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
        $this->assertInstanceOf(KinescopeException::class, $exception);
    }

    // =========================================================================
    // ValidationException Tests
    // =========================================================================

    public function testValidationException(): void
    {
        $exception = new ValidationException();

        $this->assertEquals('Validation Error', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
        $this->assertInstanceOf(KinescopeException::class, $exception);
    }

    public function testValidationExceptionWithErrors(): void
    {
        $errors = [
            'title' => ['Title is required'],
            'description' => ['Description must be at least 10 characters'],
        ];

        $exception = ValidationException::withErrors($errors, 'Validation failed');

        $this->assertTrue($exception->hasErrors());
        $this->assertEquals($errors, $exception->getErrors());
        $this->assertEquals('Validation failed', $exception->getMessage());
    }

    public function testValidationExceptionGetFieldErrors(): void
    {
        $errors = [
            'title' => ['Required', 'Too short'],
        ];

        $exception = ValidationException::withErrors($errors);

        $this->assertEquals(['Required', 'Too short'], $exception->getFieldErrors('title'));
        $this->assertEquals([], $exception->getFieldErrors('nonexistent'));
    }

    public function testValidationExceptionHasErrorsFalse(): void
    {
        $exception = new ValidationException();

        $this->assertFalse($exception->hasErrors());
        $this->assertEquals([], $exception->getErrors());
    }

    // =========================================================================
    // RateLimitException Tests
    // =========================================================================

    public function testRateLimitException(): void
    {
        $exception = new RateLimitException();

        $this->assertEquals('Too Many Requests', $exception->getMessage());
        $this->assertEquals(429, $exception->getCode());
        $this->assertInstanceOf(KinescopeException::class, $exception);
    }

    public function testRateLimitExceptionWithRetryAfter(): void
    {
        $exception = RateLimitException::withRetryAfter(60);

        $this->assertTrue($exception->hasRetryAfter());
        $this->assertEquals(60, $exception->getRetryAfter());
    }

    public function testRateLimitExceptionWithoutRetryAfter(): void
    {
        $exception = new RateLimitException();

        $this->assertFalse($exception->hasRetryAfter());
        $this->assertNull($exception->getRetryAfter());
    }

    // =========================================================================
    // NetworkException Tests
    // =========================================================================

    public function testNetworkException(): void
    {
        $exception = new NetworkException('Connection failed');

        $this->assertEquals('Connection failed', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertInstanceOf(KinescopeException::class, $exception);
    }

    public function testNetworkExceptionConnectionTimeout(): void
    {
        $exception = NetworkException::connectionTimeout('https://api.example.com', 30);

        $this->assertStringContainsString('timeout', strtolower($exception->getMessage()));
        $this->assertStringContainsString('30', $exception->getMessage());
        $this->assertEquals('https://api.example.com', $exception->getUrl());
        $this->assertEquals(30, $exception->getTimeout());
        $this->assertTrue($exception->isTimeout());
    }

    public function testNetworkExceptionDnsResolutionFailed(): void
    {
        $exception = NetworkException::dnsResolutionFailed('api.example.com');

        $this->assertStringContainsString('DNS', $exception->getMessage());
        $this->assertStringContainsString('api.example.com', $exception->getMessage());
    }

    public function testNetworkExceptionConnectionRefused(): void
    {
        $exception = NetworkException::connectionRefused('https://api.example.com');

        $this->assertStringContainsString('refused', strtolower($exception->getMessage()));
        $this->assertEquals('https://api.example.com', $exception->getUrl());
    }

    public function testNetworkExceptionSslError(): void
    {
        $exception = NetworkException::sslError(
            'https://api.example.com',
            'Certificate expired'
        );

        $this->assertStringContainsString('SSL', $exception->getMessage());
        $this->assertStringContainsString('Certificate expired', $exception->getMessage());
        $this->assertEquals('https://api.example.com', $exception->getUrl());
    }

    public function testNetworkExceptionFromClientException(): void
    {
        $clientException = new RuntimeException('HTTP client failed');

        $exception = NetworkException::fromClientException(
            $clientException,
            'https://api.example.com'
        );

        $this->assertStringContainsString('HTTP client', $exception->getMessage());
        $this->assertEquals('https://api.example.com', $exception->getUrl());
        $this->assertSame($clientException, $exception->getPrevious());
    }

    public function testNetworkExceptionIsTimeoutFalse(): void
    {
        $exception = new NetworkException('Generic network error');

        $this->assertFalse($exception->isTimeout());
        $this->assertNull($exception->getTimeout());
        $this->assertNull($exception->getUrl());
    }

    // =========================================================================
    // Hierarchy Tests
    // =========================================================================

    public function testAllExceptionsExtendKinescopeException(): void
    {
        $exceptions = [
            new BadRequestException(),
            new AuthenticationException(),
            new PaymentRequiredException(),
            new ForbiddenException(),
            new NotFoundException(),
            new ValidationException(),
            new RateLimitException(),
            new NetworkException(),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(
                KinescopeException::class,
                $exception,
                get_class($exception) . ' should extend KinescopeException'
            );
        }
    }
}
