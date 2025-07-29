<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ERM\Visitation;

class AsesmenPenunjang extends Model
{
    use HasFactory;

    protected $table = 'erm_asesmen_penunjang';

    protected $fillable = [
        'visitation_id',
        'lab_gambar',
        'lab_catatan',
        'radiologi_gambar',
        'radiologi_catatan',
        'usg_gambar',
        'usg_catatan',
        'rekamjantung_gambar',
        'rekamjantung_catatan',
        'diagnosakerja_1',
        'diagnosakerja_2',
        'diagnosakerja_3',
        'diagnosakerja_4',
        'diagnosakerja_5',
        'diagnosakerja_6',
        'diagnosa_banding',
        'masalah_medis',
        'masalah_keperawatan',
        'sasaran',
        'standing_order',
        'rtl',
        'ruang',
        'dpip',
        'indikasi',
        'pengantar',
        'rujuk_ke',
        'rujuk_rs',
        'tujuk_dokter',
        'rujuk_puskesmas',
        'rujuk_dokter',
        'homecare',
        'tanggal_homecare',
        'edukasi_1',
        'edukasi_2',
        'edukasi_3',
        'hubungan_pasien',
        'alasan',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}
