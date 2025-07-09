<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class TipeBarang extends Model
{
    use HasFactory;

    protected $table = 'inv_tipe_barang';
    protected $fillable = [
        'name',
        'description',
        'maintenance',
    ];
}
