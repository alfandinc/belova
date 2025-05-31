<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class Konsultasi extends Model
{
    protected $table = 'erm_konsultasi';
    protected $fillable = ['nama', 'harga'];

    public function transaksi()
    {
        return $this->morphOne(Transaksi::class, 'transaksible');
    }
}
