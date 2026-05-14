## Why

Large video downloads should not require a temporary PSR-18 response body that is comparable in size to the final artifact. Issue #4 showed that the current `VideoDownloader` path can create a near-2x transient disk footprint when the discovered PSR-18 backend buffers the response body before the SDK copies it to the target file.

## What Changes

- Add a narrow file-transfer abstraction for moving an already selected download URL to a destination file.
- Keep `VideoDownloader` responsible for video metadata lookup, asset selection, lifecycle events, and folder orchestration.
- Make the default downloader path use direct-to-file transfer without materializing a same-sized PSR-7 response body.
- Allow applications to inject an alternate transfer implementation, such as a Symfony HttpClient implementation using `buffer=false` and `stream()`.
- **BREAKING** Simplify `VideoDownloader` construction by removing PSR-18 `ClientInterface` and PSR-17 `RequestFactoryInterface` dependencies from its public constructor.
- Commit downloads through a `.part` sibling file so failed transfers do not leave the final destination looking complete.
- Preserve existing `downloadVideo()` and `downloadFolder()` method behavior while changing the internal byte-transfer boundary.

Non-goals:
- Do not change Kinescope metadata API calls or `Videos::get()`.
- Do not change quality-selection semantics; those remain covered by the asset-selection change.
- Do not make Symfony HttpClient a required production dependency.
- Do not add resumable/range downloads in this change.
- Do not ship a PSR-7 response-body transfer as a production strategy.

## Capabilities

### New Capabilities
- `video-file-transfer`: Direct-to-file video download transfer, transfer strategy injection, progress reporting, completion validation, and compatibility with the current `VideoDownloader` workflow.

### Modified Capabilities
- None.

## Impact

- Affected SDK code: `Kinescope\Services\Videos\VideoDownloader`, new transfer contract/value objects under the video download area, and one or more transfer implementations.
- Affected public API: `VideoDownloader` constructor changes intentionally; existing `downloadVideo()` and `downloadFolder()` method signatures remain source-compatible.
- Dependencies: default implementation should rely on existing required PHP/runtime capabilities, preferably `ext-curl`; Symfony HttpClient support, if added, remains optional.
- Tests: focused unit tests for the transfer contract integration, direct-to-file behavior, progress propagation, error handling, compatibility with the existing event workflow, and a mandatory synthetic disk-footprint comparison between a buffered baseline and the direct transfer path; integration verification when credentials and fixtures are available.
- Docs: README/CHANGELOG updates for the new direct-to-file downloader behavior and custom transfer injection.
