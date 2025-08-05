<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturBeli extends Model
{
    use HasFactory;
    protected $table = 'erm_fakturbeli';
    protected $fillable = [
        'pemasok_id', 'no_faktur', 'received_date', 'requested_date', 'due_date', 'ship_date', 'notes', 'bukti',
        'subtotal', 'global_diskon', 'global_pajak', 'total'
    ];

    public function pemasok()
    {
        return $this->belongsTo(\App\Models\ERM\Pemasok::class, 'pemasok_id');
    }

    public function items()
    {
        return $this->hasMany(\App\Models\ERM\FakturBeliItem::class, 'fakturbeli_id');
    }
}
