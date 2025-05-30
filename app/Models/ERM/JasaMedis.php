<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class JasaMedis extends Model
{
    protected $table = 'erm_jasamedis';
    protected $fillable = ['nama', 'harga', 'jenis'];

    public function transaksi()
    {
        return $this->morphOne(Transaksi::class, 'transaksible');
    }
}
