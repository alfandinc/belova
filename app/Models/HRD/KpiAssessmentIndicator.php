<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiAssessmentIndicator extends Model
{
    use HasFactory;

    public const APPLICABILITY_OPTIONS = [
        'hrd_to_all' => 'HRD -> Semua Target',
        'hrd_to_employee' => 'HRD -> Employee',
        'hrd_to_manager' => 'HRD -> Manager',
        'hrd_to_head_manager' => 'HRD -> Head Manager',
        'manager_to_employee' => 'Manager -> Employee',
        'head_manager_to_manager' => 'Head Manager -> Manager',
        'head_manager_to_hrd' => 'Head Manager -> HRD',
    ];

    protected $table = 'hrd_kpi_assessment_indicators';

    protected $fillable = [
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
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'weight_percentage' => 'decimal:2',
        'max_score' => 'integer',
    ];

    public function scoreLabel(int $score): string
    {
        return (string) ($this->{'score_label_' . $score} ?: '');
    }

    public function applicabilityLabel(): string
    {
        return self::APPLICABILITY_OPTIONS[$this->applicability_scope] ?? $this->applicability_scope;
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function periodIndicators()
    {
        return $this->hasMany(KpiAssessmentPeriodIndicator::class, 'source_indicator_id');
    }
}