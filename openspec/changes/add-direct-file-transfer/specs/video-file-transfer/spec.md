## ADDED Requirements

### Requirement: Transfer selected video files through a dedicated file-transfer boundary
The SDK SHALL separate video metadata and asset selection from the transfer of selected video bytes into a local file.

#### Scenario: VideoDownloader delegates byte transfer after asset selection
- **WHEN** a consumer calls `VideoDownloader::downloadVideo()` for a video with a downloadable selected asset
- **THEN** `VideoDownloader` uses the selected asset `downloadLink` to create a file-transfer request for a temporary `.part` output path

#### Scenario: Video metadata remains SDK-owned
- **WHEN** a custom file-transfer implementation is injected
- **THEN** the SDK still fetches video metadata through `Videos::get()` and selects the downloadable asset before invoking the transfer implementation

#### Scenario: Folder downloads reuse the same transfer boundary
- **WHEN** a consumer calls `VideoDownloader::downloadFolder()`
- **THEN** each video in the folder is downloaded through the same file-transfer boundary used by `downloadVideo()`

### Requirement: Provide a direct-to-file default transfer
The SDK SHALL provide a default file-transfer implementation that writes video bytes directly to the target file path without relying on a PSR-18 response body as the byte source.

#### Scenario: Default downloader avoids PSR-18 response-body transfer
- **WHEN** a consumer constructs `VideoDownloader` without a custom file-transfer implementation
- **THEN** video file bytes are transferred by the SDK default direct-to-file transfer implementation

#### Scenario: Direct transfer download writes to the requested destination
- **WHEN** a download using the default transfer completes successfully
- **THEN** the requested destination path contains the downloaded video bytes

#### Scenario: Direct transfer reports bytes written
- **WHEN** the default transfer writes video bytes to disk
- **THEN** it reports progress using the number of bytes written to the destination transfer

### Requirement: Use a fixed default cURL transfer policy
The SDK default direct-to-file transfer SHALL use a fixed cURL policy suitable for large Kinescope video downloads.

#### Scenario: Default transfer follows bounded HTTP redirects
- **WHEN** the default transfer receives an HTTP redirect
- **THEN** it follows HTTP or HTTPS redirects up to 5 redirects

#### Scenario: Default transfer verifies TLS
- **WHEN** the default transfer downloads from an HTTPS URL
- **THEN** it verifies the TLS peer and host

#### Scenario: Default transfer accepts successful HTTP statuses
- **WHEN** the default transfer receives the final HTTP response
- **THEN** it treats only `2xx` response statuses as successful

#### Scenario: Default transfer times out connection setup
- **WHEN** the default transfer cannot establish a connection within 10 seconds
- **THEN** it fails the transfer with a `KinescopeException`

#### Scenario: Default transfer detects stalled connections
- **WHEN** transfer speed remains below 1024 bytes per second for 60 seconds
- **THEN** the default transfer fails with a `KinescopeException`

#### Scenario: Default transfer has no fixed total timeout
- **WHEN** a large video download continues making progress
- **THEN** the default transfer does not fail solely because a fixed total transfer timeout elapsed

### Requirement: Protect API credentials during file transfer
The SDK SHALL NOT automatically send Kinescope API credentials to selected video download URLs.

#### Scenario: Default transfer omits API bearer token
- **WHEN** `VideoDownloader` transfers a selected asset download URL
- **THEN** the SDK does not add the Kinescope API bearer token to the transfer request headers

#### Scenario: Transfer headers are explicit
- **WHEN** a transfer request includes headers
- **THEN** those headers are only the headers explicitly supplied for that transfer request

### Requirement: Allow custom file-transfer injection
The SDK SHALL allow consumers to inject a custom file-transfer implementation for video downloads.

#### Scenario: Consumer injects a framework streaming transfer
- **WHEN** a consumer injects a custom transfer implementation that streams with a framework HTTP client
- **THEN** `VideoDownloader` uses that implementation for the selected video file bytes instead of its default transfer

#### Scenario: Custom transfer receives selected asset metadata
- **WHEN** `VideoDownloader` invokes a custom transfer implementation
- **THEN** the transfer request includes the selected asset download URL, temporary output path, and selected asset file size as the expected byte count

#### Scenario: Transfer exceptions propagate through the downloader workflow
- **WHEN** a custom transfer implementation fails with an exception
- **THEN** `VideoDownloader` dispatches the download-failed event and propagates the original exception

### Requirement: Construct VideoDownloader from downloader dependencies
The SDK SHALL construct `VideoDownloader` from video metadata access, filesystem access, file transfer, event dispatching, asset selection, and logging dependencies without requiring PSR-18 or PSR-17 dependencies.

#### Scenario: New constructor does not require PSR HTTP dependencies
- **WHEN** a consumer constructs `VideoDownloader`
- **THEN** the constructor does not require a PSR-18 `ClientInterface` or PSR-17 `RequestFactoryInterface`

#### Scenario: Default construction uses direct transfer
- **WHEN** a consumer constructs `VideoDownloader` with only the required `Videos` dependency
- **THEN** the downloader uses the SDK default direct-to-file transfer implementation

#### Scenario: Logger is the final constructor dependency
- **WHEN** the `VideoDownloader` constructor exposes optional dependencies
- **THEN** `LoggerInterface` is the final optional dependency after filesystem, transfer, event dispatcher, and asset selector dependencies

