<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KodeTindakan extends Model
{
    /**
     * Many-to-many relationship to Tindakan
     */
    public function tindakans()
    {
        return $this->belongsToMany(Tindakan::class, 'erm_tindakan_kode_tindakan', 'kode_tindakan_id', 'tindakan_id');
    }
    use HasFactory;

    protected $table = 'erm_kode_tindakan';

    protected $fillable = [
        'kode',
        'nama',
        'hpp',
        'harga_jasmed',
        'harga_jual',
        'harga_bottom',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'hpp' => 'decimal:2',
        'harga_jasmed' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'harga_bottom' => 'decimal:2',
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
