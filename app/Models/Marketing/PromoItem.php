<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoItem extends Model
{
    use HasFactory;

    protected $table = 'marketing_promo_items';

    protected $fillable = [
        'promo_id',
        'item_type',
        'item_id',
        'discount_type',
        'discount_value',
        'discount_percent',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'discount_percent' => 'float',
    ];

    public function getResolvedDiscountTypeAttribute(): string
    {
        $type = strtolower(trim((string) ($this->discount_type ?? '')));
        if (in_array($type, ['percent', 'nominal'], true)) {
            return $type;
        }

        return 'percent';
    }

    public function getResolvedDiscountValueAttribute(): float
    {
        if ($this->resolved_discount_type === 'nominal') {
            return round((float) ($this->discount_value ?? 0), 2);
        }

        $fallback = $this->discount_value;
        if ($fallback === null || $fallback === '') {
            $fallback = $this->discount_percent;
        }

        return round((float) ($fallback ?? 0), 2);
    }

    public function calculateDiscountAmount(float $basePrice): float
    {
        $basePrice = max(0, round($basePrice, 2));
        $value = $this->resolved_discount_value;
        if ($value <= 0 || $basePrice <= 0) {
            return 0.0;
        }

        if ($this->resolved_discount_type === 'percent') {
            return round(max(0, $basePrice * ($value / 100)), 2);
        }

        return round(min($basePrice, $value), 2);
    }

    public function calculateDiscountedPrice(float $basePrice): float
    {
        return round(max(0, $basePrice - $this->calculateDiscountAmount($basePrice)), 2);
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class, 'promo_id');
    }
}
