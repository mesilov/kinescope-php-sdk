<?php

declare(strict_types=1);

namespace Kinescope\Tests\Integration\Services\Videos;

use Kinescope\Core\ApiClientFactory;
use Kinescope\Core\Credentials;
use Kinescope\Core\Pagination;
use Kinescope\DTO\Video\VideoDTO;
use Kinescope\Services\Videos\VideoFetcher;
use Kinescope\Services\Videos\Videos;
use Kinescope\Services\Videos\VideoSlugExtractor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
class VideoFetcherTest extends TestCase
{
    private Videos $videos;

    private VideoFetcher $fetcher;

    private VideoSlugExtractor $slugExtractor;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = getenv('KINESCOPE_API_KEY');

        if ($apiKey === false || $apiKey === '') {
            $this->markTestSkipped('KINESCOPE_API_KEY environment variable not set');
        }

        $credentials = Credentials::fromString($apiKey);
        $apiClient = ApiClientFactory::create()
            ->withCredentials($credentials)
            ->build();

        $this->videos = new Videos($apiClient);
        $this->slugExtractor = new VideoSlugExtractor();
        $this->fetcher = new VideoFetcher($this->videos, $this->slugExtractor);
    }

    public function testFindByTitleReturnsMatchingVideoFromRealApi(): void
    {
        $video = $this->firstVideo();

        $result = $this->fetcher->findByTitle($video->title);

        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf(VideoDTO::class, $result);
        $this->assertContains($video->id, array_map(
            static fn (VideoDTO $video): string => $video->id,
            $result
        ));
    }

    public function testFindByVideoSlugReturnsMatchingVideoFromRealApi(): void
    {
        [$video, $slug] = $this->firstVideoWithSlug();

        $result = $this->fetcher->findByVideoSlug($slug);

        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf(VideoDTO::class, $result);
        $this->assertContains($video->id, array_map(
            static fn (VideoDTO $video): string => $video->id,
            $result
        ));

        foreach ($result as $matchedVideo) {
            $this->assertSame($slug, $this->slugExtractor->fromVideoDTO($matchedVideo));
        }
    }

    public function testFindByVideoSlugReturnsEmptyArrayForUnknownSlug(): void
    {
        $result = $this->fetcher->findByVideoSlug('codex-integration-test-missing-slug');

        $this->assertSame([], $result);
    }

    private function firstVideo(): VideoDTO
    {
        $videos = $this->videos->list(pagination: new Pagination(perPage: 1));

        if ($videos->isEmpty()) {
            $this->markTestSkipped('No videos found in the account');
        }

        return $videos->getData()[0];
    }

    /**
     * @return array{0: VideoDTO, 1: string}
     */
    private function firstVideoWithSlug(): array
    {
        $videos = $this->videos->list(pagination: new Pagination(perPage: Pagination::MAX_PER_PAGE));

        foreach ($videos->getData() as $video) {
            $slug = $this->slugExtractor->fromVideoDTO($video);

            if ($slug !== null) {
                return [$video, $slug];
            }
        }

        $this->markTestSkipped('No videos with extractable Kinescope slug found in the account');
    }
}
