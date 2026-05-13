<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos;

use Kinescope\DTO\Video\AssetDTO;
use Kinescope\Enum\QualityPreference;

/**
 * Selects a downloadable asset from a video according to the requested quality preference.
 *
 * Selection rules:
 * - assets without a `downloadLink` are ignored
 * - {@see QualityPreference::BEST} prefers the asset with the greatest known `height`
 * - {@see QualityPreference::WORST} prefers the asset with the smallest `fileSize`,
 *   using known `height` as a secondary tie-breaker
 * - a missing `height` is treated as unknown metadata, never as resolution `0`
 */
final readonly class AssetSelector
{
    /**
     * Select a downloadable asset matching the requested quality preference.
     *
     * @param array<AssetDTO> $assets All assets reported for a video
     *
     * @return AssetDTO|null The selected asset, or null when no asset has a download link
     */
    public function select(array $assets, QualityPreference $quality): ?AssetDTO
    {
        $downloadable = array_values(array_filter(
            $assets,
            static fn (AssetDTO $asset): bool => $asset->downloadLink !== null,
        ));

        if ($downloadable === []) {
            return null;
        }

        $selected = $downloadable[0];

        foreach (array_slice($downloadable, 1) as $asset) {
            if ($this->compare($asset, $selected, $quality) < 0) {
                $selected = $asset;
            }
        }

        return $selected;
    }

    private function compare(AssetDTO $a, AssetDTO $b, QualityPreference $quality): int
    {
        if ($quality === QualityPreference::BEST) {
            return self::compareKnownHeightDesc($a, $b);
        }

        $fileSizeComparison = $a->fileSize <=> $b->fileSize;

        if ($fileSizeComparison !== 0) {
            return $fileSizeComparison;
        }

        return self::compareKnownHeightAsc($a, $b);
    }

    private static function compareKnownHeightAsc(AssetDTO $a, AssetDTO $b): int
    {
        if ($a->height !== null && $b->height !== null) {
            return $a->height <=> $b->height;
        }

        if ($a->height !== null) {
            return -1;
        }

        if ($b->height !== null) {
            return 1;
        }

        return 0;
    }

    private static function compareKnownHeightDesc(AssetDTO $a, AssetDTO $b): int
    {
        if ($a->height !== null && $b->height !== null) {
            return $b->height <=> $a->height;
        }

        if ($a->height !== null) {
            return -1;
        }

        if ($b->height !== null) {
            return 1;
        }

        return 0;
    }
}
