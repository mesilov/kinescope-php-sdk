<?php

declare(strict_types=1);

namespace Kinescope\Services\Videos;

use Kinescope\DTO\Video\VideoDTO;

final class VideoSlugExtractor
{
    /** Extracts slug from HLS link, e.g. https://kinescope.io/{slug}/master.m3u8 */
    public function fromHlsLink(string $hlsLink): ?string
    {
        if (preg_match('~kinescope\.io/([^/]+)/master\.m3u8~', $hlsLink, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /** Extracts slug from embed code, e.g. <iframe src="https://kinescope.io/embed/{slug}" ...> */
    public function fromEmbedCode(string $embedCode): ?string
    {
        if (preg_match('~kinescope\.io/embed/([^"\'\\s/]+)~', $embedCode, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /** Tries hlsLink first, then embedCode. Returns null if neither is present/parseable. */
    public function fromVideoDTO(VideoDTO $video): ?string
    {
        if ($video->hlsLink !== null) {
            $slug = $this->fromHlsLink($video->hlsLink);

            if ($slug !== null) {
                return $slug;
            }
        }

        if ($video->embedCode !== null) {
            return $this->fromEmbedCode($video->embedCode);
        }

        return null;
    }
}
