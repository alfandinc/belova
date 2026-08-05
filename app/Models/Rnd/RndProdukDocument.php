<?php

namespace App\Models\Rnd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RndProdukDocument extends Model
{
    use HasFactory;

    protected $table = 'rnd_produk_document';

    protected $fillable = [
        'produk_id',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    public function produk()
    {
        return $this->belongsTo(RndProduk::class, 'produk_id');
    }
}