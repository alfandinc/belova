<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObatExpiredTindakLanjut extends Model
{
    use HasFactory;

    protected $table = 'erm_obat_expired_tindak_lanjut';

    const UPDATED_AT = null;

    protected $fillable = [
        'obat_id',
        'obat_stok_gudang_id',
        'jumlah',
        'expiration_date',
        'tindak_lanjut',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'expiration_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function stokGudang()
    {
        return $this->belongsTo(ObatStokGudang::class, 'obat_stok_gudang_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}