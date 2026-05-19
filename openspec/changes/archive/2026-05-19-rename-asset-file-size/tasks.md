## 1. Contract Tests

- [x] 1.1 Update asset DTO tests so raw API `file_size` maps to `videoStreamSize`, `toArray()` exports `video_stream_size`, and the public `fileSize` property is absent.
- [x] 1.2 Update quality-selection and downloader tests to use stream-size terminology while preserving behavior.
- [x] 1.3 Update CLI command tests so asset summaries expose `video_stream_size` and do not expose `file_size`.

## 2. Implementation

- [x] 2.1 Rename `AssetDTO::$fileSize` to `videoStreamSize`, update validation messages, human-size helper naming, and array export.
- [x] 2.2 Update `AssetSelector`, `VideoDownloader`, and downloader log/metadata keys to use `videoStreamSize`.
- [x] 2.3 Update CLI asset normalization/sorting/table rows to use `video_stream_size`.
- [x] 2.4 Update remaining unit fixtures and code references to remove public `fileSize` usage.

## 3. Documentation and Specs

- [x] 3.1 Update README and CHANGELOG with the issue #19 breaking rename and real-size semantics.
- [x] 3.2 Update active/raw DTO and downloader OpenSpec wording so the stream metadata exception is explicit.

## 4. Validation

- [x] 4.1 Run focused DTO, downloader, and console unit tests.
- [x] 4.2 Run `make test-unit`.
- [x] 4.3 Run `make lint-all`.
- [x] 4.4 Run `make openspec-validate`.
