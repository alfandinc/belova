<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class LabKategori extends Model
{
    protected $table = 'erm_lab_kategori';
    protected $fillable = ['nama'];

    public function labTests()
    {
        return $this->hasMany(LabTest::class, 'lab_kategori_id');
    }
}
