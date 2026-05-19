## ADDED Requirements

### Requirement: DTOs mirror current raw API field names
The SDK SHALL expose Kinescope resource DTO data using field names that correspond to the current raw API payloads instead of SDK-invented legacy aliases.

#### Scenario: Folder payload uses items and size
- **WHEN** a folder payload contains `id`, `name`, `project_id`, `parent_id`, `size`, `items_count`, `created_at`, `updated_at`, and `deleted_at`
- **THEN** `FolderDTO` exposes those values through matching raw-shaped DTO fields
- **AND** the exported array contains `items_count` and `size`
- **AND** the exported array does not contain `videos_count`

#### Scenario: Video payload preserves current fields
- **WHEN** a video payload contains current API fields such as `player_id`, `version`, `subtitle`, `progress`, `poster`, `privacy_type`, `privacy_domains`, `play_link`, `embed_link`, `subtitles`, `chapters`, `audio_tracks`, and `meta`
- **THEN** `VideoDTO` exposes those values without fabricating unavailable legacy fields such as `embed_code`, `dash_link`, `poster_url`, `thumbnail_url`, `views_count`, or `plays_count`

#### Scenario: Asset payload preserves current fields
- **WHEN** an asset payload contains `original_name`, `file_size`, `md5`, `filetype`, `quality`, `resolution`, `url`, and `download_link`
- **THEN** `AssetDTO` exposes those values and exports them with the same API keys
- **AND** the exported array does not contain unavailable legacy `bitrate` or `codec` keys

#### Scenario: Playlist payload uses current playlist fields
- **WHEN** a playlist payload contains `name`, `workspace_id`, `parent_id`, `player_id`, `privacy_type`, `privacy_domains`, `privacy_email_domains`, `privacy_share`, `unique_codes_enabled`, `tags`, `settings`, `play_link`, and `embed_link`
- **THEN** `PlaylistDTO` exposes those values and exports the same API keys
- **AND** the exported array does not contain legacy `title`, `project_id`, `items_count`, `total_duration`, `poster_url`, `embed_code`, or `is_public` keys

### Requirement: SDK date handling uses CarbonImmutable
The SDK SHALL base date and timestamp handling on `Carbon\CarbonImmutable`, exposing resource timestamps as Carbon objects while preserving raw API date key names in exported arrays.

#### Scenario: Resource timestamp payloads are parsed into CarbonImmutable
- **WHEN** a resource payload contains timestamp keys such as `created_at`, `updated_at`, or `deleted_at`
- **THEN** the corresponding DTO exposes the camelCase properties as `Carbon\CarbonImmutable` or `null`
- **AND** `toArray()` exports the timestamp values under the original API keys as ISO JSON strings or `null`

#### Scenario: Generated SDK timestamps use CarbonImmutable
- **WHEN** the SDK generates a timestamp such as statistics `generatedAt`
- **THEN** the public DTO property is a `Carbon\CarbonImmutable` instance

#### Scenario: HTTP date Retry-After is parsed through CarbonImmutable
- **WHEN** a 429 response contains an HTTP-date `Retry-After` header
- **THEN** the SDK parses the date using `Carbon\CarbonImmutable`
- **AND** the thrown `RateLimitException` exposes the computed retry delay in seconds

### Requirement: Non-paginated data endpoints are not forced into pagination
The SDK SHALL parse API endpoints that return `data` without `meta.pagination` as unpaginated collections.

#### Scenario: Playlist entities have no pagination metadata
- **WHEN** `/v1/playlists/{playlist_id}/entities` returns `data` without `meta`
- **THEN** playlist entity parsing succeeds without calling `MetaDTO::fromArray()`

#### Scenario: Subtitle and annotation data responses have no pagination metadata
- **WHEN** `/v1/videos/{video_id}/subtitles` or `/v1/videos/{video_id}/annotations` returns `data` without `meta`
- **THEN** subtitle and annotation list parsing succeeds without requiring pagination metadata
