<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console\Command;

use Kinescope\Exception\KinescopeException;
use Kinescope\Exception\NotFoundException;
use Kinescope\Services\Folders\FoldersService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kinescope:folder:show', description: 'Fetch one Kinescope folder as JSON')]
final class FolderShowCommand extends AbstractKinescopeCommand
{
    public function __invoke(
        #[Argument(description: 'Folder UUID')]
        string $folderId,
        OutputInterface $output,
        #[Option(description: 'Kinescope API key (fallback: KINESCOPE_API_KEY env var)', shortcut: 'k')]
        ?string $apiKey = null,
        #[Option(description: 'Kinescope project UUID')]
        ?string $projectId = null,
    ): int {
        $stderr = $this->stderr($output);

        if ($projectId === null) {
            return $this->invalid($stderr, 'folder:show requires --project-id.');
        }

        foreach ([
            $this->validateUuid('folder-id', $folderId),
            $this->validateUuid('project-id', $projectId),
        ] as $validationError) {
            if ($validationError !== null) {
                return $this->invalid($stderr, $validationError);
            }
        }

        $apiClient = $this->createApiClient($apiKey, $stderr);

        if ($apiClient === null) {
            return Command::FAILURE;
        }

        $this->logger->info('Fetching Kinescope folder', ['projectId' => $projectId, 'folderId' => $folderId]);
        $folders = new FoldersService($apiClient);

        try {
            $folder = $folders->get($projectId, $folderId);
        } catch (NotFoundException) {
            return $this->failure($stderr, sprintf('Folder "%s" not found for project "%s".', $folderId, $projectId));
        } catch (KinescopeException $e) {
            return $this->handleSdkFailure($stderr, $e);
        }

        $this->renderJson($output, $folder->toArray());

        return Command::SUCCESS;
    }
}
