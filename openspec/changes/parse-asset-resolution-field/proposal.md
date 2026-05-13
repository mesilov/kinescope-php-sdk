## Why

Tracked in [issue #11](https://github.com/mesilov/kinescope-php-sdk/issues/11). Live Kinescope `GET /v1/videos/{id}` responses do not include separate `width` or `height` fields on `assets[]`. Instead the API returns a composite `resolution` string (for example `"1920x1080"`, `"1280x720"`, `"640x360"`) for every downloadable asset, including `original`. `AssetDTO::fromArray()` currently reads only `width` and `height` numeric keys, so `AssetDTO::$width` and `AssetDTO::$height` are always `null` for current API payloads. This makes height-aware features such as `isHd()`, `isFullHd()`, `is4K()`, `getAspectRatio()` and `QualityPreference::BEST` selection effectively unusable for SDK consumers, even though Kinescope reports the resolution per asset.

The existing field pair (`?int $width`, `?int $height`) is also a weak shape: callers can pass `width` without `height` or one of them as `0`, and the `getResolution()` accessor synthesizes a string from two unrelated nullable fields. A small value object captures the invariants ("both dimensions are positive integers; format is `WxH`") and gives a single place for parsing, formatting, and resolution-derived predicates.

## What Changes

**Breaking** — `AssetDTO` surface changes; consumers that read `$width` / `$height` directly or rely on `getResolution(): ?string` must adapt.

- Introduce `Kinescope\DTO\Video\Resolution` — a `final readonly` value object with positive-int `width` and `height`, `Resolution::tryFromString(string): ?self`, `Resolution::aspectRatio(): float`, `Resolution::__toString(): string` formatting `"<width>x<height>"`, and height-based predicates `isHd()`, `isFullHd()`, `is4K()`.
- Replace `AssetDTO::$width` and `AssetDTO::$height` with a single `?Resolution $resolution` property.
- `AssetDTO::fromArray()` parses the API `resolution` string into a `Resolution` via `Resolution::tryFromString()`. When the payload supplies numeric `width`/`height` keys (current contract or future), they remain authoritative and are combined into a `Resolution` only when both are present and positive.
- `AssetDTO::getResolution(): ?string` is removed (the `Resolution` VO is now reachable directly through `$asset->resolution` and stringifies via `(string) $asset->resolution`).
- `AssetDTO::getAspectRatio()`, `isHd()`, `isFullHd()`, `is4K()` keep their public names but delegate to the new `Resolution` VO so they reflect parsed values.
- `AssetDTO::toArray()` emits a single `resolution` key (string or `null`) and stops emitting separate `width` / `height` keys; this matches the live Kinescope response shape.
- Malformed or empty `resolution` strings are treated as missing metadata (no exception).
- Add unit tests for `Resolution` and update `AssetDTO` / `VideoDTO` / `AssetSelector` coverage to the new property shape.

## Capabilities

### New Capabilities
- `video-asset-resolution-parsing`: Defines how `AssetDTO` derives a `Resolution` value object from the API payload, including the composite `resolution` field, and how the VO exposes resolution-derived predicates.

### Modified Capabilities
- None (this is the first time `AssetDTO` resolution handling is specified).

## Impact

- Affected SDK behavior: `Kinescope\DTO\Video\AssetDTO` parsing and every accessor that depends on resolution (`isHd()`, `isFullHd()`, `is4K()`, `getAspectRatio()`).
- New public type: `Kinescope\DTO\Video\Resolution`.
- Affected internal callers updated in the same change: `Kinescope\DTO\Video\VideoDTO::getHighestQualityAsset()` / `getLowestQualityAsset()` (sort by `resolution?->height`), `Kinescope\Services\Videos\AssetSelector` (height comparator reads `resolution?->height`).
- Affected tests: `AssetDTOTest`, `VideoDTOTest`, `AssetSelectorTest`, plus the new `ResolutionTest`.
- Public API surface is **not** backwards-compatible:
  - `AssetDTO::$width` / `$height` properties are removed.
  - `AssetDTO::getResolution(): ?string` is removed.
  - `AssetDTO::toArray()` no longer emits `width` / `height` keys.
- Indirect benefit: `QualityPreference::BEST` in `VideoDownloader` regains height-based ordering when the API supplies `resolution`. Existing `WORST`-by-`fileSize` behavior (shipped in `fix-worst-quality-file-selection`) is unaffected.
- Documentation/changelog must call out the breaking change with a migration note (`$asset->width` → `$asset->resolution?->width`; `$asset->getResolution()` → `(string) $asset->resolution`).
