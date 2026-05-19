## Context

Kinescope's public API has no statistics endpoint. To answer "how many videos do I have and what is the total runtime?", consumers must paginate `GET /v1/videos` (filterable by `project_id`, `folder_id`, `status[]`) and sum `duration` across the resulting `VideoDTO`s. The response envelope already carries `meta.pagination.total`, so the file count itself is cheap once the filter is fixed; the duration sum is what forces full pagination. The OpenAPI schema marks `duration` as integer in the list response shape, but documented examples include fractional seconds such as `59.96` and `179.305`, so the SDK must define a precision policy explicitly.

The SDK already exposes a tested `Videos::list()` method that builds the filtered query, handles pagination parameters, decodes the response into typed DTOs, and exposes `PaginatedResponse::getTotal()` / `hasNextPage()`. A dedicated statistics service can sit on top of `Videos` without touching `ApiClient` directly, keeping the new code focused on aggregation.

## Goals / Non-Goals

**Goals:**

- Provide a dedicated, typed service for video count + total runtime, scoped to account / project / folder.
- Align the SDK video status surface with the documented `/v1/videos` status values.
- Replace raw string status filtering in `Videos::list()` with a `VideoStatus` argument and the documented `status[]` query key.
- Normalize fractional API `duration` values to whole seconds before statistics aggregation.
- Return a single immutable DTO carrying raw seconds plus convenience accessors for minutes and hours.
- Capture `videosCount` from the first page's `meta.pagination.total` so the figure is stable.
- Filter on `VideoStatus::DONE` so the statistics describe deliverable content, not in-flight uploads.
- Reject empty `$projectId` / `$folderId` strings as caller bugs before any HTTP traffic.
- Propagate underlying `KinescopeException` subclasses without wrapping; do not return partial results.
- Cover the behaviour with isolated unit tests using the real `Videos` service over a reusable fake `ApiClientInterface`; provide an opt-in integration test gated on credentials.

**Non-Goals:**

- Do not expose configurable status filters in this change. `VideoStatus::DONE` is hard-wired; callers that need other status breakdowns can call `Videos::list()` directly.
- Do not add a progress callback, retry policy, or partial-result mode. The aggregation either completes or fails.
- Do not introduce caching, memoisation, or background refresh. Each method call walks the API fresh; `generatedAt` reflects when iteration started.
- Do not add view-time, watch-minute, or audience analytics. Those belong to a future service backed by `/v1/analytics/*`.
- Do not introduce a generic `paginateAll()` helper on `AbstractService`. Add one only when a second consumer needs it.
- Do not change `MetaDTO`, `PaginatedResponse`, or request pagination parameters beyond the typed status-filter break on `Videos::list()`.
- Do not preserve fractional seconds in public DTOs in this change. `VideoDTO::$duration` remains an `int` in whole seconds.

## Decisions

- **Service depends on `Videos`, not `ApiClient`.**
  - Rationale: `Videos::list()` already encodes the filter shape, status mapping, and pagination glue. Calling it keeps `Statistics` thin and means future fixes in `Videos` (rate-limit handling, query-building) propagate for free.
  - Alternative considered: direct `ApiClient` access for a leaner payload (skip `VideoDTO` construction, decode only `duration`). Rejected as premature optimisation — typical workspaces are far from the scale where `VideoDTO` allocation cost matters, and the cost can be reduced later behind the same public surface.
  - Alternative considered: a generic `AbstractService::paginateAll()` helper. Rejected — only one consumer needs it today; designing for a hypothetical second is speculative.

