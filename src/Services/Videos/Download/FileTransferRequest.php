<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos\Download;

final readonly class FileTransferRequest
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $url,
        public string $outputPath,
        public ?int $expectedBytes = null,
        public array $headers = [],
    ) {
    }
}
