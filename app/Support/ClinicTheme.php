<?php

namespace App\Support;

/**
 * Accent colour of the clinic picked at login (session 'clinic_choice'); same accents as the login page.
 * Used by the shared sidebar and the dashboard header.
 */
class ClinicTheme
{
    private const ACCENTS = [
        'premiere' => ['base' => '#2563eb', 'deep' => '#1e3a8a', 'text_light' => '#1d4ed8', 'text_dark' => '#7fb0ff', 'rgb' => '37, 99, 235'],
        'skin' => ['base' => '#db2777', 'deep' => '#831843', 'text_light' => '#be185d', 'text_dark' => '#f58bbd', 'rgb' => '219, 39, 119'],
        'dental' => ['base' => '#16a34a', 'deep' => '#14532d', 'text_light' => '#15803d', 'text_dark' => '#5fdc8f', 'rgb' => '22, 163, 74'],
    ];

    /** Belova Corp / no clinic chosen: plum of the Belova logo */
    private const DEFAULT = ['base' => '#9d3b8e', 'deep' => '#5b2157', 'text_light' => '#86307a', 'text_dark' => '#e09ad5', 'rgb' => '157, 59, 142'];

    /** @return array{base: string, deep: string, text_light: string, text_dark: string, rgb: string} */
    public static function accent(): array
    {
        return self::ACCENTS[session('clinic_choice')] ?? self::DEFAULT;
    }

    /** Inline CSS custom properties (--{prefix}-accent, -accent-deep, -accent-rgb, -accent-text-light, -accent-text-dark). */
    public static function cssVars(string $prefix): string
    {
        $a = self::accent();

        return "--{$prefix}-accent: {$a['base']}; --{$prefix}-accent-deep: {$a['deep']}; --{$prefix}-accent-rgb: {$a['rgb']}; "
            . "--{$prefix}-accent-text-light: {$a['text_light']}; --{$prefix}-accent-text-dark: {$a['text_dark']};";
    }
}
