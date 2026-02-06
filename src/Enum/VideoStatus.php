<?php

declare(strict_types=1);

namespace Kinescope\Enum;

/**
 * Video processing status.
 *
 * Represents the current state of a video in the Kinescope system.
 */
enum VideoStatus: string
{
    /**
     * Video is waiting to be processed.
     */
    case PENDING = 'pending';

    /**
     * Video is currently being uploaded.
     */
    case UPLOADING = 'uploading';

    /**
     * Video is being processed/transcoded.
     */
    case PROCESSING = 'processing';

    /**
     * Video processing is complete and ready for playback.
     */
    case DONE = 'done';

    /**
     * An error occurred during processing.
     */
    case ERROR = 'error';

    /**
     * Check if the video is ready for playback.
     */
    public function isReady(): bool
    {
        return $this === self::DONE;
    }

    /**
     * Check if the video is still being processed.
     */
    public function isProcessing(): bool
    {
        return match ($this) {
            self::PENDING, self::UPLOADING, self::PROCESSING => true,
            self::DONE, self::ERROR => false,
        };
    }

    /**
     * Check if the video has an error.
     */
    public function hasError(): bool
    {
        return $this === self::ERROR;
    }

    /**
     * Get a human-readable label for this status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::UPLOADING => 'Uploading',
            self::PROCESSING => 'Processing',
            self::DONE => 'Done',
            self::ERROR => 'Error',
        };
    }
}
