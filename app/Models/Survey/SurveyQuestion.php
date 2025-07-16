<?php

namespace App\Models\Survey;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    protected $table = 'survey_questions';
    protected $fillable = [
        'question_text',
        'question_type',
        'options',
        'order',
        'klinik_name',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function answers()
    {
        return $this->hasMany(SurveyAnswer::class, 'question_id');
    }
}
