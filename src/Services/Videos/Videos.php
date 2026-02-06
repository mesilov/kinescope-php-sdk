<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos;

use Kinescope\Core\Pagination;
use Kinescope\Core\Sort;
use Kinescope\DTO\Video\VideoDTO;
use Kinescope\DTO\Video\VideoListResult;
use Kinescope\Services\AbstractService;

final class Videos extends AbstractService
{
    /**
     * API endpoint for videos.
     */
    private const string ENDPOINT = '/v1/videos';

    /**
     * Get a paginated list of videos.
     *
     * @param Pagination $pagination Pagination parameters
     * @param string|null $projectId Filter by project ID
     * @param string|null $folderId Filter by folder ID
     * @param Sort|null $sort Sorting parameters
     * @param string|null $search Search query for video title
     * @param string|null $status Filter by status (pending, uploading, processing, done, error)
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return VideoListResult Paginated list of videos
     */
    public function list(
        Pagination $pagination = new Pagination(),
        ?Sort $sort = null,
        ?string $projectId = null,
        ?string $folderId = null,
        ?string $search = null,
        ?string $status = null,
    ): VideoListResult {
        $query = $this->mergeQueries(
            $pagination->toQueryParams(),
            $sort?->toQueryParams() ?? [],
            $this->buildFilterQuery([
                'project_id' => $projectId,
                'folder_id' => $folderId,
                'q' => $search,
                'status' => $status,
            ])
        );

        $response = $this->apiClient->get(self::ENDPOINT, $query);

        return VideoListResult::fromArray($response);
    }

    /**
     * Get a specific video by ID.
     *
     * @param string $videoId Video UUID
     *
     * @throws \Kinescope\Exception\NotFoundException If video not found
     * @throws \Kinescope\Exception\KinescopeException On other API errors
     *
     * @return VideoDTO The video data
     */
    public function get(string $videoId): VideoDTO
    {
        $endpoint = $this->buildEndpoint(self::ENDPOINT . '/{video_id}', [
            'video_id' => $videoId,
        ]);

        $response = $this->apiClient->get($endpoint);

        return VideoDTO::fromArray($this->extractData($response));
    }

    /**
     * Get videos by project ID.
     *
     * Convenience method for filtering videos by project.
     *
     * @param string $projectId Project UUID
     * @param Pagination $pagination Pagination parameters
     *
     * @return VideoListResult Paginated list of videos
     */
    public function listByProject(
        string $projectId,
        Pagination $pagination = new Pagination()
    ): VideoListResult {
        return $this->list(
            pagination: $pagination,
            projectId: $projectId
        );
    }

    /**
     * Get videos by folder ID.
     *
     * Convenience method for filtering videos by folder.
     *
     * @param string $folderId Folder UUID
     * @param Pagination $pagination Pagination parameters
     *
     * @return VideoListResult Paginated list of videos
     */
    public function listByFolder(
        string $folderId,
        Pagination $pagination = new Pagination()
    ): VideoListResult {
        return $this->list(
            pagination: $pagination,
            folderId: $folderId
        );
    }

    /**
     * Search videos by title.
     *
     * @param string $query Search query
     * @param Pagination $pagination Pagination parameters
     *
     * @return VideoListResult Paginated search results
     */
    public function search(
        string $query,
        Pagination $pagination = new Pagination()
    ): VideoListResult {
        return $this->list(
            pagination: $pagination,
            search: $query
        );
    }
}
