## Context

Kinescope's public API has no statistics endpoint. To answer "how many videos do I have and what is the total runtime?", consumers must paginate `GET /v1/videos` (filterable by `project_id`, `folder_id`, `status[]`) and sum `duration` across the resulting `VideoDTO`s. The response envelope already carries `meta.pagination.total`, so the file count itself is cheap once the filter is fixed; the duration sum is what forces full pagination.

The SDK already exposes a tested `Videos::list()` method that builds the filtered query, handles pagination parameters, decodes the response into typed DTOs, and exposes `PaginatedResponse::getTotal()` / `hasNextPage()`. A dedicated statistics service can sit on top of `Videos` without touching `ApiClient` directly, keeping the new code focused on aggregation.

## Goals / Non-Goals

**Goals:**

- Provide a dedicated, typed service for video count + total runtime, scoped to account / project / folder.
- Return a single immutable DTO carrying raw seconds plus convenience accessors for minutes and hours.
- Capture `videosCount` from the first page's `meta.pagination.total` so the figure is stable.
- Filter on `status = 'done'` so the statistics describe deliverable content, not in-flight uploads.
- Reject empty `$projectId` / `$folderId` strings as caller bugs before any HTTP traffic.
- Propagate underlying `KinescopeException` subclasses without wrapping; do not return partial results.
- Cover the behaviour with isolated unit tests using a mocked `Videos` service; provide an opt-in integration test gated on credentials.

**Non-Goals:**

- Do not expose configurable status filters in this change. `status = 'done'` is hard-wired; callers that need other status breakdowns can call `Videos::list()` directly.
- Do not add a progress callback, retry policy, or partial-result mode. The aggregation either completes or fails.
- Do not introduce caching, memoisation, or background refresh. Each method call walks the API fresh; `generatedAt` reflects when iteration started.
- Do not add view-time, watch-minute, or audience analytics. Those belong to a future service backed by `/v1/analytics/*`.
- Do not introduce a generic `paginateAll()` helper on `AbstractService`. Add one only when a second consumer needs it.
- Do not change `VideoDTO`, `MetaDTO`, `PaginatedResponse`, `Videos`, or any existing public surface.

## Decisions

- **Service depends on `Videos`, not `ApiClient`.**
  - Rationale: `Videos::list()` already encodes the filter shape, status mapping, and pagination glue. Calling it keeps `Statistics` thin and means future fixes in `Videos` (rate-limit handling, query-building) propagate for free.
  - Alternative considered: direct `ApiClient` access for a leaner payload (skip `VideoDTO` construction, decode only `duration`). Rejected as premature optimisation — typical workspaces are far from the scale where `VideoDTO` allocation cost matters, and the cost can be reduced later behind the same public surface.
  - Alternative considered: a generic `AbstractService::paginateAll()` helper. Rejected — only one consumer needs it today; designing for a hypothetical second is speculative.

- **Dedicated methods per scope: `forAccount`, `forProject`, `forFolder`.**
  - Rationale: intent is obvious at the call site, no null-as-wildcard ambiguity, mirrors the existing `Videos::listByProject` / `listByFolder` convenience pattern.
  - Alternative considered: single `get(?string $projectId = null, ?string $folderId = null)`. Rejected — caller must remember argument order or use named arguments, and `null` overloading reads worse than three explicit methods.

- **`status = 'done'` is hard-wired.**
  - Rationale: confirmed with the user during brainstorming. "Statistics" describes deliverable content; counting pending / uploading / processing rows would skew totals because their `duration` is often `0`.
  - Alternative considered: pass-through status filter. Rejected — adds API surface for an unsubstantiated need. Easy to add later in a non-breaking follow-up.

