<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiAssessmentScore extends Model
{
    use HasFactory;

    protected $table = 'hrd_kpi_assessment_scores';

    protected $fillable = [
        'assessment_id',
        'period_indicator_id',
        'score',
        'note',
    ];

    protected $casts = [
        'score' => 'float',
    ];

    public function assessment()
    {
        return $this->belongsTo(KpiAssessment::class, 'assessment_id');
    }

    public function periodIndicator()
    {
        return $this->belongsTo(KpiAssessmentPeriodIndicator::class, 'period_indicator_id');
    }
}