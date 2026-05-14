## 1. Live Payload Fixtures and Tests

- [ ] 1.1 Add unit fixtures for the current `/v1/playlists` item shape with `name`, `parent_id`, links, privacy fields, tags, and settings.
- [ ] 1.2 Add unit fixtures for `/v1/playlists/{playlist_id}/entities` responses that contain `data` without `meta`.
- [ ] 1.3 Update playlist DTO tests to assert current API fields and remove assertions that depend on unavailable legacy fields.
- [ ] 1.4 Update playlist list tests to assert strict `meta.pagination` parsing.
- [ ] 1.5 Update playlist entity tests to assert collection behavior without pagination helpers.

## 2. Playlist DTO and List Implementation

- [ ] 2.1 Update `PlaylistDTO` to parse and expose current playlist payload fields.
- [ ] 2.2 Decide and implement any truthful compatibility aliases, such as mapping display title to `name` if retained.
- [ ] 2.3 Update `PlaylistListResult` to keep paginated behavior for `/v1/playlists`.
- [ ] 2.4 Remove or replace list-result helpers that depend on unavailable fields such as `projectId`, `itemsCount`, `totalDuration`, or `isPublic`.

## 3. Playlist Entity Collection

- [ ] 3.1 Replace `PlaylistEntityListResult extends PaginatedResponse` with an unpaginated playlist entity collection type.
- [ ] 3.2 Update `PlaylistsService::entities()` return type and implementation to parse `data` without requiring `meta`.
- [ ] 3.3 Update `PlaylistsService::getAllEntities()` so it does not loop on `hasNextPage()`.
- [ ] 3.4 Keep useful collection helpers such as iteration, count, sorting by position, and lookup by entity or video id.

## 4. Project and Parent Resolution

- [ ] 4.1 Remove, deprecate, or replace `PlaylistsService::listByProject()` so it no longer relies on ignored `/v1/playlists?project_id=...` filtering.
- [ ] 4.2 Add an explicit resolver/catalog API that maps playlist `parentId` to a project directly or through project folders.
- [ ] 4.3 Add tests for parent id matching a project id, parent id matching a folder id, and unresolved parent id.
- [ ] 4.4 Document that parent/project resolution performs additional API reads and is not part of ordinary `list()`.

## 5. Documentation and Validation

- [ ] 5.1 Update `README.md` examples for playlists and playlist entities.
- [ ] 5.2 Update `CHANGELOG.md` with the playlist API contract fix and breaking behavior.
- [ ] 5.3 Run `make openspec-validate`.
- [ ] 5.4 Run `make test-unit`.
- [ ] 5.5 Run `make lint-all`.
- [ ] 5.6 Run relevant playlist integration checks when Kinescope credentials are available.
