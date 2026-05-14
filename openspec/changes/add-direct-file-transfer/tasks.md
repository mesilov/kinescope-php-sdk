## 1. Transfer Contract

- [ ] 1.1 Add `FileTransferInterface` under the video download area with a `transfer(FileTransferRequest $request, ?callable $onProgress = null): FileTransferResult` contract.
- [ ] 1.2 Add immutable `FileTransferRequest`, `FileTransferProgress`, and `FileTransferResult` value objects with typed fields for URL, output path, expected/reported bytes, and progress helpers.
- [ ] 1.3 Add unit coverage for transfer value-object construction, nullable totals, and percent calculation behavior.

## 2. Default Direct-to-File Transfer

- [ ] 2.1 Implement a default `CurlFileTransfer` that writes response chunks directly to the provided output path without materializing a PSR-18 response body.
- [ ] 2.2 Apply the default cURL preset: GET, HTTP/HTTPS only, follow up to 5 redirects, TLS peer/host verification, `2xx` success statuses only, 10-second connect timeout, no fixed total transfer timeout, and low-speed failure below 1024 bytes/s for 60 seconds.
- [ ] 2.3 Ensure `CurlFileTransfer` validates HTTP failures, cURL failures, local file-open failures, and short writes as `KinescopeException` failures.
- [ ] 2.4 Ensure `CurlFileTransfer` reports progress from bytes actually written to disk.
- [ ] 2.5 Ensure `CurlFileTransfer` does not add Kinescope API bearer credentials to download-link requests.
- [ ] 2.6 Add unit tests proving the default transfer writes the expected file content, reports progress, fails on transport/write errors, applies the cURL preset, and does not depend on a PSR-18 response body.

## 3. VideoDownloader Integration

- [ ] 3.1 Update `VideoDownloader` constructor to remove PSR-18 `ClientInterface` and PSR-17 `RequestFactoryInterface` dependencies.
- [ ] 3.2 Define the new constructor order as `Videos`, `Filesystem`, `FileTransferInterface`, `EventDispatcherInterface`, `AssetSelector`, `LoggerInterface`, with `LoggerInterface` last.
- [ ] 3.3 Wire `VideoDownloader::downloadVideo()` so metadata lookup, asset selection, started/completed/failed events, and 10 MiB progress throttling stay in `VideoDownloader`, while byte transfer is delegated to the transfer implementation.
- [ ] 3.4 Ensure `downloadFolder()` continues to reuse `downloadVideo()` and therefore the same injected transfer implementation.
- [ ] 3.5 Add unit tests proving custom transfer injection receives the selected asset URL, temporary `.part` output path, expected size, and propagates original failures through `DownloadFailedEvent`.
- [ ] 3.6 Add unit tests proving existing lifecycle events remain compatible when the default transfer path is used.
- [ ] 3.7 Add unit tests proving `VideoDownloader` rejects a successful transfer result when `bytesWritten` differs from the selected asset `fileSize`, including for a custom transfer implementation.
- [ ] 3.8 Implement `.part` handling in `VideoDownloader`: pass a `.part` output path to transfer, rename it to the final destination only after size validation succeeds, and delete it on handled transfer or validation failures.
- [ ] 3.9 Add unit tests proving `.part` is renamed on success, deleted on handled failure, and the final destination is not reported complete after failure.

## 4. Documentation And Migration Notes

- [ ] 4.1 Update README downloader examples to show the new constructor, default direct-to-file construction path, and custom transfer injection.
- [ ] 4.2 Add a CHANGELOG breaking-change entry explaining the removed PSR-18/PSR-17 constructor dependencies, the new constructor order, and custom transfer injection.
- [ ] 4.3 Document that Symfony HttpClient remains optional and can be used by applications through a custom `FileTransferInterface` implementation with `buffer=false` and `stream()`.
- [ ] 4.4 Document the default cURL transfer policy, API credential/header behavior, 10 MiB progress interval, and `.part` cleanup behavior.

## 5. Verification

- [ ] 5.1 Run `make openspec-validate`.
- [ ] 5.2 Run `make test-unit`.
- [ ] 5.3 Run `make lint-all`.
- [ ] 5.4 Add and run the mandatory synthetic disk-footprint test comparing a test-only buffered baseline strategy with the default direct transfer strategy.
- [ ] 5.5 Run downloader integration tests when Kinescope credentials and fixtures are available, or document why they were skipped.
