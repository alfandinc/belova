<?php

namespace App\Support;

use App\Models\ERM\Klinik;
use App\Models\ERM\Visitation;
use App\Models\PdfKop;

/**
 * Letterheads (kop surat) of the PDFs.
 *
 * Three kinds, each customisable in Klinik Setting (table pdf_kop) with the built-in artwork in public/img as fallback:
 *  - per klinik: banner header/footer (patient letters), logo (invoice, nota, inform consent, retur) and small icon
 *    (farmasi resep / edukasi); without a banner: logo + name + address lines
 *  - perusahaan (Belova Corp): pengajuan dana, slip gaji dokter, memo/disposisi without a klinik banner
 *  - lab: one header/footer for every hasil lab
 *
 * PDFs only ever read small JPEGs: uploads are converted once at upload (KopImage), built-in PNGs once into
 * storage/app/pdf-assets. DomPDF takes seconds to decode a 2000px PNG with transparency on every PDF, a JPEG
 * takes milliseconds.
 */
class PdfLetterhead
{
    /**
     * Built-in artwork per klinik id: banner header / footer (public/img, 2000x381), the logo used when there is no
     * header, the address lines printed under the name in that case (when the klinik has no alamat/telp/website
     * filled in), and the small square icon of compact headers.
     */
    private const KLINIK = [
        1 => ['header' => 'img/premiere-header.png', 'footer' => 'img/premiere-footer.png', 'logo' => 'img/logo-premiere.png', 'icon' => 'img/favicon-premiere.png', 'lines' => ['Jl. Melon Raya No.27, Karangasem, Laweyan, Surakarta', 'Telp. 0821-1600-0093', 'www.premierebelova.id']],
        2 => ['header' => 'img/belova-header.png', 'footer' => 'img/belova-footer.png', 'logo' => 'img/logo-belovaskin.png', 'icon' => 'img/favicon-belovaskin.png', 'lines' => ['Jl. Melon Raya 1 No.29, Laweyan, Surakarta', 'Telp. 0851-0099-0319']],
        3 => ['header' => null, 'footer' => null, 'logo' => 'img/logo-dental.png', 'icon' => null, 'lines' => []],
    ];

    private const FALLBACK_KLINIK_ID = 1;

    /** Built-in company letterhead (used while nothing is uploaded). */
    public const PERUSAHAAN_NAMA = 'Belova Corp';
    public const PERUSAHAAN_LOGO = 'img/logo-belovacorp.png';

    /** Built-in lab letterhead. */
    private const LAB_HEADER = 'img/lab_header.png';
    private const LAB_FOOTER = 'img/lab_footer.png';

    /** @return array{nama: string, header: ?array, footer: ?array, logo: ?string, icon: ?string, lines: string[]} */
    public static function forVisitation(Visitation $visitation): array
    {
        return self::forKlinik($visitation->klinik);
    }

    /** Letterhead of a klinik id (null / unknown id: the default klinik's artwork). */
    public static function forKlinikId($klinikId): array
    {
        return self::forKlinik($klinikId ? Klinik::find($klinikId) : null);
    }

    /** @return array{nama: string, header: ?array, footer: ?array, logo: ?string, icon: ?string, lines: string[]} */
    public static function forKlinik(?Klinik $klinik): array
    {
        $id = $klinik && isset(self::KLINIK[$klinik->id]) ? $klinik->id : self::FALLBACK_KLINIK_ID;
        $config = self::KLINIK[$id];
        $custom = $klinik ? PdfKop::untuk(PdfKop::LINGKUP_KLINIK, (int) $klinik->id) : null;

        return [
            'nama' => mb_strtoupper($klinik->nama ?? 'Klinik Utama Premiere Belova'),
            // ['path' => jpeg, 'height_mm' => printed height at the full A4 width of 210 mm]
            'header' => self::customBanner($custom, 'header') ?? ($config['header'] ? self::banner($config['header'], false) : null),
            'footer' => self::customBanner($custom, 'footer') ?? ($config['footer'] ? self::banner($config['footer'], true) : null),
            'logo' => ($custom ? $custom->path('logo') : null) ?? self::logo($config['logo']),
            'icon' => ($custom ? $custom->path('ikon') : null) ?? ($config['icon'] ? self::logo($config['icon'], 120) : null),
            'lines' => self::klinikLines($klinik) ?: $config['lines'],
        ];
    }

    /**
     * Logo of a klinik for documents that show only a logo (invoice, nota, inform consent, retur, surat permintaan).
     * The uploaded logo when there is one, otherwise the built-in file that document has always used.
     */
    public static function klinikLogo($klinikId, ?string $defaultPublicFile, int $width = 600): ?string
    {
        $custom = $klinikId ? PdfKop::untuk(PdfKop::LINGKUP_KLINIK, (int) $klinikId) : null;
        $path = $custom ? $custom->path('logo') : null;

        return $path ?? ($defaultPublicFile ? self::logo($defaultPublicFile, $width) : null);
    }

