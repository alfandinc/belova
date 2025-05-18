<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class AsesmenPerawat extends Model
{
    protected $table = 'erm_asesmen_perawats';

    protected $fillable = [
        'visitation_id',
        'user_id',
        'keluhan_utama',
        'alasan_kunjungan',
        'kesadaran',
        'td',
        'nadi',
        'rr',
        'suhu',
        'riwayat_psikososial',
        'tb',
        'bb',
        'lla',
        'diet',
        'porsi',
        'imt',
        'presentase',
        'efek',
        'nyeri',
        'p',
        'q',
        'r',
        't',
        'onset',
        'skor',
        'kategori',
        'kategori_risja',
        'status_fungsional',
        'masalah_keperawatan',
    ];

    protected $casts = [
        // Ini yang paling penting:
        'masalah_keperawatan' => 'array',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
}
