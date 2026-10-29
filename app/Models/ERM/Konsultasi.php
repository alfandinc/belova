<?php

namespace App\Models\ERM;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Konsultasi extends Model
{
    protected $table = 'erm_konsultasi';
    protected $fillable = ['nama', 'kategori', 'harga'];

    public function billing()
    {
        return $this->morphOne(Billing::class, 'billable');
    }

    /** Spesialisasi this jenis konsultasi is for; none = offered nowhere (a spesialisasi must be chosen). */
    public function spesialisasis()
    {
        return $this->belongsToMany(Spesialisasi::class, 'erm_konsultasi_spesialisasi', 'konsultasi_id', 'spesialisasi_id')
            ->withTimestamps();
    }

    private static ?bool $hasSpesialisasiMapping = null;

    /** Guard for servers that haven't run the erm_konsultasi_spesialisasi migration yet. */
    public static function hasSpesialisasiMapping(): bool
    {
        return self::$hasSpesialisasiMapping ??= Schema::hasTable('erm_konsultasi_spesialisasi');
    }

    /**
     * Jenis konsultasi the dokter can pick for a visit: only those mapped to its spesialisasi (unmapped = nowhere).
     * Only rows named "konsultasi" (the page also holds other fees, and the billing swap matches on that name).
     */
    public function scopeUntukSpesialisasi(Builder $query, ?int $spesialisasiId): Builder
    {
        $query->whereRaw('LOWER(nama) LIKE ?', ['%konsultasi%']);

        if (self::hasSpesialisasiMapping()) {
            $query->whereHas('spesialisasis', fn ($s) => $s->whereKey($spesialisasiId ?? 0));
        }

        // Cheapest first in the "Pilih Biaya Konsultasi" picker; same harga by name
        return $query->orderBy('harga')->orderBy('nama');
    }

    /** May this jenis konsultasi be billed for a visit of that spesialisasi? */
    public function berlakuUntuk(?int $spesialisasiId): bool
    {
        if (!self::hasSpesialisasiMapping()) {
            return true;
        }

        return $spesialisasiId !== null
            && $this->spesialisasis()->where('erm_spesialisasis.id', $spesialisasiId)->exists();
    }
}
