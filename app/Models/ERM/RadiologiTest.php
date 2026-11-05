<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class RadiologiTest extends Model
{
    protected $table = 'erm_radiologi_test';
    protected $fillable = ['nama', 'radiologi_kategori_id', 'harga', 'tambahan_konsultasi', 'deskripsi'];
    protected $casts = ['tambahan_konsultasi' => 'boolean'];

    public function radiologiKategori()
    {
        return $this->belongsTo(RadiologiKategori::class, 'radiologi_kategori_id');
    }

    public function radiologiPermintaan()
    {
        return $this->hasMany(RadiologiPermintaan::class);
    }

    /** Spesialisasi whose dokter see this test as a tambahan in the "Pilih Biaya Konsultasi" popup */
    public function spesialisasis()
    {
        return $this->belongsToMany(Spesialisasi::class, 'erm_radiologi_test_spesialisasi', 'radiologi_test_id', 'spesialisasi_id')
            ->withTimestamps();
    }

    /** Tests offered as tambahan konsultasi to a visit of that spesialisasi (none before the migration ran) */
    public static function tambahanUntuk(?int $spesialisasiId)
    {
        if (!Konsultasi::hasRadiologiPaket() || !$spesialisasiId) {
            return collect();
        }

        return static::where('tambahan_konsultasi', true)
            ->whereHas('spesialisasis', fn ($q) => $q->whereKey($spesialisasiId))
            ->orderBy('harga')->orderBy('nama')->get(['id', 'nama', 'harga']);
    }
}
