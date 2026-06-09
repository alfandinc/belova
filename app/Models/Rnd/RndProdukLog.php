<?php

namespace App\Models\Rnd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RndProdukLog extends Model
{
    use HasFactory;

    protected $table = 'rnd_produk_log';

    public $timestamps = false;

    protected $fillable = [
        'produk_id',
        'log_date_time',
        'status_activity',
        'notes',
    ];

    protected $casts = [
        'log_date_time' => 'datetime',
    ];

    public function produk()
    {
        return $this->belongsTo(RndProduk::class, 'produk_id');
    }
}