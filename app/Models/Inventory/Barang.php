<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'inv_barang';
    protected $fillable = [
        'name',
        'tipe_barang_id',
        'ruangan_id',
        'kode',
        'satuan',
        'merk',
        'spec',
        'depreciation_rate',
        // Alat tindakan: cost charged into paket prices (Hitung Harga Paket)
        'is_alat_tindakan',
        'harga_perolehan',
        'tanggal_perolehan',
        'umur_pakai_tahun',
        'target_pakai_per_bulan',
    ];

    protected $casts = [
        'is_alat_tindakan' => 'boolean',
        'harga_perolehan' => 'float',
        'tanggal_perolehan' => 'date:Y-m-d',
        'umur_pakai_tahun' => 'integer',
        'target_pakai_per_bulan' => 'integer',
    ];
    
    public function tipeBarang()
    {
        return $this->belongsTo(TipeBarang::class, 'tipe_barang_id');
    }
    
    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_id');
    }
    
    public function stokBarang()
    {
        return $this->hasOne(StokBarang::class, 'barang_id');
    }
    
    public function pembelianBarang()
    {
        return $this->hasMany(PembelianBarang::class, 'barang_id');
    }
    
    public function maintenanceBarang()
    {
        return $this->hasMany(MaintenanceBarang::class, 'barang_id');
    }
}
