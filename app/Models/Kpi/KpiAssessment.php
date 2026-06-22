<?php

namespace App\Models\KPI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiAssessment extends Model
{
    use HasFactory;

    protected $table = 'kpi_assessments';

    protected $fillable = [
        'period_id',
        'evaluator_employee_id',
        'evaluator_position_id',
        'evaluatee_employee_id',
        'evaluatee_position_id',
        'assessment_type',
        'status',
    ];

    public function period()
    {
        return $this->belongsTo(KpiPeriod::class, 'period_id');
    }

    public function evaluatorEmployee()
    {
        return $this->belongsTo(\App\Models\HRD\Employee::class, 'evaluator_employee_id');
    }

    public function evaluatorPosition()
    {
        return $this->belongsTo(\App\Models\HRD\Position::class, 'evaluator_position_id');
    }

    public function evaluateeEmployee()
    {
        return $this->belongsTo(\App\Models\HRD\Employee::class, 'evaluatee_employee_id');
    }

    public function evaluateePosition()
    {
        return $this->belongsTo(\App\Models\HRD\Position::class, 'evaluatee_position_id');
    }

    public function scores()
    {
        return $this->hasMany(KpiScore::class, 'assessment_id');
    }
}
