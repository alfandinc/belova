<?php

namespace App\Models\KPI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiIndicatorCategory extends Model
{
    use HasFactory;

    protected $table = 'kpi_indicator_categories';

    protected $fillable = [
        'category_name',
        'weight_percentage',
        'evaluator_type',
        'evaluator_position_id',
        'is_active',
    ];

    public function indicators()
    {
        return $this->hasMany(KpiIndicator::class, 'category_id');
    }

    public function evaluatorPosition()
    {
        return $this->belongsTo(\App\Models\HRD\Position::class, 'evaluator_position_id');
    }
}
