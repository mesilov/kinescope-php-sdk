<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console\Command;

use Kinescope\DTO\Video\VideoDTO;
use Kinescope\Exception\KinescopeException;
use Kinescope\Exception\NotFoundException;
use Kinescope\Services\Folders\FoldersService;
use Kinescope\Services\Videos\Videos;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kinescope:video:list', description: 'List Kinescope videos for one project or folder')]
final class VideoListCommand extends AbstractKinescopeCommand
{
    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Kinescope API key (fallback: KINESCOPE_API_KEY env var)', shortcut: 'k')]
        ?string $apiKey = null,
        #[Option(description: 'Kinescope project UUID')]
        ?string $projectId = null,
        #[Option(description: 'Kinescope folder UUID')]
        ?string $folderId = null,
        #[Option(description: 'Include sanitized asset summaries in videos output')]
        bool $includeAssets = false,
        #[Option(description: 'Output format: table or json')]
        string $format = 'table',
    ): int {
        $stderr = $this->stderr($output);

        if ($projectId === null) {
            return $this->invalid($stderr, 'video:list requires --project-id.');
        }

        foreach ([
            $this->validateUuid('project-id', $projectId),
            $folderId === null ? null : $this->validateUuid('folder-id', $folderId),
            $this->validateFormat($format),
        ] as $validationError) {
            if ($validationError !== null) {
                return $this->invalid($stderr, $validationError);
            }
        }

        $apiClient = $this->createApiClient($apiKey, $stderr);

        if ($apiClient === null) {
            return Command::FAILURE;
        }

        $this->logger->info('Listing Kinescope videos', [
            'projectId' => $projectId,
            'folderId' => $folderId,
            'format' => $format,
        ]);
        $folders = new FoldersService($apiClient);
        $videos = new Videos($apiClient);

        try {
            if ($folderId !== null) {
                $folders->get($projectId, $folderId);
            }

            $items = array_values(array_map(
                fn (VideoDTO $video): array => $this->normalizeVideo($video, $includeAssets),
                $this->collectVideos($videos, $projectId, $folderId),
            ));
            $this->sortRowsByString($items, 'title');
        } catch (NotFoundException) {
            return $this->invalid($stderr, sprintf('Folder %s does not belong to project %s.', $folderId, $projectId));
        } catch (KinescopeException $e) {
            return $this->handleSdkFailure($stderr, $e);
        }

        $this->renderList(
            output: $output,
            format: $format,
            payload: [
                'resource' => 'video',
                'projectId' => $projectId,
                'folderId' => $folderId,
                'items' => $items,
            ],
            tableHeaders: ['id', 'title', 'project_id', 'folder_id', 'duration', 'status'],
            tableRows: $items,
        );

        return Command::SUCCESS;
    }
}
