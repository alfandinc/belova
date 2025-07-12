<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['question_text', 'category_id', 'question_type', 'evaluation_type', 'is_active'];

    public function category()
    {
        return $this->belongsTo(PerformanceQuestionCategory::class, 'category_id');
    }

    public function scores()
    {
        return $this->hasMany(PerformanceScore::class, 'question_id');
    }
}
