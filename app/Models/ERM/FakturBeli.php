<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturBeli extends Model
{
    use HasFactory;
    protected $table = 'erm_fakturbeli';
    protected $fillable = [
        'permintaan_id', 'pemasok_id', 'no_faktur', 'no_permintaan', 'received_date', 'requested_date', 'due_date', 'ship_date', 'notes', 'bukti',
        'subtotal', 'global_diskon', 'global_pajak', 'total', 'status', 'approved_by', 'replaced_fakturbeli_id', 'source_retur_id'
    ];

    public function permintaan()
    {
        return $this->belongsTo(\App\Models\ERM\Permintaan::class, 'permintaan_id');
    }

    public function pemasok()
    {
        return $this->belongsTo(\App\Models\ERM\Pemasok::class, 'pemasok_id');
    }

    public function items()
    {
        return $this->hasMany(\App\Models\ERM\FakturBeliItem::class, 'fakturbeli_id');
    }

    public function returs()
    {
        return $this->hasMany(\App\Models\ERM\FakturRetur::class, 'fakturbeli_id');
    }

    public function replacementInvoices()
    {
        return $this->hasMany(self::class, 'replaced_fakturbeli_id');
    }

    public function replacedFaktur()
    {
        return $this->belongsTo(self::class, 'replaced_fakturbeli_id');
    }

    public function sourceRetur()
    {
        return $this->belongsTo(\App\Models\ERM\FakturRetur::class, 'source_retur_id');
    }
}
