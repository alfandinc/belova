<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ERM\Supplier;
use Illuminate\Database\Eloquent\Builder;

class Obat extends Model
{
    use HasFactory;

    protected $table = 'erm_obat';

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
}
