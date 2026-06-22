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

    public function assessment()
    {
        return $this->belongsTo(KpiAssessment::class, 'assessment_id');
    }

    public function indicator()
    {
        return $this->belongsTo(KpiIndicator::class, 'indicators_id');
    }
}
