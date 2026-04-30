<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiAssessmentPeriod extends Model
{
    use HasFactory;

    protected $table = 'hrd_kpi_assessment_periods';

    protected $fillable = [
        'name',
        'assessment_month',
        'status',
        'started_at',
        'closed_at',
        'snapshot_taken_at',
        'created_by',
    ];

    protected $casts = [
        'assessment_month' => 'date',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'snapshot_taken_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function indicators()
    {
        return $this->hasMany(KpiAssessmentPeriodIndicator::class, 'period_id');
    }

    public function assessments()
    {
        return $this->hasMany(KpiAssessment::class, 'period_id');
    }
}