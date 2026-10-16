<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

/**
 * One admin edit of a processed invoice (item prices / discounts, invoice discount / tax), with the reason
 * and the values before and after.
 */
class InvoiceEditLog extends Model
{
    protected $table = 'finance_invoice_edit_logs';

    protected $fillable = [
        'invoice_id',
        'user_id',
        'reason',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
