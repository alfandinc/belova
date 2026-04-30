<?php

namespace App\Models\HRD;

use App\Services\HRD\KpiAssessmentWeightService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiAssessment extends Model
{
    use HasFactory;

    protected $table = 'hrd_kpi_assessments';

    protected $fillable = [
        'period_id',
        'evaluatee_id',
        'evaluator_id',
        'division_id',
        'position_id',
        'evaluator_type',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(KpiAssessmentPeriod::class, 'period_id');
    }

    public function evaluatee()
    {
        return $this->belongsTo(Employee::class, 'evaluatee_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(Employee::class, 'evaluator_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function scores()
    {
        return $this->hasMany(KpiAssessmentScore::class, 'assessment_id');
    }

    public function getTotalScoreAttribute(): float
    {
        $this->loadMissing(['evaluatee.user.roles']);

        $scores = $this->relationLoaded('scores')
            ? $this->scores
            : $this->scores()->with('periodIndicator')->get();

        $effectiveWeights = app(KpiAssessmentWeightService::class)
            ->effectiveWeightsForAssessment($this);

        return round($scores->sum(function (KpiAssessmentScore $score) use ($effectiveWeights) {
            if (!$score->periodIndicator) {
                return 0;
            }

            $maxScore = max(1, (int) ($score->periodIndicator->max_score ?? 5));

            return ($score->score / $maxScore) * (float) $effectiveWeights->get($score->period_indicator_id, 0);
        }), 2);
    }
}