## 1. Regression Coverage

- [ ] 1.1 Add a unit test that reproduces issue #5: multiple downloadable assets have `height = null`, API order puts `original` first, and `QualityPreference::WORST` must select the smallest `fileSize`.
- [ ] 1.2 Add or update unit coverage showing `QualityPreference::BEST` still selects the highest known height when height metadata is present.
- [ ] 1.3 Add or update unit coverage for `WORST` tie behavior when file sizes are equal and height metadata is available.

## 2. Asset Selection Implementation

- [ ] 2.1 Extract or localize the downloader asset-selection comparison so `VideoDownloader` has an explicit rule for each `QualityPreference`.
- [ ] 2.2 Implement `WORST` selection as ascending `fileSize`, with height only as a secondary tie-breaker and missing height treated as unknown metadata.
- [ ] 2.3 Keep `BEST` height-first and preserve existing public method signatures, exceptions, and download/event workflow.
- [ ] 2.4 Ensure `downloadFolder()` continues to inherit the corrected selection rule through `downloadVideo()`.

## 3. Documentation And Validation

- [ ] 3.1 Update `CHANGELOG.md` with the corrected `QualityPreference::WORST` behavior.
- [ ] 3.2 Run `make openspec-validate`.
- [ ] 3.3 Run `make test-unit`.
- [ ] 3.4 Run `make lint-all`.
- [ ] 3.5 Run `make test-integration` when Kinescope credentials and downloader fixtures are available.
