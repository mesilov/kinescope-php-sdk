## Why

Kinescope asset payloads expose `assets[].file_size`, but live download checks show that this metadata value can describe the video stream size rather than the final downloadable MP4 file size on disk. The SDK currently exposes the value as `AssetDTO::$fileSize`, which encourages consumers to use it for download validation, disk checks, and storage accounting.

Issue #19 asks the SDK to make this semantic explicit: the metadata value must be named as stream metadata, while real file operations must rely on transfer-reported bytes, HTTP `Content-Length`, bytes written, or the final `filesize()`.

## What Changes

- Rename the SDK asset DTO property from `fileSize` to `videoStreamSize`.
- Continue accepting the raw Kinescope API input field `file_size`.
- Export SDK arrays and CLI asset summaries with `video_stream_size` and `video_stream_size_mb` instead of `file_size` and `file_size_mb`.
- Remove the public `fileSize` DTO property in this breaking `0.5.0` line.
- Update `AssetSelector` to use `videoStreamSize` only as stream metadata for quality ordering.
- Update `VideoDownloader` naming, logs, comments, and tests so transfer-reported/final bytes remain authoritative and stream metadata is only a fallback when no transfer-reported byte count exists.
- Update README and CHANGELOG migration notes.

## Impact

- Affected public SDK behavior: breaking rename `AssetDTO::$fileSize` -> `AssetDTO::$videoStreamSize`; `AssetDTO::toArray()` and CLI asset output rename `file_size` -> `video_stream_size`.
- Affected code: `AssetDTO`, asset selection, downloader metadata/log naming, CLI asset normalization, unit tests, OpenSpec specs, README, and CHANGELOG.
- Dependencies: none.
