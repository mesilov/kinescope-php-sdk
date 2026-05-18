## 1. Contract Tests

- [x] 1.1 Add or update application tests proving the CLI registers only the new resource/action commands and does not register `video:info` or `kinescope:browse`.
- [x] 1.2 Add command tests for `kinescope:project:list` covering credential failure, table output, JSON output, deterministic sorting, and invalid `--format`.
- [x] 1.3 Add command tests for `kinescope:project:show <project-id>` covering UUID validation, JSON output, not-found handling, and API failure handling.
- [x] 1.4 Add command tests for `kinescope:folder:list --project-id=<project-id>` covering required selector validation, UUID validation, project validation, table output, and JSON output.
- [x] 1.5 Add command tests for `kinescope:folder:show <folder-id> --project-id=<project-id>` covering both UUIDs, project/folder relationship validation, JSON output, and not-found handling.
- [x] 1.6 Add command tests for `kinescope:video:list --project-id=<project-id> [--folder-id=<folder-id>]` covering required project selector, optional folder selector, table output, JSON output, folder/project validation, and sanitized asset inclusion if retained as a list option.
- [x] 1.7 Add command tests for `kinescope:video:show <video-id>` covering the old `video:info` success/error behavior under the new command name plus UUID validation.
- [x] 1.8 Add command tests for `kinescope:video:asset:list <video-id>` covering JSON/table output, asset sorting, signed URL sanitization, and video not found handling.
- [x] 1.9 Add entrypoint/composer metadata tests or assertions proving `composer.json` exports `bin/kinescope` and no longer exports `bin/console`.

## 2. CLI Entry Point

- [x] 2.1 Rename `bin/console` to `bin/kinescope`.
- [x] 2.2 Update `composer.json` so `"bin"` exports `bin/kinescope`.
- [x] 2.3 Remove references to `vendor/bin/console` from documentation and replace them with `vendor/bin/kinescope`.
- [x] 2.4 Remove the default command registration for `video:info`.

## 3. Command Implementation

- [x] 3.1 Replace `VideoInfoCommand` registration with `kinescope:video:show`.
- [x] 3.2 Replace `BrowseCommand` as a public command with resource/action command classes for project, folder, video, and video assets.
- [x] 3.3 Introduce shared internal CLI helpers only where needed for API client creation, credential errors, UUID validation, JSON rendering, table rendering, and common exit-code behavior.
- [x] 3.4 Implement `kinescope:project:list` and `kinescope:project:show`.
- [x] 3.5 Implement `kinescope:folder:list` and `kinescope:folder:show`.
- [x] 3.6 Implement `kinescope:video:list` and `kinescope:video:show`.
- [x] 3.7 Implement `kinescope:video:asset:list`.
- [x] 3.8 Preserve sanitized asset output: expose presence booleans and do not print raw signed URLs or download links by default.
- [x] 3.9 Preserve deterministic list behavior: full pagination where previously supported, stable sorting, and `table` default for list commands.

## 4. Documentation

- [x] 4.1 Update `README.md` with the new `vendor/bin/kinescope` entry point and resource/action command examples.
- [x] 4.2 Add `CHANGELOG.md` breaking-change notes that map every removed command to its replacement.
- [x] 4.3 Document that no aliases are kept for `video:info` or `kinescope:browse`.

## 5. Validation

- [x] 5.1 Run `make openspec-validate`.
- [x] 5.2 Run focused console command unit tests.
- [x] 5.3 Run `make test-unit`.
- [x] 5.4 Run `make lint-all`.
- [x] 5.5 Run relevant live/manual read-only CLI checks when `KINESCOPE_API_KEY` is available; otherwise document why live API verification was skipped.
