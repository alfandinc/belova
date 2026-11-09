<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchandiseKartuStok extends Model
{
    use HasFactory;

    public const SUMBER_OPNAME = 'opname';

    protected $table = 'erm_merchandise_kartu_stok';

    protected $fillable = [
        'merchandise_id',
        'tanggal',
        'type',
        'qty',
        'current_stock',
        'notes',
        'user_id',
        'sumber',
        'pembelian_id',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public static function getLatestCurrentStock(int $merchandiseId): int
    {
        $latest = static::query()
            ->where('merchandise_id', $merchandiseId)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->first();

        return (int) ($latest->current_stock ?? 0);
    }

    public static function calculateCurrentStock(int $merchandiseId, string $type, int $qty): int
    {
        $latest = static::getLatestCurrentStock($merchandiseId);

        if ($type === 'in') {
            return $latest + $qty;
        }

        return $latest - $qty;
    }

    public function merchandise()
    {
        return $this->belongsTo(Merchandise::class, 'merchandise_id');
    }

    public function pembelian()
    {
        return $this->belongsTo(MerchandisePembelian::class, 'pembelian_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}