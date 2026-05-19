# video-list-status-filter Specification

## Purpose
Defines the documented video status enum values and the typed `VideoStatus` contract used by `Videos::list()` status filtering and statistics aggregation.

## Requirements

### Requirement: Model documented video statuses
The SDK SHALL expose every documented `/v1/videos` status value through `Kinescope\Enum\VideoStatus`.

#### Scenario: VideoStatus parses every documented status
- **WHEN** `VideoStatus::from()` receives `pending`, `uploading`, `pre-processing`, `processing`, `aborted`, `done`, or `error`
- **THEN** it returns a corresponding `VideoStatus` case without raising `ValueError`

#### Scenario: DONE is the only ready status
- **WHEN** a consumer calls `VideoStatus::isReady()`
- **THEN** it returns `true` only for `VideoStatus::DONE`

#### Scenario: Processing-like statuses are grouped
- **WHEN** a consumer calls `VideoStatus::isProcessing()`
- **THEN** it returns `true` for `PENDING`, `UPLOADING`, `PRE_PROCESSING`, and `PROCESSING`, and `false` for `ABORTED`, `DONE`, and `ERROR`

#### Scenario: ERROR remains the only error status
- **WHEN** a consumer calls `VideoStatus::hasError()`
- **THEN** it returns `true` only for `VideoStatus::ERROR`

### Requirement: Use VideoStatus for video list status filters
The SDK SHALL accept one `?VideoStatus` argument for `Videos::list()` status filtering and SHALL NOT accept arbitrary raw strings or status arrays for that argument.

#### Scenario: Videos::list accepts a VideoStatus filter
- **WHEN** a consumer calls `Videos::list(status: VideoStatus::DONE)`
- **THEN** the request is sent with the documented `status[]` query key containing the single scalar value `done`

#### Scenario: Videos::list omits status when no status is provided
- **WHEN** a consumer calls `Videos::list(status: null)`
- **THEN** the request omits the `status[]` query parameter

#### Scenario: Raw status strings are no longer source-compatible
- **WHEN** existing consumer code calls `Videos::list(status: 'done')`
- **THEN** the call is no longer source-compatible with the SDK method signature and must migrate to `Videos::list(status: VideoStatus::DONE)`

### Requirement: Statistics uses the typed DONE status
The SDK SHALL build statistics from `done` videos by passing `VideoStatus::DONE` into `Videos::list()`.

#### Scenario: Statistics delegates with VideoStatus::DONE
- **WHEN** any public `Statistics` aggregation method paginates videos
- **THEN** every delegated `Videos::list()` call uses `status: VideoStatus::DONE`
