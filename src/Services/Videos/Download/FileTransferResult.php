<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos\Download;

final readonly class FileTransferResult
{
    public function __construct(
        public string $outputPath,
        public int $bytesWritten,
        public ?int $reportedBytes = null,
    ) {
    }
}
