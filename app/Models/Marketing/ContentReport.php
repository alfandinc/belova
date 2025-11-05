<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentReport extends Model
{
    use HasFactory;

    protected $table = 'marketing_content_reports';

    protected $fillable = [
        'content_plan_id',
        'likes',
        'comments',
        'saves',
        'shares',
        'reach',
        'impressions',
        'err',
        'eri',
        'recorded_at',
    ];

    protected $casts = [
        'likes' => 'integer',
        'comments' => 'integer',
        'saves' => 'integer',
        'shares' => 'integer',
        'reach' => 'integer',
        'impressions' => 'integer',
        'err' => 'float',
        'eri' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function contentPlan()
    {
        return $this->belongsTo(ContentPlan::class, 'content_plan_id');
    }
}
