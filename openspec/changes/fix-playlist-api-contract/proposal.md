## Why

The SDK exposes playlist methods and DTOs, but their contract does not match the live Kinescope API payload. Playlist list responses are paginated, playlist entities are not, playlist items use `name` and `parent_id` instead of the SDK's older `title` and `project_id` assumptions, and the API currently ignores `project_id`/`parent_id` query filters for `/v1/playlists`.

## What Changes

- Align `PlaylistDTO` parsing with the current playlist payload: `name`, `parent_id`, `workspace_id`, `player_id`, `privacy_type`, `play_link`, `embed_link`, tags, settings, and privacy fields.
- Keep `/v1/playlists` modeled as a paginated endpoint using strict `meta.pagination`.
- **BREAKING**: Stop modeling `/v1/playlists/{playlist_id}/entities` as a paginated response while the API returns only `data`.
- **BREAKING**: Remove, deprecate, or replace project-filter behavior that depends on `/v1/playlists?project_id=...`, because the live API currently returns the unfiltered playlist list.
- Introduce an explicit playlist parent-resolution contract for mapping `PlaylistDTO::parentId` to either a project or a folder-owned project when SDK users need project context.
- Update tests and documentation to reflect the real playlist and playlist entity payloads.

## Capabilities

### New Capabilities

- `playlist-api-contract`: Contract for parsing playlist payloads, listing playlists, reading playlist entities, and exposing explicit parent/project resolution behavior.

### Modified Capabilities

- None.

## Impact

- Affected code: `src/Services/Playlists/PlaylistsService.php`, playlist DTOs/list results under `src/DTO/Playlist/`, related unit and integration tests, and public documentation.
- Affected public SDK behavior: playlist DTO field mapping, playlist entity return type/navigation helpers, and project-related playlist methods.
- Dependencies: no new runtime dependency expected.
- External systems: read-only Kinescope API integration verification should cover `/v1/playlists`, `/v1/playlists/{playlist_id}/entities`, `/v1/projects`, and `/v1/projects/{project_id}/folders` when credentials are available.
