## Why

SDK consumers need an at-a-glance view of how much video content lives in a Kinescope workspace — total file count and total runtime — scoped to the whole account, a single project, or a single folder. Kinescope's public API exposes no native statistics endpoint for this: deriving the numbers from `GET /v1/videos` requires building filtered queries, walking pages, and summing `duration` per `VideoDTO`. Every consumer would otherwise reinvent that loop, and most would do it incorrectly under pagination (forgetting `meta.pagination.total`, double-counting, or stopping early). A dedicated service collapses the operation into a one-line call with a typed, immutable result.

## What Changes

**Non-breaking** — introduces a new service and a new DTO. No existing public types, methods, or DTO contracts are modified.

- Introduce `Kinescope\Services\Statistics\Statistics` (`final`), exposing three convenience methods that each return a `StatisticsDTO`:
  - `forAccount(): StatisticsDTO` — aggregates across every `done` video accessible to the API key.
  - `forProject(string $projectId): StatisticsDTO` — aggregates across `done` videos in the given project.
  - `forFolder(string $folderId): StatisticsDTO` — aggregates across `done` videos in the given folder.
- Introduce `Kinescope\DTO\Statistics\StatisticsDTO` (`final readonly`) carrying `int $videosCount`, `Carbon\CarbonInterval $totalDuration`, and `DateTimeImmutable $generatedAt`. The DTO exposes `getTotalSeconds(): int`, `getTotalMinutes(): int`, `getTotalHours(): int` (the minute / hour getters round to the nearest whole unit via `(int) round(...)`), `forHumans(): string` (locale-aware via `CarbonInterval::forHumans()`), and `toArray(): array`. Storing duration as a `CarbonInterval` (already on the project's `nesbot/carbon ^3.0` dependency) means consumers who need decimals or arithmetic can use `$dto->totalDuration->totalMinutes` / `add()` / `spec()` directly.
- Register the service on `ServiceFactory` via a new lazy getter `statistics(): Statistics` that instantiates `new Statistics($this->videos())` on first access.
- The service paginates `Videos::list()` with `per_page = 100` and `status = 'done'`, applying `projectId` or `folderId` filters as appropriate, advancing while `MetaDTO::hasNextPage()` reports more pages.
- `videosCount` is captured from the first response's `meta.pagination.total` so the figure remains stable even if pagination spans multiple requests.
- Empty `$projectId` or `$folderId` strings raise `InvalidArgumentException` before any HTTP call.
- Network and API failures raised by the underlying `Videos::list()` propagate unchanged; partial aggregation results are discarded on error.
- Add unit tests for aggregation, scope filtering, validation, DTO math, and `toArray` shape. Add an opt-in integration test gated on `KINESCOPE_API_KEY`.

## Capabilities

### New Capabilities

- `statistics-service`: Defines how the SDK aggregates video count and total duration over the whole account, a single project, or a single folder, and the shape of the returned `StatisticsDTO`.

### Modified Capabilities

- None.

## Impact

- **New public types**: `Kinescope\Services\Statistics\Statistics`, `Kinescope\DTO\Statistics\StatisticsDTO`. The DTO exposes a `Carbon\CarbonInterval` directly; `nesbot/carbon ^3.0` is already a project dependency, so no new packages are required.
- **`ServiceFactory`** gains one lazy slot (`?Statistics $statistics`) and one method (`statistics(): Statistics`). Existing services, signatures, and DTOs are untouched.
- **No DTO contract changes**: `VideoDTO::$duration` (already public, in seconds) is the input; `meta.pagination.total` (already exposed via `MetaDTO` / `PaginatedResponse::getTotal()`) is the source of `videosCount`.
- **Cost characteristics**: `videosCount` costs one paginated request when the scope is empty; otherwise the call paginates the whole scope at `per_page = 100`. Documented in the public docblock so callers understand when to invoke it.
- **Snapshot-skew note**: `videosCount` is read from page 1; uploads completed mid-iteration may make `totalDurationSeconds` reflect rows beyond `videosCount`. This is a snapshot artefact, not a bug, and is acknowledged in the docblock.
- **Affected tests**: new `tests/Unit/Services/Statistics/StatisticsTest`, new `tests/Unit/DTO/Statistics/StatisticsDTOTest`, optional `tests/Integration/Services/Statistics/StatisticsIntegrationTest` (skipped when credentials are absent).
- **Documentation**: extend the SDK usage example in `README.md` with a `$factory->statistics()->forAccount()` snippet; add an `Added` entry to `CHANGELOG.md`.
