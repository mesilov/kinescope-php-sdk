## Context

The SDK currently exposes playlist support through `ServiceFactory::playlists()`, `PlaylistsService`, `PlaylistDTO`, `PlaylistListResult`, and `PlaylistEntityListResult`. Live API checks show that `/v1/playlists` returns a paginated response with `meta.pagination`, while `/v1/playlists/{playlist_id}/entities` returns only `data` and no `meta`.

The playlist item payload also differs from the current DTO assumptions. Live playlist items expose fields such as `name`, `parent_id`, `workspace_id`, `player_id`, `privacy_type`, `play_link`, `embed_link`, and `settings`. They do not expose `title`, `project_id`, `items_count`, `total_duration`, `poster_url`, `embed_code`, `is_public`, `created_at`, or `updated_at` in the shape currently expected by `PlaylistDTO`.

`/v1/playlists?project_id=...` and `/v1/playlists?parent_id=...` currently return the unfiltered playlist list, even with nonexistent IDs. Project ownership can be inferred only by resolving `PlaylistDTO::parentId` against project IDs and folder IDs returned from `/v1/projects/{project_id}/folders`.

## Goals / Non-Goals

**Goals:**

- Align playlist DTO parsing with the current live playlist payload.
- Keep playlist listing paginated because `/v1/playlists` returns `meta.pagination`.
- Model playlist entities as an unpaginated collection because `/v1/playlists/{playlist_id}/entities` returns no `meta`.
- Remove hidden or misleading project filtering behavior from playlist listing.
- Provide an explicit parent-resolution path for users who need to map playlists to projects.
- Cover live payload shapes with focused unit tests and optional integration checks.

**Non-Goals:**

- Do not create, update, delete, or reorder playlists.
- Do not add server-side filtering that the Kinescope API does not currently support.
- Do not make `parent_id` resolution implicit in `PlaylistsService::list()`.
- Do not require live API credentials for unit verification.

## Decisions

- Keep `PlaylistsService::list()` as the direct wrapper for `/v1/playlists`.
  - Rationale: the endpoint is paginated and returns the same strict `meta.pagination` shape as projects, videos, and folders.
  - Alternative considered: treat playlists as unpaginated because playlist entities are unpaginated; rejected because this would ignore the actual `/v1/playlists` response contract.

- Replace or adapt `PlaylistDTO` fields to match the live payload.
  - Rationale: SDK DTOs should represent API fields rather than fabricate empty `title`, null `projectId`, zero counters, or current timestamps.
  - Alternative considered: keep old fields as computed aliases; rejected for fields that cannot be derived reliably from the payload. Compatibility aliases may remain only when they map directly, such as `title` to `name`, if the implementation explicitly documents them.

- Model playlist entities as a collection, not as `PaginatedResponse`.
  - Rationale: playlist entities currently return `data` without `meta`, so pagination helpers would report fabricated state or fail under strict metadata parsing.
  - Alternative considered: synthesize metadata from item count; rejected because it hides the API contract and breaks the strict parsing direction.

- Treat project resolution as an explicit resolver/catalog operation.
  - Rationale: resolving playlist project ownership requires extra API reads and a client-side join across projects and folders. This cost and behavior must be visible in the SDK surface.
  - Alternative considered: keep `listByProject()` as a query filter; rejected because the live API ignores the filter and returns incorrect results.

## Risks / Trade-offs

- Breaking public DTO properties can affect existing consumers -> mark the change as breaking in CHANGELOG and keep direct aliases only when they are truthful.
- Parent resolution requires multiple API calls -> keep it outside ordinary `list()` and document the read pattern.
- API playlist payload may gain native project filtering later -> resolver design should not prevent adding a direct server-side filter in a future change.
- Integration fixtures can drift as live data changes -> assert response shape and relationships rather than exact playlist counts or names.
