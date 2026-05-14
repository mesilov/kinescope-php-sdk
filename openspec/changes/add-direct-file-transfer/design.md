## Context

`VideoDownloader::downloadVideo()` currently performs three responsibilities in one method:

1. Fetch video metadata through `Videos::get()`.
2. Select a downloadable asset according to `QualityPreference`.
3. Request the selected `downloadLink` through PSR-18 and copy the returned PSR-7 body into the target file.

Issue #4 showed that the third responsibility is a poor fit for large video files. PSR-18 standardizes request/response exchange, but it does not standardize a sink-style download operation. With Symfony HttpClient discovered behind PSR-18, the response body was observed being buffered through `php://temp`, creating a temporary file roughly the same size as the final video.

The application-side hotfix in `summary` proved the desired transfer behavior by keeping SDK metadata and asset selection, then writing the file with Symfony HttpClient using `buffer=false` and `stream()`. That workaround duplicated the downloader orchestration in the application. The SDK should instead expose a narrow byte-transfer boundary so applications can replace the transfer strategy without forking `VideoDownloader`.

Current responsibility split:

```text
VideoDownloader
  -> Videos::get()
  -> AssetSelector::select()
  -> PSR-18 sendRequest()
  -> PSR-7 body read loop
  -> download lifecycle events
```

Target responsibility split:

```text
VideoDownloader
  -> Videos::get()
  -> AssetSelector::select()
  -> FileTransferInterface::transfer()
  -> download lifecycle events

FileTransferInterface implementations
  -> CurlFileTransfer              default direct-to-file path
  -> Custom application transfer   e.g. Symfony HttpClient buffer=false + stream()
```

## Goals / Non-Goals

**Goals:**

- Separate `VideoDownloader` orchestration from file-byte transfer.
- Provide a direct-to-file default transfer that avoids same-sized PSR-18 response-body buffering.
- Keep `downloadVideo()` and `downloadFolder()` method signatures source-compatible.
- Simplify `VideoDownloader` construction around downloader responsibilities instead of preserving PSR-18 constructor compatibility.
- Keep lifecycle events and progress reporting owned by `VideoDownloader`.
- Allow applications to inject their own transfer implementation without duplicating metadata lookup, asset selection, folder pagination, or event dispatching.
- Keep Symfony HttpClient optional rather than a required SDK dependency.

**Non-Goals:**

- Do not change Kinescope API metadata requests.
- Do not change quality-selection rules.
- Do not add resumable or ranged downloads.
- Do not add parallel folder downloads.
- Do not require all PSR-18 clients to behave as streaming file sinks.
- Do not preserve the existing `VideoDownloader` constructor signature; this library is still in active development and the cleaner constructor is preferred.
- Do not ship a PSR-7 response-body copy implementation as a production transfer strategy in this change.

## Decisions

- Introduce a video-download-specific transfer contract.
  - Shape:
    ```php
    namespace Kinescope\Services\Videos\Download;

    interface FileTransferInterface
    {
        /**
         * @param (callable(FileTransferProgress): void)|null $onProgress
         */
        public function transfer(
            FileTransferRequest $request,
            ?callable $onProgress = null,
        ): FileTransferResult;
    }
    ```
  - Supporting value objects:
    - `FileTransferRequest`: `url`, `outputPath`, `expectedBytes`, and optional request headers.
    - `FileTransferProgress`: `bytesWritten`, `totalBytes`, and a nullable `percent()` helper.
    - `FileTransferResult`: `filePath`, `bytesWritten`, and optional `reportedBytes`.
  - Rationale: the contract describes the SDK need directly: transfer one selected URL into one target file and report bytes written. It avoids exposing a general HTTP abstraction that competes with PSR-18.
  - Alternative considered: inject a PSR-18 client with custom options. Rejected because PSR-18 has no portable request option for direct sinks or `buffer=false`.

