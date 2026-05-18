# Kinescope PHP SDK

Unofficial PHP SDK for [Kinescope](https://kinescope.io) API — a video management platform for uploading, transcoding (up to 4K), protection, and delivery of video content.

## Requirements

- PHP >= 8.4
- Extensions: `ext-json`, `ext-curl`, `ext-mbstring`
- A PSR-18 HTTP client for API requests (e.g., Guzzle or Symfony HTTP Client)
- A PSR-7/PSR-17 implementation for API requests (e.g., `nyholm/psr7`)
- Symfony components compatibility: `^5.4|^6.0|^7.0|^8.0`

## Installation

```bash
composer require mesilov/kinescope-php-sdk
```

You also need an HTTP client and PSR-7 implementation for Kinescope API requests. For example, with Guzzle:

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
$statistics = $factory->statistics()->forAccount();
```

## Available Services

| Service | Access | Description |
|---------|--------|-------------|
| Videos | `$factory->videos()` | Read/list/search videos |
| Projects | `$factory->projects()` | Read/list projects |
| Folders | `$factory->folders()` | Folder listing and tree navigation |
| Playlists | `$factory->playlists()` | Playlist and playlist-entities listing |
| Statistics | `$factory->statistics()` | Done-video count and total duration aggregation |

## CLI

The package ships a standalone Symfony Console entry point:

```bash
vendor/bin/kinescope list
```

Credentials are resolved from `KINESCOPE_API_KEY` or `--api-key` / `-k`.

Read-only commands use singular resources and explicit actions:

```bash
# List projects or show one project
vendor/bin/kinescope kinescope:project:list --format=table
vendor/bin/kinescope kinescope:project:show 00000000-0000-0000-0000-000000000000

# List folders for a project or show one folder
vendor/bin/kinescope kinescope:folder:list \
  --project-id=00000000-0000-0000-0000-000000000000 \
  --format=json
vendor/bin/kinescope kinescope:folder:show 11111111-1111-1111-1111-111111111111 \
  --project-id=00000000-0000-0000-0000-000000000000

# List videos for a project or folder, or show one video
vendor/bin/kinescope kinescope:video:list \
  --project-id=00000000-0000-0000-0000-000000000000 \
  --folder-id=11111111-1111-1111-1111-111111111111 \
  --format=json
vendor/bin/kinescope kinescope:video:show 22222222-2222-2222-2222-222222222222

# Include sanitized asset summaries in video rows
vendor/bin/kinescope kinescope:video:list \
  --project-id=00000000-0000-0000-0000-000000000000 \
  --include-assets \
  --format=json

# Inspect sanitized assets for one video
vendor/bin/kinescope kinescope:video:asset:list 22222222-2222-2222-2222-222222222222
```

List commands support `table` or deterministic `json` output. Show commands print pretty JSON. Asset output exposes booleans such as `hasUrl`, `hasDownloadLink`, and `downloadable`; raw signed CDN URLs and download links are not printed by default.

## Statistics

```php
$account = $factory->statistics()->forAccount();
$project = $factory->statistics()->forProject('project-id');
$folder = $factory->statistics()->forFolder('folder-id');

printf(
    "%d done videos, %d seconds total\n",
    $account->videosCount,
    $account->getTotalSeconds(),
);
```

## Video Downloader + Events

`VideoDownloader` fetches video metadata through `Videos`, selects the requested downloadable asset, and transfers the selected video bytes through a dedicated file-transfer boundary. By default it uses `CurlFileTransfer`, which writes directly to the in-progress file without materializing a PSR-18 response body.

It also supports event subscriptions for the download lifecycle:
- `DownloadStartedEvent`
- `DownloadProgressEvent`
- `DownloadCompletedEvent`
- `DownloadFailedEvent`

```php
use Kinescope\Enum\QualityPreference;
use Kinescope\Event\Download\DownloadProgressEvent;
use Kinescope\Services\Videos\VideoDownloader;

$downloader = new VideoDownloader($factory->videos());

$downloader->on(DownloadProgressEvent::class, function (DownloadProgressEvent $event): void {
    printf("Progress: %.1f%%\n", $event->percent);
});

$filePath = $downloader->downloadVideo(
    videoId: 'your-video-id',
    destinationDir: __DIR__ . '/downloads',
    quality: QualityPreference::BEST,
);
```

For custom transfer behavior, inject `FileTransferInterface`. The downloader still owns metadata lookup, asset selection, lifecycle events, `.part` handling, and completed-size validation:

```php
use Kinescope\Services\Videos\Download\FileTransferInterface;
use Kinescope\Services\Videos\Download\FileTransferProgress;
use Kinescope\Services\Videos\Download\FileTransferRequest;
use Kinescope\Services\Videos\Download\FileTransferResult;
use Kinescope\Services\Videos\VideoDownloader;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

final readonly class AppFileTransfer implements FileTransferInterface
{
    public function transfer(FileTransferRequest $request, ?callable $onProgress = null): FileTransferResult
    {
        $source = fopen($request->url, 'rb');
        $target = fopen($request->outputPath, 'wb');
        $bytesWritten = 0;

        if ($source === false) {
            throw new RuntimeException('Transfer stream cannot be opened.');
        }

        if ($target === false) {
            fclose($source);

            throw new RuntimeException('Transfer output cannot be opened.');
        }

        try {
            while (! feof($source)) {
                $chunk = fread($source, 1024 * 1024);

                if ($chunk === false || $chunk === '') {
                    continue;
                }

                $written = fwrite($target, $chunk);

                if ($written === false) {
                    throw new RuntimeException('Transfer stream cannot be written.');
                }

                $bytesWritten += $written;

                if ($onProgress !== null) {
                    $onProgress(new FileTransferProgress($bytesWritten, $request->expectedBytes));
                }
            }
        } finally {
            fclose($source);
            fclose($target);
        }

        return new FileTransferResult($request->outputPath, $bytesWritten, $request->expectedBytes);
    }
}

$downloader = new VideoDownloader(
    videos: $factory->videos(),
    filesystem: new Filesystem(),
    fileTransfer: new AppFileTransfer(),
);
```

Symfony applications may implement this interface with `HttpClientInterface::request()` using `buffer: false` and `stream()`; `symfony/http-client` is not required by the SDK itself.

Default transfer policy:
- cURL `GET`, HTTP/HTTPS only, follows up to 5 HTTP/HTTPS redirects.
- TLS peer and host verification are enabled.
- Only final `2xx` HTTP statuses are successful.
- Connection setup timeout is 10 seconds; there is no fixed total transfer timeout.
- Stalled transfers fail below 1024 bytes/sec for 60 seconds.
- Kinescope API bearer credentials are not sent to video download URLs automatically; only explicit `FileTransferRequest` headers are used.
- Progress events are throttled by `VideoDownloader` at 10 MiB intervals.
- Downloads are written to a sibling `.part` file first, renamed only after the written byte count matches the transfer-reported byte count or, when absent, the selected asset size, and removed on handled transfer or validation failures.

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
