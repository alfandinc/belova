<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ElabAnalyticsController extends Controller
{
    /**
     * Display the analytics dashboard for E-Lab
     */
    public function index(Request $request)
    {
        // For now return the view; data endpoints will be added later
        return view('erm.elab.analytics.index');
    }

    // Visits per day (returns labels and counts)
    public function visitsPerDay(Request $request)
    {
        $start = $request->query('start') ? \Carbon\Carbon::parse($request->query('start'))->startOfDay() : now()->subDays(29)->startOfDay();
        $end = $request->query('end') ? \Carbon\Carbon::parse($request->query('end'))->endOfDay() : now()->endOfDay();

        // base visitation query for lab (jenis logic similar to ElabController)
        $query = \App\Models\ERM\Visitation::query();
        $query->whereBetween('tanggal_visitation', [$start, $end]);
        $query->where(function($q) {
            $q->where('jenis_kunjungan', 3)
              ->orWhere(function($q2) {
                  $q2->where('jenis_kunjungan', 1)
                     ->whereExists(function($sub) {
                         $sub->select(DB::raw(1))->from('erm_lab_permintaan')->whereColumn('erm_lab_permintaan.visitation_id', 'erm_visitations.id');
                     });
              });
        });

        $query->where('status_kunjungan', '!=', 7);

        $rows = $query->select(DB::raw('DATE(tanggal_visitation) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $data = [];
        $period = new \DatePeriod(new \DateTime($start->toDateString()), new \DateInterval('P1D'), (new \DateTime($end->toDateString()))->modify('+1 day'));
        foreach ($period as $dt) {
            $d = $dt->format('Y-m-d');
            $labels[] = $d;
            $data[] = isset($rows[$d]) ? (int)$rows[$d]->count : 0;
        }

        return response()->json(['labels' => $labels, 'data' => $data]);
    }

    // Tests per category
    public function testsPerCategory(Request $request)
    {
        $start = $request->query('start') ? \Carbon\Carbon::parse($request->query('start'))->startOfDay() : null;
        $end = $request->query('end') ? \Carbon\Carbon::parse($request->query('end'))->endOfDay() : null;

        $qb = DB::table('erm_lab_permintaan as lp')
            ->join('erm_lab_test as lt', 'lp.lab_test_id', '=', 'lt.id')
            ->join('erm_lab_kategori as lk', 'lt.lab_kategori_id', '=', 'lk.id')
            ->select('lk.nama as category', DB::raw('COUNT(*) as cnt'));

        if ($start && $end) {
            $qb->whereBetween('lp.created_at', [$start, $end]);
        }

        $rows = $qb->groupBy('lk.nama')->orderByDesc('cnt')->get();

        $labels = $rows->pluck('category');
        $data = $rows->pluck('cnt');

        return response()->json(['labels' => $labels, 'data' => $data]);
    }

    // Patients type: new vs returning within range
    public function patientsType(Request $request)
    {
        $start = $request->query('start') ? \Carbon\Carbon::parse($request->query('start'))->startOfDay() : now()->subDays(29)->startOfDay();
        $end = $request->query('end') ? \Carbon\Carbon::parse($request->query('end'))->endOfDay() : now()->endOfDay();

        // Patients who had a visit in range
        $patientsInRange = DB::table('erm_visitations')
            ->whereBetween('tanggal_visitation', [$start, $end])
            ->select('pasien_id')
            ->distinct();

        // Count new: patients whose first visitation date is within range
        $newCount = DB::table(DB::raw('(SELECT pasien_id, MIN(tanggal_visitation) as first_visit FROM erm_visitations GROUP BY pasien_id) as t'))
            ->whereBetween('first_visit', [$start, $end])
            ->whereIn('pasien_id', $patientsInRange)
            ->count();

        $total = $patientsInRange->count();
        $returning = max(0, $total - $newCount);

        return response()->json(['labels' => ['New','Returning'], 'data' => [$newCount, $returning]]);
    }

    // Payment status totals (sum nominal paid vs unpaid)
    public function paymentStatus(Request $request)
    {
        $start = $request->query('start') ? \Carbon\Carbon::parse($request->query('start'))->startOfDay() : now()->subDays(29)->startOfDay();
        $end = $request->query('end') ? \Carbon\Carbon::parse($request->query('end'))->endOfDay() : now()->endOfDay();

        // Subquery: nominal per visitation (sum lab test prices)
        $sub = DB::table('erm_lab_permintaan')
            ->join('erm_lab_test', 'erm_lab_permintaan.lab_test_id', '=', 'erm_lab_test.id')
            ->selectRaw('erm_lab_permintaan.visitation_id, SUM(erm_lab_test.harga) as nominal')
            ->groupBy('erm_lab_permintaan.visitation_id');

        $visitQ = DB::table('erm_visitations as v')
            ->leftJoinSub($sub, 'lp', function($j){ $j->on('lp.visitation_id','=', 'v.id'); })
            ->leftJoin('finance_invoices as fi', 'fi.visitation_id', '=', 'v.id')
            ->whereBetween('v.tanggal_visitation', [$start, $end])
            ->select('v.id', 'lp.nominal', 'fi.amount_paid');

        $rows = $visitQ->get();
        $paid = 0; $unpaid = 0;
        foreach ($rows as $r) {
            $nom = $r->nominal ? floatval($r->nominal) : 0;
            if ($r->amount_paid && floatval($r->amount_paid) > 0) $paid += $nom; else $unpaid += $nom;
        }

        return response()->json(['labels' => ['Sudah Dibayar','Belum Dibayar'], 'data' => [$paid, $unpaid]]);
    }

    // Top lab tests (most requested)
    public function topTests(Request $request)
    {
        try {
            
            $start = $request->query('start') ? Carbon::parse($request->query('start'))->startOfDay() : null;
            $end = $request->query('end') ? Carbon::parse($request->query('end'))->endOfDay() : null;

            $qb = DB::table('erm_lab_permintaan as lp')
                ->join('erm_lab_test as lt', 'lp.lab_test_id', '=', 'lt.id')
                ->select('lt.nama as test_name', DB::raw('COUNT(*) as cnt'));

            if ($start && $end) $qb->whereBetween('lp.created_at', [$start, $end]);

            $rows = $qb->groupBy('lt.nama')->orderByDesc('cnt')->limit(10)->get();

            return response()->json(['labels' => $rows->pluck('test_name'), 'data' => $rows->pluck('cnt')]);
        } catch (\Exception $e) {
            Log::error('ElabAnalyticsController::topTests error', ['error'=>$e->getMessage()]);
            return response()->json(['error'=>'Server error while computing top tests'], 500);
        }
    }

    // Top patients by number of visits with lab requests
    public function topPatientsByVisits(Request $request)
    {
        try {
            $start = $request->query('start') ? Carbon::parse($request->query('start'))->startOfDay() : null;
            $end = $request->query('end') ? Carbon::parse($request->query('end'))->endOfDay() : null;

            $qb = DB::table('erm_lab_permintaan as lp')
                ->join('erm_visitations as v', 'lp.visitation_id', '=', 'v.id')
                ->join('erm_pasiens as p', 'v.pasien_id', '=', 'p.id')
                ->select('p.nama as patient_name', DB::raw('COUNT(DISTINCT v.id) as visits'));

            if ($start && $end) $qb->whereBetween('v.tanggal_visitation', [$start, $end]);

            $rows = $qb->groupBy('p.nama')->orderByDesc('visits')->limit(10)->get();

            return response()->json(['labels' => $rows->pluck('patient_name'), 'data' => $rows->pluck('visits')]);
        } catch (\Exception $e) {
            Log::error('ElabAnalyticsController::topPatientsByVisits error', ['error'=>$e->getMessage()]);
            return response()->json(['error'=>'Server error while computing top patients by visits'], 500);
        }
    }

    // Top patients by spending (sum of lab test prices)
    public function topPatientsBySpending(Request $request)
    {
        try {
            $start = $request->query('start') ? Carbon::parse($request->query('start'))->startOfDay() : null;
            $end = $request->query('end') ? Carbon::parse($request->query('end'))->endOfDay() : null;

            $sub = DB::table('erm_lab_permintaan')
                ->join('erm_lab_test','erm_lab_permintaan.lab_test_id','=','erm_lab_test.id')
                ->selectRaw('erm_lab_permintaan.visitation_id, SUM(erm_lab_test.harga) as nominal')
                ->groupBy('erm_lab_permintaan.visitation_id');

            $qb = DB::table('erm_visitations as v')
                ->leftJoinSub($sub, 'lp', function($j){ $j->on('lp.visitation_id','=','v.id'); })
                ->leftJoin('erm_pasiens as p', 'v.pasien_id', '=', 'p.id')
                ->select('p.nama as patient_name', DB::raw('SUM(COALESCE(lp.nominal,0)) as total_spent'));

            if ($start && $end) $qb->whereBetween('v.tanggal_visitation', [$start, $end]);

            $rows = $qb->groupBy('p.nama')->orderByDesc('total_spent')->limit(10)->get();

            return response()->json(['labels' => $rows->pluck('patient_name'), 'data' => $rows->pluck('total_spent')]);
        } catch (\Exception $e) {
            Log::error('ElabAnalyticsController::topPatientsBySpending error', ['error'=>$e->getMessage()]);
            return response()->json(['error'=>'Server error while computing top patients by spending'], 500);
        }
    }

    // Totals summary
    public function totalsSummary(Request $request)
    {
        try {
            $start = $request->query('start') ? Carbon::parse($request->query('start'))->startOfDay() : now()->subDays(29)->startOfDay();
            $end = $request->query('end') ? Carbon::parse($request->query('end'))->endOfDay() : now()->endOfDay();

            // total visits with lab
            $visitsQ = DB::table('erm_visitations as v')
                ->whereBetween('v.tanggal_visitation', [$start, $end])
                ->whereExists(function($q){
                    $q->select(DB::raw(1))->from('erm_lab_permintaan')->whereColumn('erm_lab_permintaan.visitation_id','v.id');
                });
            $total_visits = $visitsQ->count();

            // total tests
            $testsQ = DB::table('erm_lab_permintaan as lp')
                ->join('erm_lab_test as lt', 'lp.lab_test_id','=','lt.id')
                ->whereBetween('lp.created_at', [$start, $end]);
            $total_tests = $testsQ->count();

            // revenue and paid/unpaid — compute using invoice items final_amount when present, otherwise lab_test.harga
            $labQuery = DB::table('erm_lab_permintaan as lp')
                ->leftJoin('erm_lab_test as lt', 'lp.lab_test_id', '=', 'lt.id')
                ->leftJoin('finance_invoice_items as fii', function($j){
                    $j->on('fii.billable_id', '=', 'lp.id')
                      ->where('fii.billable_type', '=', \App\Models\ERM\LabPermintaan::class);
                })
                ->leftJoin('finance_invoices as fi', 'fi.visitation_id', '=', 'lp.visitation_id')
                ->whereBetween('lp.created_at', [$start, $end])
                ->select('lp.visitation_id', DB::raw('COALESCE(fii.final_amount, lt.harga, 0) as nominal'), 'fi.amount_paid');

            $rows = $labQuery->get();
            $total_nominal = 0; $total_paid = 0; $total_unpaid = 0;
            // sum per visitation but consider invoice payment status
            $byVis = [];
            foreach($rows as $r){
                $vid = $r->visitation_id;
                $nom = $r->nominal ? floatval($r->nominal) : 0;
                if (!isset($byVis[$vid])) { $byVis[$vid] = ['nominal' => 0, 'paid' => false]; }
                $byVis[$vid]['nominal'] += $nom;
                if ($r->amount_paid && floatval($r->amount_paid) > 0) $byVis[$vid]['paid'] = true;
            }
            foreach($byVis as $vid => $rec){
                $total_nominal += $rec['nominal'];
                if ($rec['paid']) $total_paid += $rec['nominal']; else $total_unpaid += $rec['nominal'];
            }

            return response()->json([
                'total_visits' => $total_visits,
                'total_tests' => $total_tests,
                'total_nominal' => $total_nominal,
                'total_paid' => $total_paid,
                'total_unpaid' => $total_unpaid,
            ]);
        } catch (\Exception $e) {
            Log::error('ElabAnalyticsController::totalsSummary error', ['error'=>$e->getMessage()]);
            return response()->json(['error'=>'Server error while computing totals summary'], 500);
        }
    }
}
