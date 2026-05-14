## 0. SDK contract prerequisites

### Video status contract

- [x] 0.1 Extend `Kinescope\Enum\VideoStatus` with `PRE_PROCESSING = 'pre-processing'` and `ABORTED = 'aborted'`.
- [x] 0.2 Update `VideoStatus::isProcessing()` so `PENDING`, `UPLOADING`, `PRE_PROCESSING`, and `PROCESSING` return `true`, while `ABORTED`, `DONE`, and `ERROR` return `false`.
- [x] 0.3 Update `VideoStatus::getLabel()` and enum unit tests for every documented value.
- [x] 0.4 Change `Kinescope\Services\Videos\Videos::list()` so the `status` argument is `?VideoStatus` instead of `?string`.
- [x] 0.5 Serialize the single status enum as documented query key `status[]` with scalar value `$status->value`; do not introduce `array<VideoStatus>` support in this change.
- [x] 0.6 Update all callers/tests that pass raw status strings to pass `VideoStatus` cases.

### Video duration contract

- [x] 0.7 Change `VideoDTO::fromArray()` so numeric API `duration` values are normalized with `(int) round((float) $data['duration'])`; missing duration remains `0`.
- [x] 0.8 Add `VideoDTO` unit coverage for duration rounding: `59.96` → `60`, `179.305` → `179`, `179.5` → `180`, and missing duration → `0`.
- [x] 0.9 Confirm duration formatting and `VideoListResult::getTotalDuration()` continue to use normalized whole-second `VideoDTO::$duration` values.

## 1. StatisticsDTO

- [x] 1.1 Create `Kinescope\DTO\Statistics\StatisticsDTO` as `final readonly` with constructor parameters `int $videosCount`, `Carbon\CarbonInterval $totalDuration`, `DateTimeImmutable $generatedAt` (in that order, all public).
- [x] 1.2 Implement `getTotalSeconds(): int` returning `(int) $this->totalDuration->totalSeconds`.
- [x] 1.3 Implement `getTotalMinutes(): int` returning `(int) round($this->totalDuration->totalMinutes)`.
- [x] 1.4 Implement `getTotalHours(): int` returning `(int) round($this->totalDuration->totalHours)`.
- [x] 1.5 Implement `forHumans(): string` returning `$this->totalDuration->forHumans()` (locale-aware via Carbon).
- [x] 1.6 Implement `toArray(): array` emitting keys `videos_count`, `total_duration_seconds`, `total_minutes`, `total_hours`, `generated_at` (ISO 8601 via `DateTimeInterface::ATOM`); `total_minutes` and `total_hours` are the rounded `int` values from 1.3 / 1.4.
- [x] 1.7 Add `tests/Unit/DTO/Statistics/StatisticsDTOTest.php` covering:
  - `CarbonInterval::seconds(0)` → `0` seconds, `0` minutes, `0` hours.
  - `CarbonInterval::seconds(90)` → `90` seconds, `2` minutes (rounds `1.5` up), `0` hours.
  - `CarbonInterval::seconds(3600)` → `3600` seconds, `60` minutes, `1` hour.
  - `CarbonInterval::seconds(5400)` → `5400` seconds, `90` minutes, `2` hours (rounds `1.5` up).
  - `forHumans()` returns a non-empty string for non-zero durations.
  - `toArray()` key set and `generated_at` format.

## 2. Statistics service

- [x] 2.1 Create `Kinescope\Services\Statistics\Statistics` (`final`, not extending `AbstractService`) with constructor accepting `Videos $videos`.
- [x] 2.2 Implement `forAccount(): StatisticsDTO` delegating to a private `aggregate(?string $projectId, ?string $folderId): StatisticsDTO` with both arguments `null`.
- [x] 2.3 Implement `forProject(string $projectId): StatisticsDTO`. Validate `$projectId !== ''` (throw `InvalidArgumentException` otherwise). Delegate to `aggregate($projectId, null)`.
- [x] 2.4 Implement `forFolder(string $folderId): StatisticsDTO`. Validate `$folderId !== ''` (throw `InvalidArgumentException` otherwise). Delegate to `aggregate(null, $folderId)`.
- [x] 2.5 Implement `aggregate()`:
  - Capture `$generatedAt = new DateTimeImmutable()` at the top.
  - Loop with `$page = 1`, fixed `$perPage = 100`, integer accumulator `$totalSeconds = 0`, sentinel `$videosCount` set from the first response.
  - Call `$this->videos->list(pagination: new Pagination($page, $perPage), projectId: $projectId, folderId: $folderId, status: VideoStatus::DONE)`.
  - On the first page, set `$videosCount = $result->getTotal()`.
  - For every `VideoDTO` in `$result->getData()`, add `$video->duration` (normalized int seconds) to `$totalSeconds`; do not apply additional rounding inside `Statistics`.
  - If `$result->hasNextPage()`, increment `$page` and repeat; else exit.
  - After the loop, construct the interval once: `$duration = CarbonInterval::seconds($totalSeconds)`.
  - Return `new StatisticsDTO($videosCount, $duration, $generatedAt)`.

## 3. ServiceFactory wiring

