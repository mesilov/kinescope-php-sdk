<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Video;

use Kinescope\DTO\Video\VideoQuality;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(VideoQuality::class)]
final class VideoQualityTest extends TestCase
{
    public function testConstructorSetsHeight(): void
    {
        $quality = new VideoQuality(1440);

        $this->assertSame(1440, $quality->height);
    }

    public function testP360Factory(): void
    {
        $quality = VideoQuality::p360();

        $this->assertSame(360, $quality->height);
    }

    public function testP480Factory(): void
    {
        $quality = VideoQuality::p480();

        $this->assertSame(480, $quality->height);
    }

    public function testP720Factory(): void
    {
        $quality = VideoQuality::p720();

        $this->assertSame(720, $quality->height);
    }

    public function testP1080Factory(): void
    {
        $quality = VideoQuality::p1080();

        $this->assertSame(1080, $quality->height);
    }

    public function testP4kFactory(): void
    {
        $quality = VideoQuality::p4k();

        $this->assertSame(2160, $quality->height);
    }
}
