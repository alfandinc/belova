<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Finance\Invoice;
use App\Models\ERM\Visitation;

class Piutang extends Model
{
    use HasFactory;

    protected $table = 'finance_piutangs';

    protected $fillable = [
        'visitation_id',
        'invoice_id',
        'amount',
        'payment_status',
        'payment_date',
        'payment_method',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2'
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
