<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console\Command;

use InvalidArgumentException;
use Kinescope\Contracts\ApiClientInterface;
use Kinescope\Core\ApiClientFactory;
use Kinescope\Core\Pagination;
use Kinescope\DTO\Folder\FolderDTO;
use Kinescope\DTO\Project\ProjectDTO;
use Kinescope\DTO\Video\AssetDTO;
use Kinescope\DTO\Video\VideoDTO;
use Kinescope\Exception\KinescopeException;
use Kinescope\Services\Projects\Projects;
use Kinescope\Services\Videos\Videos;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Uuid;

abstract class AbstractKinescopeCommand extends Command
{
    protected const int PAGE_SIZE = 100;

    /**
     * @var list<string>
     */
    private const array FORMATS = ['table', 'json'];

    public function __construct(
        protected readonly ApiClientFactory $apiClientFactory,
        protected readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function stderr(OutputInterface $output): OutputInterface
    {
        return $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
    }

    protected function createApiClient(?string $apiKey, OutputInterface $stderr): ?ApiClientInterface
    {
        try {
            return $apiKey !== null
                ? $this->apiClientFactory->withApiKey($apiKey)->build()
                : $this->apiClientFactory->buildFromEnvironment();
        } catch (InvalidArgumentException) {
            $stderr->writeln('<error>API key not provided. Use --api-key or set KINESCOPE_API_KEY.</error>');

            return null;
        }
    }

    protected function validateFormat(string $format): ?string
    {
        if (! in_array($format, self::FORMATS, true)) {
            return 'format must be one of: table, json.';
        }

        return null;
    }

    protected function validateUuid(string $name, string $value): ?string
    {
        if (! Uuid::isValid($value)) {
            return sprintf('%s must be a valid UUID.', $name);
        }

        return null;
    }

    protected function invalid(OutputInterface $stderr, string $message): int
    {
        $stderr->writeln('<error>' . $message . '</error>');

        return Command::INVALID;
    }

    protected function failure(OutputInterface $stderr, string $message): int
    {
        $stderr->writeln('<error>' . $message . '</error>');

        return Command::FAILURE;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $tableHeaders
     * @param list<array<string, mixed>> $tableRows
     */
    protected function renderList(
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
    protected function renderJson(OutputInterface $output, array $payload): void
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
    protected function renderTable(OutputInterface $output, array $headers, array $rows): void
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
     * @return list<ProjectDTO>
     */
    protected function collectProjects(Projects $projects): array
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
    protected function collectVideos(Videos $videos, string $projectId, ?string $folderId): array
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
     * @return array<string, mixed>
     */
    protected function normalizeFolder(FolderDTO $folder): array
    {
        return $folder->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeVideo(VideoDTO $video, bool $includeAssets): array
    {
        $row = [
            'id' => $video->id,
            'title' => $video->title,
            'project_id' => $video->projectId,
            'folder_id' => $video->folderId,
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
     * @return array<string, mixed>
     */
    protected function normalizeAsset(AssetDTO $asset): array
    {
        $hasDownloadLink = $asset->downloadLink !== null && $asset->downloadLink !== '';

        return [
            'id' => $asset->id,
            'quality' => $asset->quality,
            'original_name' => $asset->originalName,
            'filetype' => $asset->filetype,
            'md5' => $asset->md5,
            'resolution' => $asset->resolution === null ? null : (string) $asset->resolution,
            'width' => $asset->resolution?->width,
            'height' => $asset->resolution?->height,
            'file_size' => $asset->fileSize,
            'file_size_mb' => round($asset->fileSize / 1024 / 1024, 2),
            'has_url' => $asset->url !== null && $asset->url !== '',
            'has_download_link' => $hasDownloadLink,
            'downloadable' => $hasDownloadLink,
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    protected function sortRowsByString(array &$rows, string $field): void
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
    protected function sortAssetRows(array &$rows): void
    {
        usort(
            $rows,
            static function (array $a, array $b): int {
                $sizeComparison = (int) $b['file_size'] <=> (int) $a['file_size'];

                return $sizeComparison !== 0
                    ? $sizeComparison
                    : strcmp((string) $a['id'], (string) $b['id']);
            }
        );
    }

    protected function handleSdkFailure(OutputInterface $stderr, KinescopeException $exception): int
    {
        $stderr->writeln('<error>' . $exception->getMessage() . '</error>');

        return Command::FAILURE;
    }

    protected static function humanSize(int $bytes): string
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
}
