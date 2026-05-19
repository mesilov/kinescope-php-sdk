<?php

declare(strict_types=1);

namespace Kinescope\DTO\Video;

use Kinescope\DTO\Common\CollectionResponse;
use Kinescope\Enum\SubtitleLanguage;

/**
 * Unpaginated list of subtitles.
 *
 * @extends CollectionResponse<SubtitleDTO>
 */
final readonly class SubtitleListResult extends CollectionResponse
{
    /**
     * @param array<string, mixed> $response Raw API response
     */
    public static function fromArray(array $response): self
    {
        $data = [];

        if (isset($response['data']) && is_array($response['data'])) {
            $data = array_map(
                SubtitleDTO::fromArray(...),
                array_values(array_filter($response['data'], is_array(...))),
            );
        }

        return new self($data);
    }

    public function getByLanguage(SubtitleLanguage $language): ?SubtitleDTO
    {
        return $this->find(
            static fn (SubtitleDTO $subtitle): bool => $subtitle->language === $language,
        );
    }

    public function getByLanguageCode(string $languageCode): ?SubtitleDTO
    {
        return $this->find(
            static fn (SubtitleDTO $subtitle): bool => $subtitle->getLanguageCode() === $languageCode,
        );
    }

    /**
     * @return list<SubtitleDTO>
     */
    public function getActive(): array
    {
        return $this->filter(
            static fn (SubtitleDTO $subtitle): bool => $subtitle->active,
        );
    }

    /**
     * @return list<SubtitleDTO>
     */
    public function getInactive(): array
    {
        return $this->filter(
            static fn (SubtitleDTO $subtitle): bool => ! $subtitle->active,
        );
    }

    public function findById(string $id): ?SubtitleDTO
    {
        return $this->find(
            static fn (SubtitleDTO $subtitle): bool => $subtitle->id === $id,
        );
    }
}
