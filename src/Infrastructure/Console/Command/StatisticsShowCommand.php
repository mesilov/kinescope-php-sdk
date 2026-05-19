<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console\Command;

use Kinescope\Exception\KinescopeException;
use Kinescope\Services\Statistics\Statistics;
use Kinescope\Services\Videos\Videos;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kinescope:statistics:show', description: 'Show Kinescope done-video statistics')]
final class StatisticsShowCommand extends AbstractKinescopeCommand
{
    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Kinescope API key (fallback: KINESCOPE_API_KEY env var)', shortcut: 'k')]
        ?string $apiKey = null,
        #[Option(description: 'Project UUID statistics scope')]
        ?string $projectId = null,
        #[Option(description: 'Folder UUID statistics scope')]
        ?string $folderId = null,
        #[Option(description: 'Output format: table or json')]
        string $format = 'table',
    ): int {
        $stderr = $this->stderr($output);

        if (($validationError = $this->validateFormat($format)) !== null) {
            return $this->invalid($stderr, $validationError);
        }

        if ($projectId !== null && $folderId !== null) {
            return $this->invalid($stderr, 'statistics:show accepts either --project-id or --folder-id, not both.');
        }

        if ($projectId !== null && ($validationError = $this->validateUuid('project-id', $projectId)) !== null) {
            return $this->invalid($stderr, $validationError);
        }

        if ($folderId !== null && ($validationError = $this->validateUuid('folder-id', $folderId)) !== null) {
            return $this->invalid($stderr, $validationError);
        }

        $apiClient = $this->createApiClient($apiKey, $stderr);

        if ($apiClient === null) {
            return Command::FAILURE;
        }

        $scope = 'account';
        $selector = null;
        $statisticsService = new Statistics(new Videos($apiClient));

        try {
            if ($projectId !== null) {
                $scope = 'project';
                $selector = $projectId;
                $statistics = $statisticsService->forProject($projectId);
            } elseif ($folderId !== null) {
                $scope = 'folder';
                $selector = $folderId;
                $statistics = $statisticsService->forFolder($folderId);
            } else {
                $statistics = $statisticsService->forAccount();
            }
        } catch (KinescopeException $e) {
            return $this->handleSdkFailure($stderr, $e);
        }

        $this->logger->info('Showing Kinescope statistics', [
            'scope' => $scope,
            'selector' => $selector,
            'format' => $format,
        ]);

        $statisticsRow = $statistics->toArray();
        $payload = [
            'resource' => 'statistics',
            'scope' => $scope,
            'statistics' => $statisticsRow,
        ];

        if ($projectId !== null) {
            $payload['projectId'] = $projectId;
        }

        if ($folderId !== null) {
            $payload['folderId'] = $folderId;
        }

        $this->renderList(
            output: $output,
            format: $format,
            payload: $payload,
            tableHeaders: ['scope', 'selector', 'videos_count', 'total_duration_seconds', 'total_minutes', 'total_hours', 'generated_at'],
            tableRows: [
                [
                    'scope' => $scope,
                    'selector' => $selector,
                    ...$statisticsRow,
                ],
            ],
        );

        return Command::SUCCESS;
    }
}
