<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Videos;

use Kinescope\Contracts\ApiClientInterface;
use Kinescope\Enum\HttpMethod;
use Kinescope\Services\Videos\VideoFetcher;
use Kinescope\Services\Videos\Videos;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class VideoFetcherTest extends TestCase
{
    public function testFindByTitleReturnsSinglePageResults(): void
    {
        $apiClient = new class () implements ApiClientInterface {
            public function get(string $endpoint, array $query = []): array
            {
                return [
                    'data' => [
                        self::makeRow('video-1', 'slug-aaa'),
                        self::makeRow('video-2', 'slug-bbb'),
                    ],
                    'meta' => ['total' => 2, 'page' => 1, 'per_page' => 100, 'last_page' => 1],
                ];
            }

            /** @return array<string, mixed> */
            private static function makeRow(string $id, string $slug): array
            {
                return [
                    'id' => $id,
                    'title' => 'My Video',
                    'status' => 'done',
                    'duration' => 0,
                    'hls_link' => "https://kinescope.io/{$slug}/master.m3u8",
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-01T00:00:00Z',
                ];
            }

            public function post(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function put(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function patch(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function delete(string $endpoint, array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function request(HttpMethod $method, string $endpoint, array $options = []): array
            {
                throw new RuntimeException('Not implemented.');
            }
        };

        $fetcher = new VideoFetcher(new Videos($apiClient));
        $result = $fetcher->findByTitle('My Video');

        $this->assertCount(2, $result);
        $this->assertSame('video-1', $result[0]->id);
        $this->assertSame('video-2', $result[1]->id);
    }

    public function testFindByTitleMergesMultiplePages(): void
    {
        $apiClient = new class () implements ApiClientInterface {
            public function get(string $endpoint, array $query = []): array
            {
                $page = (int) ($query['page'] ?? 1);

                if ($page === 1) {
                    return [
                        'data' => [
                            ['id' => 'video-1', 'title' => 'Video', 'status' => 'done', 'duration' => 0, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                        ],
                        'meta' => ['total' => 2, 'page' => 1, 'per_page' => 100, 'last_page' => 2],
                    ];
                }

                return [
                    'data' => [
                        ['id' => 'video-2', 'title' => 'Video', 'status' => 'done', 'duration' => 0, 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                    ],
                    'meta' => ['total' => 2, 'page' => 2, 'per_page' => 100, 'last_page' => 2],
                ];
            }

            public function post(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function put(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function patch(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function delete(string $endpoint, array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function request(HttpMethod $method, string $endpoint, array $options = []): array
            {
                throw new RuntimeException('Not implemented.');
            }
        };

        $fetcher = new VideoFetcher(new Videos($apiClient));
        $result = $fetcher->findByTitle('Video');

        $this->assertCount(2, $result);
        $this->assertSame('video-1', $result[0]->id);
        $this->assertSame('video-2', $result[1]->id);
    }

    public function testFindByVideoSlugReturnsMatchingVideos(): void
    {
        $targetSlug = 'wDXNmhxAhnJeRtNjAVChzS';

        $apiClient = new class ($targetSlug) implements ApiClientInterface {
            public function __construct(private readonly string $targetSlug)
            {
            }

            public function get(string $endpoint, array $query = []): array
            {
                return [
                    'data' => [
                        ['id' => 'video-1', 'title' => 'Match', 'status' => 'done', 'duration' => 0, 'hls_link' => "https://kinescope.io/{$this->targetSlug}/master.m3u8", 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                        ['id' => 'video-2', 'title' => 'Other', 'status' => 'done', 'duration' => 0, 'hls_link' => 'https://kinescope.io/otherSlug/master.m3u8', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                    ],
                    'meta' => ['total' => 2, 'page' => 1, 'per_page' => 100, 'last_page' => 1],
                ];
            }

            public function post(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function put(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function patch(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function delete(string $endpoint, array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function request(HttpMethod $method, string $endpoint, array $options = []): array
            {
                throw new RuntimeException('Not implemented.');
            }
        };

        $fetcher = new VideoFetcher(new Videos($apiClient));
        $result = $fetcher->findByVideoSlug($targetSlug);

        $this->assertCount(1, $result);
        $this->assertSame('video-1', $result[0]->id);
    }

    public function testFindByVideoSlugReturnsEmptyArrayWhenNoMatch(): void
    {
        $apiClient = new class () implements ApiClientInterface {
            public function get(string $endpoint, array $query = []): array
            {
                return [
                    'data' => [
                        ['id' => 'video-1', 'title' => 'Other', 'status' => 'done', 'duration' => 0, 'hls_link' => 'https://kinescope.io/someOtherSlug/master.m3u8', 'created_at' => '2024-01-01T00:00:00Z', 'updated_at' => '2024-01-01T00:00:00Z'],
                    ],
                    'meta' => ['total' => 1, 'page' => 1, 'per_page' => 100, 'last_page' => 1],
                ];
            }

            public function post(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function put(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function patch(string $endpoint, array $data = [], array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function delete(string $endpoint, array $query = []): array
            {
                throw new RuntimeException('Not implemented.');
            }

            public function request(HttpMethod $method, string $endpoint, array $options = []): array
            {
                throw new RuntimeException('Not implemented.');
            }
        };

        $fetcher = new VideoFetcher(new Videos($apiClient));
        $result = $fetcher->findByVideoSlug('nonExistentSlug');

        $this->assertSame([], $result);
    }
}
