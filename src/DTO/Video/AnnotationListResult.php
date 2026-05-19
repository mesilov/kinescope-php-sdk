<?php

declare(strict_types=1);

namespace Kinescope\DTO\Video;

use Kinescope\DTO\Common\CollectionResponse;

/**
 * Unpaginated list of annotations.
 *
 * @extends CollectionResponse<AnnotationDTO>
 */
final readonly class AnnotationListResult extends CollectionResponse
{
    /**
     * @param array<string, mixed> $response Raw API response
     */
    public static function fromArray(array $response): self
    {
        $data = [];

        if (isset($response['data']) && is_array($response['data'])) {
            $data = array_map(
                AnnotationDTO::fromArray(...),
                array_values(array_filter($response['data'], is_array(...))),
            );
        }

        return new self($data);
    }

    /**
     * @return list<AnnotationDTO>
     */
    public function getSortedByTime(bool $ascending = true): array
    {
        $sorted = $this->data;

        usort(
            $sorted,
            static fn (AnnotationDTO $a, AnnotationDTO $b): int => $ascending
                ? $a->time <=> $b->time
                : $b->time <=> $a->time,
        );

        return $sorted;
    }

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
     * @return list<AnnotationDTO>
     */
    public function getInRange(int $startTime, int $endTime): array
    {
        return $this->filter(
            static fn (AnnotationDTO $annotation): bool => $annotation->time >= $startTime && $annotation->time <= $endTime,
        );
    }

    /**
     * @return list<AnnotationDTO>
     */
    public function getPointAnnotations(): array
    {
        return $this->filter(
            static fn (AnnotationDTO $annotation): bool => $annotation->isPoint(),
        );
    }

    /**
     * @return list<AnnotationDTO>
     */
    public function getRangeAnnotations(): array
    {
        return $this->filter(
            static fn (AnnotationDTO $annotation): bool => $annotation->isRange(),
        );
    }

    /**
     * @return list<AnnotationDTO>
     */
    public function getByType(string $type): array
    {
        return $this->filter(
            static fn (AnnotationDTO $annotation): bool => $annotation->type === $type,
        );
    }

    /**
     * @return list<string>
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

    public function findById(string $id): ?AnnotationDTO
    {
        return $this->find(
            static fn (AnnotationDTO $annotation): bool => $annotation->id === $id,
        );
    }

    /**
     * @return list<array{time: int, title: string, formatted_time: string}>
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
