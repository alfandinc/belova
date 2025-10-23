<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanItem extends Model
{
    use HasFactory;

    protected $table = 'erm_permintaan_items';

    protected $fillable = [
        'permintaan_id',
        'obat_id',
        'pemasok_id',
        'principal_id',
        'jumlah_box',
        'qty_total',
    ];

    public function permintaan()
    {
        return $this->belongsTo(Permintaan::class, 'permintaan_id');
    }

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function pemasok()
    {
        return $this->belongsTo(Pemasok::class, 'pemasok_id');
    }

    public function principal()
    {
        return $this->belongsTo(Principal::class, 'principal_id');
    }
}
