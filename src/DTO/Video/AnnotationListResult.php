<?php

declare(strict_types=1);

namespace Kinescope\DTO\Video;

use Kinescope\DTO\Common\MetaDTO;
use Kinescope\DTO\Common\PaginatedResponse;

/**
 * Paginated list of annotations.
 *
 * @extends PaginatedResponse<AnnotationDTO>
 */
final readonly class AnnotationListResult extends PaginatedResponse
{
    /**
     * Create an AnnotationListResult from API response array.
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
                static fn (array $item): AnnotationDTO => AnnotationDTO::fromArray($item),
                $response['data']
            );
        }

        $meta = MetaDTO::fromArray($response['meta'] ?? []);

        return new self($data, $meta);
    }

    /**
     * Get annotations sorted by time.
     *
     * @param bool $ascending Sort direction (true = ascending)
     *
     * @return array<AnnotationDTO>
     */
    public function getSortedByTime(bool $ascending = true): array
    {
        $sorted = $this->data;

        usort(
            $sorted,
            static fn (AnnotationDTO $a, AnnotationDTO $b): int =>
            $ascending
                ? $a->time <=> $b->time
                : $b->time <=> $a->time
        );

        return $sorted;
    }

    /**
     * Get annotation at or near a specific time.
     *
     * @param int $timeInSeconds Time in seconds
     * @param int $tolerance Tolerance in seconds for "near" matching
     *
     * @return AnnotationDTO|null
     */
    public function getAtTime(int $timeInSeconds, int $tolerance = 0): ?AnnotationDTO
    {
        foreach ($this->data as $annotation) {
            if ($annotation->containsTime($timeInSeconds)) {
                return $annotation;
            }
        }

        if ($tolerance > 0) {
            foreach ($this->data as $annotation) {
                if (abs($annotation->time - $timeInSeconds) <= $tolerance) {
                    return $annotation;
                }
            }
        }

        return null;
    }

    /**
     * Get all annotations within a time range.
     *
     * @param int $startTime Start time in seconds
     * @param int $endTime End time in seconds
     *
     * @return array<AnnotationDTO>
     */
    public function getInRange(int $startTime, int $endTime): array
    {
        return $this->filter(
            static fn (AnnotationDTO $annotation): bool =>
                $annotation->time >= $startTime && $annotation->time <= $endTime
        );
    }

    /**
     * Get all point annotations.
     *
     * @return array<AnnotationDTO>
     */
    public function getPointAnnotations(): array
    {
        return $this->filter(
            static fn (AnnotationDTO $annotation): bool => $annotation->isPoint()
        );
    }

    /**
     * Get all range annotations.
     *
     * @return array<AnnotationDTO>
     */
    public function getRangeAnnotations(): array
    {
        return $this->filter(
            static fn (AnnotationDTO $annotation): bool => $annotation->isRange()
        );
    }

    /**
     * Get annotations by type.
     *
     * @param string $type Annotation type
     *
     * @return array<AnnotationDTO>
     */
    public function getByType(string $type): array
    {
        return $this->filter(
            static fn (AnnotationDTO $annotation): bool => $annotation->type === $type
        );
    }

    /**
     * Get all unique annotation types.
     *
     * @return array<string>
     */
    public function getTypes(): array
    {
        $types = [];

        foreach ($this->data as $annotation) {
            if ($annotation->type !== null && ! in_array($annotation->type, $types, true)) {
                $types[] = $annotation->type;
            }
        }

        return $types;
    }

    /**
     * Find annotation by ID.
     *
     * @param string $id Annotation identifier
     *
     * @return AnnotationDTO|null
     */
    public function findById(string $id): ?AnnotationDTO
    {
        return $this->find(
            static fn (AnnotationDTO $annotation): bool => $annotation->id === $id
        );
    }

    /**
     * Get annotations as chapters (sorted by time with titles).
     *
     * Useful for generating video chapter markers.
     *
     * @return array<array{time: int, title: string, formatted_time: string}>
     */
    public function asChapters(): array
    {
        $chapters = [];

        foreach ($this->getSortedByTime() as $annotation) {
            $chapters[] = [
                'time' => $annotation->time,
                'title' => $annotation->title,
                'formatted_time' => $annotation->getFormattedTime(),
            ];
        }

        return $chapters;
    }
}
