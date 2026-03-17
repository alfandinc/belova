<?php

namespace App\Models\Finance;

use App\Models\ERM\Visitation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceTransaction extends Model
{
    use HasFactory;

    protected $table = 'finance_transactions';

    protected $fillable = [
        'tanggal',
        'visitation_id',
        'invoice_id',
        'jumlah',
        'jenis_transaksi',
        'metode_bayar',
        'deskripsi',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'jumlah' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function visitation()
    {
        return $this->belongsTo(Visitation::class, 'visitation_id');
    }
}