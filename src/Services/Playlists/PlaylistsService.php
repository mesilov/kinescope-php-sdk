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
 *     echo $playlist->title;
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
     * @param string|null $projectId Filter by project ID
     * @param Sort|null $sort Sorting parameters
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return PlaylistListResult Paginated list of playlists
     */
    public function list(
        int $page = 1,
        int $perPage = 20,
        ?string $projectId = null,
        ?Sort $sort = null
    ): PlaylistListResult {
        $query = $this->mergeQueries(
            $this->buildPaginationQuery($page, $perPage),
            $sort?->toQueryParams() ?? [],
            $this->buildFilterQuery([
                'project_id' => $projectId,
            ])
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
     * @param int $page Page number (1-indexed)
     * @param int $perPage Number of items per page
     *
     * @throws \Kinescope\Exception\NotFoundException If playlist not found
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return PlaylistEntityListResult Paginated list of playlist entities
     */
    public function entities(
        string $playlistId,
        int $page = 1,
        int $perPage = 20
    ): PlaylistEntityListResult {
        $endpoint = $this->buildEndpoint(self::ENDPOINT . '/{playlist_id}/entities', [
            'playlist_id' => $playlistId,
        ]);

        $query = $this->buildPaginationQuery($page, $perPage);
        $response = $this->apiClient->get($endpoint, $query);

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
        $allEntities = [];
        $page = 1;
        $perPage = 100;

        do {
            $result = $this->entities($playlistId, $page, $perPage);
            $allEntities = array_merge($allEntities, $result->getData());
            $page++;
        } while ($result->hasNextPage());

        return $allEntities;
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
     * Get playlists by project.
     *
     * @param string $projectId Project UUID
     * @param int $page Page number
     * @param int $perPage Items per page
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return PlaylistListResult Playlists in project
     */
    public function listByProject(
        string $projectId,
        int $page = 1,
        int $perPage = 20
    ): PlaylistListResult {
        return $this->list(
            page: $page,
            perPage: $perPage,
            projectId: $projectId
        );
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

        return PlaylistListResult::fromArray([
            'data' => array_map(
                static fn (PlaylistDTO $playlist): array => $playlist->toArray(),
                $public
            ),
            'meta' => $result->getMeta()->toArray(),
        ]);
    }

    /**
     * Find playlist by title.
     *
     * @param string $title Playlist title
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return PlaylistDTO|null The playlist or null if not found
     */
    public function findByTitle(string $title): ?PlaylistDTO
    {
        foreach ($this->getAll() as $playlist) {
            if ($playlist->title === $title) {
                return $playlist;
            }
        }

        return null;
    }
}
