<?php

declare(strict_types=1);

namespace Kinescope\DTO\Video;

use InvalidArgumentException;
use Stringable;

/**
 * Pixel dimensions of a video asset.
 *
 * Both `width` and `height` are positive integers. Parse helpers accept the
 * canonical `"<width>x<height>"` shape returned by the Kinescope API.
 */
final readonly class Resolution implements Stringable
{
    private const string PATTERN = '/^(\d+)x(\d+)$/';

    /**
     * @param int $width Positive width in pixels
     * @param int $height Positive height in pixels
     */
    public function __construct(
        public int $width,
        public int $height,
    ) {
        if ($this->width <= 0) {
            throw new InvalidArgumentException(sprintf('Resolution "width" must be greater than 0, got %d.', $this->width));
        }

        if ($this->height <= 0) {
            throw new InvalidArgumentException(sprintf('Resolution "height" must be greater than 0, got %d.', $this->height));
        }
    }

    public function __toString(): string
    {
        return sprintf('%dx%d', $this->width, $this->height);
    }

    /**
     * Parse a `"<width>x<height>"` string into a Resolution, returning null on malformed input.
     */
    public static function tryFromString(string $value): ?self
    {
        if (preg_match(self::PATTERN, $value, $matches) !== 1) {
            return null;
        }

        $width = (int) $matches[1];
        $height = (int) $matches[2];

        if ($width <= 0 || $height <= 0) {
            return null;
        }

        return new self(width: $width, height: $height);
    }

    /**
     * Parse a `"<width>x<height>"` string into a Resolution, throwing on malformed input.
     *
     * @throws InvalidArgumentException
     */
    public static function fromString(string $value): self
    {
        $resolution = self::tryFromString($value);

        if ($resolution === null) {
            throw new InvalidArgumentException(sprintf('Resolution "%s" does not match "<width>x<height>" with positive integers.', $value));
        }

        return $resolution;
    }

    public function aspectRatio(): float
    {
        return $this->width / $this->height;
    }

    public function isHd(): bool
    {
        return $this->height >= 720;
    }

    public function isFullHd(): bool
    {
        return $this->height >= 1080;
    }

    public function is4K(): bool
    {
        return $this->height >= 2160;
    }
}