- **Statistics unit tests use real `Videos` with `tests/Unit/FakeApiClient.php`, not a `Videos` mock.**
  - Rationale: `Videos` is `final`, and mocking it would test only `Statistics` orchestration while missing the contract that matters for this change: query serialization (`status[]`, `project_id`, `folder_id`, pagination), response parsing, `VideoDTO` duration normalization, and pagination metadata handling.
  - `FakeApiClient` lives under the unit test tree as `Kinescope\Tests\Unit\FakeApiClient`, implements `ApiClientInterface`, queues deterministic responses or exceptions, and records every request as method, endpoint, query, and body for assertions.
  - Statistics tests instantiate `new Statistics(new Videos($fakeApiClient))`; they assert both the returned `StatisticsDTO` and the captured request sequence.
  - Alternative considered: anonymous `ApiClientInterface` classes inside each test. Rejected because the existing tests already duplicate this pattern heavily; a shared fake keeps new statistics tests readable and can be reused by future service tests.
  - Alternative considered: introduce a production `VideoListerInterface` just for tests. Rejected because it adds production abstraction before a second runtime consumer needs it.

- **`FakeApiClient` is a queue-backed recorder, not an assertion helper.**
  - Architecture: `final class FakeApiClient implements ApiClientInterface` with two internal lists: queued outcomes and recorded requests. Outcomes are either decoded response arrays or `KinescopeException` instances. Requests are stored as arrays with `method: HttpMethod`, `endpoint: string`, `query: array<string, mixed>`, and `body: array<string, mixed>`.
  - Public helper API: `queueResponse(array $response): self`, `queueException(KinescopeException $exception): self`, `requests(): array`, `requestAt(int $index): array`, and `requestCount(): int`. PHPUnit assertions stay in test classes, not in the fake.
  - All verb helpers (`get`, `post`, `put`, `patch`, `delete`) delegate to `request()` so recording and queue consumption behave consistently. `request()` records first, then pops the next outcome; it returns arrays, throws queued exceptions, and throws `RuntimeException` for unexpected unqueued calls.
  - The fake stores the query exactly as the service passes it. For status filters, tests assert a single scalar value under the documented query key `status[]`; they do not assert or require a multi-status array.

- **Status filtering remains a single enum argument despite the `status[]` query key.**
  - Rationale: the API key is named `status[]`, but the SDK surface for this change is deliberately `?VideoStatus`, not `array<VideoStatus>`. `Videos::list(status: VideoStatus::DONE)` serializes one scalar value under `status[]`.
  - Multi-status filtering remains out of scope. Adding `array<VideoStatus>` later is possible without changing `Statistics`, because statistics only needs `VideoStatus::DONE`.

- **Dedicated methods per scope: `forAccount`, `forProject`, `forFolder`.**
  - Rationale: intent is obvious at the call site, no null-as-wildcard ambiguity, mirrors the existing `Videos::listByProject` / `listByFolder` convenience pattern.
  - Alternative considered: single `get(?string $projectId = null, ?string $folderId = null)`. Rejected — caller must remember argument order or use named arguments, and `null` overloading reads worse than three explicit methods.

- **`VideoStatus::DONE` is hard-wired.**
  - Rationale: confirmed with the user during brainstorming. "Statistics" describes deliverable content; counting pending / uploading / processing rows would skew totals because their `duration` is often `0`.
  - Alternative considered: pass-through status filter. Rejected — adds API surface for an unsubstantiated need. Easy to add later in a non-breaking follow-up.

- **`Videos::list()` breaks from `?string $status` to `?VideoStatus $status`.**
  - Rationale: status is a closed API vocabulary and the SDK already exposes `VideoStatus` for parsed payloads. Passing raw strings lets typos reach the API, hides missing enum cases, and made the statistics design depend on a string literal.
  - Query serialization uses the documented `status[]` key with the enum value, even though raw API probing on 2026-05-14 showed `status=done` currently works as a tolerant alias.
  - Alternative considered: keep `?string $status` for backwards compatibility. Rejected because the user accepted a breaking contract and asked to pass enum arguments where status is used.
  - Alternative considered: accept `VideoStatus|array<VideoStatus>|null` to mirror the `status[]` shape fully. Rejected for this change because existing SDK surface only allowed one status and statistics needs only `DONE`; multi-status filtering can be added later.

