<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokOpname extends Model
{
    use HasFactory;
    protected $table = 'erm_stok_opname';
    protected $fillable = [
        'tanggal_opname',
        'gudang_id',
        'periode_bulan',
        'periode_tahun',
        'notes',
        'status',
        'created_by',
    ];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
    public function items()
    {
        return $this->hasMany(StokOpnameItem::class, 'stok_opname_id');
    }
}
