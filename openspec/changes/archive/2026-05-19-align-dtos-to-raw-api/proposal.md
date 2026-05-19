## Why

The SDK DTO layer should reflect the current Kinescope API payloads instead of older guessed field names. Live read-only API checks show that folders, videos, assets, playlists, playlist entities, and subtitles expose different field names and pagination shapes than the current SDK models.

## What Changes

- Align folder DTOs with `size`, `items_count`, and nullable deletion/update timestamps.
- Align video and asset DTOs with current video payload fields such as `embed_link`, `play_link`, `poster`, `privacy_*`, `player_id`, `version`, `progress`, `subtitles`, `chapters`, `audio_tracks`, `original_name`, `filetype`, and `md5`.
- Align playlist DTOs and playlist entities with current playlist payloads.
- Represent SDK timestamp fields with `Carbon\CarbonImmutable` while keeping raw API key names in array exports.
- Use `Carbon\CarbonImmutable` for SDK HTTP-date parsing such as `Retry-After`.
- Treat playlist entities, subtitles, and annotations as unpaginated `data` collections when the API returns no `meta.pagination`.
- Update CLI JSON/table normalization so read-only commands do not reintroduce old field names.
- Update tests and changelog for the breaking DTO contract changes.

## Impact

- Affected code: DTOs under `src/DTO`, `ResponseHandler`, `RateLimitException`, `PlaylistsService`, `Statistics`, `VideoSlugExtractor`, Kinescope CLI command normalizers, unit tests, docs, and changelog.
- Affected public SDK behavior: breaking DTO properties, array exports, list-result helpers, playlist entity response type, and CLI output field names.
- Dependencies: none.
