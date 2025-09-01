<?php
namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\PerformanceEvaluationPeriod;

class PerformanceEvaluationPeriodController extends Controller
{
    public function getPeriodsForMonth(Request $request)
    {
        $bulan = $request->get('bulan'); // format YYYY-MM
        $year = substr($bulan, 0, 4);
        $month = substr($bulan, 5, 2);
        $periods = PerformanceEvaluationPeriod::whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->get();
        return response()->json($periods);
    }
}
