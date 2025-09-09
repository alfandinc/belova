<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KodeTindakan extends Model
{
    use HasFactory;

    protected $table = 'erm_kode_tindakan';

    protected $fillable = [
        'kode',
        'nama',
        'hpp',
        'harga_jasmed',
    ];

    /**
     * Many-to-many relationship to Obat
     */
    public function obats()
    {
        return $this->belongsToMany(Obat::class, 'erm_kode_tindakan_obat', 'kode_tindakan_id', 'obat_id')
            ->withPivot('qty', 'dosis', 'satuan_dosis')
            ->withTimestamps();
    }
}
