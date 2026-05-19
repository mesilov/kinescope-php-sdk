## MODIFIED Requirements

### Requirement: Allow custom file-transfer injection
The SDK SHALL allow consumers to inject a custom file-transfer implementation for video downloads.

#### Scenario: Consumer injects a framework streaming transfer
- **WHEN** a consumer injects a custom transfer implementation that streams with a framework HTTP client
- **THEN** `VideoDownloader` uses that implementation for the selected video file bytes instead of its default transfer

#### Scenario: Custom transfer receives selected asset metadata
- **WHEN** `VideoDownloader` invokes a custom transfer implementation
- **THEN** the transfer request includes the selected asset download URL, temporary output path, and selected asset video stream size as the expected byte-count hint

#### Scenario: Transfer exceptions propagate through the downloader workflow
- **WHEN** a custom transfer implementation fails with an exception
- **THEN** `VideoDownloader` dispatches the download-failed event and propagates the original exception

### Requirement: Validate completed transfers
The SDK SHALL validate completed video file transfers in `VideoDownloader` before reporting download completion, using the transfer-reported byte count when available and the selected asset video stream size only as a fallback metadata hint.

#### Scenario: Transfer request includes selected asset stream size
- **WHEN** `VideoDownloader` creates a file-transfer request for a selected asset
- **THEN** it sets the request expected byte count to the selected asset `videoStreamSize`

#### Scenario: Successful transfer matches validation byte count
- **WHEN** the transfer result reports a completed byte count
- **THEN** `VideoDownloader` uses that transfer-reported byte count as the validation byte count
- **AND** when the transfer result does not report a completed byte count, `VideoDownloader` uses the selected asset `videoStreamSize` as the validation byte count fallback
- **AND** when written bytes match the validation byte count
- **THEN** `VideoDownloader` renames the `.part` file to the final destination path and completes successfully

#### Scenario: Stream metadata differs from transfer size
- **WHEN** the transfer result reports a completed byte count that differs from the selected asset `videoStreamSize`
- **AND** written bytes match the transfer-reported byte count
- **THEN** `VideoDownloader` completes successfully and does not treat the stream metadata mismatch as a failed download

#### Scenario: Incomplete transfer fails
- **WHEN** the transfer result reports fewer or more written bytes than the validation byte count
- **THEN** the download fails with a `KinescopeException`

#### Scenario: Custom transfer cannot bypass completed-size validation
- **WHEN** a custom transfer implementation returns a successful result with a written byte count that differs from its reported completed byte count or, when absent, from the selected asset `videoStreamSize`
- **THEN** `VideoDownloader` fails the download with a `KinescopeException`

#### Scenario: Failed transfer does not report completion
- **WHEN** a transfer fails before completion validation succeeds
- **THEN** `VideoDownloader` does not dispatch the download-completed event
