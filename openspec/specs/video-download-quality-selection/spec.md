# video-download-quality-selection Specification

## Purpose
Defines how the SDK selects downloadable video assets for quality preferences, including size-first `WORST` selection, height-first `BEST` selection, and handling of missing resolution metadata.

## Requirements
### Requirement: Select downloadable assets by requested quality preference
The SDK SHALL select a downloadable video asset according to the requested `QualityPreference` after excluding assets without a `downloadLink`.

#### Scenario: BEST prefers highest known height
- **WHEN** a video has multiple downloadable assets with known heights
- **THEN** `VideoDownloader` selects the downloadable asset with the greatest height for `QualityPreference::BEST`

#### Scenario: WORST prefers smallest file
- **WHEN** a video has multiple downloadable assets with different `fileSize` values
- **THEN** `VideoDownloader` selects the downloadable asset with the smallest `fileSize` for `QualityPreference::WORST`

#### Scenario: WORST does not prefer original when heights are missing
- **WHEN** a video has multiple downloadable assets whose heights are missing and the first downloadable asset is `original` with a larger `fileSize`
- **THEN** `VideoDownloader` selects the downloadable asset with the smallest `fileSize` for `QualityPreference::WORST`

#### Scenario: Assets without download links are ignored
- **WHEN** a video has assets with and without `downloadLink`
- **THEN** `VideoDownloader` selects only from assets that include a `downloadLink`

#### Scenario: No downloadable asset exists
- **WHEN** a video has no asset with a `downloadLink`
- **THEN** `VideoDownloader` fails with a `KinescopeException`

### Requirement: Treat missing height as unknown metadata
The SDK SHALL NOT interpret a missing asset height as resolution `0` for quality selection.

#### Scenario: WORST uses height only after file size
- **WHEN** multiple downloadable assets have the same `fileSize` and known heights
- **THEN** `VideoDownloader` may use the lower known height as a tie-breaker for `QualityPreference::WORST`

#### Scenario: Missing height is not automatically lowest quality
- **WHEN** a downloadable asset has missing height and another downloadable asset has known height
- **THEN** the missing height alone does not make the unknown-height asset the preferred `QualityPreference::WORST` selection

### Requirement: Preserve download workflow compatibility
The SDK SHALL keep the existing public downloader API and event workflow while correcting asset selection.

#### Scenario: downloadVideo signature remains unchanged
- **WHEN** a consumer calls `downloadVideo(string $videoId, string $destinationDir, QualityPreference $quality)`
- **THEN** the call remains source-compatible with the current public method signature

#### Scenario: downloadFolder uses the same quality selection
- **WHEN** a consumer calls `downloadFolder()` with `QualityPreference::WORST`
- **THEN** each video download uses the corrected `WORST` asset selection rule

#### Scenario: Selected asset metadata is still emitted
- **WHEN** `VideoDownloader` dispatches the download-started event
- **THEN** the event reflects the selected asset URL, selected file size, requested quality preference, and selected height when available
