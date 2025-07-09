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
