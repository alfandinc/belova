<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Custom letterhead images of the PDFs (see the pdf_kop migration). Read through App\Support\PdfLetterhead,
 * which falls back to the built-in artwork for every empty column.
 */
class PdfKop extends Model
{
    public const LINGKUP_KLINIK = 'klinik';
    public const LINGKUP_PERUSAHAAN = 'perusahaan';
    public const LINGKUP_LAB = 'lab';

    public const GAMBAR = ['header', 'footer', 'logo', 'ikon'];

    protected $table = 'pdf_kop';

    protected $fillable = [
        'lingkup', 'klinik_id',
        'header', 'header_asli', 'footer', 'footer_asli', 'logo', 'logo_asli', 'ikon', 'ikon_asli',
        'nama', 'alamat', 'updated_by',
    ];

    /** @var array<string, ?self> rows already read in this request */
    private static array $memo = [];

    /** The row of a lingkup (and klinik), or null. Null as well while the table is not migrated yet. */
    public static function untuk(string $lingkup, ?int $klinikId = null): ?self
    {
        $key = $lingkup . ':' . ($klinikId ?? '');
        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key];
        }
        try {
            $row = self::where('lingkup', $lingkup)
                ->where(function ($q) use ($klinikId) {
                    $klinikId === null ? $q->whereNull('klinik_id') : $q->where('klinik_id', $klinikId);
                })
                ->orderBy('id')
                ->first();
        } catch (\Illuminate\Database\QueryException $e) {
            $row = null; // table missing (migration not run yet): built-in artwork
        }

        return self::$memo[$key] = $row;
    }

    /** Row for writing (created when missing). */
    public static function ambilAtauBuat(string $lingkup, ?int $klinikId = null): self
    {
        $row = self::untuk($lingkup, $klinikId) ?? self::create(['lingkup' => $lingkup, 'klinik_id' => $klinikId]);
        self::lupakan();

        return $row;
    }

    public static function lupakan(): void
    {
        self::$memo = [];
    }

    /** Absolute path of a processed image on the public disk, or null when not uploaded / file missing. */
    public function path(string $gambar): ?string
    {
        $rel = $this->{$gambar} ?? null;
        if (!$rel) {
            return null;
        }
        $path = \Illuminate\Support\Facades\Storage::disk('public')->path($rel);

        return is_file($path) ? $path : null;
    }

    public static function tabelAda(): bool
    {
        try {
            return Schema::hasTable('pdf_kop');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
