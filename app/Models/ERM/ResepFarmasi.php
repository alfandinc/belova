<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Model;

class ResepFarmasi extends Model
{
    protected $table = 'erm_resepfarmasi';
    public $incrementing = false; // non auto-increment
    protected $keyType = 'string'; // jika ID-nya string (bukan integer)

    protected $fillable = [
        'id',
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

    public function wadah()
    {
        return $this->belongsTo(WadahObat::class, 'obat_id');
    }

    // Relasi ke Visitation
    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }

    public function billing()
    {
        return $this->morphOne(Billing::class, 'billable');
    }
}
