<?php

declare(strict_types=1);

namespace Kinescope\Core;

use JsonException;
use Kinescope\Exception\KinescopeException;

/**
 * JSON decoder for API responses.
 *
 * Handles JSON decoding with proper error handling and type checking.
 */
final class JsonDecoder
{
    /**
     * Maximum depth for JSON decoding.
     */
    private const int MAX_DEPTH = 512;

    /**
     * JSON decoding flags.
     */
    private const int DECODE_FLAGS = JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING;

    /**
     * Decode a JSON string into an associative array.
     *
     * @param string $json The JSON string to decode
     *
     * @throws KinescopeException If JSON is invalid or empty
     *
     * @return array<string, mixed> The decoded data
     */
    public function decode(string $json): array
    {
        $trimmed = trim($json);

        if ($trimmed === '') {
            return [];
        }

        try {
            $decoded = json_decode(
                $trimmed,
                true,
                self::MAX_DEPTH,
                self::DECODE_FLAGS
            );

            if (! is_array($decoded)) {
                throw new KinescopeException(
                    sprintf(
                        'Expected JSON object or array, got %s',
                        gettype($decoded)
                    )
                );
            }

            return $decoded;
        } catch (JsonException $e) {
            throw new KinescopeException(
                sprintf('Failed to decode JSON response: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Safely decode JSON, returning null on failure instead of throwing.
     *
     * @param string $json The JSON string to decode
     *
     * @return array<string, mixed>|null The decoded data or null on failure
     */
    public function decodeOrNull(string $json): ?array
    {
        try {
            return $this->decode($json);
        } catch (KinescopeException) {
            return null;
        }
    }

    /**
     * Encode data to JSON string.
     *
     * @param array<string, mixed> $data The data to encode
     *
     * @throws KinescopeException If encoding fails
     *
     * @return string The JSON string
     */
    public function encode(array $data): string
    {
        try {
            return json_encode(
                $data,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (JsonException $e) {
            throw new KinescopeException(
                sprintf('Failed to encode JSON: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Extract a nested value from decoded JSON using dot notation.
     *
     * @param array<string, mixed> $data The decoded data
     * @param string $path Dot-notation path (e.g., "data.items.0.id")
     * @param mixed $default Default value if path not found
     *
     * @return mixed The value at path or default
     */
    public function extractPath(array $data, string $path, mixed $default = null): mixed
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (! is_array($current)) {
                return $default;
            }

            if (! array_key_exists($key, $current)) {
                return $default;
            }

            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Check if JSON response indicates an error.
     *
     * @param array<string, mixed> $data The decoded data
     *
     * @return bool True if response contains an error
     */
    public function isErrorResponse(array $data): bool
    {
        return isset($data['error']) || isset($data['errors']);
    }

    /**
     * Extract error message from response.
     *
     * @param array<string, mixed> $data The decoded data
     *
     * @return string|null The error message or null
     */
    public function extractErrorMessage(array $data): ?string
    {
        if (isset($data['error']) && is_string($data['error'])) {
            return $data['error'];
        }

        if (isset($data['message']) && is_string($data['message'])) {
            return $data['message'];
        }

        if (isset($data['errors']) && is_array($data['errors'])) {
            $firstError = reset($data['errors']);

            if (is_string($firstError)) {
                return $firstError;
            }

            if (is_array($firstError) && isset($firstError[0]) && is_string($firstError[0])) {
                return $firstError[0];
            }
        }

        return null;
    }
}
