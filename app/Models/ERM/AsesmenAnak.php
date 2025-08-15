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
        'allo_dengan',
        'anamnesis2',
        'keluhan_utama',
        'riwayat_penyakit_sekarang',
        'riwayat_penyakit_dahulu',
        'hasil_allo',
        'riwayat_penyakit_keluarga',
        'riwayat_makanan',
        'riwayat_tumbang',
        'riwayat_kehamilan',
        'riwayat_persalinan',
        'imunisasi_dasar',
        'imunisasi_dasar_ket',
        'imunisasi_lanjut',
        'imunisasi_lanjut_ket',
        'keadaan_umum',
        'e',
        'v',
        'm',
        'hsl',
        'td',
        'n',
        's',
        'r',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}
