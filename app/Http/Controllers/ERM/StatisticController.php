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
        // Get all doctors and clinics for the filter dropdowns
        $dokters = \App\Models\ERM\Dokter::with('user', 'spesialisasi')->get();
        $kliniks = \App\Models\ERM\Klinik::all();
        
        return view('erm.statistic.index', compact('dokters', 'kliniks'));
    }

    public function getResepData(Request $request)
    {
        $period = $request->period ?? 'daily';
        $klinikId = $request->klinik_id ?? null;
        $dokterId = $request->dokter_id ?? null;
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

        // Get visitations that have resep - count one prescription per visitation, not all resepdetail entries
        $visitations = DB::table('erm_visitations')
            ->join('erm_resepdetail', 'erm_visitations.id', '=', 'erm_resepdetail.visitation_id')
            ->select(
                DB::raw("
                    CASE 
                        WHEN '$groupBy' = 'hour' THEN DATE_FORMAT(erm_visitations.tanggal_visitation, '%H') 
                        WHEN '$groupBy' = 'day' AND '$period' = 'weekly' THEN DATE_FORMAT(erm_visitations.tanggal_visitation, '%w')
                        WHEN '$groupBy' = 'day' AND '$period' = 'monthly' THEN DATE_FORMAT(erm_visitations.tanggal_visitation, '%d')
                        WHEN '$groupBy' = 'month' THEN DATE_FORMAT(erm_visitations.tanggal_visitation, '%m')
                        ELSE DATE_FORMAT(erm_visitations.tanggal_visitation, '%Y-%m')
                    END as time_label
                "),
                DB::raw('COUNT(DISTINCT erm_visitations.id) as total'),
                DB::raw('SUM(CASE WHEN erm_resepdetail.status = 1 THEN 1 ELSE 0 END) as terlayani'),
                DB::raw('SUM(CASE WHEN erm_resepdetail.status = 0 THEN 1 ELSE 0 END) as tidak_terlayani')
            )
            ->whereIn('erm_visitations.jenis_kunjungan', [1, 2]) // Only count Rawat Jalan
            ->whereIn('erm_visitations.status_kunjungan', [1, 2]); // Match the condition in the datatable
        
        // Apply date filter
        if ($startDate && $endDate) {
            $visitations->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        }
        
        // Apply clinic filter if specified
        if ($klinikId) {
            $visitations->where('erm_visitations.klinik_id', $klinikId);
        }
        
        // Apply doctor filter if specified
        if ($dokterId) {
            $visitations->where('erm_visitations.dokter_id', $dokterId);
        }

        $data = $visitations
            ->groupBy('time_label')
            ->orderBy('time_label')
            ->get();
        
        // Get racikan and non-racikan statistics by time period
        $racikanByPeriod = $this->getRacikanByPeriod($period, $groupBy, $startDate, $endDate, $klinikId, $dokterId);
        
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
        $racikanStats = $this->getRacikanStats($startDate, $endDate, $klinikId, $dokterId);

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

    private function getRacikanByPeriod($period, $groupBy, $startDate = null, $endDate = null, $klinikId = null, $dokterId = null)
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
        
        // Join with erm_resepdetail to only count medications in prescriptions that have been processed
        
        // Query for non-racikan medications - count individual medications that are non-racikan
        $nonRacikanQuery = DB::table('erm_resepfarmasi')
            ->join('erm_visitations', 'erm_resepfarmasi.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_resepdetail', 'erm_visitations.id', '=', 'erm_resepdetail.visitation_id')
            ->select(
                DB::raw("DATE_FORMAT(erm_visitations.tanggal_visitation, '$dateFormat') as time_label"),
                DB::raw('COUNT(erm_resepfarmasi.id) as count') // Count each medication
            )
            ->whereIn('erm_visitations.jenis_kunjungan', [1, 2])
            ->whereIn('erm_visitations.status_kunjungan', [1, 2])
            ->where('erm_resepdetail.status', 1) // Only count medications that have been served/processed
            ->where(function($query) {
                $query->whereNull('erm_resepfarmasi.racikan_ke')
                      ->orWhere('erm_resepfarmasi.racikan_ke', '');
            })
            ->groupBy('time_label')
            ->orderBy('time_label');
            
        // Query for racikan medications - count unique racikan_ke combinations per visitation as one item
        $racikanQuery = DB::table('erm_resepfarmasi')
            ->join('erm_visitations', 'erm_resepfarmasi.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_resepdetail', 'erm_visitations.id', '=', 'erm_resepdetail.visitation_id')
            ->select(
                DB::raw("DATE_FORMAT(erm_visitations.tanggal_visitation, '$dateFormat') as time_label"),
                DB::raw('COUNT(DISTINCT CONCAT(erm_resepfarmasi.visitation_id, "-", erm_resepfarmasi.racikan_ke)) as count') // Count each unique racikan as one
            )
            ->whereIn('erm_visitations.jenis_kunjungan', [1, 2])
            ->whereIn('erm_visitations.status_kunjungan', [1, 2])
            ->where('erm_resepdetail.status', 1) // Only count medications that have been served/processed
            ->whereNotNull('erm_resepfarmasi.racikan_ke')
            ->where('erm_resepfarmasi.racikan_ke', '!=', '')
            ->groupBy('time_label')
            ->orderBy('time_label');
            
        // Apply date filtering if provided
        if ($startDate && $endDate) {
            $nonRacikanQuery->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
            $racikanQuery->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        }
        
        // Apply clinic filter if specified
        if ($klinikId) {
            $nonRacikanQuery->where('erm_visitations.klinik_id', $klinikId);
            $racikanQuery->where('erm_visitations.klinik_id', $klinikId);
        }
        
        // Apply doctor filter if specified
        if ($dokterId) {
            $nonRacikanQuery->where('erm_visitations.dokter_id', $dokterId);
            $racikanQuery->where('erm_visitations.dokter_id', $dokterId);
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
    }    private function getRacikanStats($startDate = null, $endDate = null, $klinikId = null, $dokterId = null)
    {
        // Total counts for the whole period - counting medications, not visitations
        // For non-racikan count - count individual non-racikan medications 
        $nonRacikanQuery = DB::table('erm_resepfarmasi')
            ->join('erm_visitations', 'erm_resepfarmasi.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_resepdetail', 'erm_visitations.id', '=', 'erm_resepdetail.visitation_id')
            ->whereIn('erm_visitations.jenis_kunjungan', [1, 2])
            ->whereIn('erm_visitations.status_kunjungan', [1, 2])
            ->where('erm_resepdetail.status', 1) // Only count medications that have been served/processed
            ->where(function($query) {
                $query->whereNull('erm_resepfarmasi.racikan_ke')
                      ->orWhere('erm_resepfarmasi.racikan_ke', '');
            });

        // For racikan count - count unique combinations of visitation_id and racikan_ke
        $racikanQuery = DB::table('erm_resepfarmasi')
            ->join('erm_visitations', 'erm_resepfarmasi.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_resepdetail', 'erm_visitations.id', '=', 'erm_resepdetail.visitation_id')
            ->whereIn('erm_visitations.jenis_kunjungan', [1, 2])
            ->whereIn('erm_visitations.status_kunjungan', [1, 2])
            ->where('erm_resepdetail.status', 1) // Only count medications that have been served/processed
            ->whereNotNull('erm_resepfarmasi.racikan_ke')
            ->where('erm_resepfarmasi.racikan_ke', '!=', '');

        // Apply date filtering if provided
        if ($startDate && $endDate) {
            $nonRacikanQuery->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
            $racikanQuery->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        }
        
        // Apply clinic filter if specified
        if ($klinikId) {
            $nonRacikanQuery->where('erm_visitations.klinik_id', $klinikId);
            $racikanQuery->where('erm_visitations.klinik_id', $klinikId);
        }
        
        // Apply doctor filter if specified
        if ($dokterId) {
            $nonRacikanQuery->where('erm_visitations.dokter_id', $dokterId);
            $racikanQuery->where('erm_visitations.dokter_id', $dokterId);
        }

        // Count medications and unique racikan combinations
        $nonRacikan = $nonRacikanQuery->count('erm_resepfarmasi.id'); // Count each medication
        $racikan = $racikanQuery->distinct()->count(DB::raw('CONCAT(erm_resepfarmasi.visitation_id, "-", erm_resepfarmasi.racikan_ke)')); // Count each unique racikan

        return [
            'racikan' => $racikan,
            'nonRacikan' => $nonRacikan
        ];
    }
}
