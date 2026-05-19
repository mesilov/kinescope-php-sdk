<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console\Command;

use Kinescope\Exception\KinescopeException;
use Kinescope\Exception\NotFoundException;
use Kinescope\Services\Videos\Videos;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kinescope:video:show', description: 'Fetch one Kinescope video as JSON')]
final class VideoShowCommand extends AbstractKinescopeCommand
{
    public function __invoke(
        #[Argument(description: 'Video UUID')]
        string $videoId,
        OutputInterface $output,
        #[Option(description: 'Kinescope API key (fallback: KINESCOPE_API_KEY env var)', shortcut: 'k')]
        ?string $apiKey = null,
    ): int {
        $stderr = $this->stderr($output);

        if (($validationError = $this->validateUuid('video-id', $videoId)) !== null) {
            return $this->invalid($stderr, $validationError);
        }

        $apiClient = $this->createApiClient($apiKey, $stderr);

        if ($apiClient === null) {
            return Command::FAILURE;
        }

        $this->logger->info('Fetching Kinescope video', ['videoId' => $videoId]);
        $videos = new Videos($apiClient);

        try {
            $video = $videos->get($videoId);
        } catch (NotFoundException) {
            return $this->failure($stderr, sprintf('Video "%s" not found.', $videoId));
        } catch (KinescopeException $e) {
            return $this->handleSdkFailure($stderr, $e);
        }

        $this->renderJson($output, $video->toArray());

        return Command::SUCCESS;
    }
}
