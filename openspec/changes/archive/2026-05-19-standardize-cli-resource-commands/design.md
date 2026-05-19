## Context

The current CLI surface grew in two steps. `video:info` was the first standalone command and uses a short, non-namespaced command name. `kinescope:browse` was added later as a read-only resource router for projects, folders, videos, and assets. Both are useful, but together they create an inconsistent public contract before the SDK CLI expands further.

The chosen direction is intentionally breaking: expose the package as a Kinescope CLI tool and make command names follow a predictable resource/action shape.

## Goals / Non-Goals

**Goals:**

- Use one public executable: `bin/kinescope` in the repository and `vendor/bin/kinescope` for Composer users.
- Use one command naming scheme: `kinescope:<singular-resource>:<action>`.
- Keep the first command set read-only with `list` and `show` actions.
- Remove old command names instead of keeping aliases.
- Keep credential resolution, error handling, UUID validation, deterministic output, and asset sanitization consistent across commands.
- Make future expansion obvious for playlists, subtitles, annotations, downloads, and later mutations.

**Non-Goals:**

- Do not introduce write commands in this change.
- Do not keep `video:info` or `kinescope:browse` compatibility aliases.
- Do not introduce a second executable beside `bin/kinescope`.
- Do not change SDK service APIs unless command extraction requires internal-only wiring cleanup.

## Decisions

- Rename the executable from `bin/console` to `bin/kinescope`.
  - Rationale: SDK package users should not get a generic `vendor/bin/console` binary from this package.
  - Alternative considered: keep only `bin/console` in Symfony application style. Rejected because the CLI is a public package tool, not a consuming application console.
  - Alternative considered: keep both `bin/console` and `bin/kinescope`. Rejected because it creates duplicate entry points without a clear user benefit.

- Keep the `kinescope:` command prefix even when the executable is `kinescope`.
  - Rationale: the same commands remain clear in repository-local command lists and do not collide with generic Symfony command names like `video:show`.
  - Trade-off: package-user invocations repeat the product name, for example `vendor/bin/kinescope kinescope:video:show`.

- Use singular resource segments.
  - Rationale: the command describes the resource type namespace, and the action (`list`, `show`) describes whether the operation targets a collection or one object.
  - Examples: `kinescope:project:list`, `kinescope:video:show`, `kinescope:video:asset:list`.

- Use `list` for collections and `show` for single-resource reads.
  - Rationale: this is compact, common CLI vocabulary and leaves room for later verbs such as `download`, `upload`, `create`, or `delete` if the CLI grows beyond read-only operations.
  - Alternative considered: keep `info` for video details. Rejected because `show` pairs better with `list` and does not encode an arbitrary detail level.
  - Statistics exception: `kinescope:statistics:show` uses `show` even without a positional resource id because the command returns one aggregate read model for the selected account/project/folder scope rather than a list of statistics resources.

- Use positional arguments for the primary resource being shown and options for filters/selectors.
  - Rationale: `kinescope:video:show <video-id>` reads naturally, while list commands can accept optional or required filters such as `--project-id` and `--folder-id`.
  - Nested list exception: `kinescope:video:asset:list <video-id>` uses the parent video id as the required positional target because assets are listed only within one video.

- Keep show commands JSON-only for now.
  - Rationale: show commands return detailed DTO-shaped data, while list commands are the human-scannable table surface.
  - Alternative considered: add `--format` to show commands too. Rejected for this change because it expands the formatting contract before there is a clear table shape for every single-resource view.

- Split command responsibilities instead of keeping `kinescope:browse` as the primary implementation surface.
  - Rationale: a resource/action command set is easier to discover, test, document, and extend without turning one command into a large router.
  - Implementation may still share internal helpers for API key resolution, UUID validation, rendering, and service construction.

## Command Contract

Repository-local usage:

```bash
bin/kinescope kinescope:project:list
bin/kinescope kinescope:project:show <project-id>
bin/kinescope kinescope:folder:list --project-id=<project-id>
bin/kinescope kinescope:folder:show <folder-id> --project-id=<project-id>
bin/kinescope kinescope:video:list --project-id=<project-id>
bin/kinescope kinescope:video:list --project-id=<project-id> --folder-id=<folder-id>
bin/kinescope kinescope:video:show <video-id>
bin/kinescope kinescope:video:asset:list <video-id>
bin/kinescope kinescope:statistics:show
bin/kinescope kinescope:statistics:show --project-id=<project-id>
bin/kinescope kinescope:statistics:show --folder-id=<folder-id>
```

Composer package usage:

```bash
vendor/bin/kinescope kinescope:project:list
vendor/bin/kinescope kinescope:video:show <video-id>
```

All commands accept:

- `--api-key=<key>` / `-k <key>` with `KINESCOPE_API_KEY` fallback.

List commands accept:

- `--format=table|json`, defaulting to `table`.

Statistics command accepts:

- `--format=table|json`, defaulting to `table`.
- At most one scope selector: `--project-id=<project-id>` or `--folder-id=<folder-id>`.
- No selector means account-wide statistics.

Show commands:

- Print pretty JSON to STDOUT.

## Risks / Trade-offs

- This is a breaking CLI change. The mitigation is explicit changelog migration notes and no compatibility aliases that would extend the mixed naming model.
- `vendor/bin/kinescope kinescope:...` is repetitive. The mitigation is consistency with Symfony Console namespaces and future compatibility with repository-local command listing.
- Splitting commands may duplicate logic. The implementation should add small internal helpers only where duplication is real: credentials, UUID validation, JSON/table rendering, and service construction.
- Existing `kinescope:browse` tests are broad and useful. They should be migrated into command-specific tests rather than discarded.
