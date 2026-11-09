<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merchandise extends Model
{
    use HasFactory;

    protected $table = 'erm_merchandises';

    protected $fillable = [
        'name', 'description', 'image', 'price', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['image_url'];

    protected static function booted(): void
    {
        // The code comes from the id, so it is unique and never changes
        static::created(function (self $merchandise) {
            $merchandise->forceFill(['kode' => 'MRC-' . str_pad((string) $merchandise->id, 4, '0', STR_PAD_LEFT)])->saveQuietly();
        });
    }

    public function getImageUrlAttribute(): ?string
    {
        return !empty($this->attributes['image']) ? asset('storage/' . $this->attributes['image']) : null;
    }

    public function kliniks()
    {
        return $this->belongsToMany(Klinik::class, 'erm_merchandise_klinik', 'merchandise_id', 'klinik_id')
            ->withTimestamps();
    }

    /**
     * "Milik bersama" when the item belongs to every klinik, otherwise the short klinik names.
     */
    public function klinikLabel(?int $totalKlinik = null): string
    {
        $kliniks = $this->kliniks;
        $totalKlinik ??= Klinik::count();

        if ($kliniks->isEmpty()) {
            return 'Belum diatur';
        }

        if ($kliniks->count() >= $totalKlinik) {
            return 'Milik bersama';
        }

        return $kliniks->sortBy('id')->map(fn (Klinik $k) => Klinik::shortName($k->nama))->implode(', ');
    }

    public function pasienReceipts()
    {
        return $this->hasMany(PasienMerchandise::class, 'merchandise_id');
    }

    public function kartuStok()
    {
        return $this->hasMany(MerchandiseKartuStok::class, 'merchandise_id');
    }

    public function pembelian()
    {
        return $this->hasMany(MerchandisePembelian::class, 'merchandise_id');
    }

    /**
     * Latest purchase price. Items without a recorded purchase fall back to the old manual price.
     */
    public function latestBuyPrice(): ?float
    {
        $price = $this->pembelian()
            ->where('sumber', MerchandisePembelian::SUMBER_PEMBELIAN)
            ->orderByDesc('tanggal_beli')
            ->orderByDesc('id')
            ->value('harga_satuan');

        $price ??= $this->price;

        return $price === null ? null : (float) $price;
    }

    public function hasHistory(): bool
    {
        return $this->kartuStok()->exists() || $this->pasienReceipts()->exists();
    }
}
