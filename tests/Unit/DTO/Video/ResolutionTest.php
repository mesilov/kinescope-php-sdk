<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Video;

use InvalidArgumentException;
use Kinescope\DTO\Video\Resolution;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResolutionTest extends TestCase
{
    public function testConstructsWithPositiveDimensions(): void
    {
        $resolution = new Resolution(width: 1920, height: 1080);

        self::assertSame(1920, $resolution->width);
        self::assertSame(1080, $resolution->height);
    }

    /**
     * @return iterable<string, array{width: int, height: int}>
     */
    public static function invalidConstructorCases(): iterable
    {
        yield 'zero width' => ['width' => 0, 'height' => 1080];
        yield 'negative width' => ['width' => -1, 'height' => 1080];
        yield 'zero height' => ['width' => 1920, 'height' => 0];
        yield 'negative height' => ['width' => 1920, 'height' => -1];
    }

    #[DataProvider('invalidConstructorCases')]
    public function testConstructorRejectsNonPositiveDimensions(int $width, int $height): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Resolution(width: $width, height: $height);
    }

    public function testTryFromStringParsesCanonicalShape(): void
    {
        $resolution = Resolution::tryFromString('1920x1080');

        self::assertNotNull($resolution);
        self::assertSame(1920, $resolution->width);
        self::assertSame(1080, $resolution->height);
    }

    /**
     * @return iterable<string, array{value: string}>
     */
    public static function malformedStringCases(): iterable
    {
        yield 'empty string' => ['value' => ''];
        yield 'quality label' => ['value' => '1080p'];
        yield 'unicode times sign' => ['value' => '1920×1080'];
        yield 'missing height' => ['value' => '1920x'];
        yield 'missing width' => ['value' => 'x1080'];
        yield 'trailing junk' => ['value' => '1920x1080p'];
        yield 'leading junk' => ['value' => 'res:1920x1080'];
        yield 'negative numbers' => ['value' => '-1920x1080'];
        yield 'decimal' => ['value' => '1920.0x1080'];
        yield 'random text' => ['value' => 'abc'];
    }

    #[DataProvider('malformedStringCases')]
    public function testTryFromStringReturnsNullForMalformedInput(string $value): void
    {
        self::assertNull(Resolution::tryFromString($value));
    }

    public function testFromStringParsesCanonicalShape(): void
    {
        $resolution = Resolution::fromString('1280x720');

        self::assertSame(1280, $resolution->width);
        self::assertSame(720, $resolution->height);
    }

    #[DataProvider('malformedStringCases')]
    public function testFromStringThrowsOnMalformedInput(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        Resolution::fromString($value);
    }

    public function testToStringFormatsAsWidthByHeight(): void
    {
        $resolution = new Resolution(width: 852, height: 480);

        self::assertSame('852x480', (string) $resolution);
    }

    public function testAspectRatioIsWidthOverHeight(): void
    {
        $resolution = new Resolution(width: 1920, height: 1080);

        self::assertEqualsWithDelta(16 / 9, $resolution->aspectRatio(), 0.0001);
    }

    /**
     * @return iterable<string, array{height: int, isHd: bool, isFullHd: bool, is4K: bool}>
     */
    public static function heightPredicateCases(): iterable
    {
        yield 'sd 480p' => ['height' => 480, 'isHd' => false, 'isFullHd' => false, 'is4K' => false];
        yield 'just-below hd 719' => ['height' => 719, 'isHd' => false, 'isFullHd' => false, 'is4K' => false];
        yield 'hd 720p' => ['height' => 720, 'isHd' => true, 'isFullHd' => false, 'is4K' => false];
        yield 'full hd 1080p' => ['height' => 1080, 'isHd' => true, 'isFullHd' => true, 'is4K' => false];
        yield 'just-below 4k 2159' => ['height' => 2159, 'isHd' => true, 'isFullHd' => true, 'is4K' => false];
        yield '4k 2160p' => ['height' => 2160, 'isHd' => true, 'isFullHd' => true, 'is4K' => true];
    }

    #[DataProvider('heightPredicateCases')]
    public function testHeightPredicates(int $height, bool $isHd, bool $isFullHd, bool $is4K): void
    {
        $resolution = new Resolution(width: 1920, height: $height);

        self::assertSame($isHd, $resolution->isHd());
        self::assertSame($isFullHd, $resolution->isFullHd());
        self::assertSame($is4K, $resolution->is4K());
    }
}
