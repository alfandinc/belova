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
        'tunjangan_jabatan',
        'overtime',
        'uang_duduk',
        'peresepan_obat',
        'rujuk_lab',
        'pembuatan_konten',
        'potongan_lain',
        'bagi_hasil',
        'pot_pajak',
        'jasmed_file',
        'total_pendapatan',
        'total_potongan',
        'total_gaji',
        'status_gaji',
    ];

    /**
     * Cast numeric columns to float for consistency.
     */
    protected $casts = [
        'jasa_konsultasi' => 'float',
        'jasa_tindakan' => 'float',
        'tunjangan_jabatan' => 'float',
        'overtime' => 'float',
        'uang_duduk' => 'float',
        'peresepan_obat' => 'float',
        'rujuk_lab' => 'float',
        'pembuatan_konten' => 'float',
        'bagi_hasil' => 'float',
        'potongan_lain' => 'float',
        'pot_pajak' => 'float',
        'total_pendapatan' => 'float',
        'total_potongan' => 'float',
        'total_gaji' => 'float',
    ];

    public function dokter()
    {
        return $this->belongsTo(\App\Models\ERM\Dokter::class, 'dokter_id');
    }
}
