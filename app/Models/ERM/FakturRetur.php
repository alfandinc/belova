<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturRetur extends Model
{
    use HasFactory;
    protected $table = 'erm_fakturretur';
    protected $fillable = [
        'fakturbeli_id', 'pemasok_id', 'no_retur', 'tanggal_retur', 'notes', 'status', 'approved_by'
    ];

    public function fakturbeli()
    {
        return $this->belongsTo(FakturBeli::class, 'fakturbeli_id');
    }

    public function pemasok()
    {
        return $this->belongsTo(Pemasok::class, 'pemasok_id');
    }

    public function items()
    {
        return $this->hasMany(FakturReturItem::class, 'fakturretur_id');
    }
}
