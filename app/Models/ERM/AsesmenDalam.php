<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ERM\Visitation;

class AsesmenDalam extends Model
{
    use HasFactory;

    protected $table = 'erm_asesmen_dalam';

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
        'qenitalia',
        'ext_atas',
        'ext_bawah',
        'status_lokalis',
        'ket_status_lokalis',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}
