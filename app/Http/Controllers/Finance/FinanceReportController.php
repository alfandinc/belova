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

    public function detailData(Request $request)
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:daily,monthly'],
            'metric' => ['required', 'in:pendapatan,piutang'],
            'clinic_key' => ['required', 'string'],
            'clinic_name' => ['nullable', 'string'],
            'report_date' => ['nullable', 'date'],
            'report_month' => ['nullable', 'date_format:Y-m'],
        ]);

        $mode = $validated['mode'];
        $metric = $validated['metric'];
        $clinicKey = $validated['clinic_key'];
        $clinicName = $validated['clinic_name'] ?? $clinicKey;

        if ($clinicKey === 'bcl') {
            $payload = $metric === 'piutang'
                ? $this->buildBclPiutangDetails($mode, $validated)
                : $this->buildBclPendapatanDetails($mode, $validated);
        } else {
            $payload = $metric === 'piutang'
                ? $this->buildFinancePiutangDetails($mode, $clinicKey, $validated)
                : $this->buildFinancePendapatanDetails($mode, $clinicKey, $validated);
        }

        return response()->json([
            'title' => ucfirst($metric) . ' - ' . $clinicName,
            'headers' => $payload['headers'],
            'rows' => $payload['rows'],
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
                'clinic_key' => $clinicRow['clinic_key'],
                'clinic_name' => $clinicRow['clinic_name'],
                'metric_label' => 'Total Visit',
                'metric_key' => 'total_visit',
                'metric_value' => $clinicRow['total_visit'],
                'metric_value_display' => number_format($clinicRow['total_visit'], 0, ',', '.'),
            ];

            $rows[] = [
                'row_type' => 'metric',
                'clinic_key' => $clinicRow['clinic_key'],
                'clinic_name' => $clinicRow['clinic_name'],
                'metric_label' => 'Pendapatan',
                'metric_key' => 'pendapatan',
                'metric_value' => $clinicRow['total_pendapatan'],
                'metric_value_display' => 'Rp ' . number_format($clinicRow['total_pendapatan'], 0, ',', '.'),
            ];

            $rows[] = [
                'row_type' => 'metric',
                'clinic_key' => $clinicRow['clinic_key'],
                'clinic_name' => $clinicRow['clinic_name'],
                'metric_label' => 'Piutang',
                'metric_key' => 'piutang',
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
            'clinic_key' => 'bcl',
            'clinic_name' => 'Belova Center Living',
            'metric_label' => 'Total Transaksi',
            'metric_key' => 'total_transaksi',
            'metric_value' => (int) ($bclSummary->total_transaksi ?? 0),
            'metric_value_display' => number_format((int) ($bclSummary->total_transaksi ?? 0), 0, ',', '.'),
        ];

        $rows[] = [
            'row_type' => 'metric',
            'clinic_key' => 'bcl',
            'clinic_name' => 'Belova Center Living',
            'metric_label' => 'Pendapatan',
            'metric_key' => 'pendapatan',
            'metric_value' => (float) ($bclSummary->total_pendapatan ?? 0),
            'metric_value_display' => 'Rp ' . number_format((float) ($bclSummary->total_pendapatan ?? 0), 0, ',', '.'),
        ];

        $rows[] = [
            'row_type' => 'metric',
            'clinic_key' => 'bcl',
            'clinic_name' => 'Belova Center Living',
            'metric_label' => 'Piutang',
            'metric_key' => 'piutang',
            'metric_value' => (float) ($bclPiutang ?? 0),
            'metric_value_display' => 'Rp ' . number_format((float) ($bclPiutang ?? 0), 0, ',', '.'),
        ];

        return $rows;
    }

    private function applyModeDateFilter($query, string $mode, string $column, array $filters)
    {
        if ($mode === 'daily') {
            $query->whereDate($column, $filters['report_date'] ?? now()->toDateString());

            return;
        }

        $reportMonth = $filters['report_month'] ?? now()->format('Y-m');
        $monthStart = Carbon::createFromFormat('Y-m', $reportMonth)->startOfMonth()->toDateString();
        $monthEnd = Carbon::createFromFormat('Y-m', $reportMonth)->endOfMonth()->toDateString();

        $query->whereBetween(DB::raw('DATE(' . $column . ')'), [$monthStart, $monthEnd]);
    }

    private function buildFinancePendapatanDetails(string $mode, string $clinicKey, array $filters): array
    {
        $query = DB::table('finance_transactions as transaction')
            ->leftJoin('erm_visitations as visitation', 'visitation.id', '=', 'transaction.visitation_id')
            ->leftJoin('erm_pasiens as pasien', 'pasien.id', '=', 'visitation.pasien_id')
            ->leftJoin('finance_invoices as invoice', 'invoice.id', '=', 'transaction.invoice_id')
            ->whereRaw("COALESCE(CAST(visitation.klinik_id AS CHAR), 'unknown') = ?", [$clinicKey])
            ->whereNotNull('transaction.tanggal');

        $this->applyModeDateFilter($query, $mode, 'transaction.tanggal', $filters);

        $rows = $query
            ->orderBy('transaction.tanggal')
            ->select('transaction.tanggal')
            ->addSelect('transaction.invoice_id')
            ->addSelect('transaction.visitation_id')
            ->selectRaw("COALESCE(pasien.nama, '-') as nama")
            ->selectRaw("COALESCE(invoice.invoice_number, '-') as invoice_number")
            ->selectRaw("LOWER(COALESCE(transaction.jenis_transaksi, 'in')) as jenis")
            ->addSelect('transaction.jumlah')
            ->selectRaw("COALESCE(transaction.deskripsi, '-') as deskripsi")
            ->get()
            ->groupBy(function ($row) {
                $tanggalKey = $row->tanggal ? Carbon::parse($row->tanggal)->format('Y-m-d H:i:s') : 'no-date';

                return implode('|', [
                    $tanggalKey,
                    $row->invoice_id ?? 'no-invoice',
                    $row->visitation_id ?? 'no-visitation',
                ]);
            })
            ->map(function ($group) {
                $first = $group->first();
                $netAmount = $group->sum(function ($item) {
                    return ($item->jenis ?? 'in') === 'out'
                        ? -1 * (float) ($item->jumlah ?? 0)
                        : (float) ($item->jumlah ?? 0);
                });

                $mergedDescription = $group
                    ->pluck('deskripsi')
                    ->filter(function ($value) {
                        return $value !== null && trim((string) $value) !== '' && trim((string) $value) !== '-';
                    })
                    ->map(function ($value) {
                        return trim((string) $value);
                    })
                    ->unique()
                    ->values()
                    ->implode(' | ');

                return [
                    $first->tanggal ? Carbon::parse($first->tanggal)->format('d/m/Y H:i') : '-',
                    $first->nama,
                    $first->invoice_number,
                    'Rp ' . number_format($netAmount, 0, ',', '.'),
                    $mergedDescription !== '' ? $mergedDescription : '-',
                ];
            })
            ->values()
            ->all();

        return [
            'headers' => ['Tanggal', 'Pasien', 'Invoice', 'Jumlah', 'Deskripsi'],
            'rows' => $rows,
        ];
    }

    private function buildFinancePiutangDetails(string $mode, string $clinicKey, array $filters): array
    {
        $query = DB::table('finance_piutangs as piutang')
            ->leftJoin('erm_visitations as visitation', 'visitation.id', '=', 'piutang.visitation_id')
            ->leftJoin('erm_pasiens as pasien', 'pasien.id', '=', 'visitation.pasien_id')
            ->leftJoin('finance_invoices as invoice', 'invoice.id', '=', 'piutang.invoice_id')
            ->whereRaw("COALESCE(CAST(visitation.klinik_id AS CHAR), 'unknown') = ?", [$clinicKey])
            ->where(function ($query) {
                $query->whereNull('piutang.payment_status')
                    ->orWhereRaw('LOWER(piutang.payment_status) != ?', ['paid'])
                    ->orWhereRaw('COALESCE(piutang.paid_amount, 0) < COALESCE(piutang.amount, 0)');
            });

        $this->applyModeDateFilter($query, $mode, 'visitation.tanggal_visitation', $filters);

        $rows = $query
            ->orderBy('visitation.tanggal_visitation')
            ->selectRaw("DATE_FORMAT(visitation.tanggal_visitation, '%d/%m/%Y') as tanggal")
            ->selectRaw("COALESCE(pasien.nama, '-') as nama")
            ->selectRaw("COALESCE(invoice.invoice_number, '-') as invoice_number")
            ->selectRaw("CONCAT('Rp ', FORMAT(piutang.amount, 0, 'de_DE')) as nominal")
            ->selectRaw("CONCAT('Rp ', FORMAT(COALESCE(piutang.paid_amount, 0), 0, 'de_DE')) as dibayar")
            ->selectRaw("CONCAT('Rp ', FORMAT(GREATEST(piutang.amount - COALESCE(piutang.paid_amount, 0), 0), 0, 'de_DE')) as sisa")
            ->get()
            ->map(function ($row) {
                return [
                    $row->tanggal,
                    $row->nama,
                    $row->invoice_number,
                    $row->nominal,
                    $row->dibayar,
                    $row->sisa,
                ];
            })
            ->all();

        return [
            'headers' => ['Tanggal', 'Pasien', 'Invoice', 'Nominal', 'Dibayar', 'Sisa Piutang'],
            'rows' => $rows,
        ];
    }

    private function buildBclPendapatanDetails(string $mode, array $filters): array
    {
        $query = DB::table('bcl_tr_renter as renter')
            ->leftJoin('bcl_renter as customer', 'customer.id', '=', 'renter.id_renter')
            ->whereNotNull('renter.tanggal');

        $this->applyModeDateFilter($query, $mode, 'renter.tanggal', $filters);

        $rows = $query
            ->orderBy('renter.tanggal')
            ->selectRaw("DATE_FORMAT(renter.tanggal, '%d/%m/%Y') as tanggal")
            ->selectRaw('COALESCE(renter.trans_id, \'-\') as trans_id')
            ->selectRaw("COALESCE(customer.nama, '-') as nama")
            ->selectRaw("CONCAT('Rp ', FORMAT(renter.harga, 0, 'de_DE')) as jumlah")
            ->get()
            ->map(function ($row) {
                return [
                    $row->tanggal,
                    $row->trans_id,
                    $row->nama,
                    $row->jumlah,
                ];
            })
            ->all();

        return [
            'headers' => ['Tanggal', 'Transaksi', 'Renter', 'Pendapatan'],
            'rows' => $rows,
        ];
    }

    private function buildBclPiutangDetails(string $mode, array $filters): array
    {
        $query = DB::table('bcl_tr_renter as renter')
            ->leftJoin('bcl_renter as customer', 'customer.id', '=', 'renter.id_renter')
            ->leftJoin('bcl_fin_jurnal as jurnal', function ($join) {
                $join->on('jurnal.doc_id', '=', 'renter.trans_id')
                    ->where('jurnal.kode_akun', '=', '4-10101');
            })
            ->whereNotNull('renter.tanggal');

        $this->applyModeDateFilter($query, $mode, 'renter.tanggal', $filters);

        $rows = $query
            ->groupBy('renter.trans_id', 'renter.tanggal', 'customer.nama')
            ->havingRaw('GREATEST(COALESCE(MAX(renter.harga), 0) - COALESCE(SUM(jurnal.kredit), 0), 0) > 0')
            ->orderBy('renter.tanggal')
            ->selectRaw("DATE_FORMAT(renter.tanggal, '%d/%m/%Y') as tanggal")
            ->selectRaw('COALESCE(renter.trans_id, \'-\') as trans_id')
            ->selectRaw("COALESCE(customer.nama, '-') as nama")
            ->selectRaw("CONCAT('Rp ', FORMAT(COALESCE(MAX(renter.harga), 0), 0, 'de_DE')) as nominal")
            ->selectRaw("CONCAT('Rp ', FORMAT(COALESCE(SUM(jurnal.kredit), 0), 0, 'de_DE')) as dibayar")
            ->selectRaw("CONCAT('Rp ', FORMAT(GREATEST(COALESCE(MAX(renter.harga), 0) - COALESCE(SUM(jurnal.kredit), 0), 0), 0, 'de_DE')) as sisa")
            ->get()
            ->map(function ($row) {
                return [
                    $row->tanggal,
                    $row->trans_id,
                    $row->nama,
                    $row->nominal,
                    $row->dibayar,
                    $row->sisa,
                ];
            })
            ->all();

        return [
            'headers' => ['Tanggal', 'Transaksi', 'Renter', 'Nominal', 'Dibayar', 'Sisa Piutang'],
            'rows' => $rows,
        ];
    }
}