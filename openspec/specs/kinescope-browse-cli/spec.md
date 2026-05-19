# kinescope-browse-cli Specification

## Purpose
Document the legacy read-only `kinescope:browse` command contract for projects, folders, videos, and assets, including credential handling, selector validation, deterministic table/JSON output, and signed asset URL sanitization.

## Requirements
### Requirement: Register read-only browse command
The SDK CLI SHALL register a read-only `kinescope:browse` command without changing the existing `video:info` command behavior.

#### Scenario: Command appears in CLI command list
- **WHEN** a user lists SDK console commands
- **THEN** the command list includes `kinescope:browse`

#### Scenario: Existing video info command remains available
- **WHEN** a user lists SDK console commands
- **THEN** the command list still includes `video:info`

### Requirement: Resolve browse credentials consistently
The browse command SHALL resolve credentials through `--api-key` / `-k` and fall back to `KINESCOPE_API_KEY`.

#### Scenario: API key option is provided
- **WHEN** a user runs `kinescope:browse projects --api-key=<key>`
- **THEN** the command uses that key to build the Kinescope API client

#### Scenario: API key is unavailable
- **WHEN** a user runs `kinescope:browse projects` without `--api-key` and without `KINESCOPE_API_KEY`
- **THEN** the command exits with `Command::FAILURE` and prints a clear credential error

### Requirement: Validate browse input
The browse command SHALL validate resources, output format, UUID selector values, required selectors, and incompatible selector combinations before executing resource-specific reads.

#### Scenario: Invalid resource is requested
- **WHEN** a user runs `kinescope:browse unknown`
- **THEN** the command exits with `Command::INVALID` and reports that the resource must be one of `projects`, `folders`, `videos`, or `assets`

#### Scenario: Invalid output format is requested
- **WHEN** a user runs `kinescope:browse projects --format=xml`
- **THEN** the command exits with `Command::INVALID` and reports that the format must be one of `table` or `json`

#### Scenario: Invalid UUID selector is provided
- **WHEN** a user provides a malformed `--project-id`, `--folder-id`, or `--video-id`
- **THEN** the command exits with `Command::INVALID` and reports the invalid field

#### Scenario: Missing required selector
- **WHEN** a user runs a resource that requires an id without the required selector
- **THEN** the command exits with `Command::INVALID` and reports the missing selector

#### Scenario: Incompatible selector combination
- **WHEN** a user passes a selector that is not accepted by the selected resource
- **THEN** the command exits with `Command::INVALID` and reports the incompatible option

### Requirement: Browse projects
The browse command SHALL list all account projects in deterministic table or JSON output.

#### Scenario: Projects JSON output
- **WHEN** a user runs `kinescope:browse projects --format=json`
- **THEN** the command prints JSON with `resource` set to `projects` and an `items` array of normalized project rows

#### Scenario: Projects table output
- **WHEN** a user runs `kinescope:browse projects`
- **THEN** the command prints a table containing project identifiers, names, video counts, and folder counts

#### Scenario: Projects reject selectors
- **WHEN** a user runs `kinescope:browse projects` with `--project-id`, `--folder-id`, or `--video-id`
- **THEN** the command exits with `Command::INVALID`

### Requirement: Browse folders
The browse command SHALL list all folders for a selected project in deterministic table or JSON output.

#### Scenario: Folders JSON output
- **WHEN** a user runs `kinescope:browse folders --project-id=<project-id> --format=json`
- **THEN** the command validates that the project exists and prints JSON with `resource` set to `folders`, `projectId` set to the selected project, and an `items` array of normalized folder rows

#### Scenario: Folders table output
- **WHEN** a user runs `kinescope:browse folders --project-id=<project-id>`
- **THEN** the command prints a table containing folder identifiers, names, parent identifiers, paths, and video counts

#### Scenario: Folders reject unrelated selectors
- **WHEN** a user runs `kinescope:browse folders --project-id=<project-id>` with `--folder-id` or `--video-id`
- **THEN** the command exits with `Command::INVALID`

### Requirement: Browse videos
The browse command SHALL list all videos for a selected project or folder in deterministic table or JSON output.

#### Scenario: Project videos JSON output
- **WHEN** a user runs `kinescope:browse videos --project-id=<project-id> --format=json`
- **THEN** the command prints JSON with `resource` set to `videos`, `projectId` set to the selected project, and an `items` array of normalized video rows

#### Scenario: Folder videos JSON output
- **WHEN** a user runs `kinescope:browse videos --project-id=<project-id> --folder-id=<folder-id> --format=json`
- **THEN** the command validates that the folder belongs to the selected project and prints JSON with `folderId` set to the selected folder

#### Scenario: Videos include sanitized assets
- **WHEN** a user runs `kinescope:browse videos --project-id=<project-id> --include-assets --format=json`
- **THEN** each video row includes sanitized asset summaries without raw `url` or `download_link` values

#### Scenario: Videos table output
- **WHEN** a user runs `kinescope:browse videos --project-id=<project-id>`
- **THEN** the command prints a table containing video identifiers, names, project identifiers, folder identifiers, durations, and statuses

#### Scenario: Videos reject video selector
- **WHEN** a user runs `kinescope:browse videos --project-id=<project-id> --video-id=<video-id>`
- **THEN** the command exits with `Command::INVALID`

### Requirement: Browse assets
The browse command SHALL fetch one video and print sanitized asset metadata in deterministic table or JSON output.

#### Scenario: Assets JSON output
- **WHEN** a user runs `kinescope:browse assets --video-id=<video-id> --format=json`
- **THEN** the command prints JSON with `resource` set to `assets`, `videoId` set to the selected video, `videoName` set to the video title, and an `items` array of sanitized asset summaries

#### Scenario: Assets table output
- **WHEN** a user runs `kinescope:browse assets --video-id=<video-id>`
- **THEN** the command prints the video title and a table containing asset identifiers, quality labels, human-readable sizes, and download-link availability

#### Scenario: Assets reject unrelated selectors
- **WHEN** a user runs `kinescope:browse assets --video-id=<video-id>` with `--project-id` or `--folder-id`
- **THEN** the command exits with `Command::INVALID`

### Requirement: Sanitize asset metadata
The browse command SHALL expose asset URL and download-link presence as booleans and SHALL NOT print raw signed asset URLs by default.

#### Scenario: Asset has signed links
- **WHEN** an asset contains `url` and `download_link`
- **THEN** browse output includes `hasUrl`, `hasDownloadLink`, and `downloadable` booleans without including the raw link values

#### Scenario: Asset output is sorted
- **WHEN** asset summaries are rendered
- **THEN** they are sorted by file size descending and then by id

### Requirement: Report API failures clearly
The browse command SHALL report SDK/API failures on STDERR and return a non-zero exit code.

#### Scenario: Kinescope API fails
- **WHEN** a Kinescope SDK exception occurs during browse execution
- **THEN** the command exits with `Command::FAILURE` and prints the exception message

#### Scenario: Project folder relationship is invalid
- **WHEN** `folders()->get(<project-id>, <folder-id>)` reports the folder is not found for the selected project
- **THEN** the command exits with `Command::INVALID` and reports that the folder does not belong to the project
