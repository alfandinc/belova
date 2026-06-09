<?php

namespace App\Models\Rnd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RndNotif extends Model
{
    use HasFactory;

    protected $table = 'rnd_notif';

    protected $fillable = [
        'produk_id',
        'doc_path',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function produk()
    {
        return $this->belongsTo(RndProduk::class, 'produk_id');
    }
}