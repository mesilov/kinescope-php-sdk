## Why

Kinescope list endpoints return pagination values under `meta.pagination`, but the SDK currently reads only the older flat `meta.total`, `meta.page`, and `meta.per_page` shape. This makes `PaginatedResponse::getTotal()`, `getLastPage()`, and `hasNextPage()` report incorrect values for real API responses, including `Videos::list()` and `Projects::list()`.

## What Changes

- Parse `meta.pagination.total`, `meta.pagination.page`, and `meta.pagination.per_page` in `MetaDTO::fromArray()`.
- Treat `meta.pagination.total`, `meta.pagination.page`, and `meta.pagination.per_page` as required keys for paginated list responses.
- Fail explicitly when required pagination keys are absent instead of silently applying defaults or reading older flat metadata keys.
- Keep `PaginatedResponse` public methods returning correct totals and navigation state when `last_page` is absent by relying on calculated last-page behavior.
- Add unit coverage for the current nested API metadata shape and failure paths for missing required keys.
- Exclude non-paginated API responses, including `/v1/playlists/{playlist_id}/entities`, from this pagination metadata contract.
- Non-goal: preserve support for older flat metadata payloads, change service method signatures, DTO constructors, API request pagination parameters, or remodel playlist entities.

## Capabilities

### New Capabilities
- `paginated-response-meta`: Contract for parsing Kinescope pagination metadata and exposing totals/page navigation through SDK paginated responses.

### Modified Capabilities
- None.

## Impact

- Affected code: `src/DTO/Common/MetaDTO.php` and unit tests around common DTO pagination behavior.
- Affected public SDK behavior: `PaginatedResponse::getTotal()`, `getCurrentPage()`, `getPerPage()`, `getLastPage()`, and `hasNextPage()` will reflect real Kinescope API list metadata; malformed list metadata will fail explicitly instead of returning hidden defaults.
- Dependencies: no new runtime or development dependencies expected.
- External systems: no API write operations; integration verification can be run when Kinescope credentials and fixtures are available.
