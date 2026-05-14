## Context

`AssetDTO::fromArray()` reads `width` and `height` only as standalone numeric keys. A live verification against `https://api.kinescope.io/v1/videos/{id}` on 2026-05-13 with two test fixtures (single video + folder listing, 5 videos × 5 assets each) showed:

- `file_size` is present and positive for every asset.
- `width` and `height` are not present in any asset payload.
- `resolution` is present for every asset (including `original`) as a `"<width>x<height>"` string.

The currently shipped `fix-worst-quality-file-selection` change fixes `WORST` by sorting on `fileSize`, but `BEST` still depends on `height`, which is always `null` in practice. Public DTO accessors that depend on height (`isHd()`, `isFullHd()`, `is4K()`, `getResolution()`, `getAspectRatio()`) likewise return `null` or `false` for every real asset.

The existing pair (`?int $width`, `?int $height`) also has a soft contract: callers can construct an `AssetDTO` with `width` set but `height` null, or one of them zero, and the synthesizing `getResolution()` will misbehave. A value object resolves that and concentrates parsing in one place.

## Goals / Non-Goals

**Goals:**

- Introduce a `Resolution` value object that captures the invariant `width > 0 && height > 0`, parses `"<width>x<height>"` strings, exposes resolution-derived predicates (`isHd`, `isFullHd`, `is4K`), and stringifies back.
- Replace `AssetDTO::$width` and `AssetDTO::$height` with `?Resolution $resolution`.
- Parse `resolution` from the API payload into the VO inside `AssetDTO::fromArray()`.
- Keep numeric `width`/`height` keys authoritative when the API supplies them (and both are positive).
- Treat malformed `resolution` strings as missing metadata silently.
- Update height-aware accessors (`getAspectRatio`, `isHd`, `isFullHd`, `is4K`) on `AssetDTO` to delegate to the VO.
- Update internal callers (`VideoDTO`, `AssetSelector`) to use `$resolution?->height`.
- Cover the new VO and the migrated DTO with focused unit tests.

**Non-Goals:**

- Do not preserve backwards compatibility on the `AssetDTO` property/method surface. This is an explicit breaking change called out in the proposal and CHANGELOG.
- Do not change `VideoDownloader` selection rules. `BEST` will benefit indirectly because the resolution VO becomes populated, but no rule changes.
- Do not change other DTOs (`VideoDTO` accessors, `SubtitleDTO`, etc.) beyond what is required to compile against the new `AssetDTO` shape.
- Do not require live Kinescope credentials for regression coverage.
- Do not perform automatic migration of stored `AssetDTO::toArray()` snapshots persisted by consumers.

## Decisions

- New type lives at `Kinescope\DTO\Video\Resolution`.
  - Rationale: only used by `AssetDTO` today; keeps the namespace next to its consumer. If a second consumer appears, it can be moved to `Kinescope\DTO\Common` in a non-breaking follow-up.
  - Alternative considered: place it in `Common` upfront. Rejected as speculative.

- `Resolution` is `final readonly`, constructor enforces `width > 0` and `height > 0`, throws `InvalidArgumentException` otherwise.
  - Rationale: matches `AssetDTO::$fileSize` validation style and prevents zero-dimension nonsense.

- Parsing entry points: `Resolution::tryFromString(string $value): ?self` and `Resolution::fromString(string $value): self`.
  - `tryFromString` returns `null` on malformed input (used by `AssetDTO::fromArray()` for soft metadata).
  - `fromString` throws `InvalidArgumentException` (available for callers that already validated input).
  - Strict regex: `^(\d+)x(\d+)$`. Rejects `"1920×1080"` (Unicode `×`), `"1080p"`, `"abc"`.

- `AssetDTO::fromArray()` source-of-truth order:
  1. If both numeric `width` and `height` are present and positive → build `Resolution` from them.
  2. Else if `resolution` is present → `Resolution::tryFromString()`.
  3. Else → `null`.
  - Rationale: future API change that emits both numeric fields stays authoritative; current payload (only `resolution`) gets parsed; partial input (one numeric only) is rejected silently.

- `AssetDTO::toArray()` shape:
  - Emits `resolution` as `string|null` (the VO's `__toString()` or `null`).
  - Does **not** emit `width` or `height` keys.
  - Rationale: matches the live Kinescope payload shape and avoids leaking the old field pair.

- `AssetDTO::getResolution(): ?string` is removed.
  - Rationale: redundant given the public `$resolution` property and `(string) $asset->resolution`.
  - Alternative considered: keep as a deprecated alias. Rejected — the proposal explicitly accepts the break, and a thin alias would entrench the old idiom.

- `AssetDTO::isHd()`, `isFullHd()`, `is4K()`, `getAspectRatio()` are kept but delegate to the VO.
  - Rationale: the method names are convenient and ergonomic on the DTO; pushing callers to write `$asset->resolution?->isHd() ?? false` everywhere would multiply churn without a clear gain.

- Cover the behavior with unit tests using fake asset payloads.
  - Rationale: parsing is deterministic and isolated. No HTTP transport needed.
  - Alternative considered: live integration coverage. Rejected — covered indirectly by the existing live check from `fix-worst-quality-file-selection`.

## Risks / Trade-offs

- **Breaking change.** Consumers that read `$asset->width`, `$asset->height` directly or call `$asset->getResolution()` must migrate to `$asset->resolution?->width`, `$asset->resolution?->height`, and `(string) $asset->resolution`. Mitigation: CHANGELOG entry under a "Breaking changes" subsection, plus migration notes; SDK is pre-1.0 so semver allows the break in a minor.

- `AssetDTO::toArray()` consumers that persisted snapshots in the old shape (with `width`/`height` keys) will not round-trip back through `fromArray()` losslessly. Mitigation: the resulting snapshot still contains a `resolution` string, which is the canonical Kinescope shape; older snapshots that captured only `width` and `height` simply produce `Resolution::tryFromString(null)` → `null`, which matches the documented contract.

- The `original` asset frequently reports the same `resolution` as `1080p`, so `getHighestQualityAsset()` could produce ties on height. Mitigation: matches Kinescope metadata; secondary tie-breakers are out of scope for this change.

- A future API change that emits `resolution` in a different shape (e.g. Unicode `×`, suffixes like `"1080p"`) would be silently ignored by the strict regex. Mitigation: documented; can be extended later in a non-breaking follow-up by relaxing `Resolution::tryFromString()`.
