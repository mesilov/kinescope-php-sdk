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

    /** Extracts slug from embed link, e.g. https://kinescope.io/embed/{slug}. */
    public function fromEmbedLink(string $embedLink): ?string
    {
        if (preg_match('~kinescope\.io/embed/([^"\'\\s/]+)~', $embedLink, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /** Extracts slug from play link, e.g. https://kinescope.io/{slug}. */
    public function fromPlayLink(string $playLink): ?string
    {
        if (preg_match('~kinescope\.io/(?!embed/|pl/)([^/?#]+)~', $playLink, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /** Backward-compatible parser for old iframe snippets. */
    public function fromEmbedCode(string $embedCode): ?string
    {
        return $this->fromEmbedLink($embedCode);
    }

    /** Tries hlsLink, embedLink, then playLink. Returns null if none is present/parseable. */
    public function fromVideoDTO(VideoDTO $video): ?string
    {
        if ($video->hlsLink !== null) {
            $slug = $this->fromHlsLink($video->hlsLink);

            if ($slug !== null) {
                return $slug;
            }
        }

        if ($video->embedLink !== null) {
            $slug = $this->fromEmbedLink($video->embedLink);

            if ($slug !== null) {
                return $slug;
            }
        }

        if ($video->playLink !== null) {
            return $this->fromPlayLink($video->playLink);
        }

        return null;
    }
}
