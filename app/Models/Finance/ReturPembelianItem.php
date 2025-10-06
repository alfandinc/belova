<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturPembelianItem extends Model
{
    use HasFactory;

    protected $table = 'finance_retur_pembelian_items';

    protected $fillable = [
        'retur_pembelian_id',
        'invoice_item_id',
        'name',
        'quantity_returned',
        'original_unit_price',
        'percentage_cut',
        'unit_price',
        'total_amount',
        'billable_type',
        'billable_id'
    ];

    protected $casts = [
        'original_unit_price' => 'decimal:2',
        'percentage_cut' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'quantity_returned' => 'decimal:2',
    ];

    public function returPembelian()
    {
        return $this->belongsTo(ReturPembelian::class, 'retur_pembelian_id');
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class, 'invoice_item_id');
    }

    // Polymorphic relationship to original billable item (Obat, Tindakan, etc.)
    public function billable()
    {
        return $this->morphTo();
    }
}