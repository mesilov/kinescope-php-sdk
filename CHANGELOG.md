# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.5.0 — UNRELEASED

### Breaking changes
- SDK CLI now exports `bin/kinescope` / `vendor/bin/kinescope` instead of the generic `bin/console` / `vendor/bin/console`.
- CLI commands now use singular resource/action names, and no compatibility aliases are kept for the old command names:
  - `video:info <video-id>` -> `kinescope:video:show <video-id>`
  - `kinescope:browse projects` -> `kinescope:project:list`
  - `kinescope:browse folders --project-id=<project-id>` -> `kinescope:folder:list --project-id=<project-id>`
  - `kinescope:browse videos --project-id=<project-id> [--folder-id=<folder-id>]` -> `kinescope:video:list --project-id=<project-id> [--folder-id=<folder-id>]`
  - `kinescope:browse assets --video-id=<video-id>` -> `kinescope:video:asset:list <video-id>`
- `ProjectDTO` now follows the raw Kinescope project API field names: use `itemsCount`, `folders`, `size`, `privacyDomains`, `privacyEmailDomains`, `privacyShare`, `playerId`, `favorite`, and `encrypted` instead of the removed aliases `videosCount`, `foldersCount`, `storageUsed`, `allowedDomains`, `isDefault`, and `settings`.
  - `ProjectListResult::getTotalVideosCount()` is replaced by `getTotalItemsCount()`.
  - `ProjectListResult::getTotalStorageUsed()` is replaced by `getTotalSize()`.
- Resource DTOs now follow current raw API fields instead of legacy SDK aliases:
  - `FolderDTO` uses `size`, `itemsCount`, and `deletedAt`; removed legacy `videosCount`, `description`, `depth`, `path`, and `position`.
  - `VideoDTO` uses current raw video fields such as `title`, `projectId`, `folderId`, `playerId`, `version`, `progress`, `poster`, `playLink`, `embedLink`, `subtitles`, `chapters`, `audioTracks`, and `meta`; removed unavailable aliases such as `embedCode`, `dashLink`, `posterUrl`, `thumbnailUrl`, `viewsCount`, and `playsCount`.
  - `VideoDTO::$duration` and `PlaylistEntityDTO::$duration` now preserve API fractional seconds as `float`.
  - `AssetDTO` uses `originalName`, `filetype`, and `md5`; removed legacy `bitrate` and `codec`.
  - `PlaylistDTO` uses `name`, `workspaceId`, `parentId`, `playerId`, `privacy*`, `settings`, `playLink`, and `embedLink`; removed legacy `title`, `projectId`, `itemsCount`, `totalDuration`, `posterUrl`, `embedCode`, and `isPublic`.
- Timestamp fields in SDK DTOs are now `Carbon\CarbonImmutable`: `createdAt`, `updatedAt`, `deletedAt`, and `generatedAt` no longer expose raw strings or `DateTimeImmutable`.
- `PlaylistEntityListResult`, `SubtitleListResult`, and `AnnotationListResult` now parse unpaginated `data` responses without synthetic `MetaDTO` pagination.
- `MetaDTO::toArray()` now exports raw-shaped `pagination` and `order` keys.
- `PlaylistsService::listByProject()` is removed because the live API does not support that filter reliably; `findByTitle()` is replaced by `findByName()`.

### Added
- Commands `kinescope:project:list`, `kinescope:project:show`, `kinescope:folder:list`, `kinescope:folder:show`, `kinescope:video:list`, `kinescope:video:show`, and `kinescope:video:asset:list` for read-only Kinescope account inspection from the SDK CLI.
  - List commands support `table` and deterministic `json` output.
  - Show commands output pretty JSON.
  - All commands resolve credentials via `KINESCOPE_API_KEY` or `--api-key` / `-k`.
  - Resource identifiers are validated locally before API reads.
  - Asset output is sanitized by exposing `has_url`, `has_download_link`, and `downloadable` booleans instead of raw signed CDN URLs.

### Fixed
- `Retry-After` HTTP-date parsing now uses `Carbon\CarbonImmutable`, and 429 errors keep retry metadata on `RateLimitException`.

## 0.4.0 — 2026-05-14

### Breaking changes
- `VideoDownloader` no longer accepts PSR-18 `ClientInterface` or PSR-17 `RequestFactoryInterface` constructor dependencies for video file bytes. The new constructor order is `Videos`, `Filesystem`, `FileTransferInterface`, `EventDispatcherInterface`, `AssetSelector`, `LoggerInterface`; the logger is the final optional dependency.
  - Migration: `new VideoDownloader($videos, $httpClient, $requestFactory, $filesystem)` -> `new VideoDownloader(videos: $videos, filesystem: $filesystem)`.
  - Applications that need a non-default transfer should inject `fileTransfer: new AppFileTransfer()` where `AppFileTransfer` implements `FileTransferInterface`.
