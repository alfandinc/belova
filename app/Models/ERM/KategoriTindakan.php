<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriTindakan extends Model
{
    use HasFactory;

    protected $table = 'erm_kategori_tindakan';

    protected $fillable = [
        'nama',
    ];

    public function kodeTindakans()
    {
        return $this->belongsToMany(KodeTindakan::class, 'erm_kode_tindakan_kategori', 'kategori_tindakan_id', 'kode_tindakan_id')
            ->withTimestamps();
    }

    public function icd10s()
    {
        return $this->belongsToMany(Icd10::class, 'erm_kategori_tindakan_icd10', 'kategori_tindakan_id', 'icd10_id')
            ->withTimestamps();
    }
}
