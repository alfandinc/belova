<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Log;

/**
 * Birthday cards: a template image per klinik (or "default") with the pasien's name, age and the date written on it.
 * Templates are set from the Ulang Tahun tab (Template Kartu) and kept in storage/app/private/birthday_templates.json;
 * the uploaded images live on the public disk under birthday_templates/. A klinik without its own template
 * uses the "default" one; nothing saved = the built-in images in public/img/templates.
 */
class BirthdayImageService
{
    private const CONFIG_FILE = 'birthday_templates.json';
    public const IMAGE_DIR = 'birthday_templates';

    /** Sizes are given for a 1600px wide image and scale with the real width. */
    private const BASE_WIDTH = 1600;

    /**
     * Built-in templates, placed as they always were: name centered 200px left of the middle,
     * age 70px below it, date at the bottom.
     */
    private const BUILT_IN = [
        '1' => ['file' => 'img/templates/birthday_premierebelova.jpg', 'color' => '#ffffff', 'x' => 37.5, 'y' => 48.3, 'size' => 60],
        '2' => ['file' => 'img/templates/birthday_belovaskin.jpg', 'color' => '#6c3483', 'x' => 37.5, 'y' => 48.3, 'size' => 60],
        'default' => ['file' => 'img/templates/birthday_default.jpg', 'color' => '#6c3483', 'x' => 33.3, 'y' => 47.6, 'size' => 80],
    ];

    private const SETTING_DEFAULTS = ['align' => 'center', 'show_age' => true, 'show_date' => true];

