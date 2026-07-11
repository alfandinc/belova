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
        'catatan',
        'created_by',
    ];

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }
}