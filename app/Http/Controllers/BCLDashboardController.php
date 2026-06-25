<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\BCL\Fin_jurnal;
use App\Models\BCL\Inventory;
use App\Models\BCL\renter;
use App\Models\BCL\tr_renter;
use App\Models\BCL\Rooms;
use App\Http\Controllers\BCL\tr_renterController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BCLDashboardController extends Controller
{
    // public function index()
    // {
        

    //     return view('bcl.home');
    // }
    public function index(Request $request)
    {
        if (!Auth::user() || !Auth::user()->hasAnyRole(['Kos','Admin'])) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $response = $this->buildDashboardResponse($startDate, $endDate);

        $ranking_penyewa = app(tr_renterController::class)->ranking_penyewa();
        $response->ranking_penyewa = $ranking_penyewa;

        return view('bcl.home')->with('response', (object) $response);
    }

    public function data(Request $request)
    {
        if (!Auth::user() || !Auth::user()->hasAnyRole(['Kos','Admin'])) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        [$startDate, $endDate] = $this->resolveDateRange($request);

        return response()->json($this->buildDashboardResponse($startDate, $endDate));
    }

    protected function resolveDateRange(Request $request): array
    {
        $now = Carbon::now();
        $defaultStart = $now->copy()->startOfYear();
        $defaultEnd = $now->copy()->endOfYear();

        try {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->input('start_date'))->startOfDay()
                : $defaultStart;
        } catch (\Throwable $e) {
            $startDate = $defaultStart;
        }

        try {
            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->input('end_date'))->endOfDay()
                : $defaultEnd;
        } catch (\Throwable $e) {
            $endDate = $defaultEnd;
        }

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    protected function buildDashboardResponse(Carbon $startDate, Carbon $endDate): \stdClass
    {
        $rangeStart = $startDate->copy()->startOfDay();
        $rangeEnd = $endDate->copy()->endOfDay();
        $now = Carbon::now();
        $isCurrentYearRange = $rangeStart->equalTo($now->copy()->startOfYear())
            && $rangeEnd->equalTo($now->copy()->endOfYear());

        $filterLabel = $isCurrentYearRange
            ? (string) $now->format('Y')
            : $rangeStart->format('d M Y') . ' - ' . $rangeEnd->format('d M Y');

        $data = Rooms::with('category')->with('renter')->get();
        $room_used = 0;
        foreach ($data as $value) {
            if ($value->renter != null) {
                $room_used++;
            }
        }
        $response = new \stdClass();
        $rooms = new \stdClass();
        $rooms->total = $data->count();
        $rooms->used = $room_used;
        $response->rooms = $rooms;
        $response->filter = (object) [
            'start_date' => $rangeStart->format('Y-m-d'),
            'end_date' => $rangeEnd->format('Y-m-d'),
            'label' => $filterLabel,
            'is_default_year' => $isCurrentYearRange,
        ];

        // Use aggregated columns to be compatible with ONLY_FULL_GROUP_BY
        $belum_lunas = Fin_jurnal::leftjoin('bcl_tr_renter', 'bcl_tr_renter.trans_id', '=', 'bcl_fin_jurnal.doc_id')
            ->leftjoin('bcl_renter', 'bcl_renter.id', '=', 'bcl_tr_renter.id_renter')
            ->select(
                DB::raw('bcl_fin_jurnal.doc_id as doc_id'),
                DB::raw('MAX(bcl_fin_jurnal.tanggal) as last_tanggal'),
                DB::raw('MAX(bcl_renter.nama) as nama'),
                DB::raw('MAX(bcl_renter.id) as renter_id'),
                DB::raw('MAX(bcl_tr_renter.harga) as harga'),
                DB::raw('IFNULL(SUM(kredit),0) AS dibayar'),
                DB::raw('IFNULL(MAX(bcl_tr_renter.harga) - SUM(kredit),0) AS kurang')
            )->where('bcl_fin_jurnal.identity', 'regexp', 'pemasukan|sewa kamar')
            ->groupBy('bcl_fin_jurnal.doc_id')
            ->havingRaw('(MAX(bcl_tr_renter.harga) - SUM(kredit)) > 0')
            ->orderByRaw('MAX(bcl_fin_jurnal.tanggal) DESC')
            ->get();
        $response->belum_lunas = $belum_lunas->count();

        // Select only aggregated or grouped columns to be compatible with ONLY_FULL_GROUP_BY
        $inventory = Inventory::leftjoin('bcl_rooms', 'bcl_rooms.id', '=', 'bcl_inventories.assigned_to')
            ->leftjoin('bcl_fin_jurnal', function ($join) {
                $join->on('bcl_fin_jurnal.kode_subledger', 'like', 'bcl_inventories.inv_number');
            })
            ->select(
                DB::raw('bcl_inventories.inv_number as inv_number'),
                DB::raw('MAX(bcl_inventories.id) as inventory_id'),
                DB::raw('MAX(bcl_inventories.name) as inv_name'),
                DB::raw('MAX(bcl_inventories.assigned_to) as assigned_to'),
                DB::raw('MAX(bcl_rooms.room_name) as room_name'),
                DB::raw('MAX(bcl_inventories.maintanance_cycle) as maintanance_cycle'),
                DB::raw('MAX(bcl_inventories.maintanance_period) as maintanance_period'),
                DB::raw('MAX(tanggal) as last_maintanance')
            )
            ->groupBy('bcl_inventories.inv_number')
            ->get();

        $needed_maintanance = 0;
        foreach ($inventory as $data) {
            $next_maintanance = null;
            $remaining = null;
            if ($data->last_maintanance != null && $data->maintanance_cycle != null) {
                $period = (int) $data->maintanance_period;
                if ($data->maintanance_cycle == 'Minggu') {
                    $next_maintanance = Carbon::parse($data->last_maintanance)->addWeeks($period)->format('Y-m-d');
                    $remaining = Carbon::parse(Carbon::now())->diffInDays($next_maintanance);
                } else if ($data->maintanance_cycle == 'Bulan') {
                    $next_maintanance = Carbon::parse($data->last_maintanance)->addMonths($period)->format('Y-m-d');
                    $remaining = Carbon::parse(Carbon::now())->diffInDays($next_maintanance);
                } else if ($data->maintanance_cycle == 'Tahun') {
                    $next_maintanance = Carbon::parse($data->last_maintanance)->addYears($period)->format('Y-m-d');
                    $remaining =  Carbon::parse(Carbon::now())->diffInDays($next_maintanance);
                }
            }
            if ($next_maintanance != null && $remaining <= 7) {
                $needed_maintanance++;
            }
        }
        $response->needed_maintanance = $needed_maintanance;
        $response->total_revenue = (float) tr_renter::query()
            ->whereBetween('tgl_mulai', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->sum('harga');

        $room_stat = Rooms::leftjoin('bcl_tr_renter', function ($join) use ($rangeStart, $rangeEnd) {
            $join->on('bcl_tr_renter.room_id', '=', 'bcl_rooms.id');
            $join->whereBetween('bcl_tr_renter.tgl_mulai', [$rangeStart->toDateString(), $rangeEnd->toDateString()]);
        })->select('bcl_rooms.id', 'bcl_rooms.room_name', DB::raw('COALESCE(sum(bcl_tr_renter.harga), 0) as total_value'))->groupby('bcl_rooms.id')->orderby('total_value', 'DESC')->get();
        $stat = new \stdClass();
        $stat->room_name = [];
        $stat->total_value = [];
        foreach ($room_stat->take(10) as $data) {
            $stat->room_name[] = $data->room_name;
            $stat->total_value[] = $data->total_value;
        }
        $response->room_stat = $stat;

        $periodRevenueRows = tr_renter::query()
            ->selectRaw("CONCAT(lama_sewa, ' ', jangka_sewa) as period_label")
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw('COALESCE(SUM(harga), 0) as total_revenue')
            ->whereBetween('tgl_mulai', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->groupBy('lama_sewa', 'jangka_sewa')
            ->orderByDesc('total_revenue')
            ->orderByDesc('total_transactions')
            ->get();

        $periodStats = new \stdClass();
        $periodStats->labels = [];
        $periodStats->counts = [];
        $periodStats->revenues = [];
        $periodStats->items = [];
        $periodStats->total_transactions = 0;
        $periodStats->total_revenue = 0;

        foreach ($periodRevenueRows as $row) {
            $label = trim((string) $row->period_label);
            $transactions = (int) $row->total_transactions;
            $revenue = (float) $row->total_revenue;

            $periodStats->labels[] = $label;
            $periodStats->counts[] = $transactions;
            $periodStats->revenues[] = $revenue;
            $periodStats->items[] = (object) [
                'label' => $label,
                'total_transactions' => $transactions,
                'total_revenue' => $revenue,
            ];
            $periodStats->total_transactions += $transactions;
            $periodStats->total_revenue += $revenue;
        }

        $response->period_stats = $periodStats;

        return $response;
    }
}
