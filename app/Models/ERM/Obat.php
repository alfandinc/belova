<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Models\ERM\Supplier;

class Obat extends Model
{
    use HasFactory;

    /**
     * Mendapatkan total stok obat di gudang tertentu
     * @param int $gudangId
     * @return float
     */
    public function getStokByGudang($gudangId)
    {
        return $this->stokGudang()->where('gudang_id', $gudangId)->sum('stok');
    }
    use HasFactory;

    protected $table = 'erm_obat';

    // Relasi ke stok per gudang
    public function stokGudang()
    {
        return $this->hasMany(ObatStokGudang::class, 'obat_id');
    }

    /**
     * Principals (many-to-many)
     */
    public function principals()
    {
        return $this->belongsToMany(Principal::class, 'erm_obat_principal', 'obat_id', 'principal_id');
    }

    // Mendapatkan total stok dari semua gudang
    public function getTotalStokAttribute()
    {
        return $this->stokGudang()->sum('stok');
    }

    protected $fillable = [
        'nama',
        'kode_obat',
        'satuan',
        'dosis',
        'harga_net',
        'harga_fornas',
        'harga_nonfornas',
        'stok',
        'kategori',
        'metode_bayar_id',
        'status_aktif',
        'hpp',
        'hpp_jual',
    ];
    
    /**
     * The "booted" method of the model.
     * This ensures that only active medications are shown by default
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('status_aktif', 1);
        });
    }

    /**
     * Scope to include inactive medications when needed
     */
    public function scopeWithInactive($query)
    {
        return $query->withoutGlobalScope('active');
    }
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function zatAktifs()
    {
        return $this->belongsToMany(ZatAktif::class, 'erm_kandungan_obat', 'obat_id', 'zataktif_id');
    }

    public function metodeBayar()
    {
        return $this->belongsTo(MetodeBayar::class, 'metode_bayar_id');
    }

        /**
         * The tindakan that this obat is bundled with.
         */
        public function tindakans()
        {
            return $this->belongsToMany(Tindakan::class, 'erm_tindakan_obat', 'obat_id', 'tindakan_id');
        }

    /**
     * @deprecated HPP calculation now handled directly in StokService
     * This method assumes harga_beli is stored per batch, but it's not in current design
     * HPP is calculated as weighted average in StokService when adding stock with price
     */
    public function recalculateHPP()
    {
        Log::warning('recalculateHPP() is deprecated. HPP calculation now handled in StokService.');
        
        // Legacy method - no longer functional with current database design
        // HPP calculation is now done in StokService->tambahStok() when hargaBeli is provided
        return false;
    }
}
