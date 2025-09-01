<?php
namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;

class PrKpiSummary extends Model
{
    protected $table = 'pr_kpi_summary';
    protected $fillable = [
        'bulan',
        'total_kpi_poin',
        'average_kpi_poin',
    ];
}
