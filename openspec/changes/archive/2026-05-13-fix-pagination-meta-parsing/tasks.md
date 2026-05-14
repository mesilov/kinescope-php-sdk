## 1. Tests

- [x] 1.1 Add unit coverage for `MetaDTO::fromArray()` parsing nested `pagination.total`, `pagination.page`, and `pagination.per_page`.
- [x] 1.2 Add unit coverage showing `getLastPage()` and `hasNextPage()` work when nested metadata omits `last_page`.
- [x] 1.3 Add unit coverage proving flat metadata without `pagination` fails explicitly.
- [x] 1.4 Add unit coverage proving missing `pagination.total`, `pagination.page`, or `pagination.per_page` fails explicitly.

## 2. Implementation

- [x] 2.1 Update `MetaDTO::fromArray()` to read only `pagination.total`, `pagination.page`, and `pagination.per_page`.
- [x] 2.2 Remove hidden pagination defaults and flat-key fallback from `MetaDTO::fromArray()`.
- [x] 2.3 Confirm paginated list result DTOs continue to use the shared `MetaDTO` parser without endpoint-specific parsing changes.
- [x] 2.4 Exclude non-paginated playlist entities from the shared paginated response contract; remodel them in `fix-playlist-api-contract`.

## 3. Documentation

- [x] 3.1 Update `CHANGELOG.md` with the pagination metadata fix and issue reference.

## 4. Validation

- [x] 4.1 Run `make openspec-validate`.
- [x] 4.2 Run `make test-unit`.
- [x] 4.3 Run `make lint-all`.
- [x] 4.4 Run `make test-integration` when Kinescope credentials and fixtures are available.
