<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'finance_invoice_items';

    protected $fillable = [
        'invoice_id',
        'name',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'discount_type',
        'final_amount',
        'billable_type',
        'billable_id'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Polymorphic relationship to original billable item
    public function billable()
    {
        return $this->morphTo();
    }
}
