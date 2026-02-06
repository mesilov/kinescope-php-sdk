<?php

declare(strict_types=1);

namespace Kinescope\Enum;

/**
 * Strategy for selecting video quality from available assets.
 */
enum QualityPreference
{
    /** Select the highest available quality (maximum height). */
    case BEST;

    /** Select the lowest available quality (minimum height). */
    case WORST;
}
