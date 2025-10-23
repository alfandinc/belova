<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterFaktur extends Model
{
    use HasFactory;

    protected $table = 'erm_master_faktur';

    protected $fillable = [
        'obat_id',
        'pemasok_id',
        'principal_id',
        'harga',
        'qty_per_box',
        'diskon',
        'diskon_type',
    ];

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
