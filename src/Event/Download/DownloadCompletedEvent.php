<?php

declare(strict_types=1);

namespace Kinescope\Event\Download;

use Carbon\CarbonImmutable;

final readonly class DownloadCompletedEvent
{
    public function __construct(
        public string $videoId,
        public string $filePath,
        public int $fileSize,
        public int $durationMs,
        public CarbonImmutable $occurredAt,
    ) {
    }
}
