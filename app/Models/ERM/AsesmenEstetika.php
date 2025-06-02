<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class AsesmenEstetika extends Model
{
    protected $table = 'erm_asesmen_estetika';

    protected $fillable = [
        'visitation_id',
        'autoanamnesis',
        'alloanamnesis',
        'anamnesis1',
        'anamnesis2',
        'keluhan_utama',
        'riwayat_penyakit_sekarang',
        'allo_dengan',
        'hasil_allo',
        'riwayat_penyakit_dahulu',
        'obat_dikonsumsi',
        'keadaan_umum',
        'td',
        'n',
        'r',
        's',
        'kebiasaan_makan',
        'kebiasaan_minum',
        'pola_tidur',
        'kontrasepsi',
        'riwayat_perawatan',
        'jenis_kulit',
        'kelembaban',
        'kekenyalan',
        'area_kerutan',
        'kelainan_kulit',
        'anjuran',
        'status_lokalis',
        'ket_status_lokalis',
    ];

    protected $casts = [
        'keluhan_utama'     => 'array',
        'kebiasaan_makan'   => 'array',
        'kebiasaan_minum'   => 'array',
        'area_kerutan'      => 'array',
        'kelainan_kulit'    => 'array',
        'kelembapan'        => 'integer',
        'kekenyalan'        => 'integer',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}
