<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console\Command;

use Kinescope\Exception\KinescopeException;
use Kinescope\Exception\NotFoundException;
use Kinescope\Services\Folders\FoldersService;
use Kinescope\Services\Projects\Projects;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kinescope:folder:list', description: 'List Kinescope folders for one project')]
final class FolderListCommand extends AbstractKinescopeCommand
{
    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Kinescope API key (fallback: KINESCOPE_API_KEY env var)', shortcut: 'k')]
        ?string $apiKey = null,
        #[Option(description: 'Kinescope project UUID')]
        ?string $projectId = null,
        #[Option(description: 'Output format: table or json')]
        string $format = 'table',
    ): int {
        $stderr = $this->stderr($output);

        if ($projectId === null) {
            return $this->invalid($stderr, 'folder:list requires --project-id.');
        }

        foreach ([
            $this->validateUuid('project-id', $projectId),
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

        $this->logger->info('Listing Kinescope folders', ['projectId' => $projectId, 'format' => $format]);
        $projects = new Projects($apiClient);
        $folders = new FoldersService($apiClient);

        try {
            $projects->get($projectId);
            $items = array_values(array_map($this->normalizeFolder(...), $folders->getAll($projectId)));
            $this->sortRowsByString($items, 'path');
        } catch (NotFoundException) {
            return $this->failure($stderr, sprintf('Project "%s" not found.', $projectId));
        } catch (KinescopeException $e) {
            return $this->handleSdkFailure($stderr, $e);
        }

        $this->renderList(
            output: $output,
            format: $format,
            payload: [
                'resource' => 'folder',
                'projectId' => $projectId,
                'items' => $items,
            ],
            tableHeaders: ['id', 'name', 'projectId', 'parentId', 'videosCount', 'path'],
            tableRows: $items,
        );

        return Command::SUCCESS;
    }
}