- Keep `VideoDownloader` as the owner of metadata lookup, asset selection, and lifecycle events.
  - Rationale: these are SDK domain concerns and should not be reimplemented by each application that needs streaming transfer.
  - Alternative considered: expose only the selected asset and let applications download it. Rejected because it preserves duplication and makes progress/error event compatibility an application burden.

- Make `CurlFileTransfer` the default direct-to-file implementation.
  - Rationale: `ext-curl` is already a required extension, so the SDK can provide direct-to-file behavior without adding a new hard dependency. A cURL write callback can write chunks to the destination file without first materializing a PSR-7 response body.
  - Alternative considered: make Symfony HttpClient the default. Rejected because `symfony/http-client` is currently only suggested and should not become mandatory for the SDK downloader.
  - Alternative considered: keep PSR-18 as default and document custom transfer injection. Rejected because issue #4 asks the SDK to avoid the buffering path for large-file consumers by default.

- Use a fixed reasonable cURL preset for the default transfer.
  - The default preset should:
    - use `GET`;
    - accept only HTTP and HTTPS protocols, including redirects;
    - follow redirects with `maxRedirects = 5`;
    - enable TLS peer and host verification for HTTPS;
    - treat only `2xx` HTTP statuses as successful response statuses;
    - use `connectTimeout = 10s`;
    - avoid a fixed total transfer timeout for large videos;
    - fail stalled transfers with `lowSpeedLimit = 1024 bytes/s` and `lowSpeedTime = 60s`.
  - Rationale: Kinescope download links are large CDN-style resources where redirects are expected, long total transfer time is normal, but stalled connections should fail instead of hanging forever.
  - Alternative considered: make all cURL options user-configurable in the first change. Rejected because a stable default preset is enough for the SDK downloader; custom policies can be implemented through `FileTransferInterface`.

- Keep download-link requests free of SDK API credentials by default.
  - `VideoDownloader` and `CurlFileTransfer` should not copy the Kinescope API bearer token from metadata clients onto selected `downloadLink` requests.
  - `FileTransferRequest::headers` should contain only headers explicitly supplied for the transfer request.
  - Rationale: selected download links may point at CDN hosts rather than the Kinescope API host, so API credentials must not leak across domains.
  - Alternative considered: reuse metadata `ApiClient` headers. Rejected because CDN file transfer is a different trust boundary than JSON API metadata calls.

- Keep Symfony support as an injection use case, not a required dependency.
  - Rationale: Symfony applications can implement or use a small adapter around `Symfony\Contracts\HttpClient\HttpClientInterface` that calls `request('GET', $url, ['buffer' => false])` and streams chunks to the file. The core SDK contract should be sufficient for that adapter without coupling the SDK to Symfony HttpClient.
  - Alternative considered: ship a Symfony-specific transfer class in the core package. Rejected for this change because it would require new dependency and static-analysis decisions that are not necessary to solve the SDK boundary.

- Make the `VideoDownloader` constructor transfer-oriented and intentionally breaking.
  - `VideoDownloader` should no longer accept PSR-18 `ClientInterface` or PSR-17 `RequestFactoryInterface` dependencies.
  - Preferred shape:
    ```php
    public function __construct(
        private Videos $videos,
        private Filesystem $filesystem = new Filesystem(),
        private FileTransferInterface $fileTransfer = new CurlFileTransfer(),
        private EventDispatcherInterface $eventDispatcher = new EventDispatcher(),
        private AssetSelector $assetSelector = new AssetSelector(),
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }
    ```
  - `LoggerInterface` is intentionally last because it is operational instrumentation rather than a core downloader dependency.
  - Rationale: if the default byte-transfer path no longer uses PSR-18, keeping PSR-18 dependencies in `VideoDownloader` creates a misleading API. PSR-18 remains appropriate in `ApiClient` / `Videos` for JSON metadata requests, not for large video byte transfer.
  - Alternative considered: keep the existing positional constructor and add an optional `FileTransferInterface` at the end. Rejected because new callers would still see and pass irrelevant PSR dependencies.
  - Alternative considered: introduce a separate `StreamingVideoDownloader`. Rejected because it would leave two public downloader orchestration classes with duplicated event and folder behavior.

