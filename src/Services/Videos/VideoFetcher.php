<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos;

use Kinescope\Core\Pagination;
use Kinescope\DTO\Video\VideoDTO;

final readonly class VideoFetcher
{
    public function __construct(
        private Videos $videos,
        private VideoSlugExtractor $slugExtractor = new VideoSlugExtractor(),
    ) {
    }

    /**
     * Find all videos whose title matches the search query (server-side, paginated).
     *
     * @return VideoDTO[]
     */
    public function findByTitle(string $title): array
    {
        $pagination = Pagination::firstPage(perPage: Pagination::MAX_PER_PAGE);
        $results = [];

        do {
            $page = $this->videos->search($title, $pagination);
            $results = array_merge($results, $page->getData());
            $pagination = $pagination->nextPage();
        } while ($page->hasNextPage());

        return $results;
    }

    /**
     * Find all videos whose slug matches (client-side filter, full list paginated).
     *
     * @return VideoDTO[]
     */
    public function findByVideoSlug(string $slug): array
    {
        $pagination = Pagination::firstPage(perPage: Pagination::MAX_PER_PAGE);
        $results = [];

        do {
            $page = $this->videos->list($pagination);

            foreach ($page->getData() as $video) {
                if ($this->slugExtractor->fromVideoDTO($video) === $slug) {
                    $results[] = $video;
                }
            }
            $pagination = $pagination->nextPage();
        } while ($page->hasNextPage());

        return $results;
    }
}
