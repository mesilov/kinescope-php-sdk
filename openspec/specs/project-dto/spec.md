# project-dto Specification

## Purpose
Define the project DTO and project CLI JSON contract around raw Kinescope project API field names instead of SDK-invented legacy aliases.

## Requirements
### Requirement: Mirror raw project API field names
The SDK SHALL expose Kinescope project fields using names that correspond to the raw project API response instead of SDK-invented aliases.

#### Scenario: Project payload is mapped to raw-shaped DTO fields
- **WHEN** `ProjectDTO::fromArray()` receives a project API payload with `items_count`, `folders`, `size`, `privacy_domains`, `privacy_email_domains`, `privacy_share`, `player_id`, `favorite`, and `encrypted`
- **THEN** the DTO exposes those values through `itemsCount`, `folders`, `size`, `privacyDomains`, `privacyEmailDomains`, `privacyShare`, `playerId`, `favorite`, and `encrypted`
- **AND** the DTO does not expose project aliases named `videosCount`, `foldersCount`, `storageUsed`, `allowedDomains`, `isDefault`, or `settings`

#### Scenario: Project array export uses API field names
- **WHEN** a project DTO is exported with `toArray()`
- **THEN** the returned array contains API-shaped keys such as `items_count`, `folders`, `size`, `privacy_domains`, `privacy_email_domains`, `privacy_share`, `player_id`, `favorite`, and `encrypted`
- **AND** the returned array does not contain the legacy keys `videos_count`, `folders_count`, `storage_used`, or `allowed_domains`

### Requirement: Use raw project terminology in project list helpers
Project list aggregate helpers SHALL use the raw API terminology for project item counts and sizes.

#### Scenario: Project list totals are computed
- **WHEN** callers need aggregate counts from a `ProjectListResult`
- **THEN** `getTotalItemsCount()` sums `ProjectDTO::$itemsCount`
- **AND** `getTotalSize()` sums `ProjectDTO::$size`

### Requirement: Project CLI JSON uses raw project field names
Project CLI JSON output SHALL use the same raw project field names exposed by `ProjectDTO::toArray()`.

#### Scenario: Project show JSON is rendered
- **WHEN** a user runs `kinescope:project:show <project-id>`
- **THEN** the JSON contains `items_count`, `folders`, `size`, and `privacy_domains`
- **AND** the JSON does not contain `videos_count`, `folders_count`, `storage_used`, or `allowed_domains`
