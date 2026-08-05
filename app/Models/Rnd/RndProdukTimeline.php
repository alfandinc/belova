<?php

namespace App\Models\Rnd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RndProdukTimeline extends Model
{
    use HasFactory;

    protected $table = 'rnd_produk_timeline';

    protected $fillable = [
        'produk_id',
        'timeline_date',
        'notes',
    ];

    protected $casts = [
        'timeline_date' => 'date',
    ];

    public function produk()
    {
        return $this->belongsTo(RndProduk::class, 'produk_id');
    }
}