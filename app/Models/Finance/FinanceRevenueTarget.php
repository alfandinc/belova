<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceRevenueTarget extends Model
{
    use HasFactory;

    protected $table = 'finance_revenue_target';

    protected $fillable = [
        'klinik_id',
        'target_amount',
        'periode_bulan',
        'periode_tahun',
        'notes',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'periode_bulan' => 'integer',
        'periode_tahun' => 'integer',
    ];

    public function klinik(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ERM\Klinik::class, 'klinik_id');
    }
}
