## ADDED Requirements

### Requirement: Parse playlist list payloads
The SDK SHALL parse `/v1/playlists` items from the current Kinescope API payload shape.

#### Scenario: Playlist item uses current API fields
- **WHEN** a playlist item contains `id`, `name`, `parent_id`, `workspace_id`, `player_id`, `privacy_type`, `play_link`, `embed_link`, tags, privacy fields, and `settings`
- **THEN** the resulting playlist DTO exposes those values without fabricating unrelated legacy fields

#### Scenario: Playlist name is preserved
- **WHEN** a playlist item contains `name`
- **THEN** the SDK exposes that value as the playlist display name

#### Scenario: Playlist parent is preserved
- **WHEN** a playlist item contains `parent_id`
- **THEN** the SDK exposes that value as the playlist parent identifier

### Requirement: Keep playlist listing paginated
The SDK SHALL treat `/v1/playlists` as a paginated endpoint and parse pagination only from `meta.pagination`.

#### Scenario: Playlist list includes pagination metadata
- **WHEN** `/v1/playlists` returns `meta.pagination.page`, `meta.pagination.per_page`, and `meta.pagination.total`
- **THEN** playlist list pagination helpers use those values

#### Scenario: Playlist list metadata is malformed
- **WHEN** `/v1/playlists` metadata is missing `pagination.total`, `pagination.page`, or `pagination.per_page`
- **THEN** playlist list parsing fails explicitly

### Requirement: Model playlist entities as an unpaginated collection
The SDK SHALL parse `/v1/playlists/{playlist_id}/entities` as an unpaginated collection while the endpoint returns `data` without `meta`.

#### Scenario: Playlist entities response has no metadata
- **WHEN** `/v1/playlists/{playlist_id}/entities` returns a response with `data` and no `meta`
- **THEN** the SDK returns a playlist entity collection without pagination helpers

#### Scenario: Playlist entities collection exposes items
- **WHEN** playlist entities are parsed from the `data` array
- **THEN** consumers can iterate, count, filter, sort by position, and find entities by id or video id without relying on pagination metadata

### Requirement: Avoid unsupported playlist project filters
The SDK SHALL NOT expose playlist project filtering as a direct `/v1/playlists?project_id=...` contract while the live API ignores that filter.

#### Scenario: Consumer asks for playlists by project
- **WHEN** a consumer needs playlists for a project
- **THEN** the SDK uses an explicit parent-resolution capability or reports that direct server-side filtering is unsupported

#### Scenario: Unsupported query filter is not hidden
- **WHEN** `/v1/playlists?project_id=...` or `/v1/playlists?parent_id=...` returns the unfiltered playlist list
- **THEN** the SDK does not present that response as a correctly filtered project playlist result

### Requirement: Resolve playlist project ownership explicitly
The SDK SHALL provide an explicit way to resolve a playlist parent to project context when consumers need project ownership.

#### Scenario: Playlist parent is a project
- **WHEN** a playlist `parent_id` matches a project id
- **THEN** the resolver reports that project as the playlist project

#### Scenario: Playlist parent is a folder
- **WHEN** a playlist `parent_id` matches a folder id returned from `/v1/projects/{project_id}/folders`
- **THEN** the resolver reports that folder and its owning project as the playlist context

#### Scenario: Playlist parent cannot be resolved
- **WHEN** a playlist `parent_id` matches neither a project id nor a known folder id
- **THEN** the resolver reports an unresolved parent without fabricating project ownership
