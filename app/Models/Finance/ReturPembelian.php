<?php

namespace App\Models\Finance;

use App\Models\ERM\Visitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturPembelian extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_retur_pembelian';

    // Pending: requested, no stock/cash effect yet. Approved: stock returned + refund recorded.
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'invoice_id',
        'retur_number',
        'total_amount',
        'reason',
        'notes',
        'user_id',
        'processed_date',
        'status',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'processed_date' => 'datetime',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Returs that hold item quantity (pending + approved), used for the remaining-qty check.
     */
    public function scopeNotRejected($query)
    {
        return $query->where('status', '!=', self::STATUS_REJECTED);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(ReturPembelianItem::class, 'retur_pembelian_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Generate a unique retur number
    public static function generateReturNumber()
    {
        $prefix = 'RET-' . date('Ymd-His');
        $exists = self::where('retur_number', $prefix)->exists();
        if (!$exists) {
            return $prefix;
        }
        return $prefix . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
}