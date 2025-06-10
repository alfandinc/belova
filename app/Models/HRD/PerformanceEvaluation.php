<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceEvaluation extends Model
{
    use HasFactory;

    protected $fillable = ['period_id', 'evaluator_id', 'evaluatee_id', 'status', 'completed_at'];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(PerformanceEvaluationPeriod::class, 'period_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(Employee::class, 'evaluator_id');
    }

    public function evaluatee()
    {
        return $this->belongsTo(Employee::class, 'evaluatee_id');
    }

    public function scores()
    {
        return $this->hasMany(PerformanceScore::class, 'evaluation_id');
    }
}
