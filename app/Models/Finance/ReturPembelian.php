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

    protected $fillable = [
        'invoice_id',
        'retur_number',
        'total_amount',
        'reason',
        'notes',
        'user_id',
        'processed_date'
    ];

    protected $casts = [
        'processed_date' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

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