# Kinescope PHP SDK

Unofficial PHP SDK for [Kinescope](https://kinescope.io) API — a video management platform for uploading, transcoding (up to 4K), protection, and delivery of video content.

## Requirements

- PHP >= 8.4
- Extensions: `ext-json`, `ext-curl`, `ext-mbstring`
- A PSR-18 HTTP client (e.g., Guzzle or Symfony HTTP Client)
- A PSR-7/PSR-17 implementation (e.g., `nyholm/psr7`)
- Symfony components compatibility: `^5.4|^6.0|^7.0|^8.0`

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
| Videos | `$factory->videos()` | Read/list/search videos |
| Projects | `$factory->projects()` | Read/list projects |
| Folders | `$factory->folders()` | Folder listing and tree navigation |
| Playlists | `$factory->playlists()` | Playlist and playlist-entities listing |

## Video Downloader + Events

`VideoDownloader` supports event subscriptions for the download lifecycle:
- `DownloadStartedEvent`
- `DownloadProgressEvent`
- `DownloadCompletedEvent`
- `DownloadFailedEvent`

```php
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Kinescope\Enum\QualityPreference;
use Kinescope\Event\Download\DownloadProgressEvent;
use Kinescope\Services\Videos\VideoDownloader;
use Symfony\Component\Filesystem\Filesystem;

$downloader = new VideoDownloader(
    $factory->videos(),
    Psr18ClientDiscovery::find(),
    Psr17FactoryDiscovery::findRequestFactory(),
    new Filesystem(),
);

$downloader->on(DownloadProgressEvent::class, function (DownloadProgressEvent $event): void {
    printf("Progress: %.1f%%\n", $event->percent);
});

$filePath = $downloader->downloadVideo(
    videoId: 'your-video-id',
    destinationDir: __DIR__ . '/downloads',
    quality: QualityPreference::BEST,
);
```

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

# Full test suite
make test
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

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history and migration notes.
