<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ERM\Visitation;

class AsesmenUmum extends Model
{
    use HasFactory;

    protected $table = 'erm_asesmen_umum';

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
        'kepala',
        'leher',
        'thorax',
        'abdomen',
        'genitalia',
        'ext_atas',
        'ext_bawah',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}
