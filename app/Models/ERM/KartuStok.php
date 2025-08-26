<?php
namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class KartuStok extends Model
{
    protected $table = 'erm_kartu_stok';

    protected $fillable = [
        'obat_id',
        'tanggal',
        'tipe',
        'qty',
        'stok_setelah',
        'ref_type',
        'ref_id',
    ];

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }
}
