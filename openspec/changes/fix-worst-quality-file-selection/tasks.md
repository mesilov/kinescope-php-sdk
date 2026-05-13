## 1. Regression Coverage

- [x] 1.1 Add a unit test that reproduces issue #5: multiple downloadable assets have `height = null`, API order puts `original` first, and `QualityPreference::WORST` must select the smallest `fileSize`.
- [x] 1.2 Add or update unit coverage showing `QualityPreference::BEST` still selects the highest known height when height metadata is present.
- [x] 1.3 Add or update unit coverage for `WORST` tie behavior when file sizes are equal and height metadata is available.

## 2. Asset Selection Implementation

- [x] 2.1 Extract or localize the downloader asset-selection comparison so `VideoDownloader` has an explicit rule for each `QualityPreference`.
- [x] 2.2 Implement `WORST` selection as ascending `fileSize`, with height only as a secondary tie-breaker and missing height treated as unknown metadata.
- [x] 2.3 Keep `BEST` height-first and preserve existing public method signatures, exceptions, and download/event workflow.
- [x] 2.4 Ensure `downloadFolder()` continues to inherit the corrected selection rule through `downloadVideo()`.

## 3. Documentation And Validation

- [x] 3.1 Update `CHANGELOG.md` with the corrected `QualityPreference::WORST` behavior.
- [x] 3.2 Run `make openspec-validate`.
- [x] 3.3 Run `make test-unit`.
- [x] 3.4 Run `make lint-all`.
- [x] 3.5 `make test-integration` (downloader sub-suite is opt-in via `TESTS_VIDEO_DOWNLOADER_ENABLED=1` / `make test-integration-download`). Asset selection covered by `AssetSelectorTest` unit suite and `VideoDownloaderQualitySelectionTest`; live API payload verified against `https://api.kinescope.io/v1/videos/{id}` on 2026-05-13 — see issue #11 for the surfaced metadata gap.
