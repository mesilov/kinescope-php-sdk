# Plan: VideoFetcher + VideoSlugExtractor services

## Context

The SDK needs two new services to help consumers find videos by human-readable attributes:
1. **`VideoFetcher`** — search videos by title or by video slug, returning `VideoDTO[]` with auto-pagination.
2. **`VideoSlugExtractor`** — extract a video slug from an HLS link, embed code, or a `VideoDTO`.

A "video slug" is the short ID embedded in kinescope.io URLs:
- HLS link: `https://kinescope.io/wDXNmhxAhnJeRtNjAVChzS/master.m3u8` → `wDXNmhxAhnJeRtNjAVChzS`
- Embed code: `<iframe src="https://kinescope.io/embed/wDXNmhxAhnJeRtNjAVChzS" ...>` → `wDXNmhxAhnJeRtNjAVChzS`

## Files to Create

| Path | Action |
|------|--------|
| `src/Services/Videos/VideoSlugExtractor.php` | Create — pure extraction logic, no deps |
| `src/Services/Videos/VideoFetcher.php` | Create — wraps `Videos`, auto-paginated search |
| `tests/Unit/Services/Videos/VideoSlugExtractorTest.php` | Create — pure unit tests |
| `tests/Unit/Services/Videos/VideoFetcherTest.php` | Create — stub `ApiClientInterface` → `Videos` → `VideoFetcher` |

No `composer.json` changes needed — no new dependencies.

## Implementation

### `src/Services/Videos/VideoSlugExtractor.php`

Pure stateless service — no constructor, no dependencies.

```php
<?php
declare(strict_types=1);
namespace Kinescope\Services\Videos;

use Kinescope\DTO\Video\VideoDTO;

final class VideoSlugExtractor
{
    /** Extracts slug from HLS link, e.g. https://kinescope.io/{slug}/master.m3u8 */
    public function fromHlsLink(string $hlsLink): ?string
    {
        if (preg_match('~kinescope\.io/([^/]+)/master\.m3u8~', $hlsLink, $m)) {
            return $m[1];
        }
        return null;
    }

    /** Extracts slug from embed code, e.g. <iframe src="https://kinescope.io/embed/{slug}" ...> */
    public function fromEmbedCode(string $embedCode): ?string
    {
        if (preg_match('~kinescope\.io/embed/([^"\'\\s/]+)~', $embedCode, $m)) {
            return $m[1];
        }
        return null;
    }

    /** Tries hlsLink first, then embedCode. Returns null if neither is present/parseable. */
    public function fromVideoDTO(VideoDTO $video): ?string
    {
        if ($video->hlsLink !== null) {
            $slug = $this->fromHlsLink($video->hlsLink);
            if ($slug !== null) {
                return $slug;
            }
        }
        if ($video->embedCode !== null) {
            return $this->fromEmbedCode($video->embedCode);
        }
        return null;
    }
}
```

---

### `src/Services/Videos/VideoFetcher.php`

Takes `Videos` as constructor dependency (same pattern as `VideoDownloader`).
Uses `Pagination::MAX_PER_PAGE` (100) for efficient bulk fetching.

```php
<?php
declare(strict_types=1);
namespace Kinescope\Services\Videos;

use Kinescope\Core\Pagination;
use Kinescope\DTO\Video\VideoDTO;

final class VideoFetcher
{
    public function __construct(
        private readonly Videos $videos,
        private readonly VideoSlugExtractor $slugExtractor = new VideoSlugExtractor(),
    ) {}

    /**
     * Find all videos whose title matches the search query (server-side, paginated).
     * Uses Videos::search() which calls the dedicated /v1/videos/search endpoint.
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
     * Paginates through Videos::list() and filters by extracted slug.
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
```

---

### `tests/Unit/Services/Videos/VideoSlugExtractorTest.php`

Pure unit tests — no mocks. Cover all three methods with:
- Valid input → correct slug
- Invalid/unrelated URL → null
- `fromVideoDTO` priority: hlsLink first, fallback to embedCode, null if both absent

---

### `tests/Unit/Services/Videos/VideoFetcherTest.php`

Follow pattern from `VideoDownloaderEventTest.php`:
- Create anonymous class implementing `ApiClientInterface` as stub
- Pass stub into real `Videos` instance
- Pass `Videos` into `VideoFetcher`

Scenarios:
- `findByTitle` single page: stub returns 1 page with matching videos, no next page
- `findByTitle` multi-page: stub returns 2 pages, verifies both pages merged
- `findByVideoSlug` single page: stub returns videos, one matches slug
- `findByVideoSlug` no match: stub returns videos, none match slug → empty array

## Key Reused Existing Code

- `Kinescope\Services\Videos\Videos::search()` — `src/Services/Videos/Videos.php`
- `Kinescope\Services\Videos\Videos::list()` — `src/Services/Videos/Videos.php`
- `Kinescope\Core\Pagination::firstPage()` / `nextPage()` — `src/Core/Pagination.php`
- `Kinescope\DTO\Common\PaginatedResponse::hasNextPage()` / `getData()` — `src/DTO/Common/PaginatedResponse.php`
- `Kinescope\DTO\Video\VideoDTO::$hlsLink` / `$embedCode` — `src/DTO/Video/VideoDTO.php`
- `Kinescope\Contracts\ApiClientInterface` — `src/Contracts/ApiClientInterface.php`

## Verification

```bash
make test-unit                          # run all unit tests including new ones
make lint-all                           # PHPStan + CS-Fixer + Rector
```
