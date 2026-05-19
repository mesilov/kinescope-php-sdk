## 1. In-memory search service

- [x] 1.1 Create `Kinescope\Services\Videos\InMemoryVideoSearch` as a public SDK class.
- [x] 1.2 Add constructor support for an optional `VideoSlugExtractor`, defaulting to a new extractor when omitted.
- [x] 1.3 Implement local validation that rejects empty search strings and validates the entire `$videos` array contains only `VideoDTO` values before performing any match.
- [x] 1.4 Implement `byEmbedLink(array $videos, string $embedLink): ?VideoDTO` by accepting only canonical `https://kinescope.io/embed/{slug}` input through a strict full-match parser and comparing the extracted slug with each video's slug from `VideoSlugExtractor::fromVideoDTO()`.
- [x] 1.5 Implement `byName(array $videos, string $name): array` using normalized substring matching over `VideoDTO::$title`, preserving input order and returning a zero-based `list<VideoDTO>`.
- [x] 1.6 Keep the service independent from `ApiClient`, `Videos`, `ServiceFactory`, and console command wiring.

## 2. Unit tests

- [x] 2.1 Add `tests/Unit/Services/Videos/InMemoryVideoSearchTest.php`.
- [x] 2.2 Cover direct construction with the default slug extractor and construction with an explicit extractor.
- [x] 2.3 Cover `byEmbedLink()` returning a matching DTO by embed link.
- [x] 2.4 Cover `byEmbedLink()` matching DTOs that expose the same slug through `playLink` or `hlsLink`.
- [x] 2.5 Cover duplicate slug behavior: first matching DTO in input order wins.
- [x] 2.6 Cover `byEmbedLink()` returning `null` for canonical embed URLs with no match.
- [x] 2.7 Cover `byEmbedLink()` returning `null` for non-canonical input: query string, fragment, trailing slash, iframe HTML, and Kinescope play link.
- [x] 2.8 Cover `byName()` case-insensitive matching.
- [x] 2.9 Cover `byName()` treating `ё` and `е` as equivalent.
- [x] 2.10 Cover `byName()` whitespace normalization.
- [x] 2.11 Cover `byName()` ignoring leading lesson-number prefixes such as `1.3 ` and `3. `.
- [x] 2.12 Cover `byName()` returning all matches in input order as a zero-based list and returning an empty array when nothing matches.
- [x] 2.13 Cover invalid empty search strings for both public methods.
- [x] 2.14 Cover invalid non-`VideoDTO` array items for both public methods, including when the invalid item appears after an otherwise matching `VideoDTO`.

## 3. Documentation

- [x] 3.1 Add a README usage example showing how to load videos, instantiate `InMemoryVideoSearch`, and resolve a `videoId` by embed link or name.
- [x] 3.2 Add a CHANGELOG entry for the in-memory video search service.
- [x] 3.3 Document that the name search is normalized substring matching, not BM25 or fuzzy search.

## 4. Validation

- [x] 4.1 Run `make openspec-validate`.
- [x] 4.2 Run `make test-unit`.
- [x] 4.3 Run `make lint-all`.
- [x] 4.4 Do not add integration-test requirements for this change because the feature is pure in-memory and does not call the Kinescope API.
