<?php

declare(strict_types=1);

namespace Kinescope\Core;

use InvalidArgumentException;

/**
 * Value object for API credentials.
 *
 * Stores the API key securely and provides methods for authentication.
 * This class is immutable - once created, credentials cannot be changed.
 *
 * @example
 * // Create from string
 * $credentials = Credentials::fromString('your-api-key');
 *
 * // Create from environment variable
 * $credentials = Credentials::fromEnvironment();
 * $credentials = Credentials::fromEnvironment('CUSTOM_API_KEY_VAR');
 *
 * // Get authorization header
 * $header = $credentials->getAuthorizationHeader();
 */
final readonly class Credentials
{
    /**
     * Default environment variable name for API key.
     */
    public const string DEFAULT_ENV_VAR = 'KINESCOPE_API_KEY';

    /**
     * Create a new Credentials instance.
     *
     * Use static factory methods instead of calling this constructor directly.
     *
     * @param string $apiKey The API key
     */
    private function __construct(
        public string $apiKey
    ) {
    }

    /**
     * Prevent the API key from being serialized.
     *
     * @return array<string>
     */
    public function __serialize(): array
    {
        return ['apiKey' => '[REDACTED]'];
    }

    /**
     * Prevent the API key from being exposed in var_dump/print_r.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['apiKey' => $this->getMaskedApiKey()];
    }

    /**
     * Create credentials from an API key string.
     *
     * @param string $apiKey The API key
     *
     * @throws InvalidArgumentException If API key is empty
     *
     * @return self
     */
    public static function fromString(string $apiKey): self
    {
        $trimmed = trim($apiKey);

        if ($trimmed === '') {
            throw new InvalidArgumentException('API key cannot be empty');
        }

        return new self($trimmed);
    }

    /**
     * Create credentials from an environment variable.
     *
     * @param string $envVar The name of environment variable (default: KINESCOPE_API_KEY)
     *
     * @throws InvalidArgumentException If environment variable is not set or empty
     *
     * @return self
     */
    public static function fromEnvironment(string $envVar = self::DEFAULT_ENV_VAR): self
    {
        $apiKey = getenv($envVar);

        if ($apiKey === false) {
            throw new InvalidArgumentException(
                sprintf('Environment variable %s is not set', $envVar)
            );
        }

        return self::fromString($apiKey);
    }

    /**
     * Try to create credentials from an environment variable.
     * Returns null if environment variable is not set or empty.
     *
     * @param string $envVar The name of environment variable
     *
     * @return self|null
     */
    public static function tryFromEnvironment(string $envVar = self::DEFAULT_ENV_VAR): ?self
    {
        try {
            return self::fromEnvironment($envVar);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Get the Authorization header value.
     *
     * @return string The header value in format "Bearer {api_key}"
     */
    public function getAuthorizationHeader(): string
    {
        return 'Bearer ' . $this->apiKey;
    }

    /**
     * Get a masked version of the API key for logging purposes.
     *
     * Shows only the first 4 and last 4 characters, masking the rest.
     *
     * @return string Masked API key (e.g., "abcd****wxyz")
     */
    public function getMaskedApiKey(): string
    {
        $length = strlen($this->apiKey);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($this->apiKey, 0, 4)
            . str_repeat('*', $length - 8)
            . substr($this->apiKey, -4);
    }
}
