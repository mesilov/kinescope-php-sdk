# in-memory-video-search Specification

## Purpose
Specify the public in-memory video search service for finding already loaded `VideoDTO` objects by canonical embed link or normalized title without issuing Kinescope API requests.

## Requirements
### Requirement: Provide an in-memory video search service
The SDK SHALL expose `Kinescope\Services\Videos\InMemoryVideoSearch` as a public service for searching already loaded arrays whose values are `VideoDTO` instances without issuing Kinescope API requests.

#### Scenario: Service is instantiated directly
- **WHEN** a consumer creates `new InMemoryVideoSearch()`
- **THEN** the service is ready to search arrays whose values are `Kinescope\DTO\Video\VideoDTO` instances
- **AND** no API client or credentials are required

#### Scenario: Service accepts an explicit slug extractor
- **WHEN** a consumer creates `new InMemoryVideoSearch($slugExtractor)`
- **THEN** the service uses the supplied `Kinescope\Services\Videos\VideoSlugExtractor` for slug parsing

### Requirement: Find one video by embed link
`InMemoryVideoSearch` SHALL provide `byEmbedLink(array $videos, string $embedLink): ?VideoDTO` that returns the first video whose Kinescope slug matches the supplied canonical embed URL. A canonical embed URL SHALL be exactly `https://kinescope.io/embed/{slug}`, where `{slug}` contains no `/`, `?`, `#`, or whitespace.

#### Scenario: Canonical embed URL matches a video embed link
- **WHEN** `$videos` contains a `VideoDTO` whose `embedLink` is `https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB`
- **AND** `byEmbedLink($videos, 'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB')` is called
- **THEN** the matching `VideoDTO` is returned

#### Scenario: Canonical embed URL matches another video link representation
- **WHEN** `$videos` contains a `VideoDTO` whose `playLink` or `hlsLink` resolves to slug `oDko3nwPjHwpzmqUgxJmKB`
- **AND** `byEmbedLink($videos, 'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB')` is called
- **THEN** that `VideoDTO` is returned

#### Scenario: Duplicate slugs return the first input item
- **WHEN** two videos in `$videos` resolve to the same Kinescope slug
- **AND** `byEmbedLink()` is called with a matching embed link
- **THEN** the first matching `VideoDTO` in the input array order is returned

#### Scenario: Embed link has no match
- **WHEN** no video in `$videos` resolves to the supplied embed slug
- **THEN** `byEmbedLink()` returns `null`

#### Scenario: Embed URL with query string is not canonical
- **WHEN** `byEmbedLink($videos, 'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB?autoplay=1')` is called
- **THEN** `byEmbedLink()` returns `null`

#### Scenario: Embed URL with fragment is not canonical
- **WHEN** `byEmbedLink($videos, 'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB#t=10')` is called
- **THEN** `byEmbedLink()` returns `null`

#### Scenario: Embed URL with trailing slash is not canonical
- **WHEN** `byEmbedLink($videos, 'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB/')` is called
- **THEN** `byEmbedLink()` returns `null`

#### Scenario: Iframe HTML is not canonical
- **WHEN** `byEmbedLink($videos, '<iframe src="https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB"></iframe>')` is called
- **THEN** `byEmbedLink()` returns `null`

#### Scenario: Kinescope play link is not canonical
- **WHEN** `byEmbedLink($videos, 'https://kinescope.io/oDko3nwPjHwpzmqUgxJmKB')` is called
- **THEN** `byEmbedLink()` returns `null`

### Requirement: Find videos by normalized name
`InMemoryVideoSearch` SHALL provide `byName(array $videos, string $name): array` that returns all videos whose normalized title contains the normalized search text. Name normalization SHALL trim, lowercase with multibyte-safe behavior, treat `ё` and `е` as equivalent, collapse whitespace runs, and remove one leading lesson-number prefix such as `1.3 `, `3. `, or `01) ` from the start of both the video title and the search text. The returned value SHALL be a zero-based `list<VideoDTO>` and SHALL NOT preserve original array keys.

#### Scenario: Name search is case-insensitive
- **WHEN** `$videos` contains a `VideoDTO` titled `3. Сегментация и Емкость рынка`
- **AND** `byName($videos, 'сегментация')` is called
- **THEN** the returned list contains that `VideoDTO`

#### Scenario: Name search treats ё and е as equivalent
- **WHEN** `$videos` contains a `VideoDTO` titled `3. Сегментация и Емкость рынка`
- **AND** `byName($videos, 'ёмкость рынка')` is called
- **THEN** the returned list contains that `VideoDTO`

#### Scenario: Name search collapses whitespace
- **WHEN** `$videos` contains a `VideoDTO` titled `3. Сегментация и Емкость рынка`
- **AND** `byName($videos, "Сегментация   и\nЕмкость")` is called
- **THEN** the returned list contains that `VideoDTO`

#### Scenario: Name search ignores leading lesson numbering
- **WHEN** `$videos` contains a `VideoDTO` titled `3. Сегментация и Емкость рынка`
- **AND** `byName($videos, '1.3 Сегментация и ёмкость рынка')` is called
- **THEN** the returned list contains that `VideoDTO`

#### Scenario: Name search returns all matches in input order
- **WHEN** `$videos` contains three videos and the first and third titles match the normalized search text
- **THEN** `byName()` returns exactly the first and third `VideoDTO` instances in their original order as a zero-based list

#### Scenario: Name search has no match
- **WHEN** no video title contains the normalized search text
- **THEN** `byName()` returns an empty array

### Requirement: Validate in-memory search inputs
The SDK SHALL validate `InMemoryVideoSearch` inputs locally and SHALL NOT return partial results when caller input is invalid. Both public methods SHALL validate every `$videos` item before performing any match.

#### Scenario: Empty embed link is rejected
- **WHEN** `byEmbedLink($videos, '   ')` is called
- **THEN** the method throws `InvalidArgumentException`

#### Scenario: Empty name is rejected
- **WHEN** `byName($videos, '   ')` is called
- **THEN** the method throws `InvalidArgumentException`

#### Scenario: Non-video array item is rejected
- **WHEN** `$videos` contains a value that is not a `VideoDTO`, even after an otherwise matching `VideoDTO`
- **THEN** `byEmbedLink()` and `byName()` throw `InvalidArgumentException`
- **AND** no partial result is returned

### Requirement: Keep in-memory search independent from API and CLI wiring
`InMemoryVideoSearch` SHALL NOT be wired into `ServiceFactory`, CLI command registration, or HTTP clients in this change.

#### Scenario: Search performs no API requests
- **WHEN** `byEmbedLink()` or `byName()` is called
- **THEN** the result is computed only from the supplied `$videos` array
- **AND** no Kinescope API request is made

#### Scenario: Factory services remain unchanged
- **WHEN** a consumer uses `Kinescope\Services\ServiceFactory`
- **THEN** no new `inMemoryVideoSearch()` factory method is required by this change
