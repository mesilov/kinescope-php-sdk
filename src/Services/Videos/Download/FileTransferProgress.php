<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos\Download;

final readonly class FileTransferProgress
{
    public function __construct(
        public int $bytesWritten,
        public ?int $totalBytes,
    ) {
    }

    public function percent(): ?float
    {
        if ($this->totalBytes === null || $this->totalBytes <= 0) {
            return null;
        }

        return round($this->bytesWritten / $this->totalBytes * 100, 1);
    }
}
