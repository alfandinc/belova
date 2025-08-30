<?php
namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrKpi extends Model
{
    use HasFactory;
    protected $table = 'pr_kpi';
    protected $fillable = [
        'nama_poin',
        'initial_poin',
    ];
}
