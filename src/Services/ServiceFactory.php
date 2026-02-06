<?php

declare(strict_types=1);

namespace Kinescope\Services;

use Kinescope\Contracts\ApiClientInterface;
use Kinescope\Core\ApiClientFactory;
use Kinescope\Core\Credentials;
use Kinescope\Services\Folders\FoldersService;
use Kinescope\Services\Playlists\PlaylistsService;
use Kinescope\Services\Projects\Projects;
use Kinescope\Services\Videos\Videos;
use RuntimeException;

/**
 * Main factory for creating Kinescope SDK services.
 *
 * This is primary entry point for using SDK.
 * Services are lazily instantiated on first access.
 *
 * @example
 * // Initialize from credentials
 * $credentials = Credentials::fromString('your-api-key');
 * $factory = new ServiceFactory($credentials);
 *
 * // Or from environment variable
 * $factory = ServiceFactory::fromEnvironment();
 *
 * // Or with custom API client
 * $apiClient = ApiClientFactory::create()->withApiKey('your-api-key')->build();
 * $factory = new ServiceFactory(apiClient: $apiClient);
 *
 * // Use services
 * $videos = $factory->videos();
 * $projects = $factory->projects();
 */
final class ServiceFactory
{
    private ?Videos $videos = null;

    private ?Projects $projects = null;

    private ?FoldersService $folders = null;

    private ?PlaylistsService $playlists = null;

    private ?ApiClientInterface $resolvedApiClient = null;

    /**
     * Create a new ServiceFactory.
     *
     * @param Credentials|ApiClientInterface $credentialsOrClient Credentials or pre-configured API client
     */
    public function __construct(
        private readonly Credentials|ApiClientInterface $credentialsOrClient
    ) {
    }

    /**
     * Create a ServiceFactory from environment variable.
     *
     * @param string $envVar Environment variable name (default: KINESCOPE_API_KEY)
     *
     * @throws RuntimeException If environment variable is not set
     *
     * @return self
     */
    public static function fromEnvironment(string $envVar = Credentials::DEFAULT_ENV_VAR): self
    {
        return new self(Credentials::fromEnvironment($envVar));
    }

    /**
     * Create a ServiceFactory with a custom API client.
     *
     * @param ApiClientInterface $apiClient Pre-configured API client
     *
     * @return self
     */
    public static function withClient(ApiClientInterface $apiClient): self
    {
        return new self($apiClient);
    }

    /**
     * Get Videos service.
     *
     * @return Videos
     */
    public function videos(): Videos
    {
        return $this->videos ??= new Videos($this->getApiClient());
    }

    /**
     * Get Projects service.
     *
     * @return Projects
     */
    public function projects(): Projects
    {
        return $this->projects ??= new Projects($this->getApiClient());
    }

    /**
     * Get Folders service.
     *
     * @return FoldersService
     */
    public function folders(): FoldersService
    {
        return $this->folders ??= new FoldersService($this->getApiClient());
    }

    /**
     * Get Playlists service.
     *
     * @return PlaylistsService
     */
    public function playlists(): PlaylistsService
    {
        return $this->playlists ??= new PlaylistsService($this->getApiClient());
    }

    /**
     * Get resolved API client.
     *
     * Creates a new ApiClient from credentials if necessary.
     *
     * @return ApiClientInterface
     */
    private function getApiClient(): ApiClientInterface
    {
        if ($this->resolvedApiClient !== null) {
            return $this->resolvedApiClient;
        }

        if ($this->credentialsOrClient instanceof ApiClientInterface) {
            $this->resolvedApiClient = $this->credentialsOrClient;
        } else {
            $this->resolvedApiClient = ApiClientFactory::create()
                ->withCredentials($this->credentialsOrClient)
                ->build();
        }

        return $this->resolvedApiClient;
    }
}
