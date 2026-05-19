## Context

The Kinescope raw API returns stable video identifiers and playback/embed links, but consumers sometimes start from external data: a pasted `https://kinescope.io/embed/{slug}` URL or a lesson name. Once a consumer has already loaded videos through `Videos::list()` or another SDK path, resolving that external value back to a `VideoDTO` should not require more API calls.

The SDK already has `VideoDTO` fields for `playLink`, `embedLink`, and `hlsLink`, plus `VideoSlugExtractor` for extracting Kinescope slugs from those links. This change adds a small in-memory search layer on top of those DTOs.

## Goals / Non-Goals

**Goals:**

- Provide a typed service for reverse lookup over an already loaded array whose values are `VideoDTO` instances.
- Find a single video by canonical Kinescope embed URL.
- Find all matching videos by normalized title text.
- Keep the search deterministic, dependency-free, and easy to test.
- Preserve input order in results instead of introducing ranking.
- Fail fast for invalid caller input such as empty search strings or arrays containing non-`VideoDTO` values.

**Non-Goals:**

- Do not add BM25, fuzzy matching, stemming, typo correction, ranking scores, or search indexes.
- Do not call the Kinescope API.
- Do not add persistent caches or background synchronization.
- Do not wire the service into `ServiceFactory`; consumers can instantiate it directly.
- Do not add CLI commands in this change.

## Decisions

- **Add `InMemoryVideoSearch` under `Kinescope\Services\Videos`.**
  - Rationale: the service operates on video DTOs and reuses video-specific slug parsing. Keeping it next to `Videos`, `VideoDownloader`, and `VideoSlugExtractor` makes the boundary clear.
  - Alternative considered: a generic `Search` namespace. Rejected because this is not a generic search subsystem; it knows about `VideoDTO` links and titles.

- **Make the service pure in-memory and dependency-free except for `VideoSlugExtractor`.**
  - Rationale: callers are responsible for loading the video array. The service only resolves values inside that array, which keeps it predictable and avoids hidden network cost.
  - `InMemoryVideoSearch` should accept a `VideoSlugExtractor` in the constructor and default to a new extractor when omitted.

- **Accept only canonical embed URLs as lookup input, then compare by slug.**
  - Rationale: the same video can be represented by `embedLink`, `playLink`, or `hlsLink` in a DTO. Comparing normalized slugs avoids coupling to one link format.
  - The input method remains named `byEmbedLink()` and accepts only canonical `https://kinescope.io/embed/{slug}` input, where `{slug}` has no `/`, `?`, `#`, or whitespace.
  - Non-empty input that is not canonical returns `null`. This includes embed URLs with query strings, fragments, trailing slashes, iframe HTML snippets, and non-embed Kinescope play links.
  - Extract the input slug with a strict full-match canonical parser. Do not rely on a loose substring parser for user input.
  - After the canonical input slug is extracted, DTO matching still uses `VideoSlugExtractor::fromVideoDTO()` so a loaded video can match via its `embedLink`, `playLink`, or `hlsLink`.
  - If multiple DTOs resolve to the same slug, return the first one in input order.

- **Search names with normalized substring matching.**
  - Rationale: the accepted first version is simple case-insensitive matching, not BM25. Normalized substring matching is easy to explain and stable in tests.
  - Normalization is: `trim`, `mb_strtolower`, replace `ё` / `Ё` with `е`, collapse all whitespace runs to a single ASCII space, strip one leading lesson-number prefix, then `trim` again.
  - A leading lesson-number prefix is removed only at the start of the string and only when it is followed by title text, for example `1.3 `, `3. `, or `01) `. Internal numbers remain part of the searchable title.
  - A video matches when the normalized `VideoDTO::$title` contains the normalized search text.
  - Returned results preserve input order, are zero-based lists, and do not preserve original array keys. There is no ranking or score.

- **Validate public inputs locally.**
  - Empty strings after `trim()` are caller errors and throw `InvalidArgumentException`.
  - The `$videos` array may have arbitrary keys, but every value must be a `VideoDTO` instance. Both public methods validate the entire array before matching so a non-video element throws `InvalidArgumentException` even when an earlier element would otherwise match. Returned `byName()` results are reindexed as zero-based lists.

- **Keep API and CLI surfaces unchanged.**
  - Rationale: this is a utility over loaded data, not a new remote service. Adding factory wiring or CLI commands would broaden the change without improving the core lookup contract.

## Risks / Trade-offs

- **Normalized substring matching will not find misspellings or semantically similar titles.** Mitigation: this is intentional for v1; fuzzy search can be introduced later as a separate ranked service when there is a real corpus and acceptance criteria.
- **Only simple leading lesson-number prefixes are stripped.** Mitigation: the supported forms cover the observed lesson-title mismatch while avoiding aggressive number removal inside real titles; richer title canonicalization can be added later without changing `byName()` return type.
- **Duplicate embed slugs return the first matching DTO.** Mitigation: duplicate videos are already ambiguous in the input data; preserving input order gives deterministic behavior.
- **No ServiceFactory wiring means discoverability depends on docs.** Mitigation: README and CHANGELOG will show direct construction and usage.
