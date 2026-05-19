## Why

SDK `0.4.0` ships a Symfony Console entry point, but it only exposes `video:info`.
SDK users still need a safe read-only way to discover projects, folders, videos, and asset metadata from a Kinescope account without copying application-specific browse commands into every consuming project.

## What Changes

- Add a read-only `kinescope:browse <resource>` SDK CLI command for `projects`, `folders`, `videos`, and `assets`.
- Reuse the existing API key resolution contract from `video:info`: `--api-key` / `-k` with `KINESCOPE_API_KEY` fallback.
- Provide deterministic `table` and `json` output, with `table` as the default human format.
- Validate resource-specific selectors locally, including required UUIDs and incompatible option combinations.
- Sanitize asset output so signed CDN URLs and raw download links are not printed by default.
- Register the command in the SDK Console application alongside `video:info`.
- Document the new command in README and CHANGELOG.

Out of scope:

- Mutating Kinescope resources.
- Adding interactive prompts.
- Printing signed asset URLs by default.
- Syncing data into an application database.
- Replacing `video:info`.

## Capabilities

### New Capabilities

- `kinescope-browse-cli`: Read-only CLI browsing for projects, folders, videos, and sanitized assets.

### Modified Capabilities

- None.

## Impact

- Affected code: console command registration under `src/Infrastructure/Console/`, SDK services used by the command, and CLI tests.
- Affected public SDK behavior: adds a new CLI command without changing existing `video:info` behavior.
- Affected docs: `README.md` and `CHANGELOG.md`.
- Dependencies: no new runtime dependency expected; reuse Symfony Console already present in the SDK CLI.
- External systems: optional integration/manual verification against Kinescope API when credentials are available.
