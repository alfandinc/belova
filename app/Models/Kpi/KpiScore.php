<?php

namespace App\Models\KPI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiScore extends Model
{
    use HasFactory;

    protected $table = 'kpi_scores';

    protected $fillable = [
        'assessment_id',
        'indicators_id',
        'ss_category_name',
        'ss_category_weight_percentage',
        'ss_indicator_name',
        'ss_indicator_weight_percentage',
        'score',
        'final_calculated_score',
        'notes',
    ];

    public function isCounted(): bool
    {
        return (float) ($this->ss_category_weight_percentage ?? 0) > 0;
    }

    /**
     * Raw score for an indicator the source assessment did not score: the average of the source scores
     * in the same group, so a counted (bobot > 0) indicator never takes an info-only (bobot 0%) score or vice versa.
     */
    public static function fallbackRawScore($pool, KpiScore $target): float
    {
        $sameGroup = $pool->filter(fn (KpiScore $s) => $s->isCounted() === $target->isCounted());

        return (float) ($sameGroup->isNotEmpty() ? $sameGroup : $pool)->avg(fn (KpiScore $s) => (float) $s->score);
    }

    public function assessment()
    {
        return $this->belongsTo(KpiAssessment::class, 'assessment_id');
    }

    public function indicator()
    {
        return $this->belongsTo(KpiIndicator::class, 'indicators_id');
    }
}
