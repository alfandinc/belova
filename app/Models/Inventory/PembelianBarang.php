<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class PembelianBarang extends Model
{
    use HasFactory;

    protected $table = 'inv_pembelian_barang';
    protected $fillable = [
        'barang_id',
        'gedung_id',
        'dibeli_dari',
        'jumlah',
        'tanggal_pembelian',
        'no_faktur',
        'harga_satuan',
    ];
    
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
    
    public function gedung()
    {
        return $this->belongsTo(Gedung::class, 'gedung_id');
    }
}
