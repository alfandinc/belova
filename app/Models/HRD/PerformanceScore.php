<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceScore extends Model
{
    protected $table = 'performance_scores';
    use HasFactory;

    protected $fillable = ['evaluation_id', 'question_id', 'score', 'text_answer', 'comment'];

    public function evaluation()
    {
        return $this->belongsTo(PerformanceEvaluation::class, 'evaluation_id');
    }

    public function question()
    {
        return $this->belongsTo(PerformanceQuestion::class, 'question_id');
    }
}
