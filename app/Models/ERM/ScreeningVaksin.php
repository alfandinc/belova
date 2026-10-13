<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class ScreeningVaksin extends Model
{
    protected $table = 'erm_screening_vaksin';

    protected $fillable = [
        'visitation_id',
        'sakit_hari_ini',
        'alergi_obat_makanan_vaksin',
        'efek_samping_vaksin_berat',
        'gangguan_kekebalan_tubuh',
        'obat_steroid_atau_terapi',
        'transfusi_darah_atau_imunoglobulin',
        'hamil_atau_rencana_hamil',
        'vaksinasi_4_minggu_terakhir',
        'rencana_vaksin',
        'catatan',
        'created_by',
    ];

    /** Ya/tidak questions (same wording as the screening modal); a "ya" is a contraindication to check. */
    public const QUESTIONS = [
        'sakit_hari_ini' => 'Apakah Anda sakit hari ini?',
        'alergi_obat_makanan_vaksin' => 'Apakah Anda mempunyai alergi obat, makanan, atau vaksin?',
        'efek_samping_vaksin_berat' => 'Apakah Anda pernah mengalami efek samping berat setelah mendapat vaksinasi?',
        'gangguan_kekebalan_tubuh' => 'Apakah Anda menderita kanker solid atau darah, AIDS, atau penyakit lain yang menyebabkan masalah kekebalan tubuh?',
        'obat_steroid_atau_terapi' => 'Dalam waktu enam bulan terakhir, apakah Anda mendapatkan obat steroid, obat anti kanker, atau radioterapi?',
        'transfusi_darah_atau_imunoglobulin' => 'Dalam beberapa tahun terakhir, apakah Anda pernah mendapat transfusi darah atau produk darah, atau imun (gama) globulin?',
        'hamil_atau_rencana_hamil' => 'Untuk wanita: Apakah Anda hamil, atau kemungkinan akan/mau hamil dalam beberapa bulan berikut?',
        'vaksinasi_4_minggu_terakhir' => 'Apakah Anda pernah mendapat vaksinasi dalam 4 minggu sebelum ini?',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
}