- **`VideoStatus` covers every documented `/v1/videos` status value.**
  - Required values: `pending`, `uploading`, `pre-processing`, `processing`, `aborted`, `done`, and `error`.
  - `isProcessing()` returns `true` for `pending`, `uploading`, `pre-processing`, and `processing`; `false` for `aborted`, `done`, and `error`.
  - `isReady()` remains true only for `done`.
  - `hasError()` remains true only for `error`; `aborted` is terminal and not ready, but not treated as `error`.

- **`VideoDTO::fromArray()` rounds fractional API duration to nearest whole second.**
  - Rationale: the public DTO already exposes `duration` as `int` seconds and existing formatting helpers assume whole seconds. The API documentation is inconsistent: schema says integer, examples show fractional values. Rounding preserves the nearest user-visible duration while keeping the existing public type.
  - Rounding mode: `(int) round((float) $data['duration'])`, using PHP default `PHP_ROUND_HALF_AWAY_FROM_ZERO`. Examples: `59.96` becomes `60`, `179.305` becomes `179`, and `179.5` becomes `180`.
  - Missing `duration` remains `0`, matching the current DTO default.
  - Alternative considered: keep current `(int)` cast / truncation. Rejected because it silently undercounts values like `59.96` as `59`.
  - Alternative considered: change `VideoDTO::$duration` to `float`. Rejected because that is a wider public DTO break and would ripple into formatting helpers, `VideoListResult::getTotalDuration()`, playlist-style duration APIs, and statistics DTO design.
  - Alternative considered: let `Statistics` fetch raw payloads and sum fractional durations before rounding once at the aggregate boundary. Rejected because this change deliberately builds on `Videos::list()` and should not create a second duration interpretation path.

- **`StatisticsDTO` stores duration as a `Carbon\CarbonInterval`; derived whole-unit totals are computed on read and returned as rounded `int`.**
  - Rationale: `CarbonInterval` is the canonical PHP duration type and is already a project dependency (`nesbot/carbon ^3.0`). It exposes `totalSeconds` (int), `totalMinutes` (float), `totalHours` (float), `forHumans()` (locale-aware string), arithmetic (`add` / `sub`), and ISO 8601 serialisation (`spec()` / `make('PT…')`). Storing the interval object means consumers who need decimals, locale formatting, or arithmetic get it for free; the DTO does not have to re-implement any of that.
  - The DTO still exposes whole-unit getters (`getTotalSeconds`, `getTotalMinutes`, `getTotalHours`) that round to nearest int — that was the explicit consumer ask — but the underlying `CarbonInterval` remains directly accessible via the public `$totalDuration` property for callers that want precision.
  - Alternative considered: store `int $totalDurationSeconds` and expose `getDuration(): CarbonInterval` on demand. Rejected — recreates the interval on every call, and the property list no longer signals "this DTO carries a duration".
  - Alternative considered: three pre-computed integer fields. Rejected — discards the raw seconds and forces the DTO to make rounding decisions at construction time.
  - Rationale for keeping rounded `int` getters: the consumer explicitly asked for whole-unit minutes / hours for display.
  - Rounding mode: `(int) round($interval->totalMinutes)` and `(int) round($interval->totalHours)` (PHP default `PHP_ROUND_HALF_AWAY_FROM_ZERO`). Half-units therefore round away from zero (e.g. `90` seconds → `2` minutes). `floor`-style truncation was considered and rejected because nearest-int rounding is what the consumer asked for.

- **Aggregation builds the `CarbonInterval` once from a summed `int` accumulator, not by repeatedly adding intervals inside the loop.**
  - Rationale: summing primitive ints is exact and cheap; `CarbonInterval::add()` allocates a new interval each step. Build the interval once at the end via `CarbonInterval::seconds($totalSeconds)`.
  - The accumulator sums `VideoDTO::$duration` values after DTO-level rounding. There is no second per-page or aggregate-level fractional rounding inside `Statistics`.

