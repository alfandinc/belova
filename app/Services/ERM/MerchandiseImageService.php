<?php

namespace App\Services\ERM;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Laravel\Facades\Image;

class MerchandiseImageService
{
    /**
     * Stores a shrunken JPG on the public disk and returns its path.
     */
    public function store(UploadedFile $file, string $folder, int $maxPx, string $field): string
    {
        try {
            $encoded = Image::read($file)->scaleDown($maxPx, $maxPx)->toJpeg(80);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([$field => 'Gambar tidak bisa dibaca. Coba file lain.']);
        }

        $path = $folder . '/' . Str::random(40) . '.jpg';
        Storage::disk('public')->put($path, (string) $encoded);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
