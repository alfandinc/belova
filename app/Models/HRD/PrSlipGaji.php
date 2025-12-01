<?php
namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrSlipGaji extends Model
{
    use HasFactory;
    protected $table = 'pr_slip_gaji';
    protected $fillable = [
        'employee_id',
        'bulan',
        'gaji_perhari',
        'gaji_perjam',
        'gaji_pokok',
        'tunjangan_jabatan',
        'tunjangan_masa_kerja',
        'uang_makan',
        'kpi_poin',
        'poin_kehadiran',
        'poin_penilaian',
        'poin_marketing',
        'uang_kpi',
        'jasa_medis',
        'jasmed_file',
        'total_jam_lembur',
        'uang_lembur',
        'potongan_pinjaman',
        'potongan_bpjs_kesehatan',
        'potongan_jamsostek',
        'potongan_penalty',
        'potongan_lain',
        'benefit_bpjs_kesehatan',
        'benefit_jht',
        'benefit_jkk',
        'benefit_jkm',
        'total_pendapatan',
        'pendapatan_tambahan',
        'total_potongan',
    'total_gaji',
    'status_gaji',
        'total_hari_scheduled',
        'total_hari_masuk',
    ];

    /**
     * Cast numeric and array fields for consistency.
     */
    protected $casts = [
        'pendapatan_tambahan' => 'array',
        'gaji_perhari' => 'float',
        'gaji_perjam' => 'float',
        'gaji_pokok' => 'float',
        'tunjangan_jabatan' => 'float',
        'tunjangan_masa_kerja' => 'float',
        'uang_makan' => 'float',
        'uang_kpi' => 'float',
        'jasa_medis' => 'float',
        'total_jam_lembur' => 'float',
        'uang_lembur' => 'float',
        'total_pendapatan' => 'float',
        'total_potongan' => 'float',
        'total_gaji' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
