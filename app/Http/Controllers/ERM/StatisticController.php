<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\ResepDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class StatisticController extends Controller
{
    public function getResepData(Request $request)
    {
        $klinikId = $request->klinik_id ?? null;
        $dokterId = $request->dokter_id ?? null;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        // Default to today if not provided
        if (!$startDate || !$endDate) {
            $today = Carbon::now()->format('Y-m-d');
            $startDate = $today;
            $endDate = $today;
        }

        $groupBy = 'day';
        $format = 'Y-m-d';
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $visitations = DB::table('erm_visitations')
            ->join('erm_resepdetail', 'erm_visitations.id', '=', 'erm_resepdetail.visitation_id')
            ->select(
                DB::raw("DATE_FORMAT(erm_visitations.tanggal_visitation, '%Y-%m-%d') as time_label"),
                DB::raw("SUM(CASE WHEN erm_resepdetail.status = 1 THEN 1 ELSE 0 END) as terlayani"),
                DB::raw("SUM(CASE WHEN erm_resepdetail.status != 1 THEN 1 ELSE 0 END) as tidak_terlayani")
            )
            ->whereIn('erm_visitations.jenis_kunjungan', [1, 2])
            ->whereIn('erm_visitations.status_kunjungan', [1, 2]);

        if ($startDate && $endDate) {
            $visitations->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        }
        if ($klinikId) {
            $visitations->where('erm_visitations.klinik_id', $klinikId);
        }
        if ($dokterId) {
            $visitations->where('erm_visitations.dokter_id', $dokterId);
        }

        $data = $visitations
            ->groupBy('time_label')
            ->orderBy('time_label')
            ->get();

        $racikanByPeriod = $this->getRacikanByPeriod('custom', $groupBy, $startDate, $endDate, $klinikId, $dokterId);

        $formattedData = $data->map(function ($item) use ($groupBy, $format, $days, $months) {
            if ($groupBy == 'hour') {
                $label = $item->time_label . ':00';
            } elseif ($groupBy == 'day') {
                $label = $item->time_label;
            } elseif ($groupBy == 'month') {
                $label = $months[(int)date('m', strtotime($item->time_label)) - 1];
            } else {
                $label = $item->time_label;
            }
            return [
                'label' => $label,
                'terlayani' => $item->terlayani,
                'tidak_terlayani' => $item->tidak_terlayani
            ];
        });

        $racikanStats = $this->getRacikanStats($startDate, $endDate, $klinikId, $dokterId);

        return response()->json([
            'labels' => $racikanByPeriod['labels'],
            'terlayani' => $formattedData->pluck('terlayani'),
            'tidak_terlayani' => $formattedData->pluck('tidak_terlayani'),
            'racikan' => $racikanStats['racikan'],
            'nonRacikan' => $racikanStats['nonRacikan'],
            'racikanByPeriod' => $racikanByPeriod['racikan'],
            'nonRacikanByPeriod' => $racikanByPeriod['nonRacikan']
        ]);
    }
    public function index()
    {
        // Get all doctors and clinics for the filter dropdowns
        $dokters = \App\Models\ERM\Dokter::with('user', 'spesialisasi')->get();
        $kliniks = \App\Models\ERM\Klinik::all();
        return view('erm.statistic.index', compact('dokters', 'kliniks'));
    }

    private function getRacikanByPeriod($period, $groupBy, $startDate = null, $endDate = null, $klinikId = null, $dokterId = null)
    {
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

        $nonRacikanQuery = DB::table('erm_resepfarmasi')
            ->join('erm_visitations', 'erm_resepfarmasi.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_resepdetail', 'erm_visitations.id', '=', 'erm_resepdetail.visitation_id')
            ->select(
                DB::raw("DATE_FORMAT(erm_visitations.tanggal_visitation, '$dateFormat') as time_label"),
                DB::raw('COUNT(erm_resepfarmasi.id) as count')
            )
            ->whereIn('erm_visitations.jenis_kunjungan', [1, 2])
            ->whereIn('erm_visitations.status_kunjungan', [1, 2])
            ->where('erm_resepdetail.status', 1)
            ->where(function($query) {
                $query->whereNull('erm_resepfarmasi.racikan_ke')
                      ->orWhere('erm_resepfarmasi.racikan_ke', '');
            })
            ->groupBy('time_label')
            ->orderBy('time_label');

        $racikanQuery = DB::table('erm_resepfarmasi')
            ->join('erm_visitations', 'erm_resepfarmasi.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_resepdetail', 'erm_visitations.id', '=', 'erm_resepdetail.visitation_id')
            ->select(
                DB::raw("DATE_FORMAT(erm_visitations.tanggal_visitation, '$dateFormat') as time_label"),
                DB::raw('COUNT(DISTINCT CONCAT(erm_resepfarmasi.visitation_id, "-", erm_resepfarmasi.racikan_ke)) as count')
            )
            ->whereIn('erm_visitations.jenis_kunjungan', [1, 2])
            ->whereIn('erm_visitations.status_kunjungan', [1, 2])
            ->where('erm_resepdetail.status', 1)
            ->whereNotNull('erm_resepfarmasi.racikan_ke')
            ->where('erm_resepfarmasi.racikan_ke', '!=', '')
            ->groupBy('time_label')
            ->orderBy('time_label');

        if ($startDate && $endDate) {
            $nonRacikanQuery->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
            $racikanQuery->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        }
        if ($klinikId) {
            $nonRacikanQuery->where('erm_visitations.klinik_id', $klinikId);
            $racikanQuery->where('erm_visitations.klinik_id', $klinikId);
        }
        if ($dokterId) {
            $nonRacikanQuery->where('erm_visitations.dokter_id', $dokterId);
            $racikanQuery->where('erm_visitations.dokter_id', $dokterId);
        }

        $nonRacikanData = $nonRacikanQuery->get();
        $racikanData = $racikanQuery->get();

        $labels = [];
        $nonRacikanValues = [];
        $racikanValues = [];
        $allTimeLabels = collect();
        $nonRacikanData->each(function($item) use ($allTimeLabels) {
            $allTimeLabels->push($item->time_label);
        });
        $racikanData->each(function($item) use ($allTimeLabels) {
            $allTimeLabels->push($item->time_label);
        });
        $allTimeLabels = $allTimeLabels->unique()->sort()->values();

        foreach ($allTimeLabels as $label) {
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
            $nonRacikanValue = 0;
            foreach ($nonRacikanData as $item) {
                if ($item->time_label == $label) {
                    $nonRacikanValue = (int)$item->count;
                    break;
                }
            }
            $nonRacikanValues[] = $nonRacikanValue;
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

    private function getRacikanStats($startDate = null, $endDate = null, $klinikId = null, $dokterId = null)
    {
        $nonRacikanQuery = DB::table('erm_resepfarmasi')
            ->join('erm_visitations', 'erm_resepfarmasi.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_resepdetail', 'erm_visitations.id', '=', 'erm_resepdetail.visitation_id')
            ->whereIn('erm_visitations.jenis_kunjungan', [1, 2])
            ->whereIn('erm_visitations.status_kunjungan', [1, 2])
            ->where('erm_resepdetail.status', 1)
            ->where(function($query) {
                $query->whereNull('erm_resepfarmasi.racikan_ke')
                      ->orWhere('erm_resepfarmasi.racikan_ke', '');
            });
        $racikanQuery = DB::table('erm_resepfarmasi')
            ->join('erm_visitations', 'erm_resepfarmasi.visitation_id', '=', 'erm_visitations.id')
            ->join('erm_resepdetail', 'erm_visitations.id', '=', 'erm_resepdetail.visitation_id')
            ->whereIn('erm_visitations.jenis_kunjungan', [1, 2])
            ->whereIn('erm_visitations.status_kunjungan', [1, 2])
            ->where('erm_resepdetail.status', 1)
            ->whereNotNull('erm_resepfarmasi.racikan_ke')
            ->where('erm_resepfarmasi.racikan_ke', '!=', '');
        if ($startDate && $endDate) {
            $nonRacikanQuery->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
            $racikanQuery->whereBetween('erm_visitations.tanggal_visitation', [$startDate, $endDate]);
        }
        if ($klinikId) {
            $nonRacikanQuery->where('erm_visitations.klinik_id', $klinikId);
            $racikanQuery->where('erm_visitations.klinik_id', $klinikId);
        }
        if ($dokterId) {
            $nonRacikanQuery->where('erm_visitations.dokter_id', $dokterId);
            $racikanQuery->where('erm_visitations.dokter_id', $dokterId);
        }
        $nonRacikan = $nonRacikanQuery->count('erm_resepfarmasi.id');
        $racikan = $racikanQuery->distinct()->count(DB::raw('CONCAT(erm_resepfarmasi.visitation_id, "-", erm_resepfarmasi.racikan_ke)'));
        return [
            'racikan' => $racikan,
            'nonRacikan' => $nonRacikan
        ];
    }
}
