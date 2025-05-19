<?php

namespace App\Models\ERM;


use Illuminate\Database\Eloquent\Model;

class ResepFarmasi extends Model
{
    protected $table = 'erm_resepfarmasi';

    protected $fillable = [
        'visitation_id',
        'obat_id',
        'jumlah',
        'aturan_pakai',
        'harga',
        'diskon',
        'total',
        'racikan_ke',
        'wadah',
        'bungkus',
        'dosis',
        'dokter_id'
    ];

    // Relasi ke Obat
    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    // Relasi ke Visitation
    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}
