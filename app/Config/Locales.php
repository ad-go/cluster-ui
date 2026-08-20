<?php

namespace App\Config;

/**
 * Flag image path + display label per supported locale.
 *
 * Real SVG flags (public/assets/flags/*.svg, from the flag-icons project -
 * not bundled with this package, expected to already be in place), not
 * emoji.
 * Regional-indicator flag emoji render correctly on macOS/iOS/most Linux,
 * but Windows' system emoji font has no flag glyphs and Chrome/Edge on
 * Windows fall back to showing the two letters in plain boxes instead of a
 * colored flag - confirmed live. Self-hosted SVGs render identically
 * everywhere.
 */
class Locales
{
    private static array $map = [
        'en' => ['country' => 'gb', 'label' => 'English'],
        'ro' => ['country' => 'ro', 'label' => 'Română'],
    ];

    public static function flagImage(string $locale): string
    {
        $country = self::$map[$locale]['country'] ?? 'un';

        return 'assets/flags/' . $country . '.svg';
    }

    public static function label(string $locale): string
    {
        return self::$map[$locale]['label'] ?? strtoupper($locale);
    }
}
