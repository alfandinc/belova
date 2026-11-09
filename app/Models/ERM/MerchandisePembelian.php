<?php

namespace App\Models\ERM;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MerchandisePembelian extends Model
{
    public const SUMBER_PEMBELIAN = 'pembelian';

    public const SUMBER_HIBAH = 'hibah';

    public const SUMBER_STOK_AWAL = 'stok_awal';

    protected $table = 'erm_merchandise_pembelian';

    protected $fillable = [
        'merchandise_id', 'sumber', 'tanggal_beli', 'pemasok_id', 'no_nota', 'foto_nota', 'qty',
        'harga_satuan', 'ongkir', 'biaya_lain', 'catatan', 'user_id', 'updated_by',
    ];

    protected $casts = [
        'tanggal_beli' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function (self $pembelian) {
            $pembelian->forceFill(['kode' => 'PB-' . str_pad((string) $pembelian->id, 6, '0', STR_PAD_LEFT)])->saveQuietly();
        });
    }

    public function getTotalAttribute(): float
    {
        return (int) $this->qty * (float) $this->harga_satuan + (float) $this->ongkir + (float) $this->biaya_lain;
    }

    public function getNotaUrlAttribute(): ?string
    {
        return $this->foto_nota ? asset('storage/' . $this->foto_nota) : null;
    }

    public function merchandise()
    {
        return $this->belongsTo(Merchandise::class, 'merchandise_id');
    }

    public function pemasok()
    {
        return $this->belongsTo(MerchandisePemasok::class, 'pemasok_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
