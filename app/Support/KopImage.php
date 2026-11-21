<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Upload rules and processing of letterhead images (Klinik Setting > Kop & Logo).
 * An accepted upload is converted once into the small white-background JPEG the PDFs read, so a custom
 * letterhead costs the PDFs no more than the built-in one. The original is kept for re-processing later.
 */
class KopImage
{
    /**
     * Rules per image:
     *  max_kb      file size
     *  min_w/min_h smallest accepted width / height in px (min_long: smallest longest side)
     *  ratio       accepted width:height range
     *  out_w       width of the JPEG the PDFs read
     *  trim_top    drop blank rows at the top (footer artwork: wave at the bottom)
     */
    public const RULES = [
        'header' => ['label' => 'Banner kop atas', 'max_kb' => 5120, 'min_w' => 1500, 'ratio' => [4.5, 8.0], 'out_w' => 1600, 'trim_top' => false],
        'footer' => ['label' => 'Banner kop bawah', 'max_kb' => 5120, 'min_w' => 1500, 'ratio' => [4.5, 20.0], 'out_w' => 1600, 'trim_top' => true],
        'logo' => ['label' => 'Logo', 'max_kb' => 2048, 'min_long' => 300, 'ratio' => [0.33, 6.0], 'out_w' => 600, 'trim_top' => false],
        'ikon' => ['label' => 'Ikon kecil', 'max_kb' => 1024, 'min_w' => 120, 'min_h' => 120, 'ratio' => [0.75, 1.33], 'out_w' => 120, 'trim_top' => false],
    ];

    /** Largest accepted image (pixels): keeps GD within the memory limit while converting. */
    private const MAX_PIXELS = 20000000;

    /** Standard banner size (the built-in artwork and the downloadable template). */
    public const BANNER_W = 2000;
    public const BANNER_H = 381;

    /** Human-readable rule lines for the settings page. */
    public static function ruleText(string $gambar): string
    {
        $r = self::RULES[$gambar];
        $parts = ['PNG/JPG', 'maks. ' . ($r['max_kb'] >= 1024 ? round($r['max_kb'] / 1024) . ' MB' : $r['max_kb'] . ' KB')];
        if (isset($r['min_long'])) {
            $parts[] = 'sisi terpanjang min. ' . $r['min_long'] . ' px';
        } elseif (isset($r['min_h'])) {
            $parts[] = 'min. ' . $r['min_w'] . '×' . $r['min_h'] . ' px, kurang lebih persegi';
        } else {
            $parts[] = 'lebar min. ' . $r['min_w'] . ' px';
            $parts[] = 'rasio ' . self::fmt($r['ratio'][0]) . ':1 – ' . self::fmt($r['ratio'][1]) . ':1 (standar ' . self::BANNER_W . '×' . self::BANNER_H . ')';
        }

        return implode(' · ', $parts);
    }

