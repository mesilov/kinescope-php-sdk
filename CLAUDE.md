# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Guidelines

### Project Structure & Module Organization
Core library code lives in `src/` under the `Kinescope\` namespace (PSR-4). Main areas:
- `src/Core` for HTTP/client infrastructure (`ApiClient`, `ResponseHandler`, credentials, pagination/sort helpers)
- `src/Services` for API-facing service classes (`Videos`, `Projects`, `Folders`, `Playlists`)
- `src/DTO`, `src/Enum`, `src/Exception`, `src/Contracts` for typed models and shared contracts

Tests are in `tests/`:
- `tests/Unit` for isolated logic
- `tests/Integration` for API/client integration behavior
- `tests/Fixtures` for reusable test data

Local tooling/config is in `docker/`, `docker-compose.yaml`, `Makefile`, `phpunit.xml`, and `phpstan.neon`.

### Build, Test, and Development Commands
Use Make targets to keep environment parity:
- `make docker-init` - first-time setup (containers + dependencies)
- `make docker-up` / `make docker-down` - start/stop dev containers
- `make composer-install` - install PHP dependencies in container
- `make lint-all` - run style, static analysis, and Rector dry-run
- `make lint-cs-fixer-fix` - auto-fix coding style
- `make test` - run all PHPUnit suites
- `make test-unit` / `make test-integration` - run one suite
- `make test-coverage` - generate HTML coverage in `coverage/`

### Coding Style & Naming Conventions
Target PHP `>=8.4` with `declare(strict_types=1);` in PHP files. Follow PSR-12 and project rules via PHP-CS-Fixer (`.php-cs-fixer.dist.php`).

Conventions:
- 4-space indentation, short arrays (`[]`), ordered imports
- Class/enum names: `PascalCase`; methods/properties: `camelCase`
- DTO classes end with `DTO` or `ListResult`; tests end with `Test.php`
- Keep namespaces aligned with directories (for example `src/Services/Videos/Videos.php`)

### Testing Guidelines
Framework: PHPUnit (`phpunit.xml`) with `unit` and `integration` suites.
- Mirror source structure when adding tests
- Add/update unit tests for new logic and bug fixes
- Run `make test-unit` before commits; run `make test-integration` for API-related changes

Integration tests require environment variables such as `KINESCOPE_API_KEY`, `TESTS_VIDEO_DOWNLOADER_VIDEO_ID`, and `TESTS_VIDEO_DOWNLOADER_FOLDER_ID`.

### Commit & Pull Request Guidelines
Recent history uses short, imperative commit subjects (example: `Add logging to VideoDownloader and integration tests`).
- Keep one logical change per commit
- Start subject with a verb (`Add`, `Fix`, `Refactor`, `Update`)
- In PRs, include: purpose, key changes, test evidence (`make lint-all`, `make test`), and linked issue(s)
- Update docs (`README.md`) when public SDK behavior changes

### Security & Configuration Tips
Never commit real API keys or secrets. Keep local values in `.env.local`; use `.env.local.example` as the template for shared configuration.

## Project Overview

PHP SDK for Kinescope API - a video management platform for uploading, transcoding (up to 4K), protection, and delivery of video content.

**Current Status**: Active development. Core services (Videos, Projects, Folders, Playlists) are implemented with unit and integration tests.

## Key Resources

- **Technical Specification**: `.tasks/technical-specification.md` - Complete implementation guide (in Russian)
- **OpenAPI Spec**: `.tasks/openapi.yaml` - Full API documentation (253KB)
- **API Base URL**: `https://api.kinescope.io`
- **Authentication**: Bearer Token (`Authorization: Bearer {api_key}`)

## Commands

All commands are executed via Makefile for development environment consistency.

```bash
# Инициализация проекта (первый запуск)
make docker-init

# Запуск/остановка Docker
make docker-up
make docker-down

# Запуск тестов
make test-unit
make test-integration

# Проверка качества кода (все линтеры)
make lint-all

# Статический анализ
make lint-phpstan

# Проверка стиля кода (dry-run)
make lint-cs-fixer

# Исправление стиля кода
make lint-cs-fixer-fix

# Доступ к контейнеру
make php-cli-bash

# Composer команды
make composer-install
make composer-update
make composer args="require some/package"
```

## Architecture

