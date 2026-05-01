## Why

`QualityPreference::WORST` in `VideoDownloader` currently treats a missing asset height as `0`, so Kinescope responses with `height = null` can make the SDK select `original` instead of the smallest downloadable file. This defeats the practical purpose of `WORST` for consumers that use it to reduce download, disk, and processing footprint.

## What Changes

- Change `QualityPreference::WORST` selection to prefer the downloadable asset with the smallest `fileSize`.
- Keep `QualityPreference::BEST` height-oriented so it continues to prefer the highest-resolution downloadable asset when resolution metadata exists.
- Treat missing height as unknown selection metadata rather than a reason to prefer an asset for `WORST`.
- Add regression coverage for multiple downloadable assets whose heights are missing and whose API order places `original` first.
- Do not add a new quality enum value in this change.
- Do not change download transport, file writing, progress events, or folder iteration behavior.

## Capabilities

### New Capabilities
- `video-download-quality-selection`: Defines how `VideoDownloader` chooses a downloadable asset for `BEST` and `WORST` quality preferences.

### Modified Capabilities
- None.

## Impact

- Affected SDK behavior: `Kinescope\Services\Videos\VideoDownloader::downloadVideo()` asset selection for `QualityPreference::WORST`.
- Affected tests: unit coverage around downloader asset selection and event metadata.
- Public API surface remains backwards-compatible: existing enum values and method signatures stay unchanged.
- Documentation/changelog should mention the corrected `WORST` behavior because it changes which file can be downloaded for the same input video.
