<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class MaintenanceBarang extends Model
{
    use HasFactory;

    protected $table = 'inv_maintenance_barang';
    protected $fillable = [
        'barang_id',
        'tanggal_maintenance',
        'biaya_maintenance',
        'nama_vendor',
        'no_faktur',
        'tanggal_next_maintenance',
        'keterangan',
    ];
}
