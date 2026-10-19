<?php

namespace App\Support;

use App\Models\ERM\Visitation;

/**
 * Letterhead (kop surat) of patient PDFs: the visit's own klinik, not always Premiere.
 * A klinik with letterhead artwork gets it full width at the top of every page and its footer wave at the
 * bottom (as on the lab PDFs); otherwise its logo + name. Images go in as small JPEGs made once from the big
 * PNGs: DomPDF takes seconds to decode a 2000px PNG with transparency on every PDF, a JPEG takes milliseconds.
 */
class PdfLetterhead
{
    /**
     * Per klinik id: letterhead header / footer artwork (public/img, 2000x381), the logo used when there is no
     * header, the address lines printed under the name in that case, and the small square icon of compact
     * headers (logo icon + name + address, e.g. the farmasi resep / edukasi).
     */
    private const KLINIK = [
        1 => ['header' => 'img/premiere-header.png', 'footer' => 'img/premiere-footer.png', 'logo' => 'img/logo-premiere.png', 'icon' => 'img/favicon-premiere.png', 'lines' => ['Jl. Melon Raya No.27, Karangasem, Laweyan, Surakarta', 'Telp. 0821-1600-0093', 'www.premierebelova.id']],
        2 => ['header' => 'img/belova-header.png', 'footer' => 'img/belova-footer.png', 'logo' => 'img/logo-belovaskin.png', 'icon' => 'img/favicon-belovaskin.png', 'lines' => ['Jl. Melon Raya 1 No.29, Laweyan, Surakarta', 'Telp. 0851-0099-0319']],
        3 => ['header' => null, 'footer' => null, 'logo' => 'img/logo-dental.png', 'icon' => null, 'lines' => []],
    ];

    private const FALLBACK_KLINIK_ID = 1;

    /** @return array{nama: string, header: ?array, footer: ?array, logo: ?string, icon: ?string, lines: string[]} */
    public static function forVisitation(Visitation $visitation): array
    {
        $klinik = $visitation->klinik;
        $id = $klinik && isset(self::KLINIK[$klinik->id]) ? $klinik->id : self::FALLBACK_KLINIK_ID;
        $config = self::KLINIK[$id];

        return [
            'nama' => mb_strtoupper($klinik->nama ?? 'Klinik Utama Premiere Belova'),
            // ['path' => jpeg, 'height_mm' => printed height at the full A4 width of 210 mm]
            'header' => $config['header'] ? self::banner($config['header'], false) : null,
            'footer' => $config['footer'] ? self::banner($config['footer'], true) : null,
            'logo' => self::logo($config['logo']),
            'icon' => $config['icon'] ? self::logo($config['icon'], 120) : null,
            'lines' => $config['lines'],
        ];
    }

    /** A full-width banner as JPEG + its printed height; a footer loses its empty top so it covers no text. */
    private static function banner(string $publicFile, bool $trimTop): ?array
    {
        $path = self::logo($publicFile, 1600, $trimTop);
        if (!$path || !($size = @getimagesize($path))) {
            return null;
        }

        return ['path' => $path, 'height_mm' => round(210 * $size[1] / $size[0], 1)];
    }

    /**
     * Path of a small white-background JPEG of a public image (made once per source version), or null.
     * $trimTop drops the blank rows at the top (footer artwork is mostly empty space above the wave).
     */
    public static function logo(string $publicFile, int $width = 600, bool $trimTop = false): ?string
    {
        $source = public_path($publicFile);
        if (!is_file($source)) {
            return null;
        }

        $dir = storage_path('app/pdf-assets');
        $target = $dir . '/' . pathinfo($publicFile, PATHINFO_FILENAME) . '-' . $width . ($trimTop ? '-trim' : '') . '-' . filemtime($source) . '.jpg';
        if (is_file($target)) {
            return $target;
        }
        if (!function_exists('imagecreatefromstring') || !($image = @imagecreatefromstring(file_get_contents($source)))) {
            return $source;
        }

        // Palette PNGs (the footer artwork) give colour indexes from imagecolorat: read them as RGBA
        imagepalettetotruecolor($image);
        $w = imagesx($image);
        $h = imagesy($image);
        $top = $trimTop ? self::firstInkRow($image, $w, $h) : 0;
        $srcH = $h - $top;
        $newW = min($width, $w);
        $newH = max(1, (int) round($srcH * $newW / $w));
        $canvas = imagecreatetruecolor($newW, $newH);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, 0, 0, 0, $top, $newW, $newH, $w, $srcH);

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $ok = @imagejpeg($canvas, $target, 88);

        return $ok ? $target : $source;
    }

    /** First row (from the top) with a visible, non-white pixel; sampled every few pixels. */
    private static function firstInkRow($image, int $w, int $h): int
    {
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x += 4) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;           // 127 = fully transparent
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                if ($alpha < 100 && ($r < 245 || $g < 245 || $b < 245)) {
                    return max(0, $y - 2);
                }
            }
        }

        return 0;
    }
}
