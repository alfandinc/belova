<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinancePengajuanDanaApproval extends Model
{
    use HasFactory;

    protected $table = 'finance_pengajuan_dana_approval';

    protected $fillable = [
        'pengajuan_id',
        'approver_id',
        'status',
        'tanggal_approve',
    ];

    protected $casts = [
        'tanggal_approve' => 'datetime',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(FinancePengajuanDana::class, 'pengajuan_id');
    }

    public function approver()
    {
        return $this->belongsTo(FinanceDanaApprover::class, 'approver_id');
    }
}
