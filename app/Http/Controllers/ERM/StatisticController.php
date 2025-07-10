<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\ResepDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticController extends Controller
{
    public function index()
    {
        return view('erm.statistic.index');
    }

    public function getResepData(Request $request)
    {
        $period = $request->period ?? 'daily';
        $today = Carbon::now();
        
        switch ($period) {
            case 'daily':
                $startDate = $today->copy()->startOfDay();
                $endDate = $today->copy()->endOfDay();
                $format = 'H:00'; // Hour format
                $groupBy = 'hour';
                break;
            case 'weekly':
                $startDate = $today->copy()->startOfWeek();
                $endDate = $today->copy()->endOfWeek();
                $format = 'D'; // Day name format
                $groupBy = 'day';
                break;
            case 'monthly':
                $startDate = $today->copy()->startOfMonth();
                $endDate = $today->copy()->endOfMonth();
                $format = 'd'; // Day of month format
                $groupBy = 'day';
                break;
            case 'yearly':
                $startDate = $today->copy()->startOfYear();
                $endDate = $today->copy()->endOfYear();
                $format = 'M'; // Month name format
                $groupBy = 'month';
                break;
            case 'all':
                $startDate = null;
                $endDate = null;
                $format = 'Y-m'; // Year-month format
                $groupBy = 'month';
                break;
            default:
                $startDate = $today->copy()->startOfDay();
                $endDate = $today->copy()->endOfDay();
                $format = 'H:00';
                $groupBy = 'hour';
        }

        // Get status statistics (terlayani, tidak terlayani)
        $query = ResepDetail::select(
            DB::raw("
                CASE 
                    WHEN '$groupBy' = 'hour' THEN DATE_FORMAT(created_at, '%H') 
                    WHEN '$groupBy' = 'day' AND '$period' = 'weekly' THEN DATE_FORMAT(created_at, '%w')
                    WHEN '$groupBy' = 'day' AND '$period' = 'monthly' THEN DATE_FORMAT(created_at, '%d')
                    WHEN '$groupBy' = 'month' THEN DATE_FORMAT(created_at, '%m')
                    ELSE DATE_FORMAT(created_at, '%Y-%m')
                END as time_label
            "),
            DB::raw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as terlayani'),
            DB::raw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as tidak_terlayani')
        );

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $data = $query->groupBy('time_label')
                     ->orderBy('time_label')
                     ->get();
        
        // Get racikan and non-racikan statistics by time period
        $racikanByPeriod = $this->getRacikanByPeriod($period, $groupBy, $startDate, $endDate);
        
        // Format labels based on period
        $formattedData = $data->map(function ($item) use ($period, $format, $groupBy) {
            if ($groupBy == 'hour') {
                $label = $item->time_label . ':00';
            } elseif ($groupBy == 'day' && $period == 'weekly') {
                $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                $label = $days[(int)$item->time_label];
            } elseif ($groupBy == 'day' && $period == 'monthly') {
                $label = $item->time_label;
            } elseif ($groupBy == 'month') {
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                $label = $months[(int)$item->time_label - 1];
            } else {
                $label = $item->time_label;
            }
            
            return [
                'label' => $label,
                'terlayani' => (int)$item->terlayani,
                'tidak_terlayani' => (int)$item->tidak_terlayani
            ];
        });

        // Get total Racikan vs Non-Racikan statistics
        $racikanStats = $this->getRacikanStats($startDate, $endDate);

        return response()->json([
            'labels' => $racikanByPeriod['labels'],
            'terlayani' => $formattedData->pluck('terlayani'),
            'tidak_terlayani' => $formattedData->pluck('tidak_terlayani'),
            'period' => $period,
            'racikan' => $racikanStats['racikan'],
            'nonRacikan' => $racikanStats['nonRacikan'],
            'racikanByPeriod' => $racikanByPeriod['racikan'],
            'nonRacikanByPeriod' => $racikanByPeriod['nonRacikan']
        ]);
    }

    private function getRacikanByPeriod($period, $groupBy, $startDate = null, $endDate = null)
    {
        // Date format for grouping by period
        $dateFormat = '';
        switch ($groupBy) {
            case 'hour':
                $dateFormat = '%H'; // Hour
                break;
            case 'day':
                if ($period == 'weekly') {
                    $dateFormat = '%w'; // Day of week (0-6)
                } else {
                    $dateFormat = '%d'; // Day of month
                }
                break;
            case 'month':
                $dateFormat = '%m'; // Month
                break;
            default:
                $dateFormat = '%Y-%m'; // Year-month
        }
        
        // Query for non-racikan prescriptions
        $nonRacikanQuery = DB::table('erm_resepfarmasi')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') as time_label"),
                DB::raw('COUNT(*) as count')
            )
            ->where(function($query) {
                $query->whereNull('racikan_ke')
                      ->orWhere('racikan_ke', '');
            })
            ->groupBy('time_label')
            ->orderBy('time_label');
            
        // Query for racikan prescriptions - count distinct combinations of visitation_id and racikan_ke
        $racikanQuery = DB::table('erm_resepfarmasi')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') as time_label"),
                DB::raw('COUNT(DISTINCT CONCAT(visitation_id, racikan_ke)) as count')
            )
            ->whereNotNull('racikan_ke')
            ->where('racikan_ke', '!=', '')
            ->groupBy('time_label')
            ->orderBy('time_label');
            
        // Apply date filtering if provided
        if ($startDate && $endDate) {
            $nonRacikanQuery->whereBetween('created_at', [$startDate, $endDate]);
            $racikanQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        $nonRacikanData = $nonRacikanQuery->get();
        $racikanData = $racikanQuery->get();
        
        // Format labels
        $labels = [];
        $nonRacikanValues = [];
        $racikanValues = [];
        
        // Create combined list of all time labels
        $allTimeLabels = collect();
        $nonRacikanData->each(function($item) use ($allTimeLabels) {
            $allTimeLabels->push($item->time_label);
        });
        $racikanData->each(function($item) use ($allTimeLabels) {
            $allTimeLabels->push($item->time_label);
        });
        $allTimeLabels = $allTimeLabels->unique()->sort()->values();
        
        // Format labels and prepare data arrays
        foreach ($allTimeLabels as $label) {
            // Format label based on period
            $formattedLabel = $label;
            if ($groupBy == 'hour') {
                $formattedLabel = $label . ':00';
            } elseif ($groupBy == 'day' && $period == 'weekly') {
                $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                $formattedLabel = $days[(int)$label];
            } elseif ($groupBy == 'month') {
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                $formattedLabel = $months[(int)$label - 1];
            }
            
            $labels[] = $formattedLabel;
            
            // Find matching data for non-racikan
            $nonRacikanValue = 0;
            foreach ($nonRacikanData as $item) {
                if ($item->time_label == $label) {
                    $nonRacikanValue = (int)$item->count;
                    break;
                }
            }
            $nonRacikanValues[] = $nonRacikanValue;
            
            // Find matching data for racikan
            $racikanValue = 0;
            foreach ($racikanData as $item) {
                if ($item->time_label == $label) {
                    $racikanValue = (int)$item->count;
                    break;
                }
            }
            $racikanValues[] = $racikanValue;
        }
        
        return [
            'labels' => $labels,
            'nonRacikan' => $nonRacikanValues,
            'racikan' => $racikanValues
        ];
    }

    private function getRacikanStats($startDate = null, $endDate = null)
    {
        // Total counts for the whole period
        // For non-racikan count (doesn't have racikan_ke value or racikan_ke is null)
        $nonRacikanQuery = DB::table('erm_resepfarmasi')
            ->whereNull('racikan_ke')
            ->orWhere('racikan_ke', '');

        // For racikan count (has racikan_ke value, count unique combinations of visitation_id and racikan_ke)
        $racikanQuery = DB::table('erm_resepfarmasi')
            ->whereNotNull('racikan_ke')
            ->where('racikan_ke', '!=', '');

        // Apply date filtering if provided
        if ($startDate && $endDate) {
            $nonRacikanQuery->whereBetween('created_at', [$startDate, $endDate]);
            $racikanQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Get non-racikan count
        $nonRacikan = $nonRacikanQuery->count();

        // Get distinct count of racikan (grouped by visitation_id and racikan_ke)
        $racikan = $racikanQuery->select('visitation_id', 'racikan_ke')
            ->distinct()
            ->count(DB::raw('CONCAT(visitation_id, racikan_ke)'));

        return [
            'racikan' => $racikan,
            'nonRacikan' => $nonRacikan
        ];
    }
}
