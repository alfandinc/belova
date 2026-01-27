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
        'discount_percent',
    ];

    public function promo()
    {
        return $this->belongsTo(Promo::class, 'promo_id');
    }
}
