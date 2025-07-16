<?php

namespace App\Models\Survey;

use Illuminate\Database\Eloquent\Model;

class SurveyAnswer extends Model
{
    protected $table = 'survey_answers';
    protected $fillable = [
        'question_id',
        'answer',
        'submission_id',
        'submitted_at',
    ];

    protected $dates = [
        'submitted_at',
    ];

    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id');
    }
}
