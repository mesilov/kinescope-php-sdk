<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Video;

use Kinescope\DTO\Video\AssetDTO;
use Kinescope\DTO\Video\SubtitleDTO;
use Kinescope\DTO\Video\VideoDTO;
use Kinescope\Enum\PrivacyType;
use Kinescope\Enum\VideoStatus;
use PHPUnit\Framework\TestCase;

final class VideoDTOTest extends TestCase
{
    public function testFromArrayMapsCurrentApiPayload(): void
    {
        $video = VideoDTO::fromArray($this->payload());

        self::assertSame('video-id', $video->id);
        self::assertSame('project-id', $video->projectId);
        self::assertSame('folder-id', $video->folderId);
        self::assertSame('player-id', $video->playerId);
        self::assertSame(1, $video->version);
        self::assertSame(VideoStatus::DONE, $video->status);
        self::assertSame(183.13579, $video->duration);
        self::assertSame(PrivacyType::CUSTOM, $video->privacyType);
        self::assertContainsOnlyInstancesOf(AssetDTO::class, $video->assets);
        self::assertContainsOnlyInstancesOf(SubtitleDTO::class, $video->subtitles);
    }

    public function testToArrayUsesCurrentApiFieldNames(): void
    {
        $array = VideoDTO::fromArray($this->payload())->toArray();

        self::assertSame('https://kinescope.io/slug', $array['play_link']);
        self::assertSame('https://kinescope.io/embed/slug', $array['embed_link']);
        self::assertArrayHasKey('poster', $array);
        self::assertArrayHasKey('privacy_domains', $array);
        self::assertArrayHasKey('subtitles', $array);
        self::assertArrayNotHasKey('embed_code', $array);
        self::assertArrayNotHasKey('dash_link', $array);
        self::assertArrayNotHasKey('poster_url', $array);
        self::assertArrayNotHasKey('views_count', $array);
    }

    public function testStatusDurationAndLinkHelpers(): void
    {
        $done = VideoDTO::fromArray($this->payload(status: 'done', duration: 65.4));
        $processing = VideoDTO::fromArray($this->payload(status: 'processing'));
        $error = VideoDTO::fromArray($this->payload(status: 'error'));

        self::assertTrue($done->isReady());
        self::assertSame('1:05', $done->getFormattedDuration());
        self::assertTrue($done->hasHlsLink());
        self::assertTrue($done->hasEmbedLink());
        self::assertTrue($done->hasPlayLink());
        self::assertTrue($processing->isProcessing());
        self::assertTrue($error->hasError());
    }

    public function testAssetQualityHelpers(): void
    {
        $video = VideoDTO::fromArray($this->payload(assets: [
            $this->asset('small', 100, '640x360'),
            $this->asset('large', 200, '1920x1080'),
        ]));

        self::assertSame('large', $video->getHighestQualityAsset()?->id);
        self::assertSame('small', $video->getLowestQualityAsset()?->id);
    }

    /**
     * @param list<array<string, mixed>>|null $assets
     *
     * @return array<string, mixed>
     */
    private function payload(string $status = 'done', float $duration = 183.13579, ?array $assets = null): array
    {
        return [
            'id' => 'video-id',
            'project_id' => 'project-id',
            'folder_id' => 'folder-id',
            'player_id' => 'player-id',
            'version' => 1,
            'title' => 'Video',
            'subtitle' => '',
            'description' => '',
            'status' => $status,
            'progress' => 0,
            'duration' => $duration,
            'assets' => $assets ?? [$this->asset('asset-id', 17267227, '1280x720')],
            'has_audio' => true,
            'audio_tracks' => [],
            'chapters' => ['items' => [], 'enabled' => false],
            'privacy_type' => 'custom',
            'privacy_domains' => ['learn.rarus.ru'],
            'privacy_email_domains' => [],
            'privacy_share' => [],
            'tags' => [],
            'poster' => ['id' => 'poster-id', 'original' => 'https://example.com/poster.jpg'],
            'additional_materials' => [],
            'additional_materials_enabled' => false,
            'annotation_text_enabled' => false,
            'annotation_video_enabled' => false,
            'play_link' => 'https://kinescope.io/slug',
            'embed_link' => 'https://kinescope.io/embed/slug',
            'created_at' => '2025-07-30T19:01:12.323724Z',
            'updated_at' => null,
            'subtitles' => [[
                'id' => 'subtitle-id',
                'language' => 'ru',
                'description' => 'Автоматические',
                'active' => true,
            ]],
            'subtitles_enabled' => false,
            'hls_link' => 'https://kinescope.io/slug/master.m3u8',
            'meta' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function asset(string $id, int $videoStreamSize, string $resolution): array
    {
        return [
            'id' => $id,
            'video_id' => 'video-id',
            'original_name' => $id,
            'file_size' => $videoStreamSize,
            'filetype' => 'mp4',
            'quality' => $id,
            'resolution' => $resolution,
            'download_link' => 'https://example.com/download.mp4',
        ];
    }
}
