<?php

namespace App\Models\Rnd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RndSampleLog extends Model
{
    use HasFactory;

    protected $table = 'rnd_sample_log';

    /** Evaluation aspects of a received sample (keys of the evaluasi column). */
    public const EVALUASI = [
        'tekstur' => 'Tekstur',
        'aroma' => 'Aroma',
        'warna' => 'Warna',
        'reaksi_kulit' => 'Reaksi kulit / iritasi',
    ];

    protected $fillable = [
        'produk_id',
        'no_produksi',
        'tanggal_terima',
        'status_sample',
        'evaluasi',
        'notes',
    ];

    protected $casts = [
        'tanggal_terima' => 'date:Y-m-d',
        'evaluasi' => 'array',
    ];

    public function produk()
    {
        return $this->belongsTo(RndProduk::class, 'produk_id');
    }
}
