<?php

declare(strict_types=1);

namespace Kinescope\Tests\Integration\Services\Videos;

use Kinescope\Core\ApiClientFactory;
use Kinescope\Core\Credentials;
use Kinescope\Core\Pagination;
use Kinescope\DTO\Common\MetaDTO;
use Kinescope\DTO\Video\VideoDTO;
use Kinescope\DTO\Video\VideoListResult;
use Kinescope\Enum\VideoStatus;
use Kinescope\Exception\NotFoundException;
use Kinescope\Services\Videos\Videos;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Videos service.
 *
 * These tests verify that Videos service works correctly
 * against the real Kinescope API using read-only operations.
 *
 * Assumes the account has at least one video.
 *
 * @group integration
 */
class VideosTest extends TestCase
{
    private Videos $service;

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

        $this->service = new Videos($apiClient);
    }

    public function testListReturnsVideoListResult(): void
    {
        $result = $this->service->list();

        $this->assertInstanceOf(VideoListResult::class, $result);

        $meta = $result->getMeta();
        $this->assertInstanceOf(MetaDTO::class, $meta);
        $this->assertGreaterThanOrEqual(0, $meta->total);
    }

    public function testListWithPagination(): void
    {
        $result = $this->service->list(pagination: new Pagination(page: 1, perPage: 1));

        $data = $result->getData();
        $this->assertLessThanOrEqual(1, count($data));

        $meta = $result->getMeta();
        $this->assertSame(1, $meta->pagination->page);
        $this->assertGreaterThanOrEqual(0, $meta->total);
    }

    public function testListContainsVideoDTOs(): void
    {
        $result = $this->service->list(pagination: new Pagination(perPage: 5));

        if ($result->isEmpty()) {
            $this->markTestSkipped('No videos found in the account');
        }

        $data = $result->getData();
        $this->assertNotEmpty($data);
        $this->assertContainsOnlyInstancesOf(VideoDTO::class, $data);

        $firstVideo = $data[0];
        $this->assertNotEmpty($firstVideo->id);
        $this->assertInstanceOf(VideoStatus::class, $firstVideo->status);
    }

    public function testGetReturnsVideoDTO(): void
    {
        $list = $this->service->list(pagination: new Pagination(perPage: 1));

        if ($list->isEmpty()) {
            $this->markTestSkipped('No videos found in the account');
        }

        $firstVideo = $list->getData()[0];
        $video = $this->service->get($firstVideo->id);

        $this->assertInstanceOf(VideoDTO::class, $video);
        $this->assertSame($firstVideo->id, $video->id);
        $this->assertNotEmpty($video->title);
    }

    public function testGetThrowsNotFoundForInvalidId(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->get('00000000-0000-0000-0000-000000000000');
    }

    public function testListByProjectFiltersCorrectly(): void
    {
        $allVideos = $this->service->list(pagination: new Pagination(perPage: 5));

        if ($allVideos->isEmpty()) {
            $this->markTestSkipped('No videos found in the account');
        }

        $firstVideo = $allVideos->getData()[0];

        if ($firstVideo->projectId === null) {
            $this->markTestSkipped('First video has no project ID');
        }

        $projectVideos = $this->service->listByProject($firstVideo->projectId);

        $this->assertInstanceOf(VideoListResult::class, $projectVideos);
        $this->assertNotEmpty($projectVideos->getData());

        foreach ($projectVideos->getData() as $video) {
            $this->assertSame($firstVideo->projectId, $video->projectId);
        }
    }

    public function testSearchReturnsResults(): void
    {
        $allVideos = $this->service->list(pagination: new Pagination(perPage: 1));

        if ($allVideos->isEmpty()) {
            $this->markTestSkipped('No videos found in the account');
        }

        $firstVideo = $allVideos->getData()[0];
        $searchTerm = mb_substr($firstVideo->title, 0, 3);

        $searchResult = $this->service->search($searchTerm);

        $this->assertInstanceOf(VideoListResult::class, $searchResult);
        $this->assertGreaterThanOrEqual(0, $searchResult->getTotal());
    }
}
