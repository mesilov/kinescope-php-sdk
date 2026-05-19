<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Videos;

use InvalidArgumentException;
use Kinescope\DTO\Video\VideoDTO;
use Kinescope\Services\Videos\InMemoryVideoSearch;
use Kinescope\Services\Videos\VideoSlugExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InMemoryVideoSearchTest extends TestCase
{
    public function testDefaultConstructionFindsByCanonicalEmbedLink(): void
    {
        $video = $this->video(
            id: 'video-1',
            title: 'Video 1',
            embedLink: 'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB',
        );

        $result = new InMemoryVideoSearch()->byEmbedLink(
            [$video],
            'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB',
        );

        self::assertSame($video, $result);
    }

    public function testExplicitSlugExtractorCanBeProvided(): void
    {
        $video = $this->video(
            id: 'video-1',
            title: 'Video 1',
            embedLink: 'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB',
        );

        $result = new InMemoryVideoSearch(new VideoSlugExtractor())->byEmbedLink(
            [$video],
            'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB',
        );

        self::assertSame($video, $result);
    }

    public function testByEmbedLinkMatchesVideoExposingSlugThroughPlayLink(): void
    {
        $video = $this->video(
            id: 'video-1',
            title: 'Video 1',
            playLink: 'https://kinescope.io/oDko3nwPjHwpzmqUgxJmKB',
        );

        $result = new InMemoryVideoSearch()->byEmbedLink(
            [$video],
            'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB',
        );

        self::assertSame($video, $result);
    }

    public function testByEmbedLinkMatchesVideoExposingSlugThroughHlsLink(): void
    {
        $video = $this->video(
            id: 'video-1',
            title: 'Video 1',
            hlsLink: 'https://kinescope.io/oDko3nwPjHwpzmqUgxJmKB/master.m3u8',
        );

        $result = new InMemoryVideoSearch()->byEmbedLink(
            [$video],
            'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB',
        );

        self::assertSame($video, $result);
    }

    public function testByEmbedLinkReturnsFirstVideoForDuplicateSlugs(): void
    {
        $first = $this->video(
            id: 'video-1',
            title: 'Video 1',
            embedLink: 'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB',
        );
        $second = $this->video(
            id: 'video-2',
            title: 'Video 2',
            playLink: 'https://kinescope.io/oDko3nwPjHwpzmqUgxJmKB',
        );

        $result = new InMemoryVideoSearch()->byEmbedLink(
            [$first, $second],
            'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB',
        );

        self::assertSame($first, $result);
    }

    public function testByEmbedLinkReturnsNullWhenCanonicalUrlHasNoMatch(): void
    {
        $video = $this->video(
            id: 'video-1',
            title: 'Video 1',
            embedLink: 'https://kinescope.io/embed/anotherSlug',
        );

        $result = new InMemoryVideoSearch()->byEmbedLink(
            [$video],
            'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB',
        );

        self::assertNull($result);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonCanonicalEmbedInputProvider(): iterable
    {
        yield 'query string' => ['https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB?autoplay=1'];
        yield 'fragment' => ['https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB#t=10'];
        yield 'trailing slash' => ['https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB/'];
        yield 'iframe html' => ['<iframe src="https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB"></iframe>'];
        yield 'play link' => ['https://kinescope.io/oDko3nwPjHwpzmqUgxJmKB'];
    }

    #[DataProvider('nonCanonicalEmbedInputProvider')]
    public function testByEmbedLinkReturnsNullForNonCanonicalInput(string $embedLink): void
    {
        $video = $this->video(
            id: 'video-1',
            title: 'Video 1',
            embedLink: 'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB',
        );

        self::assertNull(new InMemoryVideoSearch()->byEmbedLink([$video], $embedLink));
    }

    public function testByNameIsCaseInsensitive(): void
    {
        $video = $this->video(id: 'video-1', title: '3. Сегментация и Емкость рынка');

        $result = new InMemoryVideoSearch()->byName([$video], 'сегментация');

        self::assertSame([$video], $result);
    }

    public function testByNameTreatsYoAndYeAsEquivalent(): void
    {
        $video = $this->video(id: 'video-1', title: '3. Сегментация и Емкость рынка');

        $result = new InMemoryVideoSearch()->byName([$video], 'ёмкость рынка');

        self::assertSame([$video], $result);
    }

    public function testByNameCollapsesWhitespace(): void
    {
        $video = $this->video(id: 'video-1', title: '3. Сегментация и Емкость рынка');

        $result = new InMemoryVideoSearch()->byName([$video], "Сегментация   и\nЕмкость");

        self::assertSame([$video], $result);
    }

    public function testByNameIgnoresLeadingLessonNumbering(): void
    {
        $video = $this->video(id: 'video-1', title: '3. Сегментация и Емкость рынка');

        $result = new InMemoryVideoSearch()->byName([$video], '1.3 Сегментация и ёмкость рынка');

        self::assertSame([$video], $result);
    }

    public function testByNameReturnsAllMatchesInInputOrderAsZeroBasedList(): void
    {
        $first = $this->video(id: 'video-1', title: '1. Сегментация B2B');
        $second = $this->video(id: 'video-2', title: '2. Конкуренты');
        $third = $this->video(id: 'video-3', title: '3. Сегментация рынка');

        $result = new InMemoryVideoSearch()->byName([
            10 => $first,
            20 => $second,
            30 => $third,
        ], 'сегментация');

        self::assertSame([$first, $third], $result);
    }

    public function testByNameReturnsEmptyArrayWhenNothingMatches(): void
    {
        $video = $this->video(id: 'video-1', title: '2. Конкуренты');

        $result = new InMemoryVideoSearch()->byName([$video], 'сегментация');

        self::assertSame([], $result);
    }

    public function testByEmbedLinkRejectsEmptyInput(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InMemoryVideoSearch()->byEmbedLink([], '   ');
    }

    public function testByNameRejectsEmptyInput(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InMemoryVideoSearch()->byName([], '   ');
    }

    public function testByEmbedLinkRejectsNonVideoItemsBeforeMatching(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InMemoryVideoSearch()->byEmbedLink([
            $this->video(
                id: 'video-1',
                title: 'Video 1',
                embedLink: 'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB',
            ),
            'not-a-video',
        ], 'https://kinescope.io/embed/oDko3nwPjHwpzmqUgxJmKB');
    }

    public function testByNameRejectsNonVideoItemsBeforeMatching(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InMemoryVideoSearch()->byName([
            $this->video(id: 'video-1', title: '3. Сегментация и Емкость рынка'),
            'not-a-video',
        ], 'сегментация');
    }

    private function video(
        string $id,
        string $title,
        ?string $embedLink = null,
        ?string $playLink = null,
        ?string $hlsLink = null,
    ): VideoDTO {
        return VideoDTO::fromArray(array_filter([
            'id' => $id,
            'title' => $title,
            'status' => 'done',
            'duration' => 0,
            'embed_link' => $embedLink,
            'play_link' => $playLink,
            'hls_link' => $hlsLink,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ], static fn (mixed $value): bool => $value !== null));
    }
}
