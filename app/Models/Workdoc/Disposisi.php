<?php

namespace App\Models\Workdoc;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disposisi extends Model
{
    use HasFactory;

    protected $table = 'workdoc_disposisi';

    protected $fillable = [
        'memorandum_id',
        'tanggal_terima',
        'disposisi_pimpinan',
        'tujuan_disposisi',
        'catatan',
        'tanggal_dibaca',
    ];

    protected $casts = [
        'tanggal_terima' => 'date',
        'tanggal_dibaca' => 'date',
        'disposisi_pimpinan' => 'array',
        'tujuan_disposisi' => 'array',
    ];

    public function memorandum()
    {
        return $this->belongsTo(\App\Models\Workdoc\Memorandum::class, 'memorandum_id');
    }
}
