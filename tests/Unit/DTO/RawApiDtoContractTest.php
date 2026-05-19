<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO;

use Carbon\CarbonImmutable;
use Kinescope\DTO\Folder\FolderDTO;
use Kinescope\DTO\Playlist\PlaylistDTO;
use Kinescope\DTO\Playlist\PlaylistEntityDTO;
use Kinescope\DTO\Playlist\PlaylistEntityListResult;
use Kinescope\DTO\Project\ProjectDTO;
use Kinescope\DTO\Video\AnnotationDTO;
use Kinescope\DTO\Video\AssetDTO;
use Kinescope\DTO\Video\SubtitleListResult;
use Kinescope\DTO\Video\VideoDTO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RawApiDtoContractTest extends TestCase
{
    public function testFolderDtoExportsCurrentApiFields(): void
    {
        $folder = FolderDTO::fromArray([
            'id' => 'folder-id',
            'name' => 'Root',
            'project_id' => 'project-id',
            'parent_id' => 'project-id',
            'size' => 123,
            'items_count' => 2,
            'created_at' => '2025-07-30T17:11:12.90517Z',
            'updated_at' => null,
            'deleted_at' => null,
        ]);

        self::assertSame(2, $folder->itemsCount);
        self::assertSame(123, $folder->size);
        self::assertArrayHasKey('items_count', $folder->toArray());
        self::assertArrayNotHasKey('videos_count', $folder->toArray());
    }

    public function testVideoDtoExportsCurrentApiFieldsWithoutLegacyAliases(): void
    {
        $video = VideoDTO::fromArray([
            'id' => 'video-id',
            'project_id' => 'project-id',
            'folder_id' => 'folder-id',
            'player_id' => 'player-id',
            'version' => 1,
            'title' => 'Video',
            'subtitle' => '',
            'description' => '',
            'status' => 'done',
            'progress' => 0,
            'duration' => 183.13579,
            'assets' => [$this->assetPayload()],
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
            'subtitles' => [],
            'subtitles_enabled' => false,
            'hls_link' => 'https://kinescope.io/slug/master.m3u8',
            'meta' => [],
        ]);

        $array = $video->toArray();

        self::assertSame(183.13579, $video->duration);
        self::assertArrayHasKey('embed_link', $array);
        self::assertArrayHasKey('play_link', $array);
        self::assertArrayHasKey('poster', $array);
        self::assertArrayNotHasKey('embed_code', $array);
        self::assertArrayNotHasKey('poster_url', $array);
        self::assertArrayNotHasKey('views_count', $array);
    }

    public function testAssetDtoExportsCurrentApiFields(): void
    {
        $asset = AssetDTO::fromArray($this->assetPayload());
        $array = $asset->toArray();

        self::assertSame('original', $asset->originalName);
        self::assertSame(17267227, $asset->videoStreamSize);
        self::assertFalse(new ReflectionClass(AssetDTO::class)->hasProperty('fileSize'));
        self::assertSame('mp4', $asset->filetype);
        self::assertSame('md5-hash', $asset->md5);
        self::assertArrayHasKey('original_name', $array);
        self::assertArrayHasKey('video_stream_size', $array);
        self::assertArrayNotHasKey('file_size', $array);
        self::assertArrayHasKey('filetype', $array);
        self::assertArrayHasKey('md5', $array);
        self::assertArrayNotHasKey('bitrate', $array);
        self::assertArrayNotHasKey('codec', $array);
    }

    public function testPlaylistDtoExportsCurrentApiFields(): void
    {
        $playlist = PlaylistDTO::fromArray([
            'id' => 'playlist-id',
            'workspace_id' => 'workspace-id',
            'player_id' => 'player-id',
            'parent_id' => 'parent-id',
            'name' => 'Playlist',
            'description' => '',
            'privacy_type' => 'anywhere',
            'privacy_domains' => [],
            'privacy_email_domains' => [],
            'privacy_share' => [],
            'unique_codes_enabled' => false,
            'tags' => [],
            'settings' => ['sort_field' => 'custom'],
            'play_link' => 'https://kinescope.io/pl/slug',
            'embed_link' => 'https://kinescope.io/embed/pl/slug',
        ]);

        $array = $playlist->toArray();

        self::assertSame('Playlist', $playlist->name);
        self::assertArrayHasKey('name', $array);
        self::assertArrayHasKey('workspace_id', $array);
        self::assertArrayNotHasKey('title', $array);
        self::assertArrayNotHasKey('project_id', $array);
        self::assertArrayNotHasKey('items_count', $array);
    }

    public function testUnpaginatedCollectionsParseDataWithoutMeta(): void
    {
        $entities = PlaylistEntityListResult::fromArray([
            'data' => [[
                'id' => 'entity-id',
                'position' => 1,
                'status' => 'done',
                'title' => 'Entity',
                'description' => '',
                'duration' => 4830.9585,
                'created_at' => '2025-06-30T07:28:27.96749Z',
                'updated_at' => '2025-06-30T07:42:48.908293Z',
            ]],
        ]);

        $subtitles = SubtitleListResult::fromArray([
            'data' => [[
                'id' => 'subtitle-id',
                'video_id' => 'video-id',
                'description' => 'Автоматические',
                'language' => 'ru',
                'status' => 'done',
                'position' => 1,
                'data' => [],
                'active' => true,
                'url' => 'https://kinescope.io/subtitles/file.vtt',
                'updated_at' => '2026-03-06T08:01:19.843367Z',
                'file' => '',
                'file_name' => 'subtitle-id.vtt',
                'hls_file' => '',
            ]],
        ]);

        self::assertCount(1, $entities);
        self::assertCount(1, $subtitles);
    }

    public function testApiDateFieldsUseCarbonImmutable(): void
    {
        $project = ProjectDTO::fromArray([
            'id' => 'project-id',
            'name' => 'Project',
            'created_at' => '2025-09-28T10:35:39.170997Z',
            'updated_at' => '2026-03-26T13:23:29.567978Z',
        ]);
        $folder = FolderDTO::fromArray([
            'id' => 'folder-id',
            'name' => 'Folder',
            'project_id' => 'project-id',
            'parent_id' => 'project-id',
            'created_at' => '2025-07-30T17:11:12.90517Z',
            'updated_at' => null,
            'deleted_at' => null,
        ]);
        $video = VideoDTO::fromArray([
            'id' => 'video-id',
            'title' => 'Video',
            'status' => 'done',
            'created_at' => '2025-07-30T19:01:12.323724Z',
            'updated_at' => null,
        ]);
        $asset = AssetDTO::fromArray($this->assetPayload());
        $entity = PlaylistEntityDTO::fromArray([
            'id' => 'entity-id',
            'created_at' => '2025-06-30T07:28:27.96749Z',
            'updated_at' => '2025-06-30T07:42:48.908293Z',
        ]);
        $subtitle = SubtitleListResult::fromArray([
            'data' => [[
                'id' => 'subtitle-id',
                'updated_at' => '2026-03-06T08:01:19.843367Z',
            ]],
        ])->first();
        $annotation = AnnotationDTO::fromArray([
            'id' => 'annotation-id',
            'created_at' => '2024-01-01T00:00:00.123456Z',
            'updated_at' => '2024-01-02T00:00:00.654321Z',
        ]);

        self::assertInstanceOf(CarbonImmutable::class, $project->createdAt);
        self::assertInstanceOf(CarbonImmutable::class, $project->updatedAt);
        self::assertInstanceOf(CarbonImmutable::class, $folder->createdAt);
        self::assertNull($folder->updatedAt);
        self::assertNull($folder->deletedAt);
        self::assertInstanceOf(CarbonImmutable::class, $video->createdAt);
        self::assertNull($video->updatedAt);
        self::assertInstanceOf(CarbonImmutable::class, $asset->createdAt);
        self::assertInstanceOf(CarbonImmutable::class, $entity->createdAt);
        self::assertInstanceOf(CarbonImmutable::class, $entity->updatedAt);
        self::assertInstanceOf(CarbonImmutable::class, $subtitle?->updatedAt);
        self::assertInstanceOf(CarbonImmutable::class, $annotation->createdAt);
        self::assertInstanceOf(CarbonImmutable::class, $annotation->updatedAt);
    }

    /**
     * @return array<string, mixed>
     */
    private function assetPayload(): array
    {
        return [
            'id' => 'asset-id',
            'video_id' => 'video-id',
            'original_name' => 'original',
            'file_size' => 17267227,
            'md5' => 'md5-hash',
            'filetype' => 'mp4',
            'quality' => 'original',
            'resolution' => '1280x720',
            'created_at' => '2025-07-30T19:01:15.692226Z',
            'url' => 'https://example.com/original.mp4',
            'download_link' => 'https://example.com/download.mp4',
        ];
    }
}
