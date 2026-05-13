## 1. Regression Coverage

- [ ] 1.1 Add a unit test for `AssetDTO::fromArray()` that populates `$width` and `$height` from a `resolution` string like `"1920x1080"` when separate numeric fields are absent.
- [ ] 1.2 Add a unit test that confirms numeric `width` and `height` keys remain authoritative when both numeric fields and `resolution` are present.
- [ ] 1.3 Add a unit test that confirms a malformed or empty `resolution` string is treated as missing metadata (both `$width` and `$height` stay `null`, no exception).
- [ ] 1.4 Add a unit test confirming `getResolution()`, `isHd()`, `isFullHd()`, and `is4K()` reflect the parsed values for a real-world payload (`original`, `1080p`, `720p`, `480p`, `360p`).

## 2. Parsing Implementation

- [ ] 2.1 Implement `resolution` parsing inside `AssetDTO::fromArray()` using a strict `^(\d+)x(\d+)$` match.
- [ ] 2.2 Apply parsed values only when numeric `width` / `height` keys are absent; leave the existing numeric path untouched.
- [ ] 2.3 Preserve `AssetDTO` constructor signature, property set, and `toArray()` output shape.
- [ ] 2.4 Ensure other DTO consumers (`VideoDTO::getHighestQualityAsset()`, `VideoDTO::getLowestQualityAsset()`) keep working unchanged.

## 3. Documentation And Validation

- [ ] 3.1 Update `CHANGELOG.md` with the new `AssetDTO::$width` / `$height` behavior.
- [ ] 3.2 Run `make openspec-validate`.
- [ ] 3.3 Run `make test-unit`.
- [ ] 3.4 Run `make lint-all`.
- [ ] 3.5 Optional: re-run `bin/check-assets.php` against live API to confirm parsed values appear in `AssetDTO`.
