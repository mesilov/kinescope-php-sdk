## 1. Implementation

- [x] 1.1 Replace `ProjectDTO` public aliases with raw API-shaped fields.
- [x] 1.2 Update `ProjectDTO::fromArray()` and `toArray()` to read and emit project API field names.
- [x] 1.3 Update `ProjectListResult` aggregate helper names to use `items` and `size` terminology.
- [x] 1.4 Update project CLI JSON output and table summaries to avoid legacy project aliases.
- [x] 1.5 Update unit tests and changelog coverage for the breaking field changes.

## 2. Validation

- [x] 2.1 Run focused project DTO/list/CLI command tests.
- [x] 2.2 Run `make test-unit`.
- [x] 2.3 Run `make lint-all`.
- [x] 2.4 Run `make openspec-validate`.
