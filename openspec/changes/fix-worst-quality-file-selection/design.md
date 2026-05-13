## Context

`VideoDownloader::downloadVideo()` filters video assets to those with a non-null `downloadLink`, sorts the filtered assets, and downloads the first item. The current sort uses `height ?? 0` for both `BEST` and `WORST`. Issue #5 shows that current Kinescope API responses often return downloadable assets with `height = null` for all variants, including `original`, `1080p`, `720p`, and `360p`. When that happens, `WORST` no longer means lower footprint and can select `original` based on API order.

`AssetDTO::fileSize` is required and validated as a positive integer, so it is available for every parsed downloadable asset. For consumers that choose `WORST`, file size is the direct signal for minimizing network, disk, and downstream media-processing cost.

## Goals / Non-Goals

**Goals:**

- Make `QualityPreference::WORST` select the smallest downloadable file by `fileSize`.
- Keep `QualityPreference::BEST` compatible with the existing highest-height intent.
- Avoid treating missing height as a lower quality signal.
- Keep `downloadVideo()` and `downloadFolder()` signatures unchanged.
- Add focused unit tests that reproduce the API-order failure from issue #5.

**Non-Goals:**

- Do not add a new `QualityPreference` enum value.
- Do not change how video metadata is fetched.
- Do not change download streaming, file naming, progress events, or folder pagination behavior.
- Do not require live Kinescope API credentials for regression coverage.

## Decisions

- Select `WORST` by ascending `fileSize`.
  - Rationale: `fileSize` is required by `AssetDTO` and directly measures the resource footprint that `WORST` users are trying to minimize.
  - Alternative considered: continue sorting by `height` while treating `null` as a large value. Rejected because it still fails when all heights are unknown and file sizes differ.

- Keep `BEST` height-first.
  - Rationale: existing consumers expect `BEST` to mean highest available resolution when resolution metadata exists.
  - Alternative considered: use largest `fileSize` for `BEST`. Rejected because size is only an indirect proxy for visual quality and may vary by codec/bitrate.

- Use height only as secondary ordering for `WORST` when file sizes are equal.
  - Rationale: equal file sizes are not the issue described in #5, but a deterministic secondary comparison is useful when assets have complete metadata.
  - Alternative considered: ignore height completely for `WORST`. Rejected because it would make equal-sized variants depend more heavily on API order.

- Treat missing height as unknown rather than as `0`.
  - Rationale: `null` means the API did not provide resolution metadata; it must not make `original` or any other unknown-height asset look like the lowest-resolution asset.
  - Alternative considered: preserve `height ?? 0`. Rejected because this is the root cause of the incorrect `WORST` selection.

- Cover the behavior with unit tests using fake video payloads and mock download responses.
  - Rationale: the issue is deterministic from asset metadata and API order, so live integration tests are unnecessary for regression coverage.
  - Alternative considered: add only integration coverage. Rejected because it would require credentials and would be harder to reproduce reliably.

## Risks / Trade-offs

- Consumers relying on the accidental API-order behavior for `WORST` may download a different asset after the fix. Mitigation: document the corrected behavior in `CHANGELOG.md`.
- File size is not a semantic quality label. Mitigation: scope the rule to `WORST`, where the practical intent is minimizing footprint, and keep `BEST` height-first.
- Assets with identical file sizes and missing heights may still require a final deterministic fallback. Mitigation: implementation should preserve a stable final tie-breaker such as original array order, asset quality, or asset id without changing the primary `fileSize` rule.
