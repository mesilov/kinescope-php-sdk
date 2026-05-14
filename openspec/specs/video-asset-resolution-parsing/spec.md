# video-asset-resolution-parsing Specification

## Purpose
Defines the asset resolution model for video assets, including the `Resolution` value object, parsing of Kinescope `resolution` strings, malformed metadata handling, and the `AssetDTO` resolution surface.

## Requirements
### Requirement: Provide a Resolution value object
The SDK SHALL expose `Kinescope\DTO\Video\Resolution` as the canonical type for asset pixel dimensions.

#### Scenario: Resolution requires positive dimensions
- **WHEN** a consumer constructs `new Resolution(width: $w, height: $h)` with `$w <= 0` or `$h <= 0`
- **THEN** the constructor throws `InvalidArgumentException`

#### Scenario: tryFromString parses canonical resolution
- **WHEN** `Resolution::tryFromString("1920x1080")` is called
- **THEN** it returns a `Resolution` with `width = 1920` and `height = 1080`

#### Scenario: tryFromString returns null for malformed input
- **WHEN** `Resolution::tryFromString` receives `""`, `"1080p"`, `"1920×1080"` (Unicode times sign), or any string that does not match `^(\d+)x(\d+)$`
- **THEN** it returns `null` and does not raise an exception

#### Scenario: fromString throws on malformed input
- **WHEN** `Resolution::fromString` receives a string that does not match `^(\d+)x(\d+)$`
- **THEN** it throws `InvalidArgumentException`

#### Scenario: Resolution exposes derived metadata
- **WHEN** a consumer reads a `Resolution`
- **THEN** `__toString()` returns `"<width>x<height>"`, `aspectRatio()` returns `width / height`, and `isHd()`, `isFullHd()`, `is4K()` report `true` when `height >= 720`, `>= 1080`, `>= 2160` respectively

### Requirement: Populate AssetDTO::$resolution from the API payload
The SDK SHALL populate `AssetDTO::$resolution` from the API payload, preferring numeric `width`/`height` keys when both are present and positive, and falling back to the composite `resolution` string otherwise.

#### Scenario: Resolution string parsed when numeric fields are absent
- **WHEN** an asset payload contains `resolution = "1920x1080"` and does not contain numeric `width` or `height`
- **THEN** `AssetDTO::fromArray()` returns an asset where `$resolution->width === 1920` and `$resolution->height === 1080`

#### Scenario: Numeric fields remain authoritative
- **WHEN** an asset payload contains both numeric `width`/`height` (each positive) and a `resolution` string
- **THEN** `AssetDTO::fromArray()` uses the numeric values to build `$resolution`

#### Scenario: Real-world asset list is parsed
- **WHEN** an asset payload mirrors the live Kinescope shape with `quality` in `original`, `1080p`, `720p`, `480p`, `360p` and `resolution` strings `"1920x1080"`, `"1920x1080"`, `"1280x720"`, `"852x480"`, `"640x360"`
- **THEN** every resulting `AssetDTO::$resolution` carries the corresponding numeric `width`/`height`

### Requirement: Treat missing or malformed resolution as missing metadata
The SDK SHALL NOT raise an exception when the `resolution` field is missing, empty, or does not match `^(\d+)x(\d+)$`.

#### Scenario: Missing resolution leaves $resolution as null
- **WHEN** an asset payload contains neither numeric `width`/`height` nor a `resolution` string
- **THEN** `AssetDTO::fromArray()` returns an asset with `$resolution === null`

#### Scenario: Malformed resolution is ignored
- **WHEN** an asset payload contains `resolution = "1080p"` or `resolution = ""`
- **THEN** `AssetDTO::fromArray()` returns an asset with `$resolution === null` and does not raise an exception

#### Scenario: Partial numeric fields are ignored
- **WHEN** an asset payload contains numeric `width` but no `height` (or vice versa), and no usable `resolution` string
- **THEN** `AssetDTO::fromArray()` returns an asset with `$resolution === null`

### Requirement: AssetDTO surface reflects the Resolution VO
The SDK SHALL replace the previous `?int $width` / `?int $height` properties on `AssetDTO` with a single `?Resolution $resolution` property and expose resolution-derived predicates through it.

#### Scenario: AssetDTO exposes the Resolution VO directly
- **WHEN** a consumer reads `$asset->resolution`
- **THEN** the value is either `null` or a `Resolution` instance

#### Scenario: Resolution-derived accessors delegate to the VO
- **WHEN** `$asset->resolution` is non-null with `height = 1080`
- **THEN** `$asset->getAspectRatio()`, `$asset->isHd()`, `$asset->isFullHd()`, and `$asset->is4K()` reflect that height (e.g. `isFullHd() === true`, `is4K() === false`)

#### Scenario: Resolution-derived accessors return safe defaults when $resolution is null
- **WHEN** `$asset->resolution === null`
- **THEN** `$asset->getAspectRatio()` returns `null` and `$asset->isHd()`, `$asset->isFullHd()`, `$asset->is4K()` return `false`

#### Scenario: toArray emits a single resolution key
- **WHEN** `AssetDTO::toArray()` is called on an asset with non-null `$resolution`
- **THEN** the result contains a `resolution` string key (formatted `"<width>x<height>"`) and does not contain separate `width` or `height` keys

#### Scenario: toArray emits null resolution when missing
- **WHEN** `AssetDTO::toArray()` is called on an asset with `$resolution === null`
- **THEN** the result contains `resolution => null` and does not contain `width` or `height` keys
