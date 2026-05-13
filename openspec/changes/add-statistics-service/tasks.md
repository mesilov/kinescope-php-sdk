## 1. StatisticsDTO

- [ ] 1.1 Create `Kinescope\DTO\Statistics\StatisticsDTO` as `final readonly` with constructor parameters `int $videosCount`, `Carbon\CarbonInterval $totalDuration`, `DateTimeImmutable $generatedAt` (in that order, all public).
- [ ] 1.2 Implement `getTotalSeconds(): int` returning `(int) $this->totalDuration->totalSeconds`.
- [ ] 1.3 Implement `getTotalMinutes(): int` returning `(int) round($this->totalDuration->totalMinutes)`.
- [ ] 1.4 Implement `getTotalHours(): int` returning `(int) round($this->totalDuration->totalHours)`.
- [ ] 1.5 Implement `forHumans(): string` returning `$this->totalDuration->forHumans()` (locale-aware via Carbon).
- [ ] 1.6 Implement `toArray(): array` emitting keys `videos_count`, `total_duration_seconds`, `total_minutes`, `total_hours`, `generated_at` (ISO 8601 via `DateTimeInterface::ATOM`); `total_minutes` and `total_hours` are the rounded `int` values from 1.3 / 1.4.
- [ ] 1.7 Add `tests/Unit/DTO/Statistics/StatisticsDTOTest.php` covering:
  - `CarbonInterval::seconds(0)` → `0` seconds, `0` minutes, `0` hours.
  - `CarbonInterval::seconds(90)` → `90` seconds, `2` minutes (rounds `1.5` up), `0` hours.
  - `CarbonInterval::seconds(3600)` → `3600` seconds, `60` minutes, `1` hour.
  - `CarbonInterval::seconds(5400)` → `5400` seconds, `90` minutes, `2` hours (rounds `1.5` up).
  - `forHumans()` returns a non-empty string for non-zero durations.
  - `toArray()` key set and `generated_at` format.

## 2. Statistics service

- [ ] 2.1 Create `Kinescope\Services\Statistics\Statistics` (`final`, not extending `AbstractService`) with constructor accepting `Videos $videos`.
- [ ] 2.2 Implement `forAccount(): StatisticsDTO` delegating to a private `aggregate(?string $projectId, ?string $folderId): StatisticsDTO` with both arguments `null`.
- [ ] 2.3 Implement `forProject(string $projectId): StatisticsDTO`. Validate `$projectId !== ''` (throw `InvalidArgumentException` otherwise). Delegate to `aggregate($projectId, null)`.
- [ ] 2.4 Implement `forFolder(string $folderId): StatisticsDTO`. Validate `$folderId !== ''` (throw `InvalidArgumentException` otherwise). Delegate to `aggregate(null, $folderId)`.
- [ ] 2.5 Implement `aggregate()`:
  - Capture `$generatedAt = new DateTimeImmutable()` at the top.
  - Loop with `$page = 1`, fixed `$perPage = 100`, integer accumulator `$totalSeconds = 0`, sentinel `$videosCount` set from the first response.
  - Call `$this->videos->list(pagination: new Pagination($page, $perPage), projectId: $projectId, folderId: $folderId, status: VideoStatus::DONE->value)`.
  - On the first page, set `$videosCount = $result->getTotal()`.
  - For every `VideoDTO` in `$result->getData()`, add `$video->duration` (int seconds) to `$totalSeconds`.
  - If `$result->hasNextPage()`, increment `$page` and repeat; else exit.
  - After the loop, construct the interval once: `$duration = CarbonInterval::seconds($totalSeconds)`.
  - Return `new StatisticsDTO($videosCount, $duration, $generatedAt)`.

## 3. ServiceFactory wiring

- [ ] 3.1 Add a `?Statistics $statistics = null` slot on `ServiceFactory`.
- [ ] 3.2 Add `statistics(): Statistics` returning `$this->statistics ??= new Statistics($this->videos())`.
- [ ] 3.3 Extend the class-level docblock example to show `$factory->statistics()->forAccount()`.

## 4. Tests

- [ ] 4.1 Add `tests/Unit/Services/Statistics/StatisticsTest.php` with:
  - `testForAccountSumsDurationsAcrossPages` — mock `Videos::list` to return page 1 with `meta.total = 5` and three durations, then page 2 with no next page and two durations; assert `videosCount === 5` and `getTotalSeconds() === sum of all five durations`.
  - `testForAccountIssuesRequestsWithStatusDone` — capture the `list` arguments and assert every call uses `status = 'done'` and no project/folder filter.
  - `testForProjectFiltersByProjectId` — assert every `list` call carries the supplied `projectId` and `status = 'done'`.
  - `testForFolderFiltersByFolderId` — analogous to the project case.
  - `testEmptyResultReturnsZeroDto` — mock `Videos::list` to return one empty page with `meta.total = 0`; assert `videosCount === 0`, `getTotalSeconds() === 0`, and exactly one `list` call.
  - `testForProjectRejectsEmptyId` — assert `forProject('')` throws `InvalidArgumentException` and no `list` calls are made.
  - `testForFolderRejectsEmptyId` — analogous.
  - `testApiExceptionsBubble` — mock `Videos::list` to throw `RateLimitException` on page 2; assert the exception propagates and no `StatisticsDTO` is returned.
- [ ] 4.2 Register a new `TESTS_VIDEO_DOWNLOADER_PROJECT_ID` env var slot in `phpunit.xml` and document it in `.env.local.example` (existing pattern: `TESTS_VIDEO_DOWNLOADER_FOLDER_ID`, `TESTS_VIDEO_DOWNLOADER_VIDEO_ID`).
- [ ] 4.3 Add `tests/Integration/Services/Statistics/StatisticsIntegrationTest.php` gated on `KINESCOPE_API_KEY` (skip when empty):
  - `testForAccountReturnsNonNegativeTotals` — runs against the live workspace, asserts the DTO and non-negative values.
  - `testForProjectReturnsNonNegativeTotals` — gated additionally on `TESTS_VIDEO_DOWNLOADER_PROJECT_ID` (skip when empty).
  - `testForFolderReturnsNonNegativeTotals` — gated additionally on `TESTS_VIDEO_DOWNLOADER_FOLDER_ID` (skip when empty).

## 5. Documentation and validation

- [ ] 5.1 Extend `README.md` with a `Statistics` usage block under the existing services example.
- [ ] 5.2 Add a `### Added` entry to `CHANGELOG.md` for the statistics service and DTO.
- [ ] 5.3 Run `make openspec-validate`.
- [ ] 5.4 Run `make lint-all`.
- [ ] 5.5 Run `make test-unit`.
- [ ] 5.6 Optional: run `make test-integration` when `KINESCOPE_API_KEY` is available.