- [x] 3.1 Add a `?Statistics $statistics = null` slot on `ServiceFactory`.
- [x] 3.2 Add `statistics(): Statistics` returning `$this->statistics ??= new Statistics($this->videos())`.
- [x] 3.3 Update the `ServiceFactory` class-level docblock so the custom-client example uses `ServiceFactory::withClient($apiClient)`, not a non-existent named constructor argument.
- [x] 3.4 Extend the same docblock service usage example to show `$factory->statistics()->forAccount()`.

## 4. Tests

- [x] 4.1 Add `tests/Unit/FakeApiClient.php` as `Kinescope\Tests\Unit\FakeApiClient`, implementing `ApiClientInterface` for service unit tests:
  - Implement helper API `queueResponse(array $response): self`, `queueException(KinescopeException $exception): self`, `requests(): array`, `requestAt(int $index): array`, and `requestCount(): int`.
  - Record every request as method (`HttpMethod`), endpoint, query, and body before consuming a queued outcome.
  - Make `get`, `post`, `put`, `patch`, and `delete` delegate to `request()` so recording is consistent across methods.
  - Return queued decoded-array responses in order.
  - Throw queued `KinescopeException` instances in order for negative paths.
  - Throw `RuntimeException` when a test makes an unexpected request after the queue is exhausted.
- [x] 4.2 Add `tests/Unit/Services/Statistics/StatisticsTest.php` using `new Statistics(new Videos($fakeApiClient))` with no `Videos` mocks:
  - `testForAccountSumsDurationsAcrossPages` — seed page 1 with `meta.total = 5` and three video rows, then page 2 with no next page and two rows; assert `videosCount === 5` and `getTotalSeconds() === sum of all five normalized durations`.
  - `testForAccountIssuesRequestsWithSingleStatusDone` — assert every captured request targets `/v1/videos`, uses query key `status[]` with single scalar value `'done'`, `per_page = 100`, sequential page numbers, and no `project_id` / `folder_id` filter.
  - `testForProjectFiltersByProjectId` — assert every captured request carries the supplied `project_id` and query key `status[]` with single scalar value `'done'`.
  - `testForFolderFiltersByFolderId` — analogous to the project case.
  - `testEmptyResultReturnsZeroDto` — seed one empty page with `meta.total = 0`; assert `videosCount === 0`, `getTotalSeconds() === 0`, and exactly one captured request.
  - `testGeneratedAtFallsInsideAggregationCallWindow` — capture `$before` immediately before the public call and `$after` immediately after it returns; assert `$before <= $dto->generatedAt <= $after`.
  - `testForProjectRejectsEmptyId` — assert `forProject('')` throws `InvalidArgumentException` and the fake client records no requests.
  - `testForFolderRejectsEmptyId` — analogous.
  - `testApiExceptionsBubble` — seed page 1 success and page 2 `RateLimitException`; assert the exception propagates and no `StatisticsDTO` is returned.
- [x] 4.3 Add or update `tests/Unit/Services/ServiceFactoryTest.php` covering:
  - `testStatisticsReturnsSameInstanceOnRepeatedCalls`.
  - `testStatisticsIsConstructedWithFactoryVideosService` (use reflection if needed; do not add production getters solely for the test).
- [x] 4.4 Register new statistics-specific env var slots in `phpunit.xml` and document them in `.env.local.example`: `TESTS_STATISTICS_PROJECT_ID` and `TESTS_STATISTICS_FOLDER_ID`.
- [x] 4.5 Add `tests/Integration/Services/Statistics/StatisticsIntegrationTest.php` gated on `KINESCOPE_API_KEY` (skip when empty):
  - `testForAccountMatchesDoneVideoListTotal` — compare `Statistics::forAccount()->videosCount` to `Videos::list(status: VideoStatus::DONE, pagination: new Pagination(1, 1))->getTotal()` and assert total seconds are non-negative.
  - `testForAccountDurationIsAtLeastFirstPageDoneDuration` — fetch the first page of `done` videos and assert the statistics total seconds are greater than or equal to the normalized duration sum of that sample.
  - `testForProjectMatchesDoneVideoListTotal` — gated additionally on `TESTS_STATISTICS_PROJECT_ID`; compare project-scoped statistics count with direct project-scoped `Videos::list(status: VideoStatus::DONE, projectId: $projectId, pagination: new Pagination(1, 1))->getTotal()`.
  - `testForFolderMatchesDoneVideoListTotal` — gated additionally on `TESTS_STATISTICS_FOLDER_ID`; compare folder-scoped statistics count with direct folder-scoped `Videos::list(status: VideoStatus::DONE, folderId: $folderId, pagination: new Pagination(1, 1))->getTotal()`.

## 5. Documentation and validation

- [x] 5.1 Extend `README.md` with a `Statistics` usage block under the existing services example.
- [x] 5.2 Add a `### Added` entry to `CHANGELOG.md` for the statistics service and DTO.
- [x] 5.3 Run `make openspec-validate`.
- [x] 5.4 Run `make lint-all`.
- [x] 5.5 Run `make test-unit`.
- [x] 5.6 Optional: run `make test-integration` when `KINESCOPE_API_KEY` is available.
