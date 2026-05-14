## 1. Resolution Value Object

- [x] 1.1 Create `Kinescope\DTO\Video\Resolution` as `final readonly` with `int $width` and `int $height` (positive-int invariants enforced in the constructor).
- [x] 1.2 Implement `Resolution::tryFromString(string $value): ?self` (strict `^(\d+)x(\d+)$`) and `Resolution::fromString(string $value): self`.
- [x] 1.3 Implement `aspectRatio(): float`, `isHd(): bool`, `isFullHd(): bool`, `is4K(): bool`, `__toString(): string`.
- [x] 1.4 Add `tests/Unit/DTO/Video/ResolutionTest.php` covering: positive-int constructor validation, valid `tryFromString`, malformed/empty input → null, `fromString` throws on malformed input, `__toString` format, `aspectRatio`, all three height predicates.

## 2. AssetDTO Migration

- [x] 2.1 Replace `?int $width` and `?int $height` properties on `AssetDTO` with `?Resolution $resolution`.
- [x] 2.2 Update `AssetDTO::fromArray()` to build `Resolution` from numeric `width`+`height` when both are present and positive, otherwise from `Resolution::tryFromString($data['resolution'] ?? '')`.
- [x] 2.3 Remove `AssetDTO::getResolution(): ?string`.
- [x] 2.4 Update `getAspectRatio()`, `isHd()`, `isFullHd()`, `is4K()` to delegate to `$this->resolution?->...()`.
- [x] 2.5 Update `AssetDTO::toArray()` to emit a single `resolution` key (string or null) and drop the `width` / `height` keys.
- [x] 2.6 Update `tests/Unit/DTO/Video/AssetDTOTest.php` for the new property and `toArray()` shape.

## 3. Internal Callers

- [x] 3.1 Update `Kinescope\DTO\Video\VideoDTO::getHighestQualityAsset()` and `getLowestQualityAsset()` to sort by `$asset->resolution?->height` semantics, and adjust their unit tests.
- [x] 3.2 Update `Kinescope\Services\Videos\AssetSelector` (and `AssetSelectorTest`) to read `$asset->resolution?->height` in the height tie-breaker.
- [x] 3.3 Update `tests/Unit/Services/Videos/VideoDownloaderQualitySelectionTest.php` asset payload shape to use `resolution` strings instead of separate `height` keys.
- [x] 3.4 Audit remaining `->height` and `->width` references on `AssetDTO` across `src/` and `tests/` and migrate them.

## 4. Documentation And Validation

- [x] 4.1 Update `CHANGELOG.md` with a "Breaking changes" entry covering the `AssetDTO` surface change and migration notes.
- [x] 4.2 Run `make openspec-validate`.
- [x] 4.3 Run `make test-unit`.
- [x] 4.4 Run `make lint-all`.
- [x] 4.5 Optional: run `make test-integration-download` after the implementation lands to confirm `BEST` now selects the highest-resolution non-original asset on live Kinescope payloads.
