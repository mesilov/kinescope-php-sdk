<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console\Command;

use InvalidArgumentException;
use Kinescope\Core\ApiClientFactory;
use Kinescope\Exception\KinescopeException;
use Kinescope\Exception\NotFoundException;
use Kinescope\Services\Videos\Videos;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'video:info', description: 'Fetch video info by ID and output as JSON')]
final class VideoInfoCommand extends Command
{
    public function __construct(
        private readonly ApiClientFactory $apiClientFactory,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    public function __invoke(
        #[Argument(description: 'Video UUID')]
        string $videoId,
        OutputInterface $output,
        #[Option(description: 'Kinescope API key (fallback: KINESCOPE_API_KEY env var)', shortcut: 'k')]
        ?string $apiKey = null,
    ): int {
        $this->logger->info('Fetching video info', ['videoId' => $videoId]);
        $stderr = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        try {
            $apiClient = $apiKey !== null
                ? $this->apiClientFactory->withApiKey($apiKey)->build()
                : $this->apiClientFactory->buildFromEnvironment();
        } catch (InvalidArgumentException) {
            $stderr->writeln('<error>API key not provided. Use --api-key or set KINESCOPE_API_KEY.</error>');

            return Command::FAILURE;
        }

        $videos = new Videos($apiClient);

        try {
            $video = $videos->get($videoId);
        } catch (NotFoundException) {
            $stderr->writeln(sprintf('<error>Video "%s" not found.</error>', $videoId));

            return Command::FAILURE;
        } catch (KinescopeException $e) {
            $stderr->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }

        $output->writeln(
            json_encode(
                $video->toArray(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            )
        );

        return Command::SUCCESS;
    }
}
