<?php

namespace App\Models\Finance;

use App\Models\ERM\Visitation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'finance_invoices';

    protected $fillable = [
        'visitation_id',
        'invoice_number',
        'subtotal',
        'discount',
        'tax',
        'total_amount',
        'status',
        'payment_date',
        'notes'
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function visitation()
    {
        return $this->belongsTo(Visitation::class);
    }

    // Generate a unique invoice number
    public static function generateInvoiceNumber()
    {
        $prefix = 'INV-' . date('Ymd');
        $lastInvoice = self::where('invoice_number', 'like', $prefix . '%')->latest()->first();

        if (!$lastInvoice) {
            return $prefix . '-0001';
        }

        $lastNumber = intval(substr($lastInvoice->invoice_number, -4));
        return $prefix . '-' . str_pad(($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}
