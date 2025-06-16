<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    protected $table = 'erm_lab_test';
    protected $fillable = ['nama', 'lab_category_id', 'harga', 'deskripsi'];

    public function labKategori()
    {
        return $this->belongsTo(LabKategori::class);
    }
    
    public function labPermintaan()
    {
        return $this->hasMany(LabPermintaan::class);
    }
}