### Directory Structure
```
src/
├── Contracts/                      # Interfaces
│   └── ApiClientInterface.php      # HTTP client contract
├── Core/                           # Core infrastructure
│   ├── ApiClient.php               # PSR-18 HTTP client implementation
│   ├── ApiClientFactory.php        # Builder for ApiClient
│   ├── Credentials.php             # API key management
│   ├── JsonDecoder.php             # JSON response decoding
│   ├── Pagination.php              # Pagination parameters
│   ├── ResponseHandler.php         # HTTP response processing
│   └── Sort.php                    # Sort parameters
├── DTO/                            # Data transfer objects
│   ├── Common/                     # MetaDTO, PaginatedResponse
│   ├── Folder/                     # FolderDTO, FolderListResult
│   ├── Playlist/                   # PlaylistDTO, PlaylistEntityDTO, list results
│   ├── Project/                    # ProjectDTO, ProjectListResult
│   └── Video/                      # VideoDTO, AssetDTO, AnnotationDTO, SubtitleDTO, etc.
├── Enum/                           # Enumerations
│   ├── HttpMethod.php
│   ├── PrivacyType.php
│   ├── QualityPreference.php
│   ├── SortDirection.php
│   ├── SubtitleLanguage.php
│   └── VideoStatus.php
├── Exception/                      # Exception hierarchy
│   ├── KinescopeException.php      # Base exception
│   ├── AuthenticationException.php
│   ├── BadRequestException.php
│   ├── ForbiddenException.php
│   ├── NetworkException.php
│   ├── NotFoundException.php
│   ├── PaymentRequiredException.php
│   ├── RateLimitException.php
│   └── ValidationException.php
└── Services/                       # API service classes
    ├── AbstractService.php         # Base class for services
    ├── ServiceFactory.php          # Main SDK entry point
    ├── Folders/
    │   └── FoldersService.php
    ├── Playlists/
    │   └── PlaylistsService.php
    ├── Projects/
    │   └── Projects.php
    └── Videos/
        ├── Videos.php
        └── VideoDownloader.php
```

### SDK Design Pattern
- Entry point is `ServiceFactory` with lazy service instantiation
- Usage: `$factory->videos()->list()`, `$factory->projects()->get($id)`
- Each service extends `AbstractService` with standard CRUD methods
- All API responses mapped to typed DTO objects
- Exception hierarchy maps to HTTP status codes (401 -> AuthenticationException, etc.)

```php
use Kinescope\Core\Credentials;
use Kinescope\Services\ServiceFactory;

$factory = new ServiceFactory(Credentials::fromString('your-api-key'));
// or
$factory = ServiceFactory::fromEnvironment(); // reads KINESCOPE_API_KEY

$videos = $factory->videos()->list();
$projects = $factory->projects()->list();
$folders = $factory->folders()->list();
$playlists = $factory->playlists()->list();
```

## Technical Requirements

- **PHP**: >= 8.4 (required for readonly classes, enums, property hooks)
- **Extensions**: `ext-json`, `ext-curl`, `ext-mbstring`
- **Standards**: PSR-1, PSR-4, PSR-7, PSR-12, PSR-17, PSR-18
- **Dependencies**: PSR-18 HTTP client, `php-http/discovery`, `symfony/uid`, `symfony/filesystem`, `symfony/mime`
- **Dev Tools**: PHPUnit ^10.0|^11.0, PHPStan ^2.0 (with phpstan-phpunit and strict-rules), PHP-CS-Fixer ^3.0, Rector ^2.0, Faker ^1.20, php-http/mock-client ^1.5

## Development Phases

1. **Foundation** (done): Project structure, Composer, ApiClient, HttpClient, exceptions, PHPUnit setup
2. **Core Resources** (done): Videos, Projects, Folders, Playlists services with DTOs and unit tests
3. **Additional Features** (in progress): Video download, annotations, subtitles, pagination, sorting
4. **Testing & Docs**: Integration tests, 80%+ coverage, documentation
5. **Finalization**: CI/CD, static analysis cleanup, Packagist publication

## API Endpoints Reference

Key HTTP status codes handled:
- 200: Success
- 400: BadRequestException
- 401: AuthenticationException
- 402: PaymentRequiredException
- 403: ForbiddenException
- 404: NotFoundException
- 429: RateLimitException
