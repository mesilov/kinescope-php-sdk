<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\Services\Videos;

use Kinescope\DTO\Video\VideoDTO;
use Kinescope\Services\Videos\VideoSlugExtractor;
use PHPUnit\Framework\TestCase;

class VideoSlugExtractorTest extends TestCase
{
    private VideoSlugExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new VideoSlugExtractor();
    }

    public function testFromHlsLinkExtractsSlug(): void
    {
        $slug = $this->extractor->fromHlsLink('https://kinescope.io/wDXNmhxAhnJeRtNjAVChzS/master.m3u8');

        $this->assertSame('wDXNmhxAhnJeRtNjAVChzS', $slug);
    }

    public function testFromHlsLinkReturnsNullForNonKinescopeUrl(): void
    {
        $slug = $this->extractor->fromHlsLink('https://example.com/video/master.m3u8');

        $this->assertNull($slug);
    }

    public function testFromHlsLinkReturnsNullWhenMasterM3u8Absent(): void
    {
        $slug = $this->extractor->fromHlsLink('https://kinescope.io/wDXNmhxAhnJeRtNjAVChzS/playlist.m3u8');

        $this->assertNull($slug);
    }

    public function testFromEmbedCodeExtractsSlug(): void
    {
        $embedCode = '<iframe src="https://kinescope.io/embed/wDXNmhxAhnJeRtNjAVChzS" allowfullscreen></iframe>';

        $slug = $this->extractor->fromEmbedCode($embedCode);

        $this->assertSame('wDXNmhxAhnJeRtNjAVChzS', $slug);
    }

    public function testFromEmbedCodeReturnsNullForNonKinescopeEmbed(): void
    {
        $embedCode = '<iframe src="https://youtube.com/embed/abc123" allowfullscreen></iframe>';

        $slug = $this->extractor->fromEmbedCode($embedCode);

        $this->assertNull($slug);
    }

    public function testFromVideoDtoExtractsSlugFromHlsLink(): void
    {
        $video = VideoDTO::fromArray([
            'id' => 'video-1',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 0,
            'hls_link' => 'https://kinescope.io/wDXNmhxAhnJeRtNjAVChzS/master.m3u8',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $slug = $this->extractor->fromVideoDTO($video);

        $this->assertSame('wDXNmhxAhnJeRtNjAVChzS', $slug);
    }

    public function testFromVideoDtoFallsBackToEmbedCode(): void
    {
        $video = VideoDTO::fromArray([
            'id' => 'video-2',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 0,
            'embed_link' => 'https://kinescope.io/embed/embedSlug123',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $slug = $this->extractor->fromVideoDTO($video);

        $this->assertSame('embedSlug123', $slug);
    }

    public function testFromVideoDtoPrioritizesHlsLinkOverEmbedCode(): void
    {
        $video = VideoDTO::fromArray([
            'id' => 'video-3',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 0,
            'hls_link' => 'https://kinescope.io/hlsSlug456/master.m3u8',
            'embed_link' => 'https://kinescope.io/embed/embedSlug789',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $slug = $this->extractor->fromVideoDTO($video);

        $this->assertSame('hlsSlug456', $slug);
    }

    public function testFromVideoDtoReturnsNullWhenBothAbsent(): void
    {
        $video = VideoDTO::fromArray([
            'id' => 'video-4',
            'title' => 'Test',
            'status' => 'done',
            'duration' => 0,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ]);

        $slug = $this->extractor->fromVideoDTO($video);

        $this->assertNull($slug);
    }
}
