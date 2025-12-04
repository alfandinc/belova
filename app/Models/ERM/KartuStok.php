<?php
namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class KartuStok extends Model
{
    protected $table = 'erm_kartu_stok';

    protected $fillable = [
        'obat_id',
        'gudang_id',
        'tanggal',
        'tipe',
        'qty',
        'stok_setelah',
        'ref_type',
        'ref_id',
        'batch',
        'keterangan'
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'qty' => 'decimal:4',
        'stok_setelah' => 'decimal:4'
    ];

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}
