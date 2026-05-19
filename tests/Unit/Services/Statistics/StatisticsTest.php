<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Statistics;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Kinescope\Exception\RateLimitException;
use Kinescope\Services\Statistics\Statistics;
use Kinescope\Services\Videos\Videos;
use Kinescope\Tests\Unit\FakeApiClient;
use PHPUnit\Framework\TestCase;

final class StatisticsTest extends TestCase
{
    public function testForAccountSumsDurationsAcrossPages(): void
    {
        $apiClient = new FakeApiClient()
            ->queueResponse($this->videoListResponse(5, 1, 3, [
                $this->videoRow('video-1', 10),
                $this->videoRow('video-2', 20),
                $this->videoRow('video-3', 30),
            ]))
            ->queueResponse($this->videoListResponse(5, 2, 3, [
                $this->videoRow('video-4', 40),
                $this->videoRow('video-5', 50),
            ]));

        $dto = $this->statistics($apiClient)->forAccount();

        $this->assertSame(5, $dto->videosCount);
        $this->assertSame(150, $dto->getTotalSeconds());
        $this->assertSame(2, $apiClient->requestCount());
    }

    public function testForAccountIssuesRequestsWithSingleStatusDone(): void
    {
        $apiClient = new FakeApiClient()
            ->queueResponse($this->videoListResponse(101, 1, 100, [$this->videoRow('video-1', 10)]))
            ->queueResponse($this->videoListResponse(101, 2, 100, [$this->videoRow('video-2', 20)]));

        $this->statistics($apiClient)->forAccount();

        $firstRequest = $apiClient->requestAt(0);
        $secondRequest = $apiClient->requestAt(1);

        $this->assertSame('/v1/videos', $firstRequest['endpoint']);
        $this->assertSame('/v1/videos', $secondRequest['endpoint']);
        $this->assertSame('done', $firstRequest['query']['status[]']);
        $this->assertSame('done', $secondRequest['query']['status[]']);
        $this->assertSame(100, $firstRequest['query']['per_page']);
        $this->assertSame(100, $secondRequest['query']['per_page']);
        $this->assertSame(1, $firstRequest['query']['page']);
        $this->assertSame(2, $secondRequest['query']['page']);
        $this->assertArrayNotHasKey('project_id', $firstRequest['query']);
        $this->assertArrayNotHasKey('folder_id', $firstRequest['query']);
    }

    public function testForProjectFiltersByProjectId(): void
    {
        $apiClient = new FakeApiClient()->queueResponse($this->videoListResponse(1, 1, 100, [
            $this->videoRow('video-1', 10),
        ]));

        $this->statistics($apiClient)->forProject('project-1');

        $query = $apiClient->requestAt(0)['query'];

        $this->assertSame('project-1', $query['project_id']);
        $this->assertSame('done', $query['status[]']);
    }

    public function testForFolderFiltersByFolderId(): void
    {
        $apiClient = new FakeApiClient()->queueResponse($this->videoListResponse(1, 1, 100, [
            $this->videoRow('video-1', 10),
        ]));

        $this->statistics($apiClient)->forFolder('folder-1');

        $query = $apiClient->requestAt(0)['query'];

        $this->assertSame('folder-1', $query['folder_id']);
        $this->assertSame('done', $query['status[]']);
    }

    public function testEmptyResultReturnsZeroDto(): void
    {
        $apiClient = new FakeApiClient()->queueResponse($this->videoListResponse(0, 1, 100, []));

        $dto = $this->statistics($apiClient)->forAccount();

        $this->assertSame(0, $dto->videosCount);
        $this->assertSame(0, $dto->getTotalSeconds());
        $this->assertSame(1, $apiClient->requestCount());
    }

    public function testGeneratedAtFallsInsideAggregationCallWindow(): void
    {
        $apiClient = new FakeApiClient()->queueResponse($this->videoListResponse(0, 1, 100, []));
        $statistics = $this->statistics($apiClient);

        $before = CarbonImmutable::now('UTC');
        $dto = $statistics->forAccount();
        $after = CarbonImmutable::now('UTC');

        $this->assertGreaterThanOrEqual($before, $dto->generatedAt);
        $this->assertLessThanOrEqual($after, $dto->generatedAt);
    }

    public function testForProjectRejectsEmptyId(): void
    {
        $apiClient = new FakeApiClient();

        $this->expectException(InvalidArgumentException::class);

        try {
            $this->statistics($apiClient)->forProject('');
        } finally {
            $this->assertSame(0, $apiClient->requestCount());
        }
    }

    public function testForFolderRejectsEmptyId(): void
    {
        $apiClient = new FakeApiClient();

        $this->expectException(InvalidArgumentException::class);

        try {
            $this->statistics($apiClient)->forFolder('');
        } finally {
            $this->assertSame(0, $apiClient->requestCount());
        }
    }

    public function testApiExceptionsBubble(): void
    {
        $apiClient = new FakeApiClient()
            ->queueResponse($this->videoListResponse(101, 1, 100, [$this->videoRow('video-1', 10)]))
            ->queueException(new RateLimitException());

        $this->expectException(RateLimitException::class);

        $this->statistics($apiClient)->forAccount();
    }

    private function statistics(FakeApiClient $apiClient): Statistics
    {
        return new Statistics(new Videos($apiClient));
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array<string, mixed>
     */
    private function videoListResponse(int $total, int $page, int $perPage, array $rows): array
    {
        return [
            'data' => $rows,
            'meta' => ['pagination' => ['total' => $total, 'page' => $page, 'per_page' => $perPage]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function videoRow(string $id, float|int $duration): array
    {
        return [
            'id' => $id,
            'title' => sprintf('Video %s', $id),
            'status' => 'done',
            'duration' => $duration,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ];
    }
}
