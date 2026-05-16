<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console\Command;

use InvalidArgumentException;
use Kinescope\Core\ApiClientFactory;
use Kinescope\Core\Pagination;
use Kinescope\DTO\Folder\FolderDTO;
use Kinescope\DTO\Project\ProjectDTO;
use Kinescope\DTO\Video\AssetDTO;
use Kinescope\DTO\Video\VideoDTO;
use Kinescope\Exception\KinescopeException;
use Kinescope\Exception\NotFoundException;
use Kinescope\Services\Folders\FoldersService;
use Kinescope\Services\Projects\Projects;
use Kinescope\Services\Videos\Videos;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(name: 'kinescope:browse', description: 'Browse Kinescope structure without mutating remote state')]
final class BrowseCommand extends Command
{
    private const int PAGE_SIZE = 100;

    /**
     * @var list<string>
     */
    private const array RESOURCES = ['projects', 'folders', 'videos', 'assets'];

    /**
     * @var list<string>
     */
    private const array FORMATS = ['table', 'json'];

    public function __construct(
        private readonly ApiClientFactory $apiClientFactory,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    public function __invoke(
        #[Argument(description: 'Resource to browse: projects, folders, videos, assets')]
        string $resource,
        OutputInterface $output,
        #[Option(description: 'Kinescope API key (fallback: KINESCOPE_API_KEY env var)', shortcut: 'k')]
        ?string $apiKey = null,
        #[Option(description: 'Kinescope project UUID')]
        ?string $projectId = null,
        #[Option(description: 'Kinescope folder UUID')]
        ?string $folderId = null,
        #[Option(description: 'Kinescope video UUID')]
        ?string $videoId = null,
        #[Option(description: 'Include sanitized asset summaries in videos output')]
        bool $includeAssets = false,
        #[Option(description: 'Output format: table or json')]
        string $format = 'table',
    ): int {
        $stderr = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $validationError = $this->validateInput(
            resource: $resource,
            format: $format,
            projectId: $projectId,
            folderId: $folderId,
            videoId: $videoId,
            includeAssets: $includeAssets,
        );

        if ($validationError !== null) {
            return $this->invalid($stderr, $validationError);
        }

        $this->logger->info('Browsing Kinescope resource', [
            'resource' => $resource,
            'format' => $format,
        ]);

        try {
            $apiClient = $apiKey !== null
                ? $this->apiClientFactory->withApiKey($apiKey)->build()
                : $this->apiClientFactory->buildFromEnvironment();
        } catch (InvalidArgumentException) {
            $stderr->writeln('<error>API key not provided. Use --api-key or set KINESCOPE_API_KEY.</error>');

            return Command::FAILURE;
        }

        $projects = new Projects($apiClient);
        $folders = new FoldersService($apiClient);
        $videos = new Videos($apiClient);

        try {
            return match ($resource) {
                'projects' => $this->browseProjects($projects, $output, $format),
                'folders' => $this->browseFolders($projects, $folders, $output, $stderr, $format, (string) $projectId),
                'videos' => $this->browseVideos($folders, $videos, $output, $stderr, $format, (string) $projectId, $folderId, $includeAssets),
                'assets' => $this->browseAssets($videos, $output, $stderr, $format, (string) $videoId),
                default => $this->invalid($stderr, 'resource must be one of: projects, folders, videos, assets.'),
            };
        } catch (KinescopeException $e) {
            $stderr->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }

    private function browseProjects(Projects $projects, OutputInterface $output, string $format): int
    {
        $items = array_values(array_map($this->normalizeProject(...), $this->collectProjects($projects)));
        $this->sortRowsByString($items, 'name');

        $this->render(
            output: $output,
            format: $format,
            payload: [
                'resource' => 'projects',
                'items' => $items,
            ],
            tableHeaders: ['id', 'name', 'videosCount', 'foldersCount'],
            tableRows: $items,
        );

        return Command::SUCCESS;
    }

    private function browseFolders(
        Projects $projects,
        FoldersService $folders,
        OutputInterface $output,
        OutputInterface $stderr,
        string $format,
        string $projectId,
    ): int {
        try {
            $projects->get($projectId);
        } catch (NotFoundException) {
            return $this->invalid($stderr, sprintf('Project %s not found.', $projectId));
        }

        $items = array_values(array_map($this->normalizeFolder(...), $folders->getAll($projectId)));
        $this->sortRowsByString($items, 'path');

        $this->render(
            output: $output,
            format: $format,
            payload: [
                'resource' => 'folders',
                'projectId' => $projectId,
                'items' => $items,
            ],
            tableHeaders: ['id', 'name', 'projectId', 'parentId', 'videosCount', 'path'],
            tableRows: $items,
        );

        return Command::SUCCESS;
    }

    private function browseVideos(
        FoldersService $folders,
        Videos $videos,
        OutputInterface $output,
        OutputInterface $stderr,
        string $format,
        string $projectId,
        ?string $folderId,
        bool $includeAssets,
    ): int {
        if ($folderId !== null) {
            try {
                $folders->get($projectId, $folderId);
            } catch (NotFoundException) {
                return $this->invalid($stderr, sprintf('Folder %s does not belong to project %s.', $folderId, $projectId));
            }
        }

        $items = array_values(array_map(
            fn (VideoDTO $video): array => $this->normalizeVideo($video, $includeAssets),
            $this->collectVideos($videos, $projectId, $folderId),
        ));
        $this->sortRowsByString($items, 'name');

        $this->render(
            output: $output,
            format: $format,
            payload: [
                'resource' => 'videos',
                'projectId' => $projectId,
                'folderId' => $folderId,
                'items' => $items,
            ],
            tableHeaders: ['id', 'name', 'projectId', 'folderId', 'duration', 'status'],
            tableRows: $items,
        );

        return Command::SUCCESS;
    }

    private function browseAssets(
        Videos $videos,
        OutputInterface $output,
        OutputInterface $stderr,
        string $format,
        string $videoId,
    ): int {
        try {
            $video = $videos->get($videoId);
        } catch (NotFoundException) {
            $stderr->writeln(sprintf('<error>Video "%s" not found.</error>', $videoId));

            return Command::FAILURE;
        }

        $items = array_values(array_map($this->normalizeAsset(...), $video->assets));
        $this->sortAssetRows($items);

        if ($format === 'json') {
            $this->renderJson($output, [
                'resource' => 'assets',
                'videoId' => $videoId,
                'videoName' => $video->title,
                'items' => $items,
            ]);

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Video: %s', $video->title));
        $output->writeln('');
        $this->renderTable($output, ['id', 'quality', 'size', 'hasDownloadLink'], array_map(
            static fn (array $item): array => [
                'id' => $item['id'],
                'quality' => $item['quality'],
                'size' => self::humanSize((int) $item['fileSize']),
                'hasDownloadLink' => $item['hasDownloadLink'],
            ],
            $items,
        ));

        return Command::SUCCESS;
    }

    private function validateInput(
        string $resource,
        string $format,
        ?string $projectId,
        ?string $folderId,
        ?string $videoId,
        bool $includeAssets,
    ): ?string {
        if (! in_array($resource, self::RESOURCES, true)) {
            return 'resource must be one of: projects, folders, videos, assets.';
        }

        if (! in_array($format, self::FORMATS, true)) {
            return 'format must be one of: table, json.';
        }

        foreach ([
            'project-id' => $projectId,
            'folder-id' => $folderId,
            'video-id' => $videoId,
        ] as $name => $value) {
            if ($value !== null && ! Uuid::isValid($value)) {
                return sprintf('%s must be a valid UUID.', $name);
            }
        }

        if ($resource === 'projects') {
            return $this->validateProjectsInput($projectId, $folderId, $videoId, $includeAssets);
        }

        if ($resource === 'folders') {
            return $this->validateFoldersInput($projectId, $folderId, $videoId, $includeAssets);
        }

        if ($resource === 'videos') {
            return $this->validateVideosInput($projectId, $videoId);
        }

        return $this->validateAssetsInput($projectId, $folderId, $videoId, $includeAssets);
    }

    private function validateProjectsInput(
        ?string $projectId,
        ?string $folderId,
        ?string $videoId,
        bool $includeAssets,
    ): ?string {
        if ($projectId !== null) {
            return 'projects does not accept --project-id.';
        }

        if ($folderId !== null) {
            return 'projects does not accept --folder-id.';
        }

        if ($videoId !== null) {
            return 'projects does not accept --video-id.';
        }

        if ($includeAssets) {
            return 'projects does not accept --include-assets.';
        }

        return null;
    }

    private function validateFoldersInput(
        ?string $projectId,
        ?string $folderId,
        ?string $videoId,
        bool $includeAssets,
    ): ?string {
        if ($projectId === null) {
            return 'folders requires --project-id.';
        }

        if ($folderId !== null) {
            return 'folders does not accept --folder-id.';
        }

        if ($videoId !== null) {
            return 'folders does not accept --video-id.';
        }

        if ($includeAssets) {
            return 'folders does not accept --include-assets.';
        }

        return null;
    }

    private function validateVideosInput(?string $projectId, ?string $videoId): ?string
    {
        if ($projectId === null) {
            return 'videos requires --project-id.';
        }

        if ($videoId !== null) {
            return 'videos does not accept --video-id.';
        }

        return null;
    }

    private function validateAssetsInput(
        ?string $projectId,
        ?string $folderId,
        ?string $videoId,
        bool $includeAssets,
    ): ?string {
        if ($videoId === null) {
            return 'assets requires --video-id.';
        }

        if ($projectId !== null) {
            return 'assets does not accept --project-id.';
        }

        if ($folderId !== null) {
            return 'assets does not accept --folder-id.';
        }

        if ($includeAssets) {
            return 'assets does not accept --include-assets.';
        }

        return null;
    }

    /**
     * @return list<ProjectDTO>
     */
    private function collectProjects(Projects $projects): array
    {
        $items = [];
        $page = 1;

        do {
            $result = $projects->list(new Pagination(page: $page, perPage: self::PAGE_SIZE));
            $items = array_merge($items, $result->getData());
            $page++;
        } while ($result->hasNextPage());

        return array_values($items);
    }

    /**
     * @return list<VideoDTO>
     */
    private function collectVideos(Videos $videos, string $projectId, ?string $folderId): array
    {
        $items = [];
        $page = 1;

        do {
            $result = $videos->list(
                pagination: new Pagination(page: $page, perPage: self::PAGE_SIZE),
                projectId: $projectId,
                folderId: $folderId,
            );
            $items = array_merge($items, $result->getData());
            $page++;
        } while ($result->hasNextPage());

        return array_values($items);
    }

    /**
     * @return array{id: string, name: string, videosCount: int, foldersCount: int}
     */
    private function normalizeProject(ProjectDTO $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'videosCount' => $project->videosCount,
            'foldersCount' => $project->foldersCount,
        ];
    }

    /**
     * @return array{id: string, name: string, projectId: string, parentId: string|null, videosCount: int, path: string}
     */
    private function normalizeFolder(FolderDTO $folder): array
    {
        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'projectId' => $folder->projectId,
            'parentId' => $folder->parentId,
            'videosCount' => $folder->videosCount,
            'path' => $folder->getFullPath(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeVideo(VideoDTO $video, bool $includeAssets): array
    {
        $row = [
            'id' => $video->id,
            'name' => $video->title,
            'projectId' => $video->projectId,
            'folderId' => $video->folderId,
            'duration' => $video->duration,
            'status' => $video->status->value,
        ];

        if ($includeAssets) {
            $assets = array_values(array_map($this->normalizeAsset(...), $video->assets));
            $this->sortAssetRows($assets);
            $row['assets'] = $assets;
        }

        return $row;
    }

    /**
     * @return array{id: string, quality: string|null, width: int|null, height: int|null, bitrate: int|null, fileSize: int, fileSizeMb: float, codec: string|null, hasUrl: bool, hasDownloadLink: bool, downloadable: bool}
     */
    private function normalizeAsset(AssetDTO $asset): array
    {
        $hasDownloadLink = $asset->downloadLink !== null && $asset->downloadLink !== '';

        return [
            'id' => $asset->id,
            'quality' => $asset->quality,
            'width' => $asset->resolution?->width,
            'height' => $asset->resolution?->height,
            'bitrate' => $asset->bitrate,
            'fileSize' => $asset->fileSize,
            'fileSizeMb' => round($asset->fileSize / 1024 / 1024, 2),
            'codec' => $asset->codec,
            'hasUrl' => $asset->url !== null && $asset->url !== '',
            'hasDownloadLink' => $hasDownloadLink,
            'downloadable' => $hasDownloadLink,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $tableHeaders
     * @param list<array<string, mixed>> $tableRows
     */
    private function render(
        OutputInterface $output,
        string $format,
        array $payload,
        array $tableHeaders,
        array $tableRows,
    ): void {
        if ($format === 'json') {
            $this->renderJson($output, $payload);

            return;
        }

        $this->renderTable($output, $tableHeaders, $tableRows);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function renderJson(OutputInterface $output, array $payload): void
    {
        $output->writeln(
            json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
            )
        );
    }

    /**
     * @param list<string> $headers
     * @param list<array<string, mixed>> $rows
     */
    private function renderTable(OutputInterface $output, array $headers, array $rows): void
    {
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows(array_map(
            static fn (array $row): array => array_map(
                static fn (string $header): string => self::tableValue($row[$header] ?? null),
                $headers,
            ),
            $rows,
        ));
        $table->render();
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function sortRowsByString(array &$rows, string $field): void
    {
        usort(
            $rows,
            static function (array $a, array $b) use ($field): int {
                $fieldComparison = strcasecmp((string) $a[$field], (string) $b[$field]);

                return $fieldComparison !== 0
                    ? $fieldComparison
                    : strcmp((string) $a['id'], (string) $b['id']);
            }
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function sortAssetRows(array &$rows): void
    {
        usort(
            $rows,
            static function (array $a, array $b): int {
                $sizeComparison = (int) $b['fileSize'] <=> (int) $a['fileSize'];

                return $sizeComparison !== 0
                    ? $sizeComparison
                    : strcmp((string) $a['id'], (string) $b['id']);
            }
        );
    }

    private function invalid(OutputInterface $stderr, string $message): int
    {
        $stderr->writeln('<error>' . $message . '</error>');

        return Command::INVALID;
    }

    private static function tableValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (is_float($value)) {
            return number_format($value, 2, '.', '');
        }

        return (string) $value;
    }

    private static function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return sprintf('%.2f %s', $size, $units[$unitIndex]);
    }
}
