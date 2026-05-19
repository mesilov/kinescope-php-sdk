## Context

The upstream API field remains `assets[].file_size`; this change does not rename the API field or reject the raw payload. The SDK rename is about semantics: the value is metadata about the video stream/rendition and is not guaranteed to match the downloadable file size on disk.

## Decisions

- Use `videoStreamSize` as the PHP property name and `video_stream_size` as the SDK array/CLI output key.
  - Rationale: the name makes the metadata nature explicit and avoids implying that it is the final file size.
  - Trade-off: this intentionally diverges from the raw API key in SDK exports, which is a narrow exception to the raw-shaped DTO work. The input mapping still keeps the upstream API boundary clear.

- Remove `fileSize` instead of keeping a deprecated alias.
  - Rationale: `0.5.0` is already a breaking CLI/DTO release, and keeping both names would preserve the ambiguity that issue #19 is meant to remove.

- Keep `videoStreamSize` as the `QualityPreference::WORST` ordering signal.
  - Rationale: it remains the best available metadata signal for choosing a smaller rendition before download starts. It is not used as proof of final file size when better transfer data exists.

- Keep `FileTransferRequest::expectedBytes` unchanged.
  - Rationale: it is a generic transfer hint. For Kinescope video downloads it receives the selected asset `videoStreamSize`, but custom transfer implementations may use or ignore it.

- Keep lifecycle event field names unchanged for now.
  - Rationale: event `sizeBytes` / `totalBytes` / `fileSize` describe event payloads, not the asset DTO field. The completed event already uses the real final file size.

## Download Semantics

`VideoDownloader` should treat selected asset `videoStreamSize` as a pre-transfer metadata hint. During transfer:

- transfer-reported bytes are authoritative when present;
- bytes written must match the validation byte count;
- final completion event uses `filesize()` when available, falling back to bytes written;
- a mismatch between `videoStreamSize` and transfer-reported bytes is logged as metadata divergence, not a failed download.
