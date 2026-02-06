<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Core;

use Kinescope\Core\JsonDecoder;
use Kinescope\Exception\KinescopeException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for JsonDecoder.
 */
class JsonDecoderTest extends TestCase
{
    private JsonDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new JsonDecoder();
    }

    public function testDecodeValidJson(): void
    {
        $json = '{"key": "value", "number": 123}';
        $result = $this->decoder->decode($json);

        $this->assertEquals(['key' => 'value', 'number' => 123], $result);
    }

    public function testDecodeNestedJson(): void
    {
        $json = '{"data": {"items": [1, 2, 3], "nested": {"deep": true}}}';
        $result = $this->decoder->decode($json);

        $this->assertEquals([
            'data' => [
                'items' => [1, 2, 3],
                'nested' => ['deep' => true],
            ],
        ], $result);
    }

    public function testDecodeEmptyStringReturnsEmptyArray(): void
    {
        $result = $this->decoder->decode('');
        $this->assertEquals([], $result);
    }

    public function testDecodeWhitespaceOnlyReturnsEmptyArray(): void
    {
        $result = $this->decoder->decode('   ');
        $this->assertEquals([], $result);
    }

    public function testDecodeInvalidJsonThrows(): void
    {
        $this->expectException(KinescopeException::class);
        $this->expectExceptionMessage('Failed to decode JSON response');

        $this->decoder->decode('invalid json {');
    }

    public function testDecodeNonObjectThrows(): void
    {
        $this->expectException(KinescopeException::class);
        $this->expectExceptionMessage('Expected JSON object or array');

        $this->decoder->decode('"just a string"');
    }

    public function testDecodeOrNullReturnsArrayOnValidJson(): void
    {
        $json = '{"valid": true}';
        $result = $this->decoder->decodeOrNull($json);

        $this->assertEquals(['valid' => true], $result);
    }

    public function testDecodeOrNullReturnsNullOnInvalidJson(): void
    {
        $result = $this->decoder->decodeOrNull('invalid json');

        $this->assertNull($result);
    }

    public function testEncodeArray(): void
    {
        $data = ['key' => 'value', 'items' => [1, 2, 3]];
        $result = $this->decoder->encode($data);

        $this->assertJson('{"key":"value","items":[1,2,3]}', $result);
    }

    public function testEncodePreservesUnicode(): void
    {
        $data = ['message' => 'Привет мир'];
        $result = $this->decoder->encode($data);

        $this->assertStringContainsString('Привет мир', $result);
    }

    public function testExtractPathSimple(): void
    {
        $data = ['key' => 'value'];
        $result = $this->decoder->extractPath($data, 'key');

        $this->assertEquals('value', $result);
    }

    public function testExtractPathNested(): void
    {
        $data = ['data' => ['items' => ['first', 'second']]];
        $result = $this->decoder->extractPath($data, 'data.items.0');

        $this->assertEquals('first', $result);
    }

    public function testExtractPathReturnsDefaultOnMissing(): void
    {
        $data = ['key' => 'value'];
        $result = $this->decoder->extractPath($data, 'nonexistent', 'default');

        $this->assertEquals('default', $result);
    }

    public function testExtractPathReturnsDefaultOnPartialPath(): void
    {
        $data = ['a' => 'scalar'];
        $result = $this->decoder->extractPath($data, 'a.b.c', 'default');

        $this->assertEquals('default', $result);
    }

    public function testExtractPathReturnsNullByDefault(): void
    {
        $data = ['key' => 'value'];
        $result = $this->decoder->extractPath($data, 'nonexistent');

        $this->assertNull($result);
    }

    public function testIsErrorResponseWithError(): void
    {
        $data = ['error' => 'Something went wrong'];

        $this->assertTrue($this->decoder->isErrorResponse($data));
    }

    public function testIsErrorResponseWithErrors(): void
    {
        $data = ['errors' => ['field' => ['Required']]];

        $this->assertTrue($this->decoder->isErrorResponse($data));
    }

    public function testIsErrorResponseWithSuccess(): void
    {
        $data = ['data' => ['id' => '123']];

        $this->assertFalse($this->decoder->isErrorResponse($data));
    }

    public function testExtractErrorMessageFromError(): void
    {
        $data = ['error' => 'Not found'];
        $result = $this->decoder->extractErrorMessage($data);

        $this->assertEquals('Not found', $result);
    }

    public function testExtractErrorMessageFromMessage(): void
    {
        $data = ['message' => 'Invalid request'];
        $result = $this->decoder->extractErrorMessage($data);

        $this->assertEquals('Invalid request', $result);
    }

    public function testExtractErrorMessageFromErrorsArray(): void
    {
        $data = ['errors' => ['First error', 'Second error']];
        $result = $this->decoder->extractErrorMessage($data);

        $this->assertEquals('First error', $result);
    }

    public function testExtractErrorMessageFromNestedErrors(): void
    {
        $data = ['errors' => ['field' => ['Field is required']]];
        $result = $this->decoder->extractErrorMessage($data);

        $this->assertEquals('Field is required', $result);
    }

    public function testExtractErrorMessageReturnsNullOnNoError(): void
    {
        $data = ['data' => 'success'];
        $result = $this->decoder->extractErrorMessage($data);

        $this->assertNull($result);
    }
}
