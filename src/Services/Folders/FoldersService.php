<?php

declare(strict_types=1);

namespace Kinescope\Services\Folders;

use Kinescope\Core\Sort;
use Kinescope\DTO\Folder\FolderDTO;
use Kinescope\DTO\Folder\FolderListResult;
use Kinescope\Services\AbstractService;

/**
 * Service for managing folders within projects.
 *
 * Provides methods for listing and retrieving folders.
 *
 * @example
 * $factory = ServiceFactory::fromEnvironment();
 *
 * // List folders in a project
 * $result = $factory->folders()->list('project-uuid');
 * foreach ($result->getData() as $folder) {
 *     echo $folder->name;
 * }
 *
 * // Get a specific folder
 * $folder = $factory->folders()->get('project-uuid', 'folder-uuid');
 */
final class FoldersService extends AbstractService
{
    /**
     * API endpoint template for folders.
     */
    private const string ENDPOINT = '/v1/projects/{project_id}/folders';

    /**
     * Get a paginated list of folders in a project.
     *
     * @param string $projectId Project UUID
     * @param int $page Page number (1-indexed)
     * @param int $perPage Number of items per page (max 100)
     * @param string|null $parentId Filter by parent folder ID (null for root folders)
     * @param Sort|null $sort Sorting parameters
     *
     * @throws \Kinescope\Exception\NotFoundException If project not found
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return FolderListResult Paginated list of folders
     */
    public function list(
        string $projectId,
        int $page = 1,
        int $perPage = 20,
        ?string $parentId = null,
        ?Sort $sort = null
    ): FolderListResult {
        $endpoint = $this->buildEndpoint(self::ENDPOINT, [
            'project_id' => $projectId,
        ]);

        $query = $this->mergeQueries(
            $this->buildPaginationQuery($page, $perPage),
            $sort?->toQueryParams() ?? [],
            $this->buildFilterQuery([
                'parent_id' => $parentId,
            ])
        );

        $response = $this->apiClient->get($endpoint, $query);

        return FolderListResult::fromArray($response);
    }

    /**
     * Get a specific folder by ID.
     *
     * @param string $projectId Project UUID
     * @param string $folderId Folder UUID
     *
     * @throws \Kinescope\Exception\NotFoundException If project or folder not found
     * @throws \Kinescope\Exception\KinescopeException On other API errors
     *
     * @return FolderDTO The folder data
     */
    public function get(string $projectId, string $folderId): FolderDTO
    {
        $endpoint = $this->buildEndpoint(self::ENDPOINT . '/{folder_id}', [
            'project_id' => $projectId,
            'folder_id' => $folderId,
        ]);

        $response = $this->apiClient->get($endpoint);

        return FolderDTO::fromArray($this->extractData($response));
    }

    /**
     * Get all folders in a project (unpaginated).
     *
     * @param string $projectId Project UUID
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return array<FolderDTO> All folders
     */
    public function getAll(string $projectId): array
    {
        $allFolders = [];
        $page = 1;
        $perPage = 100;

        do {
            $result = $this->list($projectId, $page, $perPage);
            $allFolders = array_merge($allFolders, $result->getData());
            $page++;
        } while ($result->hasNextPage());

        return $allFolders;
    }

    /**
     * Get root folders in a project.
     *
     * @param string $projectId Project UUID
     * @param int $page Page number
     * @param int $perPage Items per page
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return FolderListResult Root folders
     */
    public function getRoots(string $projectId, int $page = 1, int $perPage = 20): FolderListResult
    {
        $result = $this->list($projectId, $page, $perPage);

        $roots = $result->getRoots();

        return FolderListResult::fromArray([
            'data' => array_map(
                static fn (FolderDTO $folder): array => $folder->toArray(),
                $roots
            ),
            'meta' => $result->getMeta()->toArray(),
        ]);
    }

    /**
     * Get child folders of a specific folder.
     *
     * @param string $projectId Project UUID
     * @param string $parentId Parent folder UUID
     * @param int $page Page number
     * @param int $perPage Items per page
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return FolderListResult Child folders
     */
    public function getChildren(
        string $projectId,
        string $parentId,
        int $page = 1,
        int $perPage = 20
    ): FolderListResult {
        return $this->list($projectId, $page, $perPage, $parentId);
    }

    /**
     * Build a folder tree structure.
     *
     * @param string $projectId Project UUID
     *
     * @throws \Kinescope\Exception\KinescopeException On API errors
     *
     * @return array<array{folder: FolderDTO, children: array<mixed>}> Tree structure
     */
    public function getTree(string $projectId): array
    {
        $allFolders = $this->getAll($projectId);

        $result = FolderListResult::fromArray([
            'data' => array_map(
                static fn (FolderDTO $folder): array => $folder->toArray(),
                $allFolders
            ),
            'meta' => ['total' => count($allFolders), 'page' => 1, 'per_page' => count($allFolders)],
        ]);

        return $result->buildTree();
    }
}
