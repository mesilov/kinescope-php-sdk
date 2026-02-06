<?php

declare(strict_types=1);

namespace Kinescope\Enum;

/**
 * Project privacy type.
 *
 * Defines where a video can be played.
 */
enum PrivacyType: string
{
    /**
     * Video can be played anywhere (no domain restrictions).
     */
    case ANYWHERE = 'anywhere';

    /**
     * Video can only be played on specified domains.
     */
    case CUSTOM = 'custom';

    /**
     * Video playback is completely disabled.
     */
    case NOWHERE = 'nowhere';

    /**
     * Check if this privacy type allows public playback.
     */
    public function isPublic(): bool
    {
        return $this === self::ANYWHERE;
    }

    /**
     * Check if this privacy type uses domain restrictions.
     */
    public function hasDomainRestrictions(): bool
    {
        return $this === self::CUSTOM;
    }

    /**
     * Check if playback is disabled.
     */
    public function isDisabled(): bool
    {
        return $this === self::NOWHERE;
    }

    /**
     * Get a human-readable label for this privacy type.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ANYWHERE => 'Play anywhere',
            self::CUSTOM => 'Custom domains only',
            self::NOWHERE => 'Playback disabled',
        };
    }

    /**
     * Get a description for this privacy type.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ANYWHERE => 'Video can be embedded and played on any website',
            self::CUSTOM => 'Video can only be played on specified allowed domains',
            self::NOWHERE => 'Video playback is completely disabled',
        };
    }
}
