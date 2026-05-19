<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos;

use InvalidArgumentException;
use Kinescope\DTO\Video\VideoDTO;

final readonly class InMemoryVideoSearch
{
    public function __construct(
        private VideoSlugExtractor $slugExtractor = new VideoSlugExtractor(),
    ) {
    }

    /**
     * @param array<mixed> $videos Values must be VideoDTO instances.
     */
    public function byEmbedLink(array $videos, string $embedLink): ?VideoDTO
    {
        $videos = $this->validatedVideos($videos);
        $embedLink = trim($embedLink);

        if ($embedLink === '') {
            throw new InvalidArgumentException('embedLink must not be empty.');
        }

        $slug = $this->canonicalEmbedSlug($embedLink);

        if ($slug === null) {
            return null;
        }

        foreach ($videos as $video) {
            if ($this->slugExtractor->fromVideoDTO($video) === $slug) {
                return $video;
            }
        }

        return null;
    }

    /**
     * @param array<mixed> $videos Values must be VideoDTO instances.
     *
     * @return list<VideoDTO>
     */
    public function byName(array $videos, string $name): array
    {
        $videos = $this->validatedVideos($videos);
        $name = $this->normalizeName($name);

        if ($name === '') {
            throw new InvalidArgumentException('name must not be empty.');
        }

        $matches = [];

        foreach ($videos as $video) {
            if (str_contains($this->normalizeName($video->title), $name)) {
                $matches[] = $video;
            }
        }

        return $matches;
    }

    /**
     * @param array<mixed> $videos
     *
     * @return list<VideoDTO>
     */
    private function validatedVideos(array $videos): array
    {
        foreach ($videos as $video) {
            if (! $video instanceof VideoDTO) {
                throw new InvalidArgumentException('videos must contain only VideoDTO instances.');
            }
        }

        return array_values($videos);
    }

    private function canonicalEmbedSlug(string $embedLink): ?string
    {
        if (preg_match('~^https://kinescope\.io/embed/([^/\s?#]+)$~u', $embedLink, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function normalizeName(string $name): string
    {
        $normalized = mb_strtolower(trim($name), 'UTF-8');
        $normalized = str_replace('ё', 'е', $normalized);
        $normalized = preg_replace('~\s+~u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('~^\d+(?:\.\d+)*[.)]?\s+(?=\S)~u', '', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
