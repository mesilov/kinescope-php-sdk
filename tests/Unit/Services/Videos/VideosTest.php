<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Videos;

use Kinescope\Core\Pagination;
use Kinescope\Enum\HttpMethod;
use Kinescope\Enum\VideoStatus;
use Kinescope\Services\Videos\Videos;
use Kinescope\Tests\Unit\FakeApiClient;
use PHPUnit\Framework\TestCase;

final class VideosTest extends TestCase
{
    public function testListSerializesStatusEnumAsDocumentedSingleStatusQueryKey(): void
    {
        $apiClient = new FakeApiClient()->queueResponse($this->videoListResponse());
        $videos = new Videos($apiClient);

        $videos->list(status: VideoStatus::DONE);

        $request = $apiClient->requestAt(0);

        $this->assertSame(HttpMethod::GET, $request['method']);
        $this->assertSame('/v1/videos', $request['endpoint']);
        $this->assertSame('done', $request['query']['status[]']);
        $this->assertArrayNotHasKey('status', $request['query']);
    }

    public function testListOmitsStatusQueryWhenNoStatusProvided(): void
    {
        $apiClient = new FakeApiClient()->queueResponse($this->videoListResponse());
        $videos = new Videos($apiClient);

        $videos->list();

        $request = $apiClient->requestAt(0);

        $this->assertArrayNotHasKey('status[]', $request['query']);
        $this->assertArrayNotHasKey('status', $request['query']);
    }

    public function testListKeepsOtherFiltersWhenStatusEnumIsPassed(): void
    {
        $apiClient = new FakeApiClient()->queueResponse($this->videoListResponse());
        $videos = new Videos($apiClient);

        $videos->list(
            pagination: new Pagination(page: 2, perPage: 50),
            projectId: 'project-1',
            folderId: 'folder-1',
            search: 'needle',
            status: VideoStatus::DONE,
        );

        $query = $apiClient->requestAt(0)['query'];

        $this->assertSame(2, $query['page']);
        $this->assertSame(50, $query['per_page']);
        $this->assertSame('project-1', $query['project_id']);
        $this->assertSame('folder-1', $query['folder_id']);
        $this->assertSame('needle', $query['q']);
        $this->assertSame('done', $query['status[]']);
    }

    /**
     * @return array<string, mixed>
     */
    private function videoListResponse(): array
    {
        return [
            'data' => [],
            'meta' => ['pagination' => ['total' => 0, 'page' => 1, 'per_page' => 20]],
        ];
    }
}
