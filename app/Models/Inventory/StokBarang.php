<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class StokBarang extends Model
{
    use HasFactory;

    protected $table = 'inv_stok_barang';
    protected $fillable = [
        'barang_id',
        'jumlah',
    ];
    
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