- Let `VideoDownloader` translate transfer progress into existing download events.
  - Rationale: the transfer layer should only report bytes. It should not know about video IDs, quality preferences, selected heights, or event-dispatching policy.
  - Alternative considered: make transfer implementations dispatch `DownloadProgressEvent` directly. Rejected because that couples low-level transport to SDK video semantics.

- Throttle download progress events at 10 MiB.
  - `VideoDownloader` should emit `DownloadProgressEvent` only when written bytes cross 10 MiB (`10 * 1024 * 1024`) boundaries.
  - Rationale: cURL chunks can be much smaller than useful application progress intervals. A 10 MiB interval matches the production hotfix behavior and keeps Temporal/application heartbeats meaningful without flooding listeners.
  - Alternative considered: emit one progress event per transfer chunk. Rejected because it makes listener volume depend on transport internals.

- Make `VideoDownloader` the owner of completed-size validation.
  - `AssetDTO::fileSize` is required for selected assets, so `VideoDownloader` should pass that value as `FileTransferRequest::expectedBytes`.
  - `VideoDownloader` should compare `FileTransferResult::bytesWritten` with the selected asset file size before dispatching `DownloadCompletedEvent`.
  - Transfer implementations should still detect low-level failures such as HTTP errors, cURL errors, short writes, and local file-open failures.
  - Rationale: the invariant "the downloaded file matches the selected asset size" belongs to the downloader workflow and must apply equally to default, Symfony, custom, and test transfer implementations.
  - Alternative considered: make each `FileTransferInterface` implementation validate expected bytes itself. Rejected because custom transfers could accidentally bypass the invariant and return success for partial files.

- Commit completed files through a `.part` sibling path.
  - `VideoDownloader` should compute a temporary sibling path using a `.part` suffix, pass that path as `FileTransferRequest::outputPath`, validate `FileTransferResult::bytesWritten`, and only then rename the `.part` file to the final destination path.
  - On handled transfer or validation failure, `VideoDownloader` should delete the `.part` file.
  - A fatal process termination may still leave a `.part` file behind; that file is a cleanup candidate and must not be treated as a completed download.
  - Rationale: a failed download should not masquerade as a completed target file.
  - Alternative considered: keep writing directly to final path. Rejected because it makes partial files harder to distinguish after transfer failures.

- Require a synthetic disk-footprint regression test.
  - The test suite should compare the default direct transfer path with a test-only buffered baseline that simulates the old PSR-18 response-body behavior.
  - The buffered baseline should demonstrate an extra same-sized temporary response-body footprint in addition to the output file.
  - The direct transfer path should demonstrate only one in-progress copy (`.part`) and no separate same-sized response-body temp file.
  - Rationale: issue #4 is specifically about transient disk footprint, so the change needs a deterministic regression test for that behavior without requiring live Kinescope credentials.

## Risks / Trade-offs

- **Breaking constructor change.** Existing callers that manually instantiate `VideoDownloader` must update construction code. Mitigation: the library is still in active development; document before/after examples in README and CHANGELOG.

- **Consumers that configured a PSR-18 client specifically for CDN downloads need a custom transfer.** Mitigation: document `FileTransferInterface` injection and keep PSR-18 usage scoped to metadata clients.

- **cURL behavior is less framework-integrated than Symfony HttpClient.** Consumers may want existing retry/proxy/tracing behavior from their framework HTTP client. Mitigation: the transfer contract is intentionally narrow, so framework-specific adapters are small and application-owned.

- **Atomic rename can leave a temporary sibling file after fatal process termination.** Mitigation: use a predictable suffix and document that failed/interrupted transfers may leave cleanup candidates; runtime cleanup is outside this change.

- **Exact disk-proof integration is environment-sensitive.** Unit tests can prove the SDK no longer invokes the PSR-18 response-body path and writes chunks through the transfer contract. A live disk-footprint probe remains an optional integration/manual verification step when credentials and fixtures are available.
