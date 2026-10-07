<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ERM\Alergi;

class ZatAktif extends Model
{
    use HasFactory;

    protected $table = 'erm_zataktif';

    protected $fillable = [
        'nama',
    ];

    // Relasi ke tabel erm_alergi
    public function alergi()
    {
        return $this->hasMany(Alergi::class, 'zataktif_id');
    }

    public function obats()
    {
        return $this->belongsToMany(Obat::class, 'erm_kandungan_obat', 'zataktif_id', 'obat_id');
    }

    /**
     * Stored form of a name: trimmed, single spaces, uppercase (matches existing data).
     */
    public static function cleanNama(string $nama): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', $nama)));
    }

    /**
     * Comparison key for duplicates: letters and digits only ("Vitamin D3" == "VITAMIN-D 3").
     */
    public static function compareKey(string $nama): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($nama));
    }

    /**
     * Existing zat aktif whose name is the same as $nama after normalization.
     */
    public static function findSameName(string $nama, ?int $exceptId = null): ?self
    {
        $key = self::compareKey($nama);
        if ($key === '') {
            return null;
        }

        // ~1.3k short rows: comparing in PHP is cheap and catches punctuation/spacing differences
        return self::query()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->get(['id', 'nama'])
            ->first(fn ($z) => self::compareKey((string) $z->nama) === $key);
    }
}
