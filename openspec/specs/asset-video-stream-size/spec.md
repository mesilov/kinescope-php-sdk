# asset-video-stream-size Specification

## Purpose
Clarify that the Kinescope asset metadata value from raw API `file_size` is exposed by the SDK as video stream size metadata, not as the final downloaded file size on disk.

## Requirements
### Requirement: Expose asset metadata size as video stream size
The SDK SHALL expose the Kinescope asset metadata value from raw API `assets[].file_size` as video stream size metadata, not as the final downloadable file size.

#### Scenario: Raw API file_size maps to videoStreamSize
- **WHEN** an asset payload contains raw API field `file_size`
- **THEN** `AssetDTO` exposes the value as `videoStreamSize`
- **AND** `AssetDTO` does not expose a public `fileSize` property

#### Scenario: Asset array export uses SDK stream-size name
- **WHEN** an asset DTO is exported to an array
- **THEN** the exported array contains `video_stream_size`
- **AND** the exported array does not contain `file_size`

#### Scenario: Asset stream size is documented as metadata
- **WHEN** a consumer reads the `AssetDTO::$videoStreamSize` PHPDoc
- **THEN** it states that the value is Kinescope stream metadata and is not guaranteed to equal the downloaded file size on disk
- **AND** it directs file operations to use HTTP `Content-Length`, transfer-reported bytes, bytes written, or final `filesize()`

#### Scenario: CLI asset output uses stream-size names
- **WHEN** a user lists video assets through the SDK CLI
- **THEN** asset summaries contain `video_stream_size` and `video_stream_size_mb`
- **AND** asset summaries do not contain `file_size` or `file_size_mb`
