<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiAssessmentPeriodIndicator extends Model
{
    use HasFactory;

    protected $table = 'hrd_kpi_assessment_period_indicators';

    protected $fillable = [
        'period_id',
        'source_indicator_id',
        'name',
        'description',
        'indicator_type',
        'applicability_scope',
        'position_id',
        'weight_percentage',
        'max_score',
        'score_label_1',
        'score_label_2',
        'score_label_3',
        'score_label_4',
        'score_label_5',
    ];

    protected $casts = [
        'weight_percentage' => 'decimal:2',
        'max_score' => 'integer',
    ];

    public function scoreLabel(int $score): string
    {
        return (string) ($this->{'score_label_' . $score} ?: '');
    }

    public function applicabilityLabel(): string
    {
        return KpiAssessmentIndicator::APPLICABILITY_OPTIONS[$this->applicability_scope] ?? $this->applicability_scope;
    }

    public function period()
    {
        return $this->belongsTo(KpiAssessmentPeriod::class, 'period_id');
    }

    public function sourceIndicator()
    {
        return $this->belongsTo(KpiAssessmentIndicator::class, 'source_indicator_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function scores()
    {
        return $this->hasMany(KpiAssessmentScore::class, 'period_indicator_id');
    }
}