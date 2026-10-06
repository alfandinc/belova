<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class StatisticController extends Controller
{
    /**
     * Compact resep statistics for the E-Resep modal (2 grouped queries).
     */
    public function summary(Request $request)
    {
        $today = Carbon::now()->format('Y-m-d');
        $startDate = $request->start_date ?: $today;
        $endDate = $request->end_date ?: $startDate;
        $klinikId = $request->klinik_id;
        $dokterId = $request->dokter_id;

        $applyFilters = function ($query) use ($startDate, $endDate, $klinikId, $dokterId) {
            $query->whereIn('v.jenis_kunjungan', [1, 2])
                ->whereIn('v.status_kunjungan', [1, 2])
                ->whereBetween('v.tanggal_visitation', [$startDate, $endDate]);
            if ($klinikId) {
                $query->where('v.klinik_id', $klinikId);
            }
            if ($dokterId) {
                $query->where('v.dokter_id', $dokterId);
            }
            return $query;
        };

        $resep = $applyFilters(
            DB::table('erm_visitations as v')
                ->join('erm_resepdetail as rd', 'rd.visitation_id', '=', 'v.id')
        )
            ->selectRaw('v.tanggal_visitation as tanggal')
            ->selectRaw('SUM(CASE WHEN rd.status = 1 THEN 1 ELSE 0 END) as terlayani')
            ->selectRaw('SUM(CASE WHEN rd.status = 1 THEN 0 ELSE 1 END) as belum')
            ->groupBy('v.tanggal_visitation')
            ->get()
            ->keyBy('tanggal');

        $items = $applyFilters(
            DB::table('erm_resepfarmasi as rf')
                ->join('erm_visitations as v', 'rf.visitation_id', '=', 'v.id')
                ->join('erm_resepdetail as rd', 'rd.visitation_id', '=', 'v.id')
                ->where('rd.status', 1)
        )
            ->selectRaw('v.tanggal_visitation as tanggal')
            ->selectRaw("SUM(CASE WHEN rf.racikan_ke IS NULL OR rf.racikan_ke = '' THEN 1 ELSE 0 END) as non_racikan")
            ->selectRaw("COUNT(DISTINCT CASE WHEN rf.racikan_ke IS NOT NULL AND rf.racikan_ke <> '' THEN CONCAT(rf.visitation_id, '-', rf.racikan_ke) END) as racikan")
            ->groupBy('v.tanggal_visitation')
            ->get()
            ->keyBy('tanggal');

        $dates = $resep->keys()->merge($items->keys())->unique()->sort()->values();

        $rows = [];
        $totals = ['terlayani' => 0, 'belum' => 0, 'non_racikan' => 0, 'racikan' => 0];
        foreach ($dates as $date) {
            $row = [
                'tanggal' => $date,
                'terlayani' => (int) ($resep[$date]->terlayani ?? 0),
                'belum' => (int) ($resep[$date]->belum ?? 0),
                'non_racikan' => (int) ($items[$date]->non_racikan ?? 0),
                'racikan' => (int) ($items[$date]->racikan ?? 0),
            ];
            foreach ($totals as $key => $value) {
                $totals[$key] += $row[$key];
            }
            $rows[] = $row;
        }

        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'totals' => $totals,
            'rows' => $rows,
        ]);
    }
}
