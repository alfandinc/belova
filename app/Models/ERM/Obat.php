<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ERM\Supplier;

class Obat extends Model
{
    use HasFactory;

    protected $table = 'erm_obat';
    // public $incrementing = false; // karena kolom id bukan auto-increment
    // protected $keyType = 'string'; // karena kolom id bertipe string

    protected $fillable = [
        'id',
        'nama',
        'satuan',
        'dosis',
        'harga_het',
        'harga_fornas',
        'harga_nonfornas',

        'stok',
        'supplier_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function zatAktifs()
    {
        return $this->belongsToMany(ZatAktif::class, 'erm_kandungan_obat', 'obat_id', 'zataktif_id');
    }
    public function metodeBayar()
    {
        return $this->belongsTo(MetodeBayar::class, 'metode_bayar_id');
    }
}
