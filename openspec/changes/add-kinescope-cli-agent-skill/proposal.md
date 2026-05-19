## Why

The SDK now ships a dedicated `vendor/bin/kinescope` CLI with several resource/action commands, but AI agents still need to rediscover the command surface, safe output formats, and secret-handling rules from README and specs each time.

Providing an agent skill with the SDK through `llm/skills` gives Codex and Claude Code a reusable, Composer-distributed workflow for using the CLI correctly and safely.

## What Changes

- Add `llm/skills` as a development dependency and allow the Composer plugin in this repository.
- Add `extra.skills.source` so the SDK acts as a donor package for the Composer skill sync workflow.
- Add a canonical `skills/kinescope-cli/` skill distributed with the SDK package.
- Document the supported CLI command surface, credential rules, JSON/table selection, and asset-link sanitization guidance inside the skill.
- Add README guidance showing how consumer projects sync the skill into `.agents/skills` and optionally alias it into `.claude/skills`.
- Add validation coverage for the skill metadata and Composer donor configuration.
- No SDK PHP API, CLI command behavior, or Composer binary contract changes.

## Capabilities

### New Capabilities
- `kinescope-cli-agent-skill`: Distribution and discovery contract for the Kinescope CLI agent skill.

### Modified Capabilities
- None.

## Impact

- Affected files: `composer.json`, `skills/`, `README.md`, `CHANGELOG.md`, OpenSpec artifacts.
- Affected public artifact: Composer-distributed agent skill documentation for the SDK CLI.
- Affected runtime behavior: none.
- Dependencies: development dependency on `llm/skills` Composer plugin.
