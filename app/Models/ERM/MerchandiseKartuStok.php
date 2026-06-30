<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchandiseKartuStok extends Model
{
    use HasFactory;

    protected $table = 'erm_merchandise_kartu_stok';

    protected $fillable = [
        'merchandise_id',
        'tanggal',
        'type',
        'qty',
        'current_stock',
        'notes',
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

        return max(0, $latest - $qty);
    }

    public function merchandise()
    {
        return $this->belongsTo(Merchandise::class, 'merchandise_id');
    }
}