- `AssetDTO` no longer exposes separate `?int $width` and `?int $height` properties. They are replaced by a single `?Resolution $resolution` property that wraps the dimensions in a value object. `AssetDTO::getResolution(): ?string` is removed in favor of the public `$resolution` property; cast it to string to get the legacy `"<width>x<height>"` shape. `AssetDTO::toArray()` now emits a single `resolution` key (string or `null`) instead of separate `width` / `height` keys.
  - Migration:
    - `$asset->width` → `$asset->resolution?->width`
    - `$asset->height` → `$asset->resolution?->height`
    - `$asset->getResolution()` → `$asset->resolution === null ? null : (string) $asset->resolution`
    - `toArray()` snapshots persisted before this release will not round-trip back; the canonical key is now `resolution`.
- `Videos::list(status:)` now accepts `?VideoStatus` instead of `?string` and serializes the filter as API key `status[]` with a single scalar enum value.
  - Migration: `status: 'done'` → `status: VideoStatus::DONE`.

### Added
- `Kinescope\DTO\Video\Resolution` — `final readonly` value object with positive-int `width` and `height`, `Resolution::tryFromString()` / `Resolution::fromString()` parsers for the canonical `"<width>x<height>"` shape, `aspectRatio()`, `isHd()` / `isFullHd()` / `is4K()` predicates, and `__toString()`.
- `Kinescope\DTO\Statistics\StatisticsDTO` — immutable statistics result with done-video count, total duration, rounded minute/hour helpers, human-readable formatting, and array export.
- `Kinescope\Services\Statistics\Statistics` exposed through `$factory->statistics()` with `forAccount()`, `forProject()`, and `forFolder()` aggregations over videos with `VideoStatus::DONE`.
- `VideoStatus` now includes the documented `pre-processing` and `aborted` states.
- Statistics integration-test env slots: `TESTS_STATISTICS_PROJECT_ID` and `TESTS_STATISTICS_FOLDER_ID`.
- `AssetDTO::fromArray()` now parses the API `resolution` string into a `Resolution` value object; numeric `width`+`height` keys remain authoritative when both are positive.
- `VideoSlugExtractor` — pure stateless service for extracting video slugs from HLS links, embed codes, or `VideoDTO`.
- `VideoFetcher` — auto-paginated search service:
  - `findByTitle(string $title): VideoDTO[]` — server-side search via `Videos::search()`, iterates all pages.
  - `findByVideoSlug(string $slug): VideoDTO[]` — client-side filter via `Videos::list()`, iterates all pages and matches by slug.
- CLI application `bin/console` based on Symfony Console 8.
- Command `video:info <video-id>` — fetches video data by UUID and outputs it as pretty-printed JSON to STDOUT.
  - API key resolved from `KINESCOPE_API_KEY` env variable or `--api-key` / `-k` option.
  - Errors (not found, auth failure, network) written to STDERR; exit code `1` on failure.
- `src/Infrastructure/Console/Application` — Console application entry point.
- `src/Infrastructure/Console/ContainerFactory` — standalone Symfony DI container; wires `LoggerInterface` and pre-configured `ApiClientFactory` into commands.
- New runtime dependencies: `symfony/console ^8.0`, `symfony/dependency-injection ^8.0`.
- Makefile target `console-list` — lists all registered SDK CLI commands.
- Integration tests for `VideoFetcher` covering title search, slug lookup, and missing-slug behavior against the real Kinescope API.
- `AssetSelector` — dedicated service that picks a downloadable `AssetDTO` based on the requested `QualityPreference`. Injected into `VideoDownloader` as a constructor dependency with a sensible default; existing callers do not need to change.
- `FileTransferInterface`, `FileTransferRequest`, `FileTransferProgress`, and `FileTransferResult` for injecting custom video file-transfer implementations.
- `CurlFileTransfer` — default direct-to-file transfer implementation for selected video download links. It uses `GET`, HTTP/HTTPS only, up to 5 redirects, TLS peer/host verification, `2xx` success statuses only, a 10-second connect timeout, no fixed total timeout, and low-speed failure below 1024 bytes/sec for 60 seconds.
- Makefile targets `test-integration-fast` and `test-integration-download`. The latter automatically sets `TESTS_VIDEO_DOWNLOADER_ENABLED=1` so heavy CDN-download tests run only on demand.
- `TESTS_VIDEO_DOWNLOADER_ENABLED` env flag — opt-in gate for `VideoDownloaderTest`; downloader integration tests are skipped unless explicitly enabled.

