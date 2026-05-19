<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console\Command;

use Kinescope\Exception\KinescopeException;
use Kinescope\Exception\NotFoundException;
use Kinescope\Services\Projects\Projects;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kinescope:project:show', description: 'Fetch one Kinescope project as JSON')]
final class ProjectShowCommand extends AbstractKinescopeCommand
{
    public function __invoke(
        #[Argument(description: 'Project UUID')]
        string $projectId,
        OutputInterface $output,
        #[Option(description: 'Kinescope API key (fallback: KINESCOPE_API_KEY env var)', shortcut: 'k')]
        ?string $apiKey = null,
    ): int {
        $stderr = $this->stderr($output);

        if (($validationError = $this->validateUuid('project-id', $projectId)) !== null) {
            return $this->invalid($stderr, $validationError);
        }

        $apiClient = $this->createApiClient($apiKey, $stderr);

        if ($apiClient === null) {
            return Command::FAILURE;
        }

        $this->logger->info('Fetching Kinescope project', ['projectId' => $projectId]);
        $projects = new Projects($apiClient);

        try {
            $project = $projects->get($projectId);
        } catch (NotFoundException) {
            return $this->failure($stderr, sprintf('Project "%s" not found.', $projectId));
        } catch (KinescopeException $e) {
            return $this->handleSdkFailure($stderr, $e);
        }

        $this->renderJson($output, $project->toArray());

        return Command::SUCCESS;
    }
}
