<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiStokItem extends Model
{
    use HasFactory;

    protected $table = 'erm_mutasi_stok_items';

    protected $fillable = [
        'mutasi_stok_id',
        'obat_id',
        'jumlah',
        'keterangan',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    public function mutasiStok()
    {
        return $this->belongsTo(MutasiStok::class, 'mutasi_stok_id');
    }

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id')->withInactive();
    }
}