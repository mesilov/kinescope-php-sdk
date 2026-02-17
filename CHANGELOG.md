# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.3.0] - 2026-02-17

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

## [0.2.0] - 2026-02-07

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
