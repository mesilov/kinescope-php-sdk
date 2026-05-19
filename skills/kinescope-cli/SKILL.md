---
name: kinescope-cli
description: Use when working with the Kinescope PHP SDK CLI for read-only account inspection, including listing or showing projects, folders, videos, video assets, and statistics through vendor/bin/kinescope or bin/kinescope. Helps agents choose current kinescope:* commands, handle KINESCOPE_API_KEY safely, prefer JSON for machine parsing, summarize table output for humans, and avoid exposing signed asset URLs or removed commands.
---

# Kinescope CLI

Use the SDK CLI for read-only Kinescope inspection before reaching for raw API calls or PHP code.

## Command Entry Point

Prefer the installed package executable:

```bash
vendor/bin/kinescope <command>
```

When working inside this SDK repository before Composer bin proxies are available, use:

```bash
bin/kinescope <command>
```

If the command surface may have changed, inspect it first:

```bash
vendor/bin/kinescope list kinescope
```

Do not use removed commands: `video:info` and `kinescope:browse`.

## Credentials

Commands resolve credentials from `KINESCOPE_API_KEY` or `--api-key` / `-k`.

- Prefer an existing `KINESCOPE_API_KEY` environment value.
- Use `--api-key` only when the user explicitly provides a key for that run.
- Never print, persist, commit, or echo the API key.
- If credentials are missing, report that blocker and do not invent sample data.

## Output Selection

Use `--format=json` whenever you need to parse, compare, filter, count, or feed output to another tool.

Use table output when the user wants a compact human-readable listing.

When reporting results back, include the command shape you ran and a short summary of counts, ids, names, and scope. Avoid pasting large JSON payloads unless the user asks for raw output.

## Current Commands

Project commands:

```bash
vendor/bin/kinescope kinescope:project:list --format=json
vendor/bin/kinescope kinescope:project:show <project-id>
```

Folder commands:

```bash
vendor/bin/kinescope kinescope:folder:list --project-id=<project-id> --format=json
vendor/bin/kinescope kinescope:folder:show <folder-id> --project-id=<project-id>
```

Video commands:

```bash
vendor/bin/kinescope kinescope:video:list --project-id=<project-id> --format=json
vendor/bin/kinescope kinescope:video:list --project-id=<project-id> --folder-id=<folder-id> --format=json
vendor/bin/kinescope kinescope:video:show <video-id>
```

Asset commands:

```bash
vendor/bin/kinescope kinescope:video:list --project-id=<project-id> --include-assets --format=json
vendor/bin/kinescope kinescope:video:asset:list <video-id> --format=json
```

Statistics commands:

```bash
vendor/bin/kinescope kinescope:statistics:show --format=json
vendor/bin/kinescope kinescope:statistics:show --project-id=<project-id> --format=json
vendor/bin/kinescope kinescope:statistics:show --folder-id=<folder-id> --format=json
```

`kinescope:statistics:show` accepts at most one scope selector: either `--project-id` or `--folder-id`.

## Asset Safety

Asset output is intentionally sanitized. It exposes fields such as `video_stream_size`, `video_stream_size_mb`, `has_url`, `has_download_link`, and `downloadable`.

Do not try to expose raw signed CDN URLs or `download_link` values by default. If a direct API response ever includes signed URLs, treat them as ephemeral secrets and redact them from summaries unless the user explicitly asks for a raw payload.

`video_stream_size` is Kinescope stream metadata, not a guaranteed final downloaded file size. For file-size verification, use HTTP `Content-Length`, transfer-reported bytes, bytes written, or final `filesize()`.

## Error Handling

If a command fails:

- Preserve the command exit meaning: invalid input is a user/request issue, SDK/API failures are runtime issues.
- Report the exact resource scope and selector involved.
- For not-found responses, verify the project/folder/video id and parent-child relationship before proposing code changes.
- For auth failures, ask for a valid API key path without printing the current key.
