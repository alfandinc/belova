<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceEvaluationPeriod extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'start_date', 'end_date', 'status', 'mode'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'mode' => 'string',
    ];

    public function evaluations()
    {
        return $this->hasMany(PerformanceEvaluation::class, 'period_id');
    }
}
