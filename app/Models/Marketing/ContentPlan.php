<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentPlan extends Model
{
    use HasFactory;

    protected $table = 'marketing_content_plans';

    protected $fillable = [
        'judul',
        'deskripsi',
        'tanggal_publish',
        'platform',
        'status',
        'jenis_konten',
        'target_audience',
        'link_asset',
        'link_publikasi',
        'catatan',
        'gambar_referensi', // Gambar referensi
    ];

    protected $casts = [
        'platform' => 'array',
        'jenis_konten' => 'array',
        'tanggal_publish' => 'datetime',
    ];
}
