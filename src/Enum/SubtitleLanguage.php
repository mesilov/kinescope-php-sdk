<?php

declare(strict_types=1);

namespace Kinescope\Enum;

/**
 * Subtitle language codes (ISO 639-1).
 *
 * Represents supported subtitle languages in Kinescope.
 */
enum SubtitleLanguage: string
{
    case RU = 'ru';
    case EN = 'en';
    case DE = 'de';
    case FR = 'fr';
    case ES = 'es';
    case IT = 'it';
    case PT = 'pt';
    case ZH = 'zh';
    case JA = 'ja';
    case KO = 'ko';
    case AR = 'ar';
    case HI = 'hi';
    case TR = 'tr';
    case PL = 'pl';
    case NL = 'nl';
    case SV = 'sv';
    case DA = 'da';
    case NO = 'no';
    case FI = 'fi';
    case CS = 'cs';
    case EL = 'el';
    case HE = 'he';
    case TH = 'th';
    case VI = 'vi';
    case ID = 'id';
    case UK = 'uk';
    case RO = 'ro';
    case HU = 'hu';
    case BG = 'bg';
    case SK = 'sk';

    /**
     * Get the native name of the language.
     */
    public function getNativeName(): string
    {
        return match ($this) {
            self::RU => 'Русский',
            self::EN => 'English',
            self::DE => 'Deutsch',
            self::FR => 'Français',
            self::ES => 'Español',
            self::IT => 'Italiano',
            self::PT => 'Português',
            self::ZH => '中文',
            self::JA => '日本語',
            self::KO => '한국어',
            self::AR => 'العربية',
            self::HI => 'हिन्दी',
            self::TR => 'Türkçe',
            self::PL => 'Polski',
            self::NL => 'Nederlands',
            self::SV => 'Svenska',
            self::DA => 'Dansk',
            self::NO => 'Norsk',
            self::FI => 'Suomi',
            self::CS => 'Čeština',
            self::EL => 'Ελληνικά',
            self::HE => 'עברית',
            self::TH => 'ไทย',
            self::VI => 'Tiếng Việt',
            self::ID => 'Bahasa Indonesia',
            self::UK => 'Українська',
            self::RO => 'Română',
            self::HU => 'Magyar',
            self::BG => 'Български',
            self::SK => 'Slovenčina',
        };
    }

    /**
     * Get the English name of the language.
     */
    public function getEnglishName(): string
    {
        return match ($this) {
            self::RU => 'Russian',
            self::EN => 'English',
            self::DE => 'German',
            self::FR => 'French',
            self::ES => 'Spanish',
            self::IT => 'Italian',
            self::PT => 'Portuguese',
            self::ZH => 'Chinese',
            self::JA => 'Japanese',
            self::KO => 'Korean',
            self::AR => 'Arabic',
            self::HI => 'Hindi',
            self::TR => 'Turkish',
            self::PL => 'Polish',
            self::NL => 'Dutch',
            self::SV => 'Swedish',
            self::DA => 'Danish',
            self::NO => 'Norwegian',
            self::FI => 'Finnish',
            self::CS => 'Czech',
            self::EL => 'Greek',
            self::HE => 'Hebrew',
            self::TH => 'Thai',
            self::VI => 'Vietnamese',
            self::ID => 'Indonesian',
            self::UK => 'Ukrainian',
            self::RO => 'Romanian',
            self::HU => 'Hungarian',
            self::BG => 'Bulgarian',
            self::SK => 'Slovak',
        };
    }

    /**
     * Check if this is a right-to-left language.
     */
    public function isRtl(): bool
    {
        return match ($this) {
            self::AR, self::HE => true,
            default => false,
        };
    }

    /**
     * Try to create a SubtitleLanguage from a string.
     * Returns null if the language code is not supported.
     *
     * @param string $code ISO 639-1 language code
     */
    public static function tryFromCode(string $code): ?self
    {
        $normalized = strtolower(trim($code));

        return self::tryFrom($normalized);
    }

    /**
     * Get all available language codes.
     *
     * @return array<string>
     */
    public static function getAllCodes(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases()
        );
    }
}
