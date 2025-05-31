<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Model;

class PaketTindakan extends Model
{
    protected $table = 'erm_paket_tindakan';
    protected $fillable = ['nama', 'deskripsi', 'harga_paket'];

    public function tindakan()
    {
        return $this->belongsToMany(Tindakan::class, 'erm_paket_tindakan_detail');
    }

    public function transaksi()
    {
        return $this->morphMany(Transaksi::class, 'transaksible');
    }
}
