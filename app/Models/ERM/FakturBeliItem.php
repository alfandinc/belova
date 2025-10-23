<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturBeliItem extends Model
{
    use HasFactory;
    protected $table = 'erm_fakturbeli_items';
    protected $fillable = [
        'fakturbeli_id', 'obat_id', 'principal_id', 'qty', 'sisa', 'harga', 'diskon', 'diskon_type', 'tax', 'tax_type', 'gudang_id', 'batch', 'expiration_date', 'diminta', 'total_amount'
    ];

    public function obat()
    {
        return $this->belongsTo(\App\Models\ERM\Obat::class, 'obat_id');
    }

    public function gudang()
    {
        return $this->belongsTo(\App\Models\ERM\Gudang::class, 'gudang_id');
    }

    public function fakturbeli()
    {
        return $this->belongsTo(\App\Models\ERM\FakturBeli::class, 'fakturbeli_id');
    }

    public function principal()
    {
        return $this->belongsTo(\App\Models\ERM\Principal::class, 'principal_id');
    }
}
