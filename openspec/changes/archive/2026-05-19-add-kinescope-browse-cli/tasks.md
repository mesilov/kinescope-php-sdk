## 1. Command Contract Tests

- [x] 1.1 Add unit tests that assert `kinescope:browse` is registered without removing `video:info`.
- [x] 1.2 Add unit tests for credential failure, invalid resource, invalid format, malformed UUIDs, missing selectors, and incompatible selectors.
- [x] 1.3 Add unit tests for projects JSON/table output using fake paginated API responses.
- [x] 1.4 Add unit tests for folders JSON/table output and project validation.
- [x] 1.5 Add unit tests for videos JSON/table output, folder/project validation, and `--include-assets` sanitized output.
- [x] 1.6 Add unit tests for assets JSON/table output, asset sorting, and signed URL sanitization.
- [x] 1.7 Verify the first browse-command tests fail for the expected missing command/behavior before production implementation.

## 2. Browse Command Implementation

- [x] 2.1 Add `BrowseCommand` with Symfony Console arguments/options matching the OpenSpec contract.
- [x] 2.2 Reuse `ApiClientFactory` credential resolution and SDK exception handling from `video:info`.
- [x] 2.3 Implement resource and selector validation with local UUID checks.
- [x] 2.4 Implement full-page project, folder, and video collection with page size `100`.
- [x] 2.5 Implement normalized row builders and deterministic sorting for projects, folders, videos, and assets.
- [x] 2.6 Implement JSON output and Symfony Console table output.
- [x] 2.7 Register `BrowseCommand` in `ContainerFactory` and `Application`.

## 3. Documentation and CLI Surface

- [x] 3.1 Update `README.md` with `kinescope:browse` usage examples and sanitized asset behavior.
- [x] 3.2 Update `CHANGELOG.md` with the new read-only browse CLI command.
- [x] 3.3 Verify `bin/console list` includes `kinescope:browse`.

## 4. Validation

- [x] 4.1 Run `make openspec-validate`.
- [x] 4.2 Run focused browse command unit tests.
- [x] 4.3 Run `make test-unit` equivalent via fallback runner because the worktree `php-cli` compose service was unavailable.
- [x] 4.4 Run `make lint-all`; when PHPStan parallel worker failed on `.phpstan.cache` container creation, verify the equivalent checks separately with PHP-CS-Fixer dry-run, PHPStan serial/debug analysis, and Rector dry-run.
- [x] 4.5 Run relevant live/manual browse checks when `KINESCOPE_API_KEY` is available; otherwise document that live API verification was skipped.
