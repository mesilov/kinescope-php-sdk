<?php

declare(strict_types=1);

namespace Kinescope\Event\Download;

use Carbon\CarbonImmutable;

final readonly class DownloadProgressEvent
{
    public function __construct(
        public string $videoId,
        public string $filePath,
        public int $bytesWritten,
        public int $sizeBytes,
        public float $percent,
        public CarbonImmutable $occurredAt,
    ) {
    }
}
