<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class StokOpnameTemuan extends Model
{
    protected $table = 'erm_stok_opname_temuan';

    protected $fillable = [
        'stok_opname_id',
        'stok_opname_item_id',
        'qty',
        'jenis',
        'process_status',
        'keterangan',
        'created_by'
    ];

    public function item()
    {
        return $this->belongsTo(StokOpnameItem::class, 'stok_opname_item_id');
    }
}
