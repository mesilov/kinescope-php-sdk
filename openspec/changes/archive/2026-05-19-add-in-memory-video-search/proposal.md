## Why

Consumers often receive a Kinescope embed URL from user input or a lesson title from an external system and need to map it back to a `VideoDTO` / `videoId` from videos they have already loaded. Today every consumer must reimplement that reverse lookup, including slug parsing and Russian title normalization.

This change adds a small in-memory search service for already loaded `VideoDTO` lists, keeping reverse lookup deterministic and reusable without adding API traffic or a full-text search dependency.

## What Changes

- Add `Kinescope\Services\Videos\InMemoryVideoSearch` as a local utility service over arrays of `VideoDTO`.
- Add `byEmbedLink(array $videos, string $embedLink): ?VideoDTO` to find the first video whose Kinescope slug matches a supplied canonical embed URL.
- Add `byName(array $videos, string $name): array` to return videos whose normalized title contains the normalized search text.
- Reuse the existing `VideoSlugExtractor` for resolving slugs from video DTOs, while accepting only canonical `https://kinescope.io/embed/{slug}` input for `byEmbedLink()`.
- Define name normalization explicitly: trim, lowercase with multibyte-safe behavior, replace `ё` with `е`, collapse whitespace, and remove one leading lesson-number prefix such as `1.3 `, `3. `, or `01) `.
- Reject empty embed links and empty names with `InvalidArgumentException`.
- Return `null` for non-empty non-canonical embed input, including query strings, fragments, trailing slashes, iframe HTML, and non-embed Kinescope links.
- Treat `$videos` as an array of `VideoDTO` values; preserve input order and return zero-based reindexed `byName()` result lists.
- Add focused unit coverage for embed lookup, title lookup, normalization, no-match behavior, and invalid input.

Out of scope:

- BM25, fuzzy search, stemming, ranking scores, or locale-specific full-text search.
- Any Kinescope API requests, pagination, caching, background indexing, or persistent storage.
- CLI commands or ServiceFactory wiring.
- Changing `VideoDTO`, `Videos::list()`, or raw API DTO mapping.

## Capabilities

### New Capabilities

- `in-memory-video-search`: Local reverse lookup over already loaded video DTOs by embed URL or normalized title.

### Modified Capabilities

- None.

## Impact

- Affected public SDK API: a new `Kinescope\Services\Videos\InMemoryVideoSearch` class.
- Affected code: `src/Services/Videos/`, unit tests under `tests/Unit/Services/Videos/`, README usage examples, and CHANGELOG.
- Dependencies: no new runtime or dev dependencies.
- External systems: none; this is a pure in-memory feature and does not require integration tests or credentials.