### Changed
- `VideoDownloader` now transfers selected asset bytes through `FileTransferInterface`, writes in-progress downloads to a sibling `.part` file, validates the completed byte count against transfer-reported bytes when available and selected asset `fileSize` as a fallback, and renames `.part` to the final destination only after validation succeeds.
- `VideoDownloader` removes handled failed `.part` files and does not report completion after transfer or validation failures. Fatal process termination may still leave `.part` cleanup candidates.
- Download progress events are now throttled at 10 MiB boundaries in `VideoDownloader`; transfer implementations may report smaller chunks, but lifecycle events remain SDK-owned.
- Kinescope API bearer credentials are not sent to selected asset download URLs by default. Transfer request headers are explicit only.
- Symfony HttpClient remains optional for file downloads; applications can use it through a custom `FileTransferInterface` implementation with `buffer=false` and `stream()`.

### Fixed
- Fractional video durations from the API are now rounded to the nearest whole second in `VideoDTO::fromArray()` instead of being truncated.
- `VideoDownloader` progress callback invocation is now compatible with PHPStan strict callable analysis.
- `QualityPreference::WORST` now selects the smallest downloadable file by `fileSize` instead of treating missing asset height as resolution `0`.
- Pagination metadata parsing now reads the current Kinescope API shape from `meta.pagination.total`, `meta.pagination.page`, and `meta.pagination.per_page` instead of the obsolete flat `meta.total`, `meta.page`, and `meta.per_page` keys.
- Malformed paginated response metadata now fails explicitly instead of returning hidden pagination defaults.

### Quality
- Added project Rector configuration for PHP 8.4 dry-run linting.
- Applied Rector PHP 8.4 cleanup rules and kept exceptions for transformations that conflict with Symfony DI or PHPStan.
- Added OpenSpec support via a Docker-backed `kinescope-php-sdk-openspec:dev` image and Makefile wrappers.
- Added repository-local Codex skills generated by OpenSpec for explore, propose, apply, and archive workflows.

## 0.3.0 - 2026-02-17

### Added
- Event subscription support in `VideoDownloader` via `on(string $eventName, callable $listener, int $priority = 0): self`.
- Typed download lifecycle events:
  - `DownloadStartedEvent`
  - `DownloadProgressEvent`
  - `DownloadCompletedEvent`
  - `DownloadFailedEvent`
- Runtime dependencies for event/time handling:
  - `symfony/event-dispatcher`
  - `nesbot/carbon`
- Unit tests for event dispatch behavior in `VideoDownloader`.

### Changed
- `VideoDownloader` now dispatches events during successful and failed downloads.
- Symfony package constraints updated to include Symfony 8:
  - `symfony/event-dispatcher`
  - `symfony/filesystem`
  - `symfony/mime`
  - `symfony/uid`

## 0.2.0 - 2026-02-07

### Added

#### Scope: ServiceFactory
- Factory entry points for SDK usage:
  - `fromEnvironment()`
  - `withClient()`
- Lazy accessors for services:
  - `videos()`
  - `projects()`
  - `folders()`
  - `playlists()`

#### Scope: Videos service
- Video read operations:
  - `list()` with pagination, sorting, filters (`project`, `folder`, `status`, search)
  - `get()`
  - `listByProject()`
  - `listByFolder()`
  - `search()`

#### Scope: VideoDownloader
- File download operations:
  - `downloadVideo()`
  - `downloadFolder()`
- Asset selection strategy via `QualityPreference` (`BEST`/`WORST`).
- Streamed file writing with chunked reads and progress logging.

#### Scope: Projects service
- Project read operations:
  - `list()` with pagination/sorting
  - `get()`

#### Scope: Folders service
- Folder read/navigation operations:
  - `list()`
  - `get()`
  - `getAll()`
  - `getRoots()`
  - `getChildren()`
  - `getTree()`

#### Scope: Playlists service
- Playlist and entity operations:
  - `list()`
  - `get()`
  - `entities()`
  - `getAllEntities()`
  - `getAll()`
  - `listByProject()`
  - `getPublic()`
  - `findByTitle()`

#### Scope: Core and contracts
- API infrastructure and DTO mapping:
  - `ApiClient`, `ApiClientFactory`, `ResponseHandler`, `JsonDecoder`
  - typed DTOs for videos, projects, folders, playlists, subtitles, annotations
  - enums and exception hierarchy for API/error handling

### Quality
- Unit and integration test suites for core services and DTOs.
- Static analysis and style tooling via PHPStan and PHP-CS-Fixer.
