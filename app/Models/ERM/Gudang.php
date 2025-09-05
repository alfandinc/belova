<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    use HasFactory;
    
    protected $table = 'erm_gudang';
    
    protected $fillable = [
        'nama', 'lokasi'
    ];

    /**
     * Relasi ke ObatStokGudang
     */
    public function stokObat()
    {
        return $this->hasMany(ObatStokGudang::class, 'gudang_id');
    }

    /**
     * Relasi ke KartuStok
     */
    public function kartuStok()
    {
        return $this->hasMany(KartuStok::class, 'gudang_id');
    }

    /**
     * Relasi ke MutasiGudang sebagai gudang asal
     */
    public function mutasiAsalGudang()
    {
        return $this->hasMany(MutasiGudang::class, 'gudang_asal_id');
    }

    /**
     * Relasi ke MutasiGudang sebagai gudang tujuan
     */
    public function mutasiTujuanGudang()
    {
        return $this->hasMany(MutasiGudang::class, 'gudang_tujuan_id');
    }
}
