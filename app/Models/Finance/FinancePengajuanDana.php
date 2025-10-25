<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinancePengajuanDana extends Model
{
    use HasFactory;

    protected $table = 'finance_pengajuan_dana';

    protected $fillable = [
        'kode_pengajuan',
        'employee_id',
        'division_id',
        'tanggal_pengajuan',
        'jenis_pengajuan',
        'deskripsi',
        'status',
        'rekening_id',
        'bukti_transaksi',
        'grand_total',
    ];

    protected $casts = [
        'tanggal_pengajuan' => 'date',
        'bukti_transaksi' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(FinancePengajuanDanaItem::class, 'pengajuan_id');
    }

    public function approvals()
    {
        return $this->hasMany(FinancePengajuanDanaApproval::class, 'pengajuan_id');
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\HRD\Employee::class, 'employee_id');
    }

    public function division()
    {
        return $this->belongsTo(\App\Models\HRD\Division::class, 'division_id');
    }

    public function rekening()
    {
        return $this->belongsTo(\App\Models\Finance\FinanceRekening::class, 'rekening_id');
    }
}
