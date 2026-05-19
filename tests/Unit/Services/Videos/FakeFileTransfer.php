<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Videos;

use Kinescope\Services\Videos\Download\FileTransferInterface;
use Kinescope\Services\Videos\Download\FileTransferProgress;
use Kinescope\Services\Videos\Download\FileTransferRequest;
use Kinescope\Services\Videos\Download\FileTransferResult;
use RuntimeException;
use Throwable;

final class FakeFileTransfer implements FileTransferInterface
{
    /**
     * @var list<FileTransferRequest>
     */
    private array $requests = [];

    /**
     * @param list<int> $progressBytes
     */
    public function __construct(
        private readonly ?int $bytesWritten = null,
        private readonly ?int $reportedBytes = null,
        private readonly ?Throwable $exception = null,
        private readonly ?int $partialBytesBeforeFailure = null,
        private readonly array $progressBytes = [],
        private readonly ?int $progressTotalBytes = null,
        private readonly string $byte = 'a',
    ) {
    }

    public function transfer(FileTransferRequest $request, ?callable $onProgress = null): FileTransferResult
    {
        $this->requests[] = $request;

        foreach ($this->progressBytes as $bytesWritten) {
            if ($onProgress !== null) {
                $onProgress(new FileTransferProgress(
                    bytesWritten: $bytesWritten,
                    totalBytes: $this->progressTotalBytes ?? $request->expectedBytes,
                ));
            }
        }

        if ($this->exception !== null) {
            if ($this->partialBytesBeforeFailure !== null) {
                file_put_contents($request->outputPath, str_repeat($this->byte, $this->partialBytesBeforeFailure));
            }

            throw $this->exception;
        }

        $bytesWritten = $this->bytesWritten ?? $request->expectedBytes ?? 0;
        file_put_contents($request->outputPath, str_repeat($this->byte, $bytesWritten));

        return new FileTransferResult(
            outputPath: $request->outputPath,
            bytesWritten: $bytesWritten,
            reportedBytes: $this->reportedBytes ?? $request->expectedBytes,
        );
    }

    public function requestCount(): int
    {
        return count($this->requests);
    }

    public function requestAt(int $index): FileTransferRequest
    {
        return $this->requests[$index] ?? throw new RuntimeException(sprintf(
            'No file-transfer request recorded at index %d.',
            $index,
        ));
    }
}
