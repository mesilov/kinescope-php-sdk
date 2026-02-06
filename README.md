# Kinescope PHP SDK

Unofficial PHP SDK for [Kinescope](https://kinescope.io) API — a video management platform for uploading, transcoding (up to 4K), protection, and delivery of video content.

## Requirements

- PHP >= 8.4
- Extensions: `ext-json`, `ext-curl`, `ext-mbstring`
- A PSR-18 HTTP client (e.g., Guzzle or Symfony HTTP Client)
- A PSR-7/PSR-17 implementation (e.g., `nyholm/psr7`)

## Installation

```bash
composer require mesilov/kinescope-php-sdk
```

You also need an HTTP client and PSR-7 implementation. For example, with Guzzle:

```bash
composer require guzzlehttp/guzzle nyholm/psr7
```

## Quick Start

```php
use Kinescope\Core\Credentials;
use Kinescope\Services\ServiceFactory;

// Create factory with API key
$factory = new ServiceFactory(Credentials::fromString('your-api-key'));

// Or read from KINESCOPE_API_KEY environment variable
$factory = ServiceFactory::fromEnvironment();

// Use services
$videos = $factory->videos()->list();
$projects = $factory->projects()->list();
$folders = $factory->folders()->list();
$playlists = $factory->playlists()->list();
```

## Available Services

| Service | Access | Description |
|---------|--------|-------------|
| Videos | `$factory->videos()` | Video CRUD, upload, download |
| Projects | `$factory->projects()` | Project management |
| Folders | `$factory->folders()` | Folder management |
| Playlists | `$factory->playlists()` | Playlist management |

## Development

### Setup

```bash
# Initialize project (first run)
make docker-init

# Start Docker environment
make docker-up

# Install dependencies
make composer-install
```

### Testing

```bash
# Unit tests
make test-unit

# Integration tests (requires API key)
make test-integration
```

### Code Quality

```bash
# Run all linters
make lint-all

# Static analysis
make lint-phpstan

# Code style check (dry-run)
make lint-cs-fixer

# Fix code style
make lint-cs-fixer-fix
```

## License

MIT. See [LICENSE](LICENSE) for details.
