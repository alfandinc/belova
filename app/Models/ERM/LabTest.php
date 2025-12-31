<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    protected $table = 'erm_lab_test';
    protected $fillable = ['nama', 'lab_kategori_id', 'harga', 'deskripsi'];

    public function labKategori()
    {
        return $this->belongsTo(LabKategori::class, 'lab_kategori_id');
    }
    
    public function labPermintaan()
    {
        return $this->hasMany(LabPermintaan::class);
    }

    /**
     * Obats associated with this lab test (many-to-many pivot with dosis)
     */
    public function obats()
    {
        return $this->belongsToMany(Obat::class, 'erm_lab_test_obat', 'lab_test_id', 'obat_id')
                    ->withPivot('dosis')
                    ->withTimestamps();
    }
}