- **`videosCount` comes from `meta.pagination.total` of the first response.**
  - Rationale: a single authoritative source for "how many rows match". Reading it from page 1 means we know it before iteration completes and avoids drift if rows are added during iteration.
  - Alternative considered: counting summed `VideoDTO`s. Rejected — would diverge from `meta.total` whenever uploads land mid-iteration, and would forbid an optimisation that issues a one-page request when the caller only needs the count.

- **`generatedAt` is captured at the start of `aggregate()`, not at construction.**
  - Rationale: it tracks "when the snapshot began", which matches the moment `videosCount` was sampled. End-of-iteration timestamps would lie when iteration takes seconds for large workspaces.
  - Unit tests verify the invariant with a bounded window: capture `$before` immediately before the public statistics call and `$after` immediately after it returns, then assert `$before <= $dto->generatedAt <= $after`. No injectable clock is introduced in this change.

- **Empty `$projectId` / `$folderId` raise `InvalidArgumentException` before any HTTP.**
  - Rationale: empty UUIDs are caller bugs. Fail fast and locally, with a clear stack trace.
  - Alternative considered: silently treat empty string as "no filter". Rejected — masks bugs and conflates `forProject('')` with `forAccount()`.

- **Per-page size fixed at `100`.**
  - Rationale: matches the OpenAPI example and balances request count against payload size. Not exposed because the optimal value is implementation detail, and the public DTO is unaffected by it.

- **No retries inside `Statistics`.**
  - Rationale: retry policy is the responsibility of `ApiClient` and its caller. The aggregation either completes or surfaces the underlying exception. Mid-iteration failures discard partial state.

- **Statistics integration tests use statistics-specific fixture environment variables.**
  - `TESTS_STATISTICS_PROJECT_ID` and `TESTS_STATISTICS_FOLDER_ID` are independent from downloader fixtures. The downloader variables may point to assets chosen for download behaviour and should not become hidden prerequisites for statistics.
  - Account integration test compares `Statistics::forAccount()->videosCount` with `Videos::list(status: VideoStatus::DONE, pagination: new Pagination(1, 1))->getTotal()` and asserts total duration is at least the sum of the sampled first page's normalized durations.
  - Project and folder integration tests use the same comparison within their scope and skip when the matching statistics fixture ID is empty.

- **ServiceFactory documentation is corrected while adding the statistics example.**
  - The class docblock should use `ServiceFactory::withClient($apiClient)` for custom clients, matching the existing factory method, and should include `$factory->statistics()->forAccount()` in the service usage example.

## Risks / Trade-offs

- **Aggregation cost grows linearly with the scope size.** A workspace with 10 000 `done` videos at `per_page = 100` issues 100 requests per call. Mitigation: the public docblock states that the operation paginates the full scope; consumers can cache the resulting DTO on their side if they need to call it often.

- **`videosCount` / `StatisticsDTO::getTotalSeconds()` can be transiently inconsistent.** If videos are uploaded mid-iteration, the summed duration may include rows beyond the page-1 `meta.total`. Mitigation: documented as a snapshot-skew artefact; `generatedAt` records when sampling began.

- **Reliance on `meta.pagination.total` from the API.** If the field is ever absent or wrong, `videosCount` is wrong. Mitigation: the field is part of the documented response shape, exercised by every paginated SDK service today; regression risk is shared with `Videos::list`.

- **Hard-wired `VideoStatus::DONE` cannot be overridden.** Mitigation: the docblock states the filter; callers needing other status slices fall back to `Videos::list()` directly. A future change can widen the surface without breaking the current methods.

- **Allocating `VideoDTO`s only to read `duration` is mild overkill.** Mitigation: acceptable at SDK scale; if profiling later shows it matters, the internal implementation can switch to direct decoding without changing the public DTO or method signatures.
