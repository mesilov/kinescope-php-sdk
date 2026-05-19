## ADDED Requirements

### Requirement: Declare SDK skill donor metadata
The SDK package SHALL declare its Composer skill source so `llm/skills` can discover the distributed skill when the package is installed as a dependency.

#### Scenario: Composer package exposes skills source
- **WHEN** the SDK package is installed in a consumer project that uses `llm/skills`
- **THEN** the package metadata exposes `extra.skills.source` with value `skills`
- **AND** `skills/kinescope-cli/SKILL.md` exists inside the package

### Requirement: Provide a Kinescope CLI agent skill
The SDK package SHALL ship a `kinescope-cli` agent skill that helps coding agents use the SDK CLI safely and consistently.

#### Scenario: Agent reads the skill
- **WHEN** an agent opens `skills/kinescope-cli/SKILL.md`
- **THEN** the file contains valid skill frontmatter with name `kinescope-cli`
- **AND** the description covers Kinescope SDK CLI inspection tasks
- **AND** the body documents command selection, credential handling, JSON/table output selection, and asset-link sanitization

### Requirement: Keep skill guidance aligned with the current CLI
The `kinescope-cli` skill SHALL describe the current `vendor/bin/kinescope` command surface and SHALL NOT instruct agents to use removed CLI commands.

#### Scenario: Current commands are documented
- **WHEN** the skill documents supported read-only commands
- **THEN** it includes `kinescope:project:list`, `kinescope:project:show`, `kinescope:folder:list`, `kinescope:folder:show`, `kinescope:video:list`, `kinescope:video:show`, `kinescope:video:asset:list`, and `kinescope:statistics:show`
- **AND** it does not present `video:info` or `kinescope:browse` as supported commands

### Requirement: Document consumer sync workflow
The SDK documentation SHALL explain how consumers sync the distributed skill through `llm/skills`.

#### Scenario: Consumer wants Codex and Claude Code discovery
- **WHEN** a consumer reads the SDK README
- **THEN** the documentation shows that `llm/skills` syncs the SDK skill into `.agents/skills`
- **AND** the documentation shows how to alias the same target into `.claude/skills`
- **AND** the documentation states that `mesilov/kinescope-php-sdk` must be explicitly trusted or named during sync

### Requirement: Keep skill distribution non-runtime
The SDK SHALL keep `llm/skills` out of runtime dependencies.

#### Scenario: Runtime dependencies are inspected
- **WHEN** a consumer installs the SDK for PHP API usage
- **THEN** `llm/skills` is not required by the SDK runtime dependency list
- **AND** the package still declares the skill source for consumers that install the plugin separately