### Requirement: Preserve public downloader method compatibility
The SDK SHALL preserve the public `downloadVideo()` and `downloadFolder()` method signatures while introducing the file-transfer boundary.

#### Scenario: Existing downloadVideo callers remain source-compatible
- **WHEN** existing code calls `downloadVideo(string $videoId, string $destinationDir, QualityPreference $quality)`
- **THEN** the method call remains valid and returns the downloaded file path on success

#### Scenario: Existing downloadFolder callers remain source-compatible
- **WHEN** existing code calls `downloadFolder(string $folderId, string $destinationDir, QualityPreference $quality)`
- **THEN** the method call remains valid and returns downloaded file paths on success

#### Scenario: Existing lifecycle event subscriptions remain source-compatible
- **WHEN** existing code subscribes to download started, progress, completed, or failed events through `VideoDownloader::on()`
- **THEN** those subscriptions still receive the corresponding lifecycle events during downloads

### Requirement: Map transfer progress to download lifecycle events
The SDK SHALL translate file-transfer progress into the existing download progress event workflow.

#### Scenario: Transfer progress emits download progress
- **WHEN** the transfer implementation reports that written bytes crossed the next 10 MiB progress boundary
- **THEN** `VideoDownloader` dispatches a download-progress event with the video ID, target file path, bytes written, total size, and percent when calculable

#### Scenario: Chunk size does not define event frequency
- **WHEN** the transfer implementation reports multiple chunks within the same 10 MiB progress interval
- **THEN** `VideoDownloader` does not dispatch a separate download-progress event for every chunk

#### Scenario: Download started event uses selected asset details
- **WHEN** `VideoDownloader` starts transferring a selected asset
- **THEN** it dispatches the download-started event with the selected download URL, selected file size, requested quality preference, and selected height when available

#### Scenario: Download completed event uses completed transfer result
- **WHEN** the transfer implementation completes successfully
- **THEN** `VideoDownloader` dispatches the download-completed event with the final file path and completed file size

### Requirement: Validate completed transfers
The SDK SHALL validate completed video file transfers in `VideoDownloader` against the selected asset file size before reporting download completion.

#### Scenario: Transfer request includes selected asset size
- **WHEN** `VideoDownloader` creates a file-transfer request for a selected asset
- **THEN** it sets the request expected byte count to the selected asset `fileSize`

#### Scenario: Successful transfer matches selected asset size
- **WHEN** the transfer result reports exactly the selected asset `fileSize` as written bytes
- **THEN** `VideoDownloader` renames the `.part` file to the final destination path and completes successfully

#### Scenario: Incomplete transfer fails
- **WHEN** the transfer result reports fewer or more written bytes than the selected asset `fileSize`
- **THEN** the download fails with a `KinescopeException`

#### Scenario: Custom transfer cannot bypass size validation
- **WHEN** a custom transfer implementation returns a successful result with a written byte count that differs from the selected asset `fileSize`
- **THEN** `VideoDownloader` fails the download with a `KinescopeException`

#### Scenario: Failed transfer does not report completion
- **WHEN** a transfer fails before completion validation succeeds
- **THEN** `VideoDownloader` does not dispatch the download-completed event

### Requirement: Keep failed transfers out of the final destination
The SDK SHALL write in-progress downloads to a `.part` sibling file and SHALL NOT leave handled failures at the final destination path.

#### Scenario: Transfer writes to part file first
- **WHEN** `VideoDownloader` starts transferring a selected asset
- **THEN** it passes a `.part` sibling path as the transfer output path

#### Scenario: Successful validation commits the final file
- **WHEN** transfer succeeds and completed-size validation succeeds
- **THEN** `VideoDownloader` renames the `.part` file to the final destination path

#### Scenario: Handled failure deletes part file
- **WHEN** transfer fails or completed-size validation fails with a handled exception
- **THEN** `VideoDownloader` deletes the `.part` file

#### Scenario: Fatal termination may leave cleanup candidate
- **WHEN** the process terminates fatally during transfer
- **THEN** a `.part` file may remain and is not considered a completed download

### Requirement: Keep Symfony HttpClient optional
The SDK SHALL NOT require Symfony HttpClient as a production dependency to provide direct-to-file video downloads.

#### Scenario: Core direct transfer works without Symfony HttpClient
- **WHEN** a consumer installs the SDK without `symfony/http-client`
- **THEN** the default direct-to-file video transfer remains available

#### Scenario: Symfony applications can provide their own transfer
- **WHEN** a Symfony application wants to use `HttpClientInterface::request()` with `buffer=false` and `stream()`
- **THEN** it can do so by injecting a custom implementation of the SDK file-transfer contract

### Requirement: Prove direct transfer disk footprint
The SDK SHALL include a synthetic regression test that demonstrates the disk-footprint difference between the direct transfer path and a buffered baseline.

#### Scenario: Buffered baseline has extra response-body footprint
- **WHEN** the synthetic test runs the buffered baseline strategy against a fixed-size payload
- **THEN** the measured non-output temporary response-body bytes are comparable to the payload size

#### Scenario: Direct transfer avoids extra response-body footprint
- **WHEN** the synthetic test runs the default direct transfer strategy against the same fixed-size payload
- **THEN** the measured footprint includes only the in-progress `.part` file and no separate same-sized response-body temp file
