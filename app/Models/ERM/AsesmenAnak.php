<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ERM\Visitation;

class AsesmenAnak extends Model
{
    use HasFactory;
    protected $table = 'erm_asesmen_anak';

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
        'imunisasi_dasar',
        'imunisasi_dasar_ket',
        'imunisasi_lanjut',
        'imunisasi_lanjut_ket',
        'td',
        'n',
        'r',
        's',
        'gizi',
        'bb',
        'tb',
        'lk',
        'kepala',
        'leher',
        'thorax',
        'jantung',
        'paru',
        'abdomen',
        'genitalia',
        'extremitas',
        'pemeriksaan_fisik_tambahan',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}
