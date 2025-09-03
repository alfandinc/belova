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
        'total_potongan',
    'total_gaji',
    'status_gaji',
        'total_hari_scheduled',
        'total_hari_masuk',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
