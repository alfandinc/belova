<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class RadiologiTest extends Model
{
    protected $table = 'erm_radiologi_test';
    protected $fillable = ['nama', 'radiologi_kategori_id', 'harga', 'deskripsi'];

    public function radiologiKategori()
    {
        return $this->belongsTo(RadiologiKategori::class, 'radiologi_kategori_id');
    }

    public function radiologiPermintaan()
    {
        return $this->hasMany(RadiologiPermintaan::class);
    }
}
