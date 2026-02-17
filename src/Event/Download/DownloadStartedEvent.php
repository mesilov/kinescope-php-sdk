<?php

declare(strict_types=1);

namespace Kinescope\Event\Download;

use Carbon\CarbonImmutable;
use Kinescope\Enum\QualityPreference;

final readonly class DownloadStartedEvent
{
    public function __construct(
        public string $videoId,
        public string $downloadUrl,
        public int $sizeBytes,
        public QualityPreference $qualityPreference,
        public int $selectedHeight,
        public CarbonImmutable $occurredAt,
    ) {
    }
}
