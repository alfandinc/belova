<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class PenawaranItem extends Model
{
    protected $table = 'marketing_penawaran_items';

    protected $fillable = [
        'penawaran_id',
        'obat_id',
        'jumlah',
        'dosis',
        'bungkus',
        'racikan_ke',
        'aturan_pakai',
        'wadah_id',
        'harga',
        'diskon',
        'total',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id');
    }

    public function obat()
    {
        return $this->belongsTo(\App\Models\ERM\Obat::class, 'obat_id');
    }

    public function wadah()
    {
        return $this->belongsTo(\App\Models\ERM\WadahObat::class, 'wadah_id');
    }
}
