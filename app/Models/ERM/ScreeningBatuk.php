<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class ScreeningBatuk extends Model
{
    protected $table = 'erm_screening_batuk';
    
    protected $fillable = [
        'visitation_id',
        // Sesi Gejala
        'demam_badan_panas',
        'batuk_pilek',
        'sesak_nafas',
        'kontak_covid',
        'perjalanan_luar_negeri',
        // Sesi Faktor Resiko
        'riwayat_perjalanan',
        'kontak_erat_covid',
        'faskes_covid',
        'kontak_hewan',
        'riwayat_demam',
        'riwayat_kontak_luar_negeri',
        // Sesi Tools Screening Batuk
        'riwayat_pengobatan_tb',
        'sedang_pengobatan_tb',
        'batuk_demam',
        'nafsu_makan_menurun',
        'bb_turun',
        'keringat_malam',
        'sesak_nafas_tb',
        'kontak_erat_tb',
        'hasil_rontgen',
        // Others
        'catatan',
        'created_by'
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
}
