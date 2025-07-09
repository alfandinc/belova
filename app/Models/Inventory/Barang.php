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
}
