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
        'hpp',
        'hpp_jual',
        'discount',
        'discount_type',
        'final_amount',
        'billable_type',
        'billable_id'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'hpp' => 'decimal:2',
        'hpp_jual' => 'decimal:2',
        'discount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Same rule as Billing: a percentage discount stays within 0-100 (final_amount is untouched).
        static::saving(function (InvoiceItem $item) {
            if (in_array($item->discount_type, ['%', 'percent'], true) && $item->discount !== null && $item->discount !== '') {
                $item->discount = min(100, max(0, (float) $item->discount));
            }
        });
    }

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
