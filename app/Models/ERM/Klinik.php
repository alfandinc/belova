<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Klinik extends Model
{
    protected $table = 'erm_klinik';
    // alamat .. bank_account_name: invoice footer, set in Admin › Klinik Settings (empty = built-in text)
    protected $fillable = ['nama', 'logo', 'color', 'report_cutoff_time', 'alamat', 'telp', 'website', 'bank_name', 'bank_account_number', 'bank_account_name'];

    /** Name without the "Klinik Utama / Pratama" licence prefix, for badges and chips. */
    public static function shortName(string $nama): string
    {
        return preg_replace('/^Klinik\s+(Utama|Pratama)\s+/i', '', $nama);
    }

    /** Public URL of a stored logo path (null when none). */
    public static function logoUrl(?string $logo): ?string
    {
        return $logo ? asset('storage/' . $logo) : null;
    }

    public function visitation()
    {
        return $this->hasMany(Visitation::class, 'klinik_id');
    }

    public function dokters(): BelongsToMany
    {
        return $this->belongsToMany(Dokter::class, 'erm_dokter_kliniks', 'klinik_id', 'dokter_id')
            ->withTimestamps();
    }
}
