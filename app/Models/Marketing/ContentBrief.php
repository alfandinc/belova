<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Marketing\ContentPlan;

class ContentBrief extends Model
{
    use HasFactory;

    protected $table = 'marketing_content_briefs';

    protected $fillable = [
        'content_plan_id',
        'headline',
        'sub_headline',
        'isi_konten',
        'visual_references',
    ];

    protected $casts = [
        'visual_references' => 'array',
    ];

    public function contentPlan()
    {
        return $this->belongsTo(ContentPlan::class, 'content_plan_id');
    }
}
