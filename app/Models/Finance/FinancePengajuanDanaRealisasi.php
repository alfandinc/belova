<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

/**
 * Realisasi (settlement) of a paid pengajuan dana: what was actually spent.
 * sisa = total dibayar - total realisasi (> 0: money to return, < 0: overspent).
 * status: 'selesai' (settled) or 'menunggu_konfirmasi' (has a difference, waiting for finance).
 */
class FinancePengajuanDanaRealisasi extends Model
{
    protected $table = 'finance_pengajuan_dana_realisasi';

    protected $fillable = [
        'pengajuan_id',
        'total_realisasi',
        'sisa',
        'note',
        'nota',
        'status',
        'bukti_pengembalian',
        'submitted_by',
        'submitted_at',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'total_realisasi' => 'decimal:2',
        'sisa' => 'decimal:2',
        'nota' => 'array',
        'submitted_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(FinancePengajuanDana::class, 'pengajuan_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'submitted_by');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'confirmed_by');
    }
}
