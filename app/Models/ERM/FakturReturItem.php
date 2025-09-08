<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturReturItem extends Model
{
    use HasFactory;
    protected $table = 'erm_fakturretur_items';
    protected $fillable = [
        'fakturretur_id', 'fakturbeli_item_id', 'obat_id', 'gudang_id', 'qty', 'batch', 'expiration_date', 'alasan', 'status'
    ];

    public function fakturretur()
    {
        return $this->belongsTo(FakturRetur::class, 'fakturretur_id');
    }

    public function fakturbeliitem()
    {
        return $this->belongsTo(FakturBeliItem::class, 'fakturbeli_item_id');
    }

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}
