<?php

namespace App\Models\Rnd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RndSampleLog extends Model
{
    use HasFactory;

    protected $table = 'rnd_sample_log';

    protected $fillable = [
        'produk_id',
        'no_produksi',
        'status_sample',
        'notes',
    ];

    public function produk()
    {
        return $this->belongsTo(RndProduk::class, 'produk_id');
    }
}