- **`StatisticsDTO` stores duration as a `Carbon\CarbonInterval`; derived whole-unit totals are computed on read and returned as rounded `int`.**
  - Rationale: `CarbonInterval` is the canonical PHP duration type and is already a project dependency (`nesbot/carbon ^3.0`). It exposes `totalSeconds` (int), `totalMinutes` (float), `totalHours` (float), `forHumans()` (locale-aware string), arithmetic (`add` / `sub`), and ISO 8601 serialisation (`spec()` / `make('PT…')`). Storing the interval object means consumers who need decimals, locale formatting, or arithmetic get it for free; the DTO does not have to re-implement any of that.
  - The DTO still exposes whole-unit getters (`getTotalSeconds`, `getTotalMinutes`, `getTotalHours`) that round to nearest int — that was the explicit consumer ask — but the underlying `CarbonInterval` remains directly accessible via the public `$totalDuration` property for callers that want precision.
  - Alternative considered: store `int $totalDurationSeconds` and expose `getDuration(): CarbonInterval` on demand. Rejected — recreates the interval on every call, and the property list no longer signals "this DTO carries a duration".
  - Alternative considered: three pre-computed integer fields. Rejected — discards the raw seconds and forces the DTO to make rounding decisions at construction time.
  - Rationale for keeping rounded `int` getters: the consumer explicitly asked for whole-unit minutes / hours for display.
  - Rounding mode: `(int) round($interval->totalMinutes)` and `(int) round($interval->totalHours)` (PHP default `PHP_ROUND_HALF_AWAY_FROM_ZERO`). Half-units therefore round away from zero (e.g. `90` seconds → `2` minutes). `floor`-style truncation was considered and rejected because nearest-int rounding is what the consumer asked for.

- **Aggregation builds the `CarbonInterval` once from a summed `int` accumulator, not by repeatedly adding intervals inside the loop.**
  - Rationale: summing primitive ints is exact and cheap; `CarbonInterval::add()` allocates a new interval each step. Build the interval once at the end via `CarbonInterval::seconds($totalSeconds)`.

- **`videosCount` comes from `meta.pagination.total` of the first response.**
  - Rationale: a single authoritative source for "how many rows match". Reading it from page 1 means we know it before iteration completes and avoids drift if rows are added during iteration.
  - Alternative considered: counting summed `VideoDTO`s. Rejected — would diverge from `meta.total` whenever uploads land mid-iteration, and would forbid an optimisation that issues a one-page request when the caller only needs the count.

- **`generatedAt` is captured at the start of `aggregate()`, not at construction.**
  - Rationale: it tracks "when the snapshot began", which matches the moment `videosCount` was sampled. End-of-iteration timestamps would lie when iteration takes seconds for large workspaces.

- **Empty `$projectId` / `$folderId` raise `InvalidArgumentException` before any HTTP.**
  - Rationale: empty UUIDs are caller bugs. Fail fast and locally, with a clear stack trace.
  - Alternative considered: silently treat empty string as "no filter". Rejected — masks bugs and conflates `forProject('')` with `forAccount()`.

- **Per-page size fixed at `100`.**
  - Rationale: matches the OpenAPI example and balances request count against payload size. Not exposed because the optimal value is implementation detail, and the public DTO is unaffected by it.

- **No retries inside `Statistics`.**
  - Rationale: retry policy is the responsibility of `ApiClient` and its caller. The aggregation either completes or surfaces the underlying exception. Mid-iteration failures discard partial state.

## Risks / Trade-offs

- **Aggregation cost grows linearly with the scope size.** A workspace with 10 000 `done` videos at `per_page = 100` issues 100 requests per call. Mitigation: the public docblock states that the operation paginates the full scope; consumers can cache the resulting DTO on their side if they need to call it often.

- **`videosCount` / `totalDurationSeconds` can be transiently inconsistent.** If videos are uploaded mid-iteration, the summed duration may include rows beyond the page-1 `meta.total`. Mitigation: documented as a snapshot-skew artefact; `generatedAt` records when sampling began.

- **Reliance on `meta.pagination.total` from the API.** If the field is ever absent or wrong, `videosCount` is wrong. Mitigation: the field is part of the documented response shape, exercised by every paginated SDK service today; regression risk is shared with `Videos::list`.

- **Hard-wired `status = 'done'` cannot be overridden.** Mitigation: the docblock states the filter; callers needing other status slices fall back to `Videos::list()` directly. A future change can widen the surface without breaking the current methods.

- **Allocating `VideoDTO`s only to read `duration` is mild overkill.** Mitigation: acceptable at SDK scale; if profiling later shows it matters, the internal implementation can switch to direct decoding without changing the public DTO or method signatures.
