<?php

declare(strict_types=1);

namespace Kinescope\Event\Download;

use Carbon\CarbonImmutable;
use Throwable;

final readonly class DownloadFailedEvent
{
    public function __construct(
        public string $videoId,
        public ?string $filePath,
        public int $totalBytes,
        public int $bytesWritten,
        public Throwable $exception,
        public CarbonImmutable $occurredAt,
    ) {
    }
}
