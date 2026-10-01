<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MetodeBayar extends Model
{
    protected $table = 'erm_metode_bayar';

    protected $fillable = ['nama', 'is_asuransi', 'is_active'];

    protected $casts = [
        'is_asuransi' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeUmum(Builder $query): Builder
    {
        return $query->where('is_asuransi', false);
    }

    public function scopeAsuransi(Builder $query): Builder
    {
        return $query->where('is_asuransi', true);
    }

    public const CACHE_KEY = 'erm_metode_bayar_list';

    /**
     * All metode bayar (incl. inactive, for labels of old visits), Umum first then Asuransi.
     * Use ->where('is_active', true) on the result for pickers.
     */
    public static function cachedList()
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            return self::select('id', 'nama', 'is_asuransi', 'is_active')
                ->orderBy('is_asuransi')
                ->orderBy('nama')
                ->get();
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('erm_metode_bayar'); // legacy key
    }

    public function getGroupLabelAttribute(): string
    {
        return $this->is_asuransi ? 'Asuransi' : 'Umum';
    }

    public function visitations()
    {
        return $this->hasMany(Visitation::class, 'metode_bayar_id');
    }
    public function obat()
    {
        return $this->hasMany(Obat::class, 'metode_bayar_id');
    }
}
