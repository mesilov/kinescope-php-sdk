<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console\Command;

use Kinescope\DTO\Project\ProjectDTO;
use Kinescope\Exception\KinescopeException;
use Kinescope\Services\Projects\Projects;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kinescope:project:list', description: 'List Kinescope projects')]
final class ProjectListCommand extends AbstractKinescopeCommand
{
    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Kinescope API key (fallback: KINESCOPE_API_KEY env var)', shortcut: 'k')]
        ?string $apiKey = null,
        #[Option(description: 'Output format: table or json')]
        string $format = 'table',
    ): int {
        $stderr = $this->stderr($output);

        if (($validationError = $this->validateFormat($format)) !== null) {
            return $this->invalid($stderr, $validationError);
        }

        $apiClient = $this->createApiClient($apiKey, $stderr);

        if ($apiClient === null) {
            return Command::FAILURE;
        }

        $this->logger->info('Listing Kinescope projects', ['format' => $format]);
        $projects = new Projects($apiClient);

        try {
            $items = array_values(array_map(
                static fn (ProjectDTO $project): array => $project->toArray(),
                $this->collectProjects($projects),
            ));
            $this->sortRowsByString($items, 'name');
        } catch (KinescopeException $e) {
            return $this->handleSdkFailure($stderr, $e);
        }

        $tableRows = array_map(
            static fn (array $project): array => [
                'id' => $project['id'],
                'name' => $project['name'],
                'items_count' => $project['items_count'],
                'folders' => is_array($project['folders']) ? count($project['folders']) : 0,
            ],
            $items,
        );

        $this->renderList(
            output: $output,
            format: $format,
            payload: [
                'resource' => 'project',
                'items' => $items,
            ],
            tableHeaders: ['id', 'name', 'items_count', 'folders'],
            tableRows: $tableRows,
        );

        return Command::SUCCESS;
    }
}
