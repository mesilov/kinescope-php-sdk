<?php

declare(strict_types=1);

namespace Kinescope\DTO\Video;

use Kinescope\DTO\Common\MetaDTO;
use Kinescope\DTO\Common\PaginatedResponse;
use Kinescope\Enum\VideoStatus;

/**
 * Paginated list of videos.
 *
 * @extends PaginatedResponse<VideoDTO>
 */
final readonly class VideoListResult extends PaginatedResponse
{
    /**
     * Create a VideoListResult from API response array.
     *
     * @param array<string, mixed> $response Raw API response
     *
     * @return self
     */
    public static function fromArray(array $response): self
    {
        $data = [];

        if (isset($response['data']) && is_array($response['data'])) {
            $data = array_map(
                static fn (array $item): VideoDTO => VideoDTO::fromArray($item),
                $response['data']
            );
        }

        $meta = MetaDTO::fromArray($response['meta'] ?? []);

        return new self($data, $meta);
    }

    /**
     * Get videos in a specific folder.
     *
     * @param string $folderId Folder UUID
     *
     * @return array<VideoDTO> Videos in the folder
     */
    public function getByFolder(string $folderId): array
    {
        return $this->filter(
            static fn (VideoDTO $video): bool => $video->folderId === $folderId
        );
    }

    /**
     * Get all videos that have errors.
     *
     * @return array<VideoDTO> Videos with errors
     */
    public function getWithErrors(): array
    {
        return $this->getByStatus(VideoStatus::ERROR);
    }

    /**
     * Get all videos that are ready for playback.
     *
     * @return array<VideoDTO> Ready videos
     */
    public function getReady(): array
    {
        return $this->getByStatus(VideoStatus::DONE);
    }

    /**
     * Get all videos that are still processing.
     *
     * @return array<VideoDTO> Processing videos
     */
    public function getProcessing(): array
    {
        return $this->getByStatus(VideoStatus::PROCESSING);
    }

    /**
     * Get videos by status.
     *
     * @param VideoStatus $status Video status to filter by
     *
     * @return array<VideoDTO>
     */
    public function getByStatus(VideoStatus $status): array
    {
        return $this->filter(
            static fn (VideoDTO $video): bool => $video->status === $status
        );
    }

    /**
     * Get videos in a specific project.
     *
     * @param string $projectId Project identifier
     *
     * @return array<VideoDTO>
     */
    public function getByProject(string $projectId): array
    {
        return $this->filter(
            static fn (VideoDTO $video): bool => $video->projectId === $projectId
        );
    }

    /**
     * Find a video by ID.
     *
     * @param string $id Video identifier
     *
     * @return VideoDTO|null
     */
    public function findById(string $id): ?VideoDTO
    {
        return $this->find(
            static fn (VideoDTO $video): bool => $video->id === $id
        );
    }

    /**
     * Get total duration of all videos in seconds.
     *
     * @return int
     */
    public function getTotalDuration(): int
    {
        return array_reduce(
            $this->data,
            static fn (int $total, VideoDTO $video): int => $total + $video->duration,
            0
        );
    }

    /**
     * Get IDs of all videos.
     *
     * @return array<string>
     */
    public function getIds(): array
    {
        return $this->map(static fn (VideoDTO $video): string => $video->id);
    }
}
