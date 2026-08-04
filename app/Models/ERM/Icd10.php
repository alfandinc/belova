<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class Icd10 extends Model
{
    protected $table = 'erm_icd10';

    protected $fillable = [
        'code',
        'description',
        'category',
    ];

    public function kategoriTindakans()
    {
        return $this->belongsToMany(KategoriTindakan::class, 'erm_kategori_tindakan_icd10', 'icd10_id', 'kategori_tindakan_id')
            ->withTimestamps();
    }
}
