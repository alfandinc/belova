<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ERM\Visitation;
use App\Models\ERM\Pasien;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;

class ERMDashboardController extends Controller
{

    public function index(Request $request)
    {
        return view('erm.dashboard');
    }

    // AJAX: Get visitation count filtered by dokter and date range
    public function visitationCount(Request $request)
    {
        $dokterId = $request->input('dokter_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $baseQuery = \App\Models\ERM\Visitation::query();
        if ($dokterId) {
            $baseQuery->where('dokter_id', $dokterId);
        }
        if ($startDate && $endDate) {
            $baseQuery->whereBetween('tanggal_visitation', [$startDate, $endDate]);
        }

        $count = $baseQuery->count();

        // Jenis Kunjungan counts
        $jenisKunjungan = [];
        foreach ([1,2,3] as $jk) {
            $jenisKunjungan[$jk] = (clone $baseQuery)->where('jenis_kunjungan', $jk)->count();
        }
        $dokterId = $request->input('dokter_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $baseQuery = \App\Models\ERM\Visitation::query();
        if ($dokterId) {
            $baseQuery->where('dokter_id', $dokterId);
        }
        if ($startDate && $endDate) {
            $baseQuery->whereBetween('tanggal_visitation', [$startDate, $endDate]);
        }

        $count = $baseQuery->count();

        // Clone base query for each status
        $dilayani = (clone $baseQuery)->where('status_kunjungan', 2)->count();
        $belum = (clone $baseQuery)->where('status_kunjungan', 1)->count();
        $tidakdatang = (clone $baseQuery)->where('status_kunjungan', 0)->count();
        $dibatalkan = (clone $baseQuery)->where('status_kunjungan', 7)->count();

        // Find top 5 pasien with most visits
        $topVisits = (clone $baseQuery)
            ->selectRaw('pasien_id, COUNT(*) as total')
            ->groupBy('pasien_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $topPasien = [];
        foreach ($topVisits as $visit) {
            if ($visit->pasien_id) {
                $pasien = \App\Models\ERM\Pasien::find($visit->pasien_id);
                if ($pasien) {
                    $topPasien[] = $pasien->nama . ' (' . $visit->total . ')';
                }
            }
        }

        // Pasien Baru: pasien created in the filtered date range
        $pasienBaruCount = 0;
        if ($startDate && $endDate) {
            $pasienBaruCount = \App\Models\ERM\Pasien::whereBetween('created_at', [$startDate, $endDate])->count();
        }

        // Monthly visit count for current year, filtered by dokter if selected
        $monthlyVisitCounts = [];
        $year = date('Y');
        for ($m = 1; $m <= 12; $m++) {
            $monthStart = Carbon::create($year, $m, 1)->startOfMonth()->format('Y-m-d');
            $monthEnd = Carbon::create($year, $m, 1)->endOfMonth()->format('Y-m-d');
            $monthlyQuery = \App\Models\ERM\Visitation::query();
            if ($dokterId) {
                $monthlyQuery->where('dokter_id', $dokterId);
            }
            $monthlyQuery->whereBetween('tanggal_visitation', [$monthStart, $monthEnd]);
            $monthlyVisitCounts[] = $monthlyQuery->count();
        }

        // Metode Bayar counts (1=Umum, 2=InHealth)
        $metodeBayarCounts = [];
        foreach ([1,2] as $mb) {
            $metodeBayarCounts[$mb] = (clone $baseQuery)->where('metode_bayar_id', $mb)->count();
        }

        // Find top 5 most spender pasien (by invoice total_amount)
        $invoiceQuery = \App\Models\Finance\Invoice::query();
        if ($dokterId) {
            $invoiceQuery->whereHas('visitation', function($q) use ($dokterId) {
                $q->where('dokter_id', $dokterId);
            });
        }
        if ($startDate && $endDate) {
            $invoiceQuery->whereHas('visitation', function($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal_visitation', [$startDate, $endDate]);
            });
        }
        $spenderResults = $invoiceQuery
            ->selectRaw('visitation_id, SUM(total_amount) as total_spent')
            ->groupBy('visitation_id')
            ->orderByDesc('total_spent')
            ->get();

        // Aggregate by pasien_id
        $pasienSpend = [];
        foreach ($spenderResults as $row) {
            $visitation = \App\Models\ERM\Visitation::find($row->visitation_id);
            if ($visitation && $visitation->pasien_id) {
                if (!isset($pasienSpend[$visitation->pasien_id])) {
                    $pasienSpend[$visitation->pasien_id] = 0;
                }
                $pasienSpend[$visitation->pasien_id] += $row->total_spent;
            }
        }
        // Sort pasienSpend by total_spent desc and get top 5
        arsort($pasienSpend);
        $mostSpenderPasien = [];
        foreach (array_slice($pasienSpend, 0, 5, true) as $pasienId => $totalSpent) {
            $pasien = \App\Models\ERM\Pasien::find($pasienId);
            if ($pasien) {
                $mostSpenderPasien[] = $pasien->nama . ' (Rp ' . number_format($totalSpent, 0, ',', '.') . ')';
            }
        }
        return response()->json([
            'count' => $count,
            'dilayani' => $dilayani,
            'belum' => $belum,
            'tidakdatang' => $tidakdatang,
            'dibatalkan' => $dibatalkan,
            'most_visit_pasien' => $topPasien,
            'most_spender_pasien' => $mostSpenderPasien,
            'metode_bayar_counts' => $metodeBayarCounts,
            'jenis_kunjungan' => $jenisKunjungan,
            'pasien_baru_count' => $pasienBaruCount,
            'monthly_visit_counts' => $monthlyVisitCounts,
        ]);
    }

    public function daftarpasien()
    {
        return view('erm.daftarpasien');
    }

        // AJAX: Get visitation detail for modal
    public function visitationDetail(Request $request)
    {
        $dokterId = $request->input('dokter_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status');

        $query = \App\Models\ERM\Visitation::with('pasien');
        if ($dokterId) {
            $query->where('dokter_id', $dokterId);
        }
        if ($startDate && $endDate) {
            $query->whereBetween('tanggal_visitation', [$startDate, $endDate]);
        }
        if ($status !== 'all') {
            $query->where('status_kunjungan', $status);
        }

        // Use Yajra DataTables for server-side processing
        return DataTables::of($query)
            ->addColumn('nama_pasien', function($item) {
                return $item->pasien->nama ?? '-';
            })
            ->editColumn('tanggal_visitation', function($item) {
                return $item->tanggal_visitation;
            })
            ->make(true);
    }

}
