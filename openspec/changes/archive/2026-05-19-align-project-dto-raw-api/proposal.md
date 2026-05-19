## Why

`ProjectDTO` exposed SDK-invented aliases such as `videosCount`, `foldersCount`, `storageUsed`, and `allowedDomains`, while the Kinescope API returns `items_count`, `folders`, `size`, and `privacy_domains`. This made CLI output and SDK objects ambiguous because values looked authoritative but did not match the raw API contract.

The current release already contains breaking CLI changes, so this change aligns the project DTO contract with the raw API field names instead of keeping compatibility aliases.

## What Changes

- Replace project DTO aliases with fields that mirror the current project API response:
  - `items_count` -> `ProjectDTO::$itemsCount`
  - `folders` -> `ProjectDTO::$folders`
  - `size` -> `ProjectDTO::$size`
  - `privacy_domains` -> `ProjectDTO::$privacyDomains`
  - `privacy_email_domains` -> `ProjectDTO::$privacyEmailDomains`
  - `privacy_share` -> `ProjectDTO::$privacyShare`
  - `player_id` -> `ProjectDTO::$playerId`
  - `favorite` -> `ProjectDTO::$favorite`
  - `encrypted` -> `ProjectDTO::$encrypted`
- Remove ambiguous project aliases and helpers based on them.
- Update project CLI JSON output to emit raw API field names for projects.
- Keep convenience predicates such as `hasVideos()` and `hasFolders()`, but make them derive from `itemsCount` and `folders`.

## Impact

- Affected public API: `ProjectDTO` constructor/properties, `ProjectDTO::toArray()`, and `ProjectListResult` aggregate helper names.
- Affected CLI JSON: `kinescope:project:list --format=json` and `kinescope:project:show`.
- Affected docs/tests: changelog, CLI OpenSpec contract, project DTO/list tests, project command tests.
