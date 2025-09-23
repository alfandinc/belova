<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class Rujuk extends Model
{
    protected $table = 'erm_rujuks';

    protected $fillable = [
        'pasien_id',
        'dokter_pengirim_id',
        'dokter_tujuan_id',
        'jenis_permintaan',
        'keterangan',
        'penunjang',
        'visitation_id',
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function dokterPengirim()
    {
        return $this->belongsTo(Dokter::class, 'dokter_pengirim_id');
    }

    public function dokterTujuan()
    {
        return $this->belongsTo(Dokter::class, 'dokter_tujuan_id');
    }

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}
