<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KartuStok extends Model
{
    use HasFactory;

    protected $table = 'inv_kartu_stok';
    protected $fillable = [
        'barang_id',
        'stok_awal',
        'stok_masuk',
        'stok_keluar',
        'stok_akhir',
        'keterangan',
        'reference_type',
        'reference_id',
        'user_id',
        'tanggal',
    ];

    protected $dates = ['tanggal'];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
