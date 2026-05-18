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

#[AsCommand(name: 'kinescope:video:asset:list', description: 'List sanitized assets for one Kinescope video')]
final class VideoAssetListCommand extends AbstractKinescopeCommand
{
    public function __invoke(
        #[Argument(description: 'Video UUID')]
        string $videoId,
        OutputInterface $output,
        #[Option(description: 'Kinescope API key (fallback: KINESCOPE_API_KEY env var)', shortcut: 'k')]
        ?string $apiKey = null,
        #[Option(description: 'Output format: table or json')]
        string $format = 'table',
    ): int {
        $stderr = $this->stderr($output);

        foreach ([
            $this->validateUuid('video-id', $videoId),
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

        $this->logger->info('Listing Kinescope video assets', ['videoId' => $videoId, 'format' => $format]);
        $videos = new Videos($apiClient);

        try {
            $video = $videos->get($videoId);
        } catch (NotFoundException) {
            return $this->failure($stderr, sprintf('Video "%s" not found.', $videoId));
        } catch (KinescopeException $e) {
            return $this->handleSdkFailure($stderr, $e);
        }

        $items = array_values(array_map($this->normalizeAsset(...), $video->assets));
        $this->sortAssetRows($items);

        if ($format === 'json') {
            $this->renderJson($output, [
                'resource' => 'video_asset',
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
}
