## Context

The SDK exposes a Symfony Console executable at `vendor/bin/kinescope` with resource/action commands for read-only Kinescope inspection. Agents can already infer the command surface from README and OpenSpec, but that makes every consumer project repeat discovery and safety decisions.

`llm/skills` is a Composer plugin that copies skill directories from installed donor packages into a project-local target, defaulting to `.agents/skills`. Donor packages opt in with `extra.skills.source`, and consumers control trust, aliases, and auto-sync from their root `composer.json`.

## Goals / Non-Goals

**Goals:**

- Ship one `kinescope-cli` skill with the SDK package.
- Declare the SDK as an `llm/skills` donor through Composer metadata.
- Keep the skill usable by both Codex and Claude Code by using a plain `SKILL.md` structure and documenting `.agents/skills` plus `.claude/skills` aliasing.
- Keep all CLI guidance aligned with the current `kinescope:<resource>:<action>` command surface.
- Validate the skill as a package artifact without changing SDK runtime behavior.

**Non-Goals:**

- Do not reintroduce `video:info` or `kinescope:browse`.
- Do not make `llm/skills` a runtime dependency of SDK consumers.
- Do not copy generated skill mirrors into `.agents/skills` or `.claude/skills` in this repository.
- Do not add a Codex plugin package in this change.

## Decisions

### Use `llm/skills` as the distribution path

Add `llm/skills` to `require-dev` and allow it under Composer `config.allow-plugins`. The package is needed for local validation and documentation, but not for SDK runtime calls.

Alternative considered: add `llm/skills` to `require`. Rejected because it would force a Composer plugin into every SDK installation even when the consumer only needs the PHP library.

### Use `skills/` as the donor source

Declare:

```json
{
  "extra": {
    "skills": {
      "source": "skills"
    }
  }
}
```

The plugin treats immediate subdirectories of that source as skills, so the distributed skill path is `skills/kinescope-cli/SKILL.md`.

Alternative considered: `resources/skills`. Rejected for this package because the user explicitly wants a root `skills` distribution surface, and `llm/skills` supports that shape directly.

### Let consumers create agent-specific paths

Do not commit `.agents/skills` or `.claude/skills` mirrors. Consumer projects can run:

```bash
composer skills:update mesilov/kinescope-php-sdk --alias=.claude/skills
```

or configure `extra.skills.aliases`. This keeps one physical synced target and lets `llm/skills` manage symlink/junction mirrors.

### Keep the skill instruction-only

The first version should contain one `SKILL.md` and no scripts. The CLI already supplies deterministic JSON/table output; the skill's job is to choose the right command, protect secrets, prefer JSON for machine processing, and avoid printing signed asset links.

## Risks / Trade-offs

- `mesilov/*` is not in the current built-in trusted vendor list for `llm/skills` -> document that consumers must run the package explicitly or add `mesilov/kinescope-php-sdk` to `extra.skills.trusted`.
- Claude Code projects may prefer `.claude/skills` -> document `--alias=.claude/skills` instead of duplicating files.
- The skill can drift from the CLI -> validate its command list against README/OpenSpec and keep it updated when CLI commands change.
