<?php

declare(strict_types=1);

namespace Kinescope\Services\Statistics;

use Carbon\CarbonInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Kinescope\Core\Pagination;
use Kinescope\DTO\Statistics\StatisticsDTO;
use Kinescope\Enum\VideoStatus;
use Kinescope\Services\Videos\Videos;

final readonly class Statistics
{
    public function __construct(
        private Videos $videos,
    ) {
    }

    public function forAccount(): StatisticsDTO
    {
        return $this->aggregate(null, null);
    }

    public function forProject(string $projectId): StatisticsDTO
    {
        if ($projectId === '') {
            throw new InvalidArgumentException('Project ID cannot be empty.');
        }

        return $this->aggregate($projectId, null);
    }

    public function forFolder(string $folderId): StatisticsDTO
    {
        if ($folderId === '') {
            throw new InvalidArgumentException('Folder ID cannot be empty.');
        }

        return $this->aggregate(null, $folderId);
    }

    private function aggregate(?string $projectId, ?string $folderId): StatisticsDTO
    {
        $generatedAt = new DateTimeImmutable();
        $page = 1;
        $perPage = Pagination::MAX_PER_PAGE;
        $totalSeconds = 0;
        $videosCount = 0;

        do {
            $result = $this->videos->list(
                pagination: new Pagination(page: $page, perPage: $perPage),
                projectId: $projectId,
                folderId: $folderId,
                status: VideoStatus::DONE,
            );

            if ($page === 1) {
                $videosCount = $result->getTotal();
            }

            foreach ($result->getData() as $video) {
                $totalSeconds += $video->duration;
            }

            ++$page;
        } while ($result->hasNextPage());

        return new StatisticsDTO(
            videosCount: $videosCount,
            totalDuration: CarbonInterval::seconds($totalSeconds),
            generatedAt: $generatedAt,
        );
    }
}
