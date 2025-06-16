<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class RadiologiKategori extends Model
{
    protected $table = 'erm_radiologi_kategori';
    protected $fillable = ['nama'];

    public function radiologiTests()
    {
        return $this->hasMany(RadiologiTest::class, 'radiologi_kategori_id');
    }
}