    /** Error message when the upload breaks a rule, null when it is accepted. */
    public static function validate(UploadedFile $file, string $gambar): ?string
    {
        $r = self::RULES[$gambar] ?? null;
        if (!$r) {
            return 'Jenis gambar tidak dikenal.';
        }
        if (!$file->isValid()) {
            return 'Upload gagal, silakan coba lagi.';
        }
        if ($file->getSize() > $r['max_kb'] * 1024) {
            return $r['label'] . ' terlalu besar: ' . self::kb($file->getSize()) . '. Maksimal ' . self::kb($r['max_kb'] * 1024) . '.';
        }
        $info = @getimagesize($file->getRealPath());
        if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            return 'Format tidak didukung. Gunakan PNG atau JPG (bukan SVG, WebP, atau PDF).';
        }
        [$w, $h] = $info;
        if ($w * $h > self::MAX_PIXELS) {
            return 'Gambar terlalu besar: ' . $w . '×' . $h . ' px. Perkecil dulu (maks. sekitar 20 megapiksel).';
        }
        if (isset($r['min_long']) && max($w, $h) < $r['min_long']) {
            return $r['label'] . ' terlalu kecil: ' . $w . '×' . $h . ' px. Sisi terpanjang minimal ' . $r['min_long'] . ' px agar tidak pecah saat dicetak.';
        }
        if (isset($r['min_w']) && $w < $r['min_w']) {
            return $r['label'] . ' terlalu kecil: lebar ' . $w . ' px. Minimal ' . $r['min_w'] . ' px agar tidak pecah saat dicetak.';
        }
        if (isset($r['min_h']) && $h < $r['min_h']) {
            return $r['label'] . ' terlalu kecil: tinggi ' . $h . ' px. Minimal ' . $r['min_h'] . ' px.';
        }
        $ratio = $w / $h;
        [$min, $max] = $r['ratio'];
        if ($ratio < $min || $ratio > $max) {
            if ($gambar === 'ikon') {
                return 'Ikon harus kurang lebih persegi. Ukuran sekarang ' . $w . '×' . $h . ' px.';
            }
            if ($gambar === 'logo') {
                return 'Logo terlalu ' . ($ratio > $max ? 'pipih' : 'tinggi') . ': ' . $w . '×' . $h . ' px. Gunakan logo dengan perbandingan lebar:tinggi antara 1:3 dan 6:1.';
            }
            $saran = (int) round($w / ((self::BANNER_W / self::BANNER_H)));

            return $r['label'] . ' terlalu ' . ($ratio < $min ? 'tinggi' : 'pipih') . ': ' . $w . '×' . $h . ' px (rasio ' . self::fmt($ratio) . ':1). '
                . 'Gunakan rasio ' . self::fmt($min) . ':1 sampai ' . self::fmt($max) . ':1, misalnya ' . $w . '×' . $saran . ' px.';
        }

        return null;
    }

    /**
     * Store the original and its PDF JPEG on the public disk.
     * @return array{asli: string, jpg: string}|null null when the image cannot be converted
     */
    public static function store(UploadedFile $file, string $gambar, string $folder): ?array
    {
        $r = self::RULES[$gambar];
        $ext = strtolower($file->getClientOriginalExtension()) === 'png' ? 'png' : 'jpg';
        $stamp = date('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 6);
        $disk = Storage::disk('public');

        $asli = $file->storeAs($folder . '/asli', $gambar . '-' . $stamp . '.' . $ext, 'public');
        $jpg = $folder . '/' . $gambar . '-' . $stamp . '.jpg';
        $disk->makeDirectory($folder);
        if (!PdfLetterhead::toJpeg($disk->path($asli), $disk->path($jpg), $r['out_w'], $r['trim_top'])) {
            $disk->delete($asli);

            return null;
        }

        return ['asli' => $asli, 'jpg' => $jpg];
    }

    /**
     * A PNG template of the standard banner size with the safe area marked, for designers.
     * $jenis 'header' | 'footer'.
     */
    public static function templatePng(string $jenis): string
    {
        $w = self::BANNER_W;
        $h = self::BANNER_H;
        $img = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($img, 255, 255, 255);
        $grey = imagecolorallocate($img, 200, 200, 200);
        $blue = imagecolorallocate($img, 23, 97, 253);
        $text = imagecolorallocate($img, 120, 120, 120);
        imagefill($img, 0, 0, $white);
        imagerectangle($img, 0, 0, $w - 1, $h - 1, $grey);
        // safe area: 18 mm side margins of the letters (= 171 px at 2000 px for 210 mm), 20 px top/bottom
        $side = (int) round($w * 18 / 210);
        imagesetthickness($img, 3);
        imagerectangle($img, $side, 20, $w - $side, $h - 20, $blue);
        imagesetthickness($img, 1);
        $lines = [
            ($jenis === 'footer' ? 'KOP BAWAH (FOOTER)' : 'KOP ATAS (HEADER)') . '  ' . $w . ' x ' . $h . ' px',
            'Teks & logo penting di dalam kotak biru. Warna latar boleh sampai tepi.',
            $jenis === 'footer' ? 'Bagian atas yang kosong otomatis dipotong.' : 'Dicetak selebar kertas A4 (210 mm), tinggi kira-kira 40 mm.',
        ];
        foreach ($lines as $i => $line) {
            imagestring($img, 5, $side + 20, 40 + $i * 26, $line, $text);
        }
        ob_start();
        imagepng($img);
        imagedestroy($img);

        return (string) ob_get_clean();
    }

    private static function kb(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : round($bytes / 1024) . ' KB';
    }

    private static function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',');
    }
}
