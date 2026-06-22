<?php

namespace App\Models\KPI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiPositionIndicator extends Model
{
    use HasFactory;

    protected $table = 'kpi_position_indicators';

    protected $fillable = [
        'position_id',
        'indicator_id',
        'weight_percentage',
    ];

    public function position()
    {
        return $this->belongsTo(\App\Models\HRD\Position::class, 'position_id');
    }

    public function indicator()
    {
        return $this->belongsTo(KpiIndicator::class, 'indicator_id');
    }
}
