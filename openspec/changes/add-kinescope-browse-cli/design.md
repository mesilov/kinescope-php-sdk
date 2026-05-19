## Context

The SDK Console application currently registers only `video:info`. That command already establishes the CLI wiring pattern: Symfony Console attributes, `ApiClientFactory`, `--api-key` / `-k` credential resolution, and explicit SDK exception handling.

Issue `#18` asks to move the proven read-only Kinescope browse workflow from a consuming application into the SDK. The SDK should provide this operational inspection tool without depending on Symfony FrameworkBundle, Doctrine, or application-specific storage.

## Goals / Non-Goals

**Goals:**

- Add one read-only `kinescope:browse <resource>` command for `projects`, `folders`, `videos`, and `assets`.
- Keep `video:info` behavior unchanged.
- Produce deterministic table and JSON output for humans and scripts.
- Validate common operator mistakes before remote API calls where possible.
- Hide signed CDN URLs and raw download links by default.
- Cover command registration, validation, JSON output, and representative success paths with unit tests.

**Non-Goals:**

- Do not add mutation commands.
- Do not add interactive prompts.
- Do not add raw or unsafe URL output flags in the first version.
- Do not change DTO contracts only for the browse command.
- Do not depend on a consuming application's database or container.

## Decisions

- Implement a single `BrowseCommand` rather than four resource-specific commands.
  - Rationale: the issue describes one proven `resource`-argument command and this keeps CLI discovery compact.
  - Alternative considered: `kinescope:projects:list`, `kinescope:folders:list`, and similar commands. Rejected for the first version because it duplicates credential, output, and validation logic.

- Keep normalization inside the command layer for the first version.
  - Rationale: normalized browse rows are CLI presentation data, not new SDK DTO contracts.
  - Alternative considered: add new DTOs for browse rows. Rejected because it would expand the public SDK surface for presentation-only shapes.

- Use local UUID validation for selector options.
  - Rationale: invalid IDs are operator input errors and should return `Command::INVALID` with clear messages.
  - Alternative considered: let the remote API reject all bad identifiers. Rejected because it turns simple CLI mistakes into less predictable API failures.

- Validate resource relationships only when the SDK has a truthful API path.
  - Rationale: `folders` can validate project existence with `Projects::get()`, and `videos --folder-id` can validate folder ownership with `FoldersService::get($projectId, $folderId)`.
  - Alternative considered: trust the video listing endpoint to handle all selectors. Rejected because the existing application command already proved clearer local errors are useful.

- Iterate all pages with page size `100` for project and video listings.
  - Rationale: browse output is meant to represent the full selected account/project/folder view, and the issue specifies stable full listings.
  - Alternative considered: expose pagination flags. Rejected for the first version to keep the CLI contract focused and deterministic.

- Render `table` output with Symfony Console `Table`, and render `json` with `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR`.
  - Rationale: these match existing Symfony Console practices and the SDK's `video:info` JSON behavior.
  - Alternative considered: hand-format tables. Rejected because Symfony Console already provides stable table rendering.

## Risks / Trade-offs

- Large accounts can produce large output because the command fetches all pages -> keep page size at `100` and document the command as an inspection tool rather than a streaming export.
- Relationship validation adds extra read requests -> keep those reads explicit to resources that require them and document the behavior.
- Asset metadata still uses the current `fileSize` DTO naming until issue `#19` is handled -> keep browse output aligned with the current DTO contract and avoid asserting that metadata size is the final downloaded file size.
- Live Kinescope payloads may differ by account -> unit tests should cover normalized behavior with fake responses, and optional integration/manual checks can verify live credentials when available.
