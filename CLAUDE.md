# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

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