    /**
     * An image path as a data URI ('' for null / missing file). For PDFs whose controller calls setOptions():
     * that replaces DomPDF's whole config including the chroot, so local file paths stop loading. A small
     * JPEG is embedded as-is (no decoding), so this stays fast.
     */
    public static function dataUri(?string $path): string
    {
        if (!$path || !is_file($path)) {
            return '';
        }
        $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    /** Whether the klinik has a banner header (uploaded or built-in). */
    public static function klinikHasBanner($klinikId): bool
    {
        $custom = $klinikId ? PdfKop::untuk(PdfKop::LINGKUP_KLINIK, (int) $klinikId) : null;

        return ($custom && $custom->path('header')) || !empty(self::KLINIK[(int) $klinikId]['header']);
    }

    /**
     * Company letterhead (pengajuan dana, slip gaji dokter, memo/disposisi without a klinik banner).
     * @return array{nama: string, header: ?array, footer: ?array, logo: ?string, lines: string[]}
     */
    public static function perusahaan(?string $defaultLogo = self::PERUSAHAAN_LOGO): array
    {
        $custom = PdfKop::untuk(PdfKop::LINGKUP_PERUSAHAAN);
        $nama = trim((string) ($custom->nama ?? ''));

        return [
            'nama' => $nama !== '' ? $nama : self::PERUSAHAAN_NAMA,
            'header' => self::customBanner($custom, 'header'),
            'footer' => self::customBanner($custom, 'footer'),
            'logo' => ($custom ? $custom->path('logo') : null) ?? ($defaultLogo ? self::logo($defaultLogo) : null),
            'lines' => self::splitLines($custom->alamat ?? ''),
        ];
    }

    /**
     * Full-width header image of a memorandum / disposisi: the klinik's banner (uploaded or built-in), else the
     * company banner, else the built-in Belova Corp logo (what these documents have always shown). JPEG path or null.
     */
    public static function workdocHeader(?Klinik $klinik): ?string
    {
        if ($klinik && self::klinikHasBanner($klinik->id)) {
            return self::forKlinik($klinik)['header']['path'] ?? null;
        }
        $perusahaan = self::perusahaan(null);

        return $perusahaan['header']['path'] ?? self::logo('img/header-belovacorp.png', 1600);
    }

    /** Lab letterhead: ['header' => banner|null, 'footer' => banner|null] (same for every klinik). */
    public static function lab(): array
    {
        $custom = PdfKop::untuk(PdfKop::LINGKUP_LAB);

        return [
            'header' => self::customBanner($custom, 'header') ?? self::banner(self::LAB_HEADER, false),
            'footer' => self::customBanner($custom, 'footer') ?? self::banner(self::LAB_FOOTER, false),
        ];
    }

    /** Built-in files per lingkup / klinik, for the settings page ("bawaan"). Paths relative to public/. */
    public static function bawaan(string $lingkup, ?int $klinikId = null): array
    {
        if ($lingkup === PdfKop::LINGKUP_LAB) {
            return ['header' => self::LAB_HEADER, 'footer' => self::LAB_FOOTER, 'logo' => null, 'ikon' => null];
        }
        if ($lingkup === PdfKop::LINGKUP_PERUSAHAAN) {
            return ['header' => null, 'footer' => null, 'logo' => self::PERUSAHAAN_LOGO, 'ikon' => null];
        }
        $config = self::KLINIK[$klinikId] ?? null;

        return [
            'header' => $config['header'] ?? null,
            'footer' => $config['footer'] ?? null,
            'logo' => $config['logo'] ?? null,
            'ikon' => $config['icon'] ?? null,
        ];
    }

    /** Address lines from the klinik's own alamat / telp / website (Klinik Setting), [] when all empty. */
    private static function klinikLines(?Klinik $klinik): array
    {
        if (!$klinik) {
            return [];
        }
        $lines = self::splitLines((string) ($klinik->alamat ?? ''));
        if (trim((string) ($klinik->telp ?? '')) !== '') {
            $lines[] = 'Telp. ' . trim($klinik->telp);
        }
        if (trim((string) ($klinik->website ?? '')) !== '') {
            $lines[] = trim($klinik->website);
        }

        return $lines;
    }

    private static function splitLines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text)), 'strlen'));
    }

    /** Uploaded banner (already a small JPEG) + its printed height, or null. */
    private static function customBanner(?PdfKop $custom, string $gambar): ?array
    {
        $path = $custom ? $custom->path($gambar) : null;
        if (!$path || !($size = @getimagesize($path))) {
            return null;
        }

        return ['path' => $path, 'height_mm' => round(210 * $size[1] / $size[0], 1)];
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
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return self::toJpeg($source, $target, $width, $trimTop) ? $target : $source;
    }

    /**
     * Write $source (PNG/JPEG) as a white-background JPEG of at most $width px wide to $target.
     * $trimTop drops the blank rows at the top. Returns false when the image cannot be read or written.
     */
    public static function toJpeg(string $source, string $target, int $width, bool $trimTop = false): bool
    {
        if (!function_exists('imagecreatefromstring') || !($image = @imagecreatefromstring(file_get_contents($source)))) {
            return false;
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
        imagedestroy($image);

        $ok = @imagejpeg($canvas, $target, 88);
        imagedestroy($canvas);

        return (bool) $ok;
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
