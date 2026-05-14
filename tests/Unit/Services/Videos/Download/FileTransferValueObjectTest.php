<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Videos\Download;

use Kinescope\Services\Videos\Download\FileTransferInterface;
use Kinescope\Services\Videos\Download\FileTransferProgress;
use Kinescope\Services\Videos\Download\FileTransferRequest;
use Kinescope\Services\Videos\Download\FileTransferResult;
use PHPUnit\Framework\TestCase;

final class FileTransferValueObjectTest extends TestCase
{
    public function testRequestStoresUrlOutputPathExpectedBytesAndHeaders(): void
    {
        $request = new FileTransferRequest(
            url: 'https://cdn.example.test/video.mp4',
            outputPath: '/tmp/video.mp4.part',
            expectedBytes: 123,
            headers: ['Range' => 'bytes=0-122'],
        );

        $this->assertSame('https://cdn.example.test/video.mp4', $request->url);
        $this->assertSame('/tmp/video.mp4.part', $request->outputPath);
        $this->assertSame(123, $request->expectedBytes);
        $this->assertSame(['Range' => 'bytes=0-122'], $request->headers);
    }

    public function testProgressPercentIsNullableWithoutTotalBytes(): void
    {
        $progress = new FileTransferProgress(bytesWritten: 50, totalBytes: null);

        $this->assertSame(50, $progress->bytesWritten);
        $this->assertNull($progress->totalBytes);
        $this->assertNull($progress->percent());
    }

    public function testProgressPercentIsCalculatedFromTotalBytes(): void
    {
        $progress = new FileTransferProgress(bytesWritten: 512, totalBytes: 1024);

        $this->assertSame(50.0, $progress->percent());
    }

    public function testResultStoresOutputPathWrittenBytesAndReportedBytes(): void
    {
        $result = new FileTransferResult(
            outputPath: '/tmp/video.mp4.part',
            bytesWritten: 1024,
            reportedBytes: 2048,
        );

        $this->assertSame('/tmp/video.mp4.part', $result->outputPath);
        $this->assertSame(1024, $result->bytesWritten);
        $this->assertSame(2048, $result->reportedBytes);
    }

    public function testInterfaceAcceptsNullableProgressCallback(): void
    {
        $transfer = new class () implements FileTransferInterface {
            public ?FileTransferRequest $request = null;

            /**
             * @var (callable(FileTransferProgress): void)|null
             */
            public $onProgress = null;

            public function transfer(FileTransferRequest $request, ?callable $onProgress = null): FileTransferResult
            {
                $this->request = $request;
                $this->onProgress = $onProgress;

                return new FileTransferResult($request->outputPath, 0, null);
            }
        };

        $request = new FileTransferRequest(
            url: 'https://cdn.example.test/video.mp4',
            outputPath: '/tmp/video.mp4.part',
            expectedBytes: null,
        );

        $result = $transfer->transfer($request);

        $this->assertSame($request, $transfer->request);
        $this->assertNull($transfer->onProgress);
        $this->assertSame('/tmp/video.mp4.part', $result->outputPath);
    }
}
