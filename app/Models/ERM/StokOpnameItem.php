<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokOpnameItem extends Model
{
    use HasFactory;
    protected $table = 'erm_stok_opname_items';
    protected $fillable = [
        'stok_opname_id',
        'obat_id',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'notes',
    ];

    public function stokOpname()
    {
        return $this->belongsTo(StokOpname::class, 'stok_opname_id');
    }
    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }
}
