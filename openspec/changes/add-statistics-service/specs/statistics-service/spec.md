## ADDED Requirements

### Requirement: Expose a Statistics service on the SDK factory
The SDK SHALL expose `Kinescope\Services\Statistics\Statistics` via `ServiceFactory::statistics(): Statistics`, instantiated lazily on first access and reused on subsequent calls.

#### Scenario: Factory returns the same instance on repeated calls
- **WHEN** `$factory->statistics()` is called twice on the same `ServiceFactory`
- **THEN** both calls return the identical `Statistics` instance

#### Scenario: Service is constructed with the factory's Videos service
- **WHEN** `ServiceFactory::statistics()` instantiates `Statistics` for the first time
- **THEN** the new `Statistics` is constructed with the same `Videos` instance returned by `ServiceFactory::videos()`

### Requirement: Provide a StatisticsDTO that stores duration as a CarbonInterval
The SDK SHALL expose `Kinescope\DTO\Statistics\StatisticsDTO` as a `final readonly` class with public `int $videosCount`, `Carbon\CarbonInterval $totalDuration`, and `DateTimeImmutable $generatedAt`, and SHALL be the return type of every public method on `Statistics`.

#### Scenario: DTO carries a non-negative count, a duration, and a generation timestamp
- **WHEN** a consumer reads a `StatisticsDTO` returned by any public `Statistics` method
- **THEN** `$dto->videosCount >= 0`, `$dto->totalDuration` is a `Carbon\CarbonInterval` whose `totalSeconds` is `>= 0`, and `$dto->generatedAt` is a `DateTimeImmutable` captured when the aggregation started

#### Scenario: DTO exposes whole-second access via getTotalSeconds
- **WHEN** `$dto->totalDuration` represents `90` seconds
- **THEN** `$dto->getTotalSeconds() === 90`

#### Scenario: DTO exposes derived minute and hour totals as rounded integers
- **WHEN** `$dto->totalDuration` represents `90` seconds
- **THEN** `$dto->getTotalMinutes() === 2` (rounds `1.5` to the nearest whole minute) and `$dto->getTotalHours() === 0`

#### Scenario: Zero-duration totals report cleanly
- **WHEN** `$dto->totalDuration` represents `0` seconds
- **THEN** `$dto->getTotalSeconds() === 0`, `$dto->getTotalMinutes() === 0`, and `$dto->getTotalHours() === 0`

#### Scenario: Exact-hour totals report exact integers
- **WHEN** `$dto->totalDuration` represents `3600` seconds
- **THEN** `$dto->getTotalMinutes() === 60` and `$dto->getTotalHours() === 1`

#### Scenario: Half-hour totals round up
- **WHEN** `$dto->totalDuration` represents `5400` seconds
- **THEN** `$dto->getTotalMinutes() === 90` and `$dto->getTotalHours() === 2` (rounds `1.5` to the nearest whole hour)

#### Scenario: forHumans delegates to CarbonInterval
- **WHEN** `$dto->forHumans()` is called on a DTO whose `$totalDuration` is non-zero
- **THEN** it returns the same string as `$dto->totalDuration->forHumans()`

#### Scenario: toArray emits derived totals alongside raw seconds
- **WHEN** `StatisticsDTO::toArray()` is called
- **THEN** the returned array contains exactly the keys `videos_count`, `total_duration_seconds`, `total_minutes`, `total_hours`, and `generated_at`, where `total_duration_seconds` is `(int) $dto->totalDuration->totalSeconds`, `total_minutes` and `total_hours` are the rounded integer values returned by `getTotalMinutes()` / `getTotalHours()`, and `generated_at` is an ISO 8601 string (`DateTimeInterface::ATOM`)

### Requirement: Aggregate statistics across the whole account
The SDK SHALL provide `Statistics::forAccount(): StatisticsDTO` that aggregates over every `done` video accessible to the configured API key.

#### Scenario: forAccount sums durations across paginated responses
- **WHEN** the API returns two pages of `done` videos whose `duration` values sum to `18450` seconds and the first page reports `meta.pagination.total === 42`
- **THEN** `forAccount()` returns a `StatisticsDTO` with `videosCount === 42` and `$dto->getTotalSeconds() === 18450`

#### Scenario: forAccount issues every request with status=done and no scope filters
- **WHEN** `forAccount()` paginates `Videos::list`
- **THEN** every request carries `status = 'done'` and omits the `projectId` and `folderId` filters

### Requirement: Aggregate statistics within a project
The SDK SHALL provide `Statistics::forProject(string $projectId): StatisticsDTO` that aggregates over every `done` video in the given project.

#### Scenario: forProject filters by projectId
- **WHEN** `forProject('prj-123')` is invoked
- **THEN** every paginated call to `Videos::list` carries `projectId = 'prj-123'` and `status = 'done'`

#### Scenario: forProject rejects empty projectId
- **WHEN** `forProject('')` is invoked
- **THEN** the call throws `InvalidArgumentException` and no HTTP request is made

### Requirement: Aggregate statistics within a folder
The SDK SHALL provide `Statistics::forFolder(string $folderId): StatisticsDTO` that aggregates over every `done` video in the given folder.

#### Scenario: forFolder filters by folderId
- **WHEN** `forFolder('fld-abc')` is invoked
- **THEN** every paginated call to `Videos::list` carries `folderId = 'fld-abc'` and `status = 'done'`

#### Scenario: forFolder rejects empty folderId
- **WHEN** `forFolder('')` is invoked
- **THEN** the call throws `InvalidArgumentException` and no HTTP request is made

### Requirement: Capture videosCount from the first response and paginate until exhausted
The SDK SHALL set `videosCount` from the `meta.pagination.total` of the first paginated response, and SHALL continue paginating with a fixed `per_page = 100` while `PaginatedResponse::hasNextPage()` reports more pages.

#### Scenario: videosCount mirrors meta.total of the first response
- **WHEN** the first paginated response reports `meta.pagination.total === 7`
- **THEN** the resulting `StatisticsDTO::$videosCount === 7`, regardless of how many `VideoDTO` rows are summed across subsequent pages

#### Scenario: Empty scope returns a zeroed snapshot after one request
- **WHEN** the scope contains no `done` videos
- **THEN** the method issues exactly one paginated request and returns a `StatisticsDTO` with `videosCount === 0`, `totalDurationSeconds === 0`, and a non-null `generatedAt`

#### Scenario: Pagination continues while hasNextPage is true
- **WHEN** the API reports three pages of `done` videos with `hasNextPage() === true` for pages 1 and 2 and `false` for page 3
- **THEN** the service issues exactly three paginated requests and the durations from all three pages are summed into `totalDurationSeconds`

### Requirement: Propagate API errors without partial results
The SDK SHALL propagate `Kinescope\Exception\KinescopeException` subclasses raised by the underlying paginated calls, and SHALL NOT return a partially aggregated `StatisticsDTO` when iteration fails.

#### Scenario: A rate-limit mid-iteration aborts the aggregate
- **WHEN** the second paginated request raises `RateLimitException`
- **THEN** the exception bubbles up to the caller and no `StatisticsDTO` is returned

#### Scenario: An authentication failure propagates as AuthenticationException
- **WHEN** the API rejects the configured API key on the first request
- **THEN** the underlying `AuthenticationException` is raised without being wrapped or swallowed
