<?php

declare(strict_types=1);

namespace Kinescope\Services\Projects;

use Kinescope\Core\Pagination;
use Kinescope\Core\Sort;
use Kinescope\DTO\Project\ProjectDTO;
use Kinescope\DTO\Project\ProjectListResult;
use Kinescope\Services\AbstractService;

final class Projects extends AbstractService
{
    /**
     * API endpoint for projects.
     */
    private const string ENDPOINT = '/v1/projects';

    /**
     * Get a paginated list of projects.
     *
     * @param Pagination $pagination Pagination parameters
     * @param Sort|null $sort Sorting parameters
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return ProjectListResult Paginated list of projects
     */
    public function list(
        Pagination $pagination = new Pagination(),
        ?Sort $sort = null
    ): ProjectListResult {
        $query = $this->mergeQueries(
            $pagination->toQueryParams(),
            $sort?->toQueryParams() ?? []
        );

        $response = $this->apiClient->get(self::ENDPOINT, $query);

        return ProjectListResult::fromArray($response);
    }

    /**
     * Get a specific project by ID.
     *
     * @param string $projectId Project UUID
     *
     * @return ProjectDTO The project data
     * @throws \Kinescope\Exception\KinescopeException On other API errors
     *
     * @throws \Kinescope\Exception\NotFoundException If project not found
     */
    public function get(string $projectId): ProjectDTO
    {
        $endpoint = $this->buildEndpoint(self::ENDPOINT . '/{project_id}', [
            'project_id' => $projectId,
        ]);

        $response = $this->apiClient->get($endpoint);

        return ProjectDTO::fromArray($this->extractData($response));
    }
}
