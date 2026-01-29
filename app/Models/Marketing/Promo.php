<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory;

    protected $table = 'marketing_promos';

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function promoItems()
    {
        return $this->hasMany(PromoItem::class, 'promo_id');
    }

    // Computed status based on current date and promo periode
    public function getStatusAttribute()
    {
        $now = \Carbon\Carbon::now()->startOfDay();
        $start = $this->start_date ? \Carbon\Carbon::parse($this->start_date)->startOfDay() : null;
        $end = $this->end_date ? \Carbon\Carbon::parse($this->end_date)->endOfDay() : null;

        if ($start && $end) {
            return ($now->between($start, $end)) ? 'active' : 'inactive';
        }
        if ($start && !$end) {
            return $now->gte($start) ? 'active' : 'inactive';
        }
        if (!$start && $end) {
            return $now->lte($end) ? 'active' : 'inactive';
        }
        // No dates defined: treat as inactive by default
        return 'inactive';
    }
}
