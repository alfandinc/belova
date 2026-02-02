<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RunningPeserta extends Model
{
    use HasFactory;

    protected $table = 'running_pesertas';

    protected $fillable = [
        'nama_peserta',
        'kategori',
        'status',
        'unique_code',
        'verified_at',
        ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

        protected static function booted()
        {
            static::creating(function ($model) {
                if (empty($model->unique_code)) {
                    // generate a unique alphanumeric code
                    do {
                        $code = Str::upper(Str::random(8));
                    } while (self::where('unique_code', $code)->exists());

                    $model->unique_code = $code;
                }
            });
        }
}
