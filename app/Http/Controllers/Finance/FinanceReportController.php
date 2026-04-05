<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    public function index()
    {
        return view('finance.reports.index');
    }

    public function dailyData(Request $request)
    {
        $validated = $request->validate([
            'report_date' => ['nullable', 'date'],
        ]);

        $reportDate = $validated['report_date'] ?? now()->toDateString();

        return response()->json([
            'period_label' => Carbon::parse($reportDate)->translatedFormat('d F Y'),
            'data' => $this->buildSummaryRows('daily', [
            'report_date' => $reportDate,
            ]),
        ]);
    }

    public function monthlyData(Request $request)
    {
        $validated = $request->validate([
            'report_month' => ['nullable', 'date_format:Y-m'],
        ]);

        $reportMonth = $validated['report_month'] ?? now()->format('Y-m');

        return response()->json([
            'period_label' => Carbon::createFromFormat('Y-m', $reportMonth)->translatedFormat('F Y'),
            'data' => $this->buildSummaryRows('monthly', [
            'report_month' => $reportMonth,
            ]),
        ]);
    }

    private function buildSummaryRows(string $mode, array $filters = []): array
    {
        $monthStart = null;
        $monthEnd = null;

        $visitAggregate = DB::table('erm_visitations as visitation')
            ->leftJoin('erm_klinik as klinik', 'klinik.id', '=', 'visitation.klinik_id')
            ->selectRaw("COALESCE(CAST(visitation.klinik_id AS CHAR), 'unknown') as clinic_key")
            ->selectRaw("COALESCE(klinik.nama, 'Tanpa Klinik') as clinic_name")
            ->selectRaw('COUNT(DISTINCT visitation.id) as total_visit')
            ->where('visitation.status_kunjungan', 2)
            ->whereNotNull('visitation.tanggal_visitation');

        $transactionAggregate = DB::table('finance_transactions as transaction')
            ->leftJoin('erm_visitations as visitation', 'visitation.id', '=', 'transaction.visitation_id')
            ->leftJoin('erm_klinik as klinik', 'klinik.id', '=', 'visitation.klinik_id')
            ->selectRaw("COALESCE(CAST(visitation.klinik_id AS CHAR), 'unknown') as clinic_key")
            ->selectRaw("COALESCE(klinik.nama, 'Tanpa Klinik') as clinic_name")
            ->selectRaw("COALESCE(SUM(CASE WHEN LOWER(COALESCE(transaction.jenis_transaksi, 'in')) = 'out' THEN -transaction.jumlah ELSE transaction.jumlah END), 0) as total_pendapatan")
            ->whereNotNull('transaction.tanggal');

        $piutangAggregate = DB::table('finance_piutangs as piutang')
            ->leftJoin('erm_visitations as visitation', 'visitation.id', '=', 'piutang.visitation_id')
            ->leftJoin('erm_klinik as klinik', 'klinik.id', '=', 'visitation.klinik_id')
            ->selectRaw("COALESCE(CAST(visitation.klinik_id AS CHAR), 'unknown') as clinic_key")
            ->selectRaw("COALESCE(klinik.nama, 'Tanpa Klinik') as clinic_name")
            ->selectRaw('COALESCE(SUM(GREATEST(piutang.amount - COALESCE(piutang.paid_amount, 0), 0)), 0) as total_piutang')
            ->where(function ($query) {
                $query->whereNull('piutang.payment_status')
                    ->orWhereRaw('LOWER(piutang.payment_status) != ?', ['paid'])
                    ->orWhereRaw('COALESCE(piutang.paid_amount, 0) < COALESCE(piutang.amount, 0)');
            });

        if ($mode === 'daily') {
            $reportDate = $filters['report_date'] ?? now()->toDateString();

            $visitAggregate->whereDate('visitation.tanggal_visitation', $reportDate);
            $transactionAggregate->whereDate('transaction.tanggal', $reportDate);
            $piutangAggregate->whereDate('visitation.tanggal_visitation', $reportDate);
        } else {
            $reportMonth = $filters['report_month'] ?? now()->format('Y-m');
            $monthStart = Carbon::createFromFormat('Y-m', $reportMonth)->startOfMonth()->toDateString();
            $monthEnd = Carbon::createFromFormat('Y-m', $reportMonth)->endOfMonth()->toDateString();

            $visitAggregate->whereBetween(DB::raw('DATE(visitation.tanggal_visitation)'), [$monthStart, $monthEnd]);
            $transactionAggregate->whereBetween(DB::raw('DATE(transaction.tanggal)'), [$monthStart, $monthEnd]);
            $piutangAggregate->whereBetween(DB::raw('DATE(visitation.tanggal_visitation)'), [$monthStart, $monthEnd]);
        }

        $visitByClinic = $visitAggregate
            ->groupBy('visitation.klinik_id', 'klinik.nama')
            ->orderBy('clinic_name')
            ->get()
            ->keyBy('clinic_key');

        $pendapatanByClinic = $transactionAggregate
            ->groupBy('visitation.klinik_id', 'klinik.nama')
            ->orderBy('clinic_name')
            ->get()
            ->keyBy('clinic_key');

        $piutangByClinic = $piutangAggregate
            ->groupBy('visitation.klinik_id', 'klinik.nama')
            ->orderBy('clinic_name')
            ->get()
            ->keyBy('clinic_key');

        $clinicRows = collect($visitByClinic->keys())
            ->merge($pendapatanByClinic->keys())
            ->merge($piutangByClinic->keys())
            ->unique()
            ->map(function ($clinicKey) use ($visitByClinic, $pendapatanByClinic, $piutangByClinic) {
                $visitRow = $visitByClinic->get($clinicKey);
                $pendapatanRow = $pendapatanByClinic->get($clinicKey);
                $piutangRow = $piutangByClinic->get($clinicKey);

                return [
                    'clinic_key' => $clinicKey,
                    'clinic_name' => $visitRow->clinic_name ?? $pendapatanRow->clinic_name ?? $piutangRow->clinic_name ?? 'Tanpa Klinik',
                    'total_visit' => (int) ($visitRow->total_visit ?? 0),
                    'total_pendapatan' => (float) ($pendapatanRow->total_pendapatan ?? 0),
                    'total_piutang' => (float) ($piutangRow->total_piutang ?? 0),
                ];
            })
            ->sortBy('clinic_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        if ($clinicRows->isEmpty()) {
            $clinicRows = collect([[
                'clinic_key' => 'unknown',
                'clinic_name' => 'Tanpa Klinik',
                'total_visit' => 0,
                'total_pendapatan' => 0,
                'total_piutang' => 0,
            ]]);
        }

        $rows = [];

        foreach ($clinicRows as $clinicRow) {
            $rows[] = [
                'row_type' => 'clinic_header',
                'clinic_name' => $clinicRow['clinic_name'],
            ];

            $rows[] = [
                'row_type' => 'metric',
                'metric_label' => 'Total Visit',
                'metric_value' => $clinicRow['total_visit'],
                'metric_value_display' => number_format($clinicRow['total_visit'], 0, ',', '.'),
            ];

            $rows[] = [
                'row_type' => 'metric',
                'metric_label' => 'Pendapatan',
                'metric_value' => $clinicRow['total_pendapatan'],
                'metric_value_display' => 'Rp ' . number_format($clinicRow['total_pendapatan'], 0, ',', '.'),
            ];

            $rows[] = [
                'row_type' => 'metric',
                'metric_label' => 'Piutang',
                'metric_value' => $clinicRow['total_piutang'],
                'metric_value_display' => 'Rp ' . number_format($clinicRow['total_piutang'], 0, ',', '.'),
            ];
        }

        $bclTransactionAggregate = DB::table('bcl_tr_renter')
            ->selectRaw('COUNT(*) as total_transaksi')
            ->selectRaw('COALESCE(SUM(harga), 0) as total_pendapatan')
            ->whereNotNull('tanggal');

        $bclPiutangBase = DB::table('bcl_tr_renter as renter')
            ->leftJoin('bcl_fin_jurnal as jurnal', function ($join) {
                $join->on('jurnal.doc_id', '=', 'renter.trans_id')
                    ->where('jurnal.kode_akun', '=', '4-10101');
            })
            ->selectRaw('renter.trans_id')
            ->selectRaw('GREATEST(COALESCE(MAX(renter.harga), 0) - COALESCE(SUM(jurnal.kredit), 0), 0) as sisa_piutang')
            ->whereNotNull('renter.tanggal');

        if ($mode === 'daily') {
            $bclTransactionAggregate->whereDate('tanggal', $reportDate);
            $bclPiutangBase->whereDate('renter.tanggal', $reportDate);
        } else {
            $bclTransactionAggregate->whereBetween(DB::raw('DATE(tanggal)'), [$monthStart, $monthEnd]);
            $bclPiutangBase->whereBetween(DB::raw('DATE(renter.tanggal)'), [$monthStart, $monthEnd]);
        }

        $bclSummary = $bclTransactionAggregate->first();
        $bclPiutang = DB::query()
            ->fromSub($bclPiutangBase->groupBy('renter.trans_id'), 'bcl_piutang')
            ->selectRaw('COALESCE(SUM(sisa_piutang), 0) as total_piutang')
            ->value('total_piutang');

        $rows[] = [
            'row_type' => 'clinic_header',
            'clinic_name' => 'Belova Center Living',
        ];

        $rows[] = [
            'row_type' => 'metric',
            'metric_label' => 'Total Transaksi',
            'metric_value' => (int) ($bclSummary->total_transaksi ?? 0),
            'metric_value_display' => number_format((int) ($bclSummary->total_transaksi ?? 0), 0, ',', '.'),
        ];

        $rows[] = [
            'row_type' => 'metric',
            'metric_label' => 'Pendapatan',
            'metric_value' => (float) ($bclSummary->total_pendapatan ?? 0),
            'metric_value_display' => 'Rp ' . number_format((float) ($bclSummary->total_pendapatan ?? 0), 0, ',', '.'),
        ];

        $rows[] = [
            'row_type' => 'metric',
            'metric_label' => 'Piutang',
            'metric_value' => (float) ($bclPiutang ?? 0),
            'metric_value_display' => 'Rp ' . number_format((float) ($bclPiutang ?? 0), 0, ',', '.'),
        ];

        return $rows;
    }
}