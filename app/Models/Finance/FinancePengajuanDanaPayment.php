<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

/** One transfer for a pengajuan dana (a pengajuan can be paid in several parts). */
class FinancePengajuanDanaPayment extends Model
{
    protected $table = 'finance_pengajuan_dana_payment';

    protected $fillable = [
        'pengajuan_id',
        'nominal',
        'tanggal_bayar',
        'bukti',
        'note',
        'paid_by',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'tanggal_bayar' => 'datetime',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(FinancePengajuanDana::class, 'pengajuan_id');
    }

    public function paidBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'paid_by');
    }
}
