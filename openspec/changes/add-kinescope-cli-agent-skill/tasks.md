## 1. Composer distribution metadata
- [x] 1.1 Add `llm/skills` as a development dependency.
- [x] 1.2 Allow the `llm/skills` Composer plugin in repository configuration.
- [x] 1.3 Declare `extra.skills.source` as `skills` in `composer.json`.

## 2. Skill package
- [x] 2.1 Add `skills/kinescope-cli/SKILL.md` with valid skill frontmatter.
- [x] 2.2 Document the current `vendor/bin/kinescope` command surface.
- [x] 2.3 Document safe credential handling and JSON/table output selection.
- [x] 2.4 Document sanitized asset output and signed-link handling.
- [x] 2.5 Avoid removed commands such as `video:info` and `kinescope:browse`.

## 3. Documentation
- [x] 3.1 Update README with `llm/skills` consumer sync instructions.
- [x] 3.2 Document `.agents/skills` as the default target and `.claude/skills` as an alias.
- [x] 3.3 Update CHANGELOG with the distributed agent skill and `llm/skills` dev dependency.

## 4. Verification
- [x] 4.1 Validate OpenSpec artifacts.
- [x] 4.2 Validate skill frontmatter and required files.
- [x] 4.3 Run `composer skills:show` / `composer skills:update --dry-run` checks where applicable.
- [x] 4.4 Run `make test-unit`.
- [x] 4.5 Run `make lint-all`.
