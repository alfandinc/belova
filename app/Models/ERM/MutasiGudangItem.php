<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MutasiGudangItem extends Model
{
    use HasFactory;

    protected $table = 'erm_mutasi_gudang_items';
    protected $fillable = [
        'mutasi_id',
        'obat_id',
        'jumlah',
        'keterangan'
    ];

    protected $casts = [
        'jumlah' => 'integer',
    ];

    public function mutasi()
    {
        return $this->belongsTo(MutasiGudang::class, 'mutasi_id');
    }

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }
}
