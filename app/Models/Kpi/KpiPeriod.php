<?php

namespace App\Models\KPI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiPeriod extends Model
{
    use HasFactory;

    protected $table = 'kpi_periods';

    protected $fillable = [
        'month',
        'year',
        'status',
        'started_at',
        'open_at',
        'closed_at',
        'period_name',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'open_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // model default attribute — ensure newly created model defaults to draft when not provided
    protected $attributes = [
        'status' => 'draft',
    ];

    public function assessments()
    {
        return $this->hasMany(KpiAssessment::class, 'period_id');
    }
}
