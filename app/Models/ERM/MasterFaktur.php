<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterFaktur extends Model
{
    use HasFactory;

    protected $table = 'erm_master_faktur';

    /**
     * The principal is no longer stored here: it belongs to the obat (erm_obat.principal_id, Master Obat).
     * The old principal_id column stays until it is dropped and is not written anymore.
     */
    protected $fillable = [
        'obat_id',
        'pemasok_id',
        'harga',
        'qty_per_box',
        'diskon',
        'diskon_type',
        'notes',
    ];

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function pemasok()
    {
        return $this->belongsTo(Pemasok::class, 'pemasok_id');
    }
}
