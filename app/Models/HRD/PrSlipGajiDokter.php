<?php
namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrSlipGajiDokter extends Model
{
    use HasFactory;

    protected $table = 'pr_slip_gaji_dokter';

    protected $fillable = [
        'dokter_id',
        'bulan',
        'jasa_konsultasi',
        'jasa_tindakan',
        'uang_duduk',
        'bagi_hasil',
        'pot_pajak',
        'total_pendapatan',
        'total_potongan',
        'total_gaji',
        'status_gaji',
    ];

    public function dokter()
    {
        return $this->belongsTo(\App\Models\ERM\Dokter::class, 'dokter_id');
    }
}
