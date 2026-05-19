## Why

The SDK CLI currently exposes two styles at once: the older `video:info` command and the newer resource-router command `kinescope:browse <resource>`. The package also exports a generic `vendor/bin/console` binary through Composer, which is ambiguous for an SDK package installed into applications that often already have their own console entry point.

This change makes the CLI intentionally breaking and standardizes it around one public executable and one resource-oriented command naming scheme before more read-only commands are added.

## What Changes

- Replace the Composer binary entry point with `bin/kinescope` so package users run `vendor/bin/kinescope ...`.
- Remove the old `bin/console` entry point and stop exporting generic `vendor/bin/console`.
- Remove the public commands `video:info` and `kinescope:browse`.
- Add canonical read-only commands using singular resources and explicit actions:
  - `kinescope:project:list`
  - `kinescope:project:show <project-id>`
  - `kinescope:folder:list --project-id=<project-id>`
  - `kinescope:folder:show <folder-id> --project-id=<project-id>`
  - `kinescope:video:list --project-id=<project-id> [--folder-id=<folder-id>]`
  - `kinescope:video:show <video-id>`
  - `kinescope:video:asset:list <video-id>`
  - `kinescope:statistics:show [--project-id=<project-id>|--folder-id=<folder-id>]`
- Keep all commands read-only.
- Keep credential resolution consistent: `--api-key` / `-k`, falling back to `KINESCOPE_API_KEY`.
- Keep deterministic `table` and `json` output for list-style commands, and JSON output for show-style commands.
- Validate UUID arguments and selector options locally before API reads.
- Document the breaking CLI migration in `README.md` and `CHANGELOG.md`.

Out of scope:

- Mutating CLI commands such as upload, create, update, or delete.
- Keeping compatibility aliases for `video:info` or `kinescope:browse`.
- Adding interactive prompts.
- Printing raw signed asset URLs or download links by default.
- Replacing the PHP SDK service API.

## Capabilities

### New Capabilities

- `kinescope-cli`: Standard resource-oriented read-only SDK CLI contract.

### Modified Capabilities

- None yet. Existing active browse CLI behavior is superseded by this breaking CLI contract rather than kept as a compatibility layer.

## Impact

- Affected public behavior: CLI entry point and command names are breaking changes.
- Affected code: `bin/`, `composer.json`, console application registration, console commands, shared CLI helpers if introduced, and console unit tests.
- Affected docs: `README.md` and `CHANGELOG.md`.
- Dependencies: no new runtime dependency expected; reuse Symfony Console and DependencyInjection already present.
- External systems: optional live Kinescope verification when `KINESCOPE_API_KEY` is available.
