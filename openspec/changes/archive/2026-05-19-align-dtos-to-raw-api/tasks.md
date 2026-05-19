## 1. Contract Fixtures and Tests

- [x] 1.1 Add raw API contract fixtures for folder, video, asset, playlist, playlist entity, subtitle, and unpaginated collection responses.
- [x] 1.2 Update unit tests to assert raw API field names and remove legacy alias expectations.

## 2. DTO Implementation

- [x] 2.1 Update folder DTO/list-result fields and helpers to use `items_count` and `size`.
- [x] 2.2 Update video DTO/list-result fields and helpers to use the current video payload.
- [x] 2.3 Update asset DTO fields to preserve current asset payload metadata.
- [x] 2.4 Update playlist DTO/list-result fields and service behavior to use current playlist payloads.
- [x] 2.5 Update playlist entity, subtitle, and annotation collection handling for unpaginated `data` responses.
- [x] 2.6 Preserve useful raw unknown fields without fabricating unavailable legacy fields.
- [x] 2.7 Use `Carbon\CarbonImmutable` for SDK date and timestamp fields.
- [x] 2.8 Use `Carbon\CarbonImmutable` for SDK HTTP-date parsing such as `Retry-After`.

## 3. CLI, Docs, and Validation

- [x] 3.1 Update CLI normalizers/table headers to avoid legacy DTO aliases.
- [x] 3.2 Update `CHANGELOG.md` and relevant README examples.
- [x] 3.3 Run focused DTO/CLI unit tests.
- [x] 3.4 Run `make test-unit`.
- [x] 3.5 Run `make lint-all`.
- [x] 3.6 Run `make openspec-validate`.
