## ADDED Requirements

### Requirement: Populate width and height from the resolution field
The SDK SHALL populate `AssetDTO::$width` and `AssetDTO::$height` from the API `resolution` field when the asset payload does not provide separate numeric `width` and `height` keys.

#### Scenario: Resolution string parsed when numeric fields are absent
- **WHEN** an asset payload contains `resolution = "1920x1080"` and does not contain `width` or `height`
- **THEN** `AssetDTO::fromArray()` returns an asset with `$width = 1920` and `$height = 1080`

#### Scenario: Numeric fields remain authoritative
- **WHEN** an asset payload contains both numeric `width` / `height` and a `resolution` string
- **THEN** `AssetDTO::fromArray()` uses the numeric `width` and `height` values from the payload

#### Scenario: Real-world asset list is parsed
- **WHEN** an asset payload mirrors the live Kinescope shape with `quality` in `original`, `1080p`, `720p`, `480p`, `360p` and `resolution` strings `"1920x1080"`, `"1920x1080"`, `"1280x720"`, `"852x480"`, `"640x360"`
- **THEN** every resulting `AssetDTO` exposes the corresponding numeric `$width` and `$height`

### Requirement: Treat malformed resolution as missing metadata
The SDK SHALL NOT raise an exception when the `resolution` field is missing, empty, or does not match `^(\d+)x(\d+)$`.

#### Scenario: Missing resolution leaves width and height as null
- **WHEN** an asset payload contains neither `width`, `height`, nor `resolution`
- **THEN** `AssetDTO::fromArray()` returns an asset with `$width = null` and `$height = null`

#### Scenario: Malformed resolution is ignored
- **WHEN** an asset payload contains `resolution = "1080p"` or `resolution = ""`
- **THEN** `AssetDTO::fromArray()` returns an asset with `$width = null` and `$height = null` and does not raise an exception

### Requirement: Preserve existing AssetDTO surface
The SDK SHALL keep the existing `AssetDTO` constructor signature, property set, and `toArray()` shape while parsing `resolution`.

#### Scenario: Constructor signature is unchanged
- **WHEN** a consumer constructs `AssetDTO` directly with `$width` and `$height`
- **THEN** the call remains source-compatible with the current constructor

#### Scenario: toArray output shape is unchanged
- **WHEN** `AssetDTO::toArray()` is called on a parsed asset
- **THEN** the result still exposes numeric `width` and `height` keys and does not introduce a `resolution` key

#### Scenario: Height-aware accessors reflect parsed values
- **WHEN** `resolution` is parsed into `$height`
- **THEN** `getResolution()`, `getAspectRatio()`, `isHd()`, `isFullHd()`, and `is4K()` return values consistent with that height
