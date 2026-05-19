<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Core;

use Carbon\CarbonImmutable;
use Kinescope\Core\ResponseHandler;
use Kinescope\Exception\RateLimitException;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ResponseHandlerTest extends TestCase
{
    public function testRateLimitExceptionKeepsNumericRetryAfter(): void
    {
        $response = new Response(
            429,
            ['Retry-After' => '60', 'Content-Type' => 'application/json'],
            '{"message":"Too many requests"}',
        );

        try {
            new ResponseHandler()->handle($response);
            self::fail('Expected rate limit exception.');
        } catch (RateLimitException $exception) {
            self::assertSame(60, $exception->getRetryAfter());
            self::assertTrue($exception->hasRetryAfter());
            self::assertTrue($exception->hasResponse());
        }
    }

    public function testRateLimitExceptionParsesHttpDateRetryAfter(): void
    {
        $retryAt = CarbonImmutable::now('UTC')->addSeconds(90);
        $response = new Response(
            429,
            ['Retry-After' => $retryAt->format('D, d M Y H:i:s \G\M\T')],
            '{"message":"Too many requests"}',
        );

        try {
            new ResponseHandler()->handle($response);
            self::fail('Expected rate limit exception.');
        } catch (RateLimitException $exception) {
            self::assertNotNull($exception->getRetryAfter());
            self::assertGreaterThanOrEqual(0, $exception->getRetryAfter());
            self::assertLessThanOrEqual(90, $exception->getRetryAfter());
        }
    }
}
