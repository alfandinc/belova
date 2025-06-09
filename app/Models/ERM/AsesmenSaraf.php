<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ERM\Visitation;

class AsesmenSaraf extends Model
{
    use HasFactory;

    protected $table = 'erm_asesmen_saraf';

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
        'e',
        'm',
        'v',
        'hsl',
        'vas',
        'diameter_ket',
        'diameter_1',
        'diameter_2',
        'isokor',
        'anisokor',
        'reflek_cahaya',
        'reflek_cahaya1',
        'reflek_cahaya2',
        'reflek_cornea',
        'reflek_cornea1',
        'reflek_cornea2',
        'nervus',
        'kaku_kuduk',
        'sign',
        'brudzinki',
        'kernig',
        'doll',
        'phenomena',
        'vertebra',
        'extremitas',
        'gerak1',
        'gerak2',
        'gerak3',
        'gerak4',
        'reflek_fisio1',
        'reflek_fisio2',
        'reflek_fisio3',
        'reflek_fisio4',
        'reflek_pato1',
        'reflek_pato2',
        'reflek_pato3',
        'reflek_pato4',
        'add_tambahan',
        'clonus',
        'sensibilitas',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}
