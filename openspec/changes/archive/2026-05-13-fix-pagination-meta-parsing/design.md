## Context

Paginated list result DTOs centralize metadata parsing through `MetaDTO::fromArray()`, and `PaginatedResponse` delegates totals, current page, per-page count, last-page calculation, and next/previous checks to `MetaDTO`. The current parser only reads a flat metadata shape, while real paginated Kinescope list responses include required pagination values under `meta.pagination` and may omit `last_page`.

Issue #6 includes raw API evidence showing `pagination.total`, `pagination.page`, and `pagination.per_page` for videos and projects, while SDK-level totals remain `0`.

## Goals / Non-Goals

**Goals:**

- Support the real nested `meta.pagination` shape for paginated SDK list responses that receive `meta.pagination` from the API, including videos, projects, folders, and playlist lists.
- Require the current API payload shape: `meta.pagination.total`, `meta.pagination.page`, and `meta.pagination.per_page`.
- Fail explicitly when required pagination keys are absent instead of returning hidden defaults.
- Keep last-page and next-page behavior deterministic when the API omits `last_page`.
- Cover the behavior with focused unit tests and keep implementation localized.

**Non-Goals:**

- Do not change service method signatures or pagination request parameters.
- Do not add endpoint-specific metadata parsing in `VideoListResult`, `ProjectListResult`, or other list result classes.
- Do not change `MetaDTO` constructor arguments or `PaginatedResponse` public method names.
- Do not introduce a new dependency or live API requirement for unit verification.
- Do not preserve compatibility with older flat metadata arrays for list responses.
- Do not remodel `/v1/playlists/{playlist_id}/entities`; that endpoint is non-paginated in the live API and is covered by `fix-playlist-api-contract`.

## Decisions

- Parse metadata centrally in `MetaDTO::fromArray()`.
  - Rationale: every paginated list result should share one strict parser, so a single fix updates videos, projects, folders, and playlist lists without duplicating parsing logic.
  - Alternative considered: patch only `VideoListResult` and `ProjectListResult`; rejected because it would leave the common pagination contract inconsistent.

- Keep non-paginated responses out of `PaginatedResponse`.
  - Rationale: live `/v1/playlists/{playlist_id}/entities` returns `data` without `meta`, so it should not be forced through `MetaDTO` or pagination helpers.
  - Alternative considered: synthesize metadata for playlist entities; rejected because it would hide the raw API contract and conflict with strict parsing.

- Read only the current nested pagination payload shape.
  - Rationale: SDK behavior should match the raw Kinescope API contract directly and fail when that contract is not present.
  - Alternative considered: nested values with flat-key fallback; rejected because fallback would keep hidden behavior that masks malformed payloads and makes SDK state diverge from the raw API response.

- Treat `pagination.total`, `pagination.page`, and `pagination.per_page` as required keys.
  - Rationale: list pagination is part of the response contract; missing keys should surface as an integration or parsing error, not as total `0`, page `1`, or per-page `20`.
  - Alternative considered: keep lenient defaults for missing values; rejected because defaults hide payload drift and make pagination helpers report fabricated state.

- Keep last-page calculation in `MetaDTO::getLastPage()`.
  - Rationale: the calculation already exists and handles absent `last_page`; the fix only needs to feed it correct `total` and `per_page` values.
  - Alternative considered: persist calculated `lastPage` during `fromArray()`; rejected because it changes constructor state unnecessarily and duplicates existing behavior.

## Risks / Trade-offs

- Existing tests and fixtures that pass flat `meta.total`, `meta.page`, and `meta.per_page` will need to be updated to the current nested payload shape.
- Consumers manually constructing flat metadata arrays through SDK DTO factories will now receive an explicit parsing error; this is intentional to avoid undocumented compatibility behavior.
- The exact exception type should fit the existing exception hierarchy and make missing pagination metadata actionable for SDK consumers.
- Live API metadata may vary by endpoint -> unit tests cover the common parser for paginated responses, and non-paginated playlist entities are handled in the separate playlist contract change.
