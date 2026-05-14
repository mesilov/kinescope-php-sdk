<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Enum;

use Kinescope\Enum\VideoStatus;
use PHPUnit\Framework\TestCase;

final class VideoStatusTest extends TestCase
{
    public function testParsesEveryDocumentedStatus(): void
    {
        $this->assertSame(VideoStatus::PENDING, VideoStatus::from('pending'));
        $this->assertSame(VideoStatus::UPLOADING, VideoStatus::from('uploading'));
        $this->assertSame(VideoStatus::PRE_PROCESSING, VideoStatus::from('pre-processing'));
        $this->assertSame(VideoStatus::PROCESSING, VideoStatus::from('processing'));
        $this->assertSame(VideoStatus::ABORTED, VideoStatus::from('aborted'));
        $this->assertSame(VideoStatus::DONE, VideoStatus::from('done'));
        $this->assertSame(VideoStatus::ERROR, VideoStatus::from('error'));
    }

    public function testDoneIsOnlyReadyStatus(): void
    {
        foreach (VideoStatus::cases() as $status) {
            $this->assertSame($status === VideoStatus::DONE, $status->isReady());
        }
    }

    public function testProcessingLikeStatusesAreGrouped(): void
    {
        $processingStatuses = [
            VideoStatus::PENDING,
            VideoStatus::UPLOADING,
            VideoStatus::PRE_PROCESSING,
            VideoStatus::PROCESSING,
        ];

        foreach (VideoStatus::cases() as $status) {
            $this->assertSame(in_array($status, $processingStatuses, true), $status->isProcessing());
        }
    }

    public function testErrorIsOnlyErrorStatus(): void
    {
        foreach (VideoStatus::cases() as $status) {
            $this->assertSame($status === VideoStatus::ERROR, $status->hasError());
        }
    }

    public function testLabelsCoverEveryDocumentedStatus(): void
    {
        $this->assertSame('Pending', VideoStatus::PENDING->getLabel());
        $this->assertSame('Uploading', VideoStatus::UPLOADING->getLabel());
        $this->assertSame('Pre-processing', VideoStatus::PRE_PROCESSING->getLabel());
        $this->assertSame('Processing', VideoStatus::PROCESSING->getLabel());
        $this->assertSame('Aborted', VideoStatus::ABORTED->getLabel());
        $this->assertSame('Done', VideoStatus::DONE->getLabel());
        $this->assertSame('Error', VideoStatus::ERROR->getLabel());
    }
}
