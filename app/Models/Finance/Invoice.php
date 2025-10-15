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
        'amount_paid',
        'change_amount',
        'shortage_amount',
        'payment_method',
        'status',
        'payment_date',
        'notes',
        'user_id',
        'discount_type',
        'discount_value',
        'tax_percentage'
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'shortage_amount' => 'decimal:2',
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
        // Add hour, minute, second to prefix for uniqueness
        $prefix = 'INV-' . date('Ymd-His');
        // Check if invoice with this prefix already exists
        $exists = self::where('invoice_number', $prefix)->exists();
        if (!$exists) {
            return $prefix;
        }
        // If exists, append a random 3-digit number
        return $prefix . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
}
