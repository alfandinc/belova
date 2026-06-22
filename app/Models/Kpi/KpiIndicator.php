<?php

namespace App\Models\KPI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiIndicator extends Model
{
    use HasFactory;

    protected $table = 'kpi_indicators';

    protected $fillable = [
        'category_id',
        'indicator_name',
        'notes',
        'is_active',
    ];

    public function category()
    {
        return $this->belongsTo(KpiIndicatorCategory::class, 'category_id');
    }

    public function positionIndicators()
    {
        return $this->hasMany(KpiPositionIndicator::class, 'indicator_id');
    }

    public function scores()
    {
        return $this->hasMany(KpiScore::class, 'indicators_id');
    }
}
