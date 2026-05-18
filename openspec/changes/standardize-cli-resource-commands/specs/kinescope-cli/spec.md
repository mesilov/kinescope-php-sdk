## ADDED Requirements

### Requirement: Export a named Kinescope CLI executable
The SDK package SHALL expose its public CLI through `bin/kinescope` instead of a generic `bin/console` executable.

#### Scenario: Composer binary is named kinescope
- **WHEN** the package is installed with Composer
- **THEN** Composer exposes the SDK CLI as `vendor/bin/kinescope`
- **AND** the package does not export `vendor/bin/console`

#### Scenario: Repository-local CLI uses the same executable
- **WHEN** a developer runs the CLI inside this repository
- **THEN** the executable path is `bin/kinescope`

### Requirement: Use singular resource/action command names
The SDK CLI SHALL name read-only commands as `kinescope:<singular-resource>:<action>`.

#### Scenario: Commands are listed
- **WHEN** a user lists SDK CLI commands
- **THEN** the read-only command list includes `kinescope:project:list`, `kinescope:project:show`, `kinescope:folder:list`, `kinescope:folder:show`, `kinescope:video:list`, `kinescope:video:show`, and `kinescope:video:asset:list`

#### Scenario: Legacy commands are removed
- **WHEN** a user lists SDK CLI commands
- **THEN** the command list does not include `video:info`
- **AND** the command list does not include `kinescope:browse`

### Requirement: Resolve CLI credentials consistently
Every Kinescope CLI command SHALL resolve credentials through `--api-key` / `-k` and fall back to `KINESCOPE_API_KEY`.

#### Scenario: API key option is provided
- **WHEN** a user runs a Kinescope CLI command with `--api-key=<key>`
- **THEN** the command uses that key to build the Kinescope API client

#### Scenario: API key is unavailable
- **WHEN** a user runs a Kinescope CLI command without `--api-key` and without `KINESCOPE_API_KEY`
- **THEN** the command exits with `Command::FAILURE` and prints a clear credential error

### Requirement: Validate CLI identifiers locally
The SDK CLI SHALL validate UUID arguments and selector options before executing resource-specific API reads.

#### Scenario: Positional identifier is malformed
- **WHEN** a user passes a malformed positional resource id
- **THEN** the command exits with `Command::INVALID` and reports the invalid identifier

#### Scenario: Selector identifier is malformed
- **WHEN** a user passes a malformed `--project-id`, `--folder-id`, or other UUID selector option
- **THEN** the command exits with `Command::INVALID` and reports the invalid selector

#### Scenario: Required selector is missing
- **WHEN** a command requires a selector such as `--project-id` and the user omits it
- **THEN** the command exits with `Command::INVALID` and reports the missing selector

### Requirement: List projects
The CLI SHALL list all account projects through `kinescope:project:list`.

#### Scenario: Projects table output
- **WHEN** a user runs `kinescope:project:list`
- **THEN** the command prints a deterministic table containing project identifiers, names, API `items_count` values, and folder counts derived from the API `folders` array

#### Scenario: Projects JSON output
- **WHEN** a user runs `kinescope:project:list --format=json`
- **THEN** the command prints JSON with `resource` set to `project` and an `items` array whose project rows use the Kinescope API field names such as `items_count`, `size`, `privacy_domains`, and `folders`

### Requirement: Show one project
The CLI SHALL fetch one project through `kinescope:project:show <project-id>`.

#### Scenario: Project exists
- **WHEN** a user runs `kinescope:project:show <project-id>`
- **THEN** the command prints the project as pretty JSON using Kinescope API field names such as `items_count`, `size`, `privacy_domains`, and `folders`

#### Scenario: Project does not exist
- **WHEN** the selected project is not found
- **THEN** the command exits with `Command::FAILURE` and prints a clear not-found error

### Requirement: List folders
The CLI SHALL list folders for one project through `kinescope:folder:list --project-id=<project-id>`.

#### Scenario: Folders table output
- **WHEN** a user runs `kinescope:folder:list --project-id=<project-id>`
- **THEN** the command validates that the project exists and prints a deterministic table containing folder identifiers, names, parent identifiers, paths, and video counts

#### Scenario: Folders JSON output
- **WHEN** a user runs `kinescope:folder:list --project-id=<project-id> --format=json`
- **THEN** the command prints JSON with `resource` set to `folder`, `projectId` set to the selected project, and an `items` array of normalized folder rows

### Requirement: Show one folder
The CLI SHALL fetch one folder through `kinescope:folder:show <folder-id> --project-id=<project-id>`.

#### Scenario: Folder belongs to project
- **WHEN** a user runs `kinescope:folder:show <folder-id> --project-id=<project-id>`
- **THEN** the command prints the folder as pretty JSON

#### Scenario: Folder does not belong to project
- **WHEN** the selected folder is not found for the selected project
- **THEN** the command exits with `Command::FAILURE` and prints a clear not-found error

### Requirement: List videos
The CLI SHALL list videos for one project, optionally scoped to a folder, through `kinescope:video:list`.

#### Scenario: Project videos table output
- **WHEN** a user runs `kinescope:video:list --project-id=<project-id>`
- **THEN** the command prints a deterministic table containing video identifiers, names, project identifiers, folder identifiers, durations, and statuses

#### Scenario: Folder videos JSON output
- **WHEN** a user runs `kinescope:video:list --project-id=<project-id> --folder-id=<folder-id> --format=json`
- **THEN** the command validates that the folder belongs to the selected project and prints JSON with `resource` set to `video`, `projectId` set to the selected project, `folderId` set to the selected folder, and an `items` array of normalized video rows

#### Scenario: Videos include sanitized assets
- **WHEN** a user runs `kinescope:video:list --project-id=<project-id> --include-assets --format=json`
- **THEN** each video row includes sanitized asset summaries without raw `url` or `download_link` values

### Requirement: Show one video
The CLI SHALL fetch one video through `kinescope:video:show <video-id>`.

#### Scenario: Video exists
- **WHEN** a user runs `kinescope:video:show <video-id>`
- **THEN** the command prints the video as pretty JSON

#### Scenario: Video does not exist
- **WHEN** the selected video is not found
- **THEN** the command exits with `Command::FAILURE` and prints a clear not-found error

### Requirement: List video assets
The CLI SHALL list sanitized asset metadata for one video through `kinescope:video:asset:list <video-id>`.

#### Scenario: Assets table output
- **WHEN** a user runs `kinescope:video:asset:list <video-id>`
- **THEN** the command prints the video title and a deterministic table containing asset identifiers, quality labels, human-readable sizes, and download-link availability

#### Scenario: Assets JSON output
- **WHEN** a user runs `kinescope:video:asset:list <video-id> --format=json`
- **THEN** the command prints JSON with `resource` set to `video_asset`, `videoId` set to the selected video, `videoName` set to the video title, and an `items` array of sanitized asset summaries

#### Scenario: Asset links are sanitized
- **WHEN** an asset contains `url` or `download_link`
- **THEN** asset output includes presence booleans such as `hasUrl`, `hasDownloadLink`, and `downloadable`
- **AND** asset output does not include the raw signed link values

### Requirement: Report API failures clearly
The SDK CLI SHALL report SDK/API failures on STDERR and return a non-zero exit code.

#### Scenario: Kinescope API fails
- **WHEN** a Kinescope SDK exception occurs during CLI execution
- **THEN** the command exits with `Command::FAILURE` and prints the exception message
