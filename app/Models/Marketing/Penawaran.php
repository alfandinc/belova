<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class Penawaran extends Model
{
    protected $table = 'marketing_penawarans';

    protected $fillable = [
        'pasien_id',
        'klinik_id',
        'dokter_id',
        'metode_bayar_id',
        'visitation_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function pasien()
    {
        return $this->belongsTo(\App\Models\ERM\Pasien::class, 'pasien_id');
    }

    public function visitation()
    {
        return $this->belongsTo(\App\Models\ERM\Visitation::class, 'visitation_id');
    }

    public function klinik()
    {
        return $this->belongsTo(\App\Models\ERM\Klinik::class, 'klinik_id');
    }

    public function dokter()
    {
        return $this->belongsTo(\App\Models\ERM\Dokter::class, 'dokter_id');
    }

    public function metodeBayar()
    {
        return $this->belongsTo(\App\Models\ERM\MetodeBayar::class, 'metode_bayar_id');
    }

    public function items()
    {
        return $this->hasMany(PenawaranItem::class, 'penawaran_id');
    }
}
