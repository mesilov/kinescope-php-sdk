## Why

Tracked in [issue #11](https://github.com/mesilov/kinescope-php-sdk/issues/11). Live Kinescope `GET /v1/videos/{id}` responses do not include separate `width` or `height` fields on `assets[]`. Instead the API returns a composite `resolution` string (for example `"1920x1080"`, `"1280x720"`, `"640x360"`) for every downloadable asset, including `original`. `AssetDTO::fromArray()` currently reads only `width` and `height` keys, so `AssetDTO::$width` and `AssetDTO::$height` are always `null` for current API payloads. This makes height-aware features such as `getResolution()`, `isHd()`, `isFullHd()`, `is4K()` and `QualityPreference::BEST` selection effectively unusable for SDK consumers, even though Kinescope reports the resolution per asset.

## What Changes

- Parse the `resolution` field from each asset payload in `AssetDTO::fromArray()` and use it to populate `AssetDTO::$width` and `AssetDTO::$height` when those numeric fields are absent.
- Keep numeric `width` and `height` fields as the source of truth when the API provides them, so a future API change is not regressed by this fix.
- Treat malformed or empty `resolution` strings as missing metadata, without raising an exception, because resolution is optional.
- Do not change `AssetDTO::$resolution` (no new property is introduced; the existing `getResolution()` accessor stays the public API for the formatted string).
- Do not change downloader behavior, quality selection rules, or any other DTO.
- Add unit tests covering: `resolution` only, numeric `width`/`height` only, both present (numeric wins), malformed string ignored, missing field stays `null`.

## Capabilities

### New Capabilities
- `video-asset-resolution-parsing`: Defines how `AssetDTO::fromArray()` derives `width` and `height` from the API payload, including the composite `resolution` field.

### Modified Capabilities
- None.

## Impact

- Affected SDK behavior: `Kinescope\DTO\Video\AssetDTO::fromArray()` and every accessor that depends on `$width` or `$height` (`getResolution()`, `getAspectRatio()`, `isHd()`, `isFullHd()`, `is4K()`).
- Affected tests: unit coverage for `AssetDTO` parsing.
- Public API surface remains backwards-compatible: no constructor signature change, no property removal, no new required field.
- Indirect benefit: `QualityPreference::BEST` in `VideoDownloader` regains its height-based ordering when `resolution` is present, complementing the `fix-worst-quality-file-selection` change. Existing `WORST`-by-`fileSize` behavior is unaffected.
- Documentation/changelog should mention that `AssetDTO::$width` and `AssetDTO::$height` are now populated from the `resolution` field when separate numeric fields are absent.
