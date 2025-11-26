<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Marketing\ContentReport;
use App\Models\Marketing\ContentBrief;

class ContentPlan extends Model
{
    use HasFactory;

    protected $table = 'marketing_content_plans';

    protected $fillable = [
        'judul',
        'brand',
        'deskripsi',
        'caption',
        'mention',
        'tanggal_publish',
        'platform',
        'status',
        'jenis_konten',
        'konten_pilar',
        'target_audience',
        'link_asset',
        'link_publikasi',
        'catatan',
        'gambar_referensi', // Gambar referensi
    ];

    protected $casts = [
        'platform' => 'array',
        'jenis_konten' => 'array',
        'brand' => 'array',
        'tanggal_publish' => 'datetime',
        'link_publikasi' => 'array',
    ];

    public function reports()
    {
        return $this->hasMany(ContentReport::class, 'content_plan_id');
    }

    public function briefs()
    {
        return $this->hasMany(ContentBrief::class, 'content_plan_id');
    }
}
