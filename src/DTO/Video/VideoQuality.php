<?php

declare(strict_types=1);

namespace Kinescope\DTO\Video;

/**
 * Value object representing a video resolution by height.
 */
final readonly class VideoQuality
{
    public function __construct(
        public int $height,
    ) {
    }

    public static function p360(): self
    {
        return new self(360);
    }

    public static function p480(): self
    {
        return new self(480);
    }

    public static function p720(): self
    {
        return new self(720);
    }

    public static function p1080(): self
    {
        return new self(1080);
    }

    public static function p4k(): self
    {
        return new self(2160);
    }
}