    public function generateBirthdayImage($name, $age, $prefix = '', $klinikId = null)
    {
        try {
            $template = $this->templateFor($klinikId);
            $img = $this->render($template, $template['path'], $prefix ? "$prefix $name" : $name, $age);

            $path = 'birthday_cards/birthday_' . strtolower(str_replace(' ', '_', $name)) . '_' . uniqid() . '.jpg';
            Storage::disk('public')->put($path, $img->toJpeg(90));

            return $path;
        } catch (\Throwable $e) {
            Log::error("Birthday image generation error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Card preview as a data URI (smaller, nothing stored). $imagePath = absolute path of a not yet saved upload.
     */
    public function preview(array $settings, ?string $imagePath, string $name = 'Kakak Nama Pasien', int $age = 25): string
    {
        $img = $this->render($settings, $imagePath, $name, $age);
        return (string) $img->scaleDown(width: 900)->toJpeg(80)->toDataUri();
    }

    /** Templates for the editor: "default" and every klinik id, each with its settings and image URL. */
    public function allTemplates(array $klinikIds): array
    {
        $result = [];
        foreach (array_merge(['default'], array_map('strval', $klinikIds)) as $key) {
            $saved = $this->saved()[$key] ?? null;
            $template = $saved ? $this->withPath($saved) : (isset(self::BUILT_IN[$key]) ? $this->builtIn($key) : null);
            $result[$key] = $template ? [
                'custom' => (bool) $saved,
                'settings' => array_intersect_key($template, array_flip(['color', 'x', 'y', 'size', 'align', 'show_age', 'show_date'])),
                'image_url' => $this->imageUrl($template),
            ] : null; // no own template: the card uses "default"
        }
        return $result;
    }

    /** Settings as the editor sends them, cleaned up. */
    public function cleanSettings(array $input): array
    {
        return [
            'color' => preg_match('/^#[0-9a-f]{6}$/i', $input['color'] ?? '') ? strtolower($input['color']) : '#ffffff',
            'x' => max(0, min(100, round((float) ($input['x'] ?? 50), 1))),
            'y' => max(0, min(100, round((float) ($input['y'] ?? 50), 1))),
            'size' => max(16, min(200, (int) ($input['size'] ?? 60))),
            'align' => in_array($input['align'] ?? '', ['left', 'center', 'right'], true) ? $input['align'] : 'center',
            'show_age' => filter_var($input['show_age'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'show_date' => filter_var($input['show_date'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /** Saves the template of $key; $uploadedImage = new image path on the public disk (replaces the old one). */
    public function save(string $key, array $settings, ?string $uploadedImage): void
    {
        $all = $this->saved();
        $old = $all[$key]['image'] ?? null;
        $image = $uploadedImage ?? $old;
        if (!$image) {
            // First save without an upload: keep the built-in image of this klinik (or the default one)
            $image = 'builtin:' . ($this->builtIn($key)['file']);
        }
        $all[$key] = $settings + ['image' => $image];
        $this->write($all);

        if ($uploadedImage && $old && $old !== $uploadedImage && !str_starts_with($old, 'builtin:')) {
            Storage::disk('public')->delete($old);
        }
    }

    /** Back to the built-in template ("default"), or for a klinik: back to using "default" / its built-in one. */
    public function reset(string $key): void
    {
        $all = $this->saved();
        $old = $all[$key]['image'] ?? null;
        unset($all[$key]);
        $this->write($all);
        if ($old && !str_starts_with($old, 'builtin:')) {
            Storage::disk('public')->delete($old);
        }
    }

    /**
     * Template used for a klinik's card, with 'path' = absolute image path:
     * the klinik's saved one, else its built-in one, else "default" (saved, else built-in).
     */
    public function templateFor($klinikId): array
    {
        $key = (string) $klinikId;
        $saved = $this->saved();
        if ($key !== '' && isset($saved[$key])) {
            return $this->withPath($saved[$key]);
        }
        if ($key !== '' && $key !== 'default' && isset(self::BUILT_IN[$key])) {
            return $this->builtIn($key);
        }
        return isset($saved['default']) ? $this->withPath($saved['default']) : $this->builtIn('default');
    }

    /** Whether $key already has an image to keep when saved without an upload. */
    public function hasImage(string $key): bool
    {
        return isset($this->saved()[$key]) || isset(self::BUILT_IN[$key]);
    }

    /** Writes name, age and date on the template image. */
    private function render(array $settings, string $imagePath, string $displayName, $age)
    {
        $s = $this->cleanSettings($settings);
        $img = Image::read($imagePath);
        $width = $img->width();
        $height = $img->height();
        $scale = $width / self::BASE_WIDTH;
        $nameSize = $s['size'] * $scale;
        $x = $width * $s['x'] / 100;
        $nameY = $height * $s['y'] / 100;

        $write = function ($text, $y, $font, $size, $valign = 'middle') use ($img, $x, $s) {
            $img->text($text, (int) round($x), (int) round($y), function ($f) use ($font, $size, $s, $valign) {
                $f->filename(public_path('fonts/' . $font));
                $f->size((int) round($size));
                $f->color($s['color']);
                $f->align($s['align']);
                $f->valign($valign);
            });
        };

        $write($displayName, $nameY, 'Poppins-Bold.ttf', $nameSize);
        if ($s['show_age']) {
            $write("$age tahun", $nameY + $nameSize * 70 / 60, 'Poppins-Regular.ttf', $nameSize * 40 / 60);
        }
        if ($s['show_date']) {
            $write(now()->locale('id')->translatedFormat('d F Y'), $height - 45 * $scale, 'Poppins-Italic.ttf', max(14, $nameSize / 3), 'bottom');
        }

        return $img;
    }

    private function builtIn(string $key): array
    {
        $b = self::BUILT_IN[$key] ?? self::BUILT_IN['default'];
        return ['color' => $b['color'], 'x' => $b['x'], 'y' => $b['y'], 'size' => $b['size'], 'file' => $b['file'], 'image' => 'builtin:' . $b['file'], 'path' => public_path($b['file'])]
            + self::SETTING_DEFAULTS;
    }

    private function withPath(array $saved): array
    {
        $image = $saved['image'] ?? null;
        $path = str_starts_with((string) $image, 'builtin:')
            ? public_path(substr($image, 8))
            : Storage::disk('public')->path($image);
        return $saved + self::SETTING_DEFAULTS + ['path' => $path];
    }

    private function imageUrl(array $template): string
    {
        $image = $template['image'];
        return str_starts_with($image, 'builtin:') ? asset(substr($image, 8)) : asset('storage/' . $image);
    }

    private function saved(): array
    {
        $json = Storage::disk('local')->get(self::CONFIG_FILE);
        return $json ? (json_decode($json, true) ?: []) : [];
    }

    private function write(array $all): void
    {
        Storage::disk('local')->put(self::CONFIG_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
