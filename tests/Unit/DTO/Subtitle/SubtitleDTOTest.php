<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Subtitle;

use Kinescope\DTO\Video\SubtitleDTO;
use Kinescope\Enum\SubtitleLanguage;
use PHPUnit\Framework\TestCase;

final class SubtitleDTOTest extends TestCase
{
    public function testFromArrayMapsCurrentSubtitleEndpointPayload(): void
    {
        $subtitle = SubtitleDTO::fromArray($this->payload());

        self::assertSame('subtitle-id', $subtitle->id);
        self::assertSame('video-id', $subtitle->videoId);
        self::assertSame('Автоматические', $subtitle->description);
        self::assertSame(SubtitleLanguage::RU, $subtitle->language);
        self::assertSame('done', $subtitle->status);
        self::assertSame(1, $subtitle->position);
        self::assertTrue($subtitle->active);
        self::assertSame('subtitle-id.vtt', $subtitle->fileName);
    }

    public function testToArrayUsesRawApiFieldNames(): void
    {
        $array = SubtitleDTO::fromArray($this->payload())->toArray();

        self::assertSame('subtitle-id', $array['id']);
        self::assertSame('video-id', $array['video_id']);
        self::assertSame('ru', $array['language']);
        self::assertSame('subtitle-id.vtt', $array['file_name']);
        self::assertArrayNotHasKey('title', $array);
        self::assertArrayNotHasKey('format', $array);
        self::assertArrayNotHasKey('is_default', $array);
    }

    public function testLanguageAndUrlHelpers(): void
    {
        $subtitle = SubtitleDTO::fromArray($this->payload());
        $unknown = SubtitleDTO::fromArray($this->payload(language: 'xx', url: ''));

        self::assertTrue($subtitle->hasKnownLanguage());
        self::assertSame('Russian', $subtitle->getLanguageName());
        self::assertSame('ru', $subtitle->getLanguageCode());
        self::assertTrue($subtitle->hasUrl());
        self::assertTrue($subtitle->hasTranscript());
        self::assertFalse($unknown->hasKnownLanguage());
        self::assertSame('xx', $unknown->getLanguageCode());
        self::assertFalse($unknown->hasUrl());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $language = 'ru', string $url = 'https://kinescope.io/subtitles/file.vtt'): array
    {
        return [
            'id' => 'subtitle-id',
            'video_id' => 'video-id',
            'description' => 'Автоматические',
            'language' => $language,
            'status' => 'done',
            'position' => 1,
            'data' => [],
            'active' => true,
            'url' => $url,
            'updated_at' => '2026-03-06T08:01:19.843367Z',
            'file' => '',
            'file_name' => 'subtitle-id.vtt',
            'hls_file' => '',
            'transcribe_url' => 'https://kinescope.io/transcript/file.txt',
            'download_filename' => 'video-ru-subtitles.vtt',
            'transcribe_download_filename' => 'video-ru-transcript.txt',
        ];
    }
}
