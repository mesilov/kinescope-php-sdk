## Context

`AssetDTO::fromArray()` reads `width` and `height` only as standalone numeric keys. A live verification against `https://api.kinescope.io/v1/videos/{id}` on 2026-05-13 with two test fixtures (single video + folder listing, 5 videos × 5 assets each) showed:

- `file_size` is present and positive for every asset.
- `width` and `height` are not present in any asset payload.
- `resolution` is present for every asset (including `original`) as a `"<width>x<height>"` string.

The currently shipped `fix-worst-quality-file-selection` change fixes `WORST` by sorting on `fileSize`, but `BEST` still depends on `height`, which is always `null` in practice. Public DTO accessors that depend on height (`isHd()`, `isFullHd()`, `is4K()`, `getResolution()`, `getAspectRatio()`) likewise return `null`/`false` for every real asset.

## Goals / Non-Goals

**Goals:**

- Populate `AssetDTO::$width` and `AssetDTO::$height` from the API `resolution` field when separate numeric fields are absent.
- Keep numeric `width`/`height` as authoritative when the API does provide them.
- Treat malformed `resolution` strings as missing metadata silently.
- Cover the parsing rules with focused unit tests.
- Keep `AssetDTO` constructor signature, property set, and `toArray()` shape unchanged.

**Non-Goals:**

- Do not add a new `$resolution` property on `AssetDTO`.
- Do not change `VideoDownloader` selection logic. `BEST` will benefit indirectly because `height` becomes populated, but no rule changes.
- Do not change other DTOs (`VideoDTO`, `SubtitleDTO`, etc.).
- Do not require live Kinescope credentials for regression coverage.
- Do not perform automatic migration of stored `AssetDTO::toArray()` snapshots.

## Decisions

- Parse `resolution` strictly as `^(\d+)x(\d+)$`.
  - Rationale: matches the observed API payload (`"1920x1080"`, `"852x480"`, etc.) and rejects junk without ambiguity.
  - Alternative considered: split on `x` and cast to `int`. Rejected because `(int)"abc"` silently becomes `0` and would produce `width = 0`, `height = 0` for malformed input.

- Prefer numeric `width`/`height` when both numeric and `resolution` are present.
  - Rationale: a future API change that emits both is most likely to use the numeric pair as the precise source.
  - Alternative considered: prefer `resolution`. Rejected because the numeric pair is already the canonical shape in `AssetDTO`.

- Treat malformed or empty `resolution` as missing metadata.
  - Rationale: resolution is not validated as required anywhere else in the SDK; raising on a soft metadata field would be a regression.
  - Alternative considered: throw `InvalidArgumentException`. Rejected because it would break `fromArray()` for any future API change that emits a different string format.

- Keep `AssetDTO::toArray()` emitting `width` and `height` numerically and not re-emitting `resolution`.
  - Rationale: the existing snapshot shape stays stable; consumers already use `getResolution()` for a formatted string.
  - Alternative considered: include `resolution` in `toArray()`. Rejected to keep the change minimal and backwards-compatible.

- Cover the behavior with unit tests using fake asset payloads.
  - Rationale: the parsing rule is deterministic and isolated to `AssetDTO::fromArray()`. No HTTP transport is needed.
  - Alternative considered: live integration coverage. Rejected because the issue was confirmed live once and reverting `resolution` parsing would not require an API call to detect.

## Risks / Trade-offs

- Consumers who relied on `height === null` as a signal that an asset is "unknown resolution" will now see populated values. Mitigation: document the change in `CHANGELOG.md`; the new value is more accurate, not less.
- The `original` asset may report the same resolution as `1080p`, so `getHighestQualityAsset()`-style helpers based purely on height could produce ties. Mitigation: this matches Kinescope's own metadata; tie-breakers belong to a separate change.
- A future API change that emits `resolution` in a different shape (e.g. `1920×1080` with a Unicode multiplication sign, or `1080p`) would be ignored by the strict regex. Mitigation: documented decision; can be extended later without breaking the public API.
