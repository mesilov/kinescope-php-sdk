## ADDED Requirements

### Requirement: Parse nested pagination metadata
The SDK SHALL parse pagination totals and current page settings only from `meta.pagination.total`, `meta.pagination.page`, and `meta.pagination.per_page` when paginated list endpoints return the current Kinescope API metadata shape.

#### Scenario: Nested metadata provides list totals
- **WHEN** `MetaDTO::fromArray()` receives metadata containing `pagination.total`, `pagination.page`, and `pagination.per_page`
- **THEN** the resulting metadata exposes those values through `total`, `pagination->page`, and `pagination->perPage`

#### Scenario: Paginated response exposes nested metadata values
- **WHEN** a list result DTO is created from a response with nested `meta.pagination` values
- **THEN** `PaginatedResponse::getTotal()`, `getCurrentPage()`, and `getPerPage()` return the nested metadata values

### Requirement: Reject malformed pagination metadata
The SDK SHALL fail explicitly when a paginated list response metadata object does not contain the required `pagination.total`, `pagination.page`, and `pagination.per_page` keys.

#### Scenario: Flat metadata is rejected
- **WHEN** `MetaDTO::fromArray()` receives metadata containing flat `total`, `page`, and `per_page` keys without `pagination`
- **THEN** metadata parsing fails with an explicit error

#### Scenario: Pagination object is absent
- **WHEN** `MetaDTO::fromArray()` receives metadata without a `pagination` object
- **THEN** metadata parsing fails with an explicit error

#### Scenario: Required pagination key is absent
- **WHEN** `MetaDTO::fromArray()` receives metadata with `pagination` missing `total`, `page`, or `per_page`
- **THEN** metadata parsing fails with an explicit error

### Requirement: Calculate page navigation without last_page
The SDK SHALL calculate the last page and next-page state from `total` and `per_page` when `last_page` is absent from metadata.

#### Scenario: Nested metadata has additional pages
- **WHEN** metadata has `pagination.page` equal to `1`, `pagination.per_page` equal to `100`, `pagination.total` equal to `2156`, and no `last_page`
- **THEN** `getLastPage()` returns `22` and `hasNextPage()` returns `true`

#### Scenario: Nested metadata fits on the current page
- **WHEN** metadata has `pagination.page` equal to `1`, `pagination.per_page` equal to `100`, `pagination.total` equal to `45`, and no `last_page`
- **THEN** `getLastPage()` returns `1` and `hasNextPage()` returns `false`

### Requirement: Exclude non-paginated responses
The SDK SHALL NOT force API responses without pagination metadata into the paginated response contract.

#### Scenario: Playlist entities are not paginated
- **WHEN** `/v1/playlists/{playlist_id}/entities` returns `data` without `meta`
- **THEN** playlist entity parsing is handled outside `MetaDTO::fromArray()` and `PaginatedResponse`
