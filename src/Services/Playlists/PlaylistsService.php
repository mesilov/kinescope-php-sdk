<?php

declare(strict_types=1);

namespace Kinescope\Services\Playlists;

use Kinescope\Core\Sort;
use Kinescope\DTO\Playlist\PlaylistDTO;
use Kinescope\DTO\Playlist\PlaylistEntityDTO;
use Kinescope\DTO\Playlist\PlaylistEntityListResult;
use Kinescope\DTO\Playlist\PlaylistListResult;
use Kinescope\Services\AbstractService;

/**
 * Service for managing playlists.
 *
 * Provides methods for listing and retrieving playlists and their contents.
 *
 * @example
 * $factory = ServiceFactory::fromEnvironment();
 *
 * // List all playlists
 * $result = $factory->playlists()->list();
 * foreach ($result->getData() as $playlist) {
 *     echo $playlist->name;
 * }
 *
 * // Get a specific playlist
 * $playlist = $factory->playlists()->get('playlist-uuid');
 *
 * // Get playlist contents
 * $entities = $factory->playlists()->entities('playlist-uuid');
 */
final class PlaylistsService extends AbstractService
{
    /**
     * API endpoint for playlists.
     */
    private const string ENDPOINT = '/v1/playlists';

    /**
     * Get a paginated list of playlists.
     *
     * @param int $page Page number (1-indexed)
     * @param int $perPage Number of items per page (max 100)
     * @param Sort|null $sort Sorting parameters
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return PlaylistListResult Paginated list of playlists
     */
    public function list(
        int $page = 1,
        int $perPage = 20,
        ?Sort $sort = null
    ): PlaylistListResult {
        $query = $this->mergeQueries(
            $this->buildPaginationQuery($page, $perPage),
            $sort?->toQueryParams() ?? []
        );

        $response = $this->apiClient->get(self::ENDPOINT, $query);

        return PlaylistListResult::fromArray($response);
    }

    /**
     * Get a specific playlist by ID.
     *
     * @param string $playlistId Playlist UUID
     *
     * @throws \Kinescope\Exception\NotFoundException If playlist not found
     * @throws \Kinescope\Exception\KinescopeException On other API errors
     *
     * @return PlaylistDTO The playlist data
     */
    public function get(string $playlistId): PlaylistDTO
    {
        $endpoint = $this->buildEndpoint(self::ENDPOINT . '/{playlist_id}', [
            'playlist_id' => $playlistId,
        ]);

        $response = $this->apiClient->get($endpoint);

        return PlaylistDTO::fromArray($this->extractData($response));
    }

    /**
     * Get entities (items) in a playlist.
     *
     * @param string $playlistId Playlist UUID
     *
     * @throws \Kinescope\Exception\NotFoundException If playlist not found
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return PlaylistEntityListResult Unpaginated list of playlist entities
     */
    public function entities(
        string $playlistId
    ): PlaylistEntityListResult {
        $endpoint = $this->buildEndpoint(self::ENDPOINT . '/{playlist_id}/entities', [
            'playlist_id' => $playlistId,
        ]);

        $response = $this->apiClient->get($endpoint);

        return PlaylistEntityListResult::fromArray($response);
    }

    /**
     * Get all entities in a playlist (unpaginated).
     *
     * @param string $playlistId Playlist UUID
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return array<PlaylistEntityDTO> All playlist entities
     */
    public function getAllEntities(string $playlistId): array
    {
        return $this->entities($playlistId)->getData();
    }

    /**
     * Get all playlists (unpaginated).
     *
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return array<PlaylistDTO> All playlists
     */
    public function getAll(): array
    {
        $allPlaylists = [];
        $page = 1;
        $perPage = 100;

        do {
            $result = $this->list($page, $perPage);
            $allPlaylists = array_merge($allPlaylists, $result->getData());
            $page++;
        } while ($result->hasNextPage());

        return $allPlaylists;
    }

    /**
     * Get public playlists.
     *
     * @param int $page Page number
     * @param int $perPage Items per page
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return PlaylistListResult Public playlists
     */
    public function getPublic(int $page = 1, int $perPage = 20): PlaylistListResult
    {
        $result = $this->list($page, $perPage);

        $public = $result->getPublic();

        return new PlaylistListResult($public, $result->getMeta());
    }

    /**
     * Find playlist by name.
     *
     * @param string $name Playlist name
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return PlaylistDTO|null The playlist or null if not found
     */
    public function findByName(string $name): ?PlaylistDTO
    {
        foreach ($this->getAll() as $playlist) {
            if ($playlist->name === $name) {
                return $playlist;
            }
        }

        return null;
    }
}
