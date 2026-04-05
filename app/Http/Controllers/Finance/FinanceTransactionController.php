<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Invoice;
use App\Models\Finance\FinanceTransaction;
use App\Services\Finance\TransactionRecorderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class FinanceTransactionController extends Controller
{
    public function index()
    {
        return view('finance.transactions.index');
    }

    public function stats(Request $request)
    {
        $baseQuery = $this->buildFilteredQuery($request);

        $totalIn = (clone $baseQuery)
            ->where('jenis_transaksi', 'in')
            ->sum('jumlah');

        $totalOut = (clone $baseQuery)
            ->where('jenis_transaksi', 'out')
            ->sum('jumlah');

        return response()->json([
            'total_in' => (float) $totalIn,
            'total_out' => (float) $totalOut,
            'balance' => (float) $totalIn - (float) $totalOut,
        ]);
    }

    public function downloadExcel(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $jenisTransaksi = trim((string) $request->input('jenis_transaksi', ''));
        $metodeBayar = trim((string) $request->input('metode_bayar', ''));
        $search = trim((string) $request->input('search', ''));

        $filenameDate = now()->format('Ymd_His');

        return (new \App\Exports\Finance\TransactionExport(
            $startDate,
            $endDate,
            $jenisTransaksi,
            $metodeBayar,
            $search
        ))->download('transaksi_' . $filenameDate . '.xlsx');
    }

    public function data(Request $request)
    {
        $query = $this->buildFilteredQuery($request);

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $search = $request->get('search');
                $value = is_array($search) && isset($search['value']) ? trim((string) $search['value']) : '';

                if ($value === '') {
                    return;
                }

                $query->where(function ($q) use ($value) {
                    $q->where('finance_transactions.visitation_id', 'like', "%{$value}%")
                        ->orWhere('finance_transactions.invoice_id', 'like', "%{$value}%")
                        ->orWhere('finance_transactions.metode_bayar', 'like', "%{$value}%")
                        ->orWhere('finance_transactions.jenis_transaksi', 'like', "%{$value}%")
                        ->orWhere('finance_transactions.deskripsi', 'like', "%{$value}%")
                        ->orWhereHas('visitation.pasien', function ($pq) use ($value) {
                            $pq->where('nama', 'like', "%{$value}%")
                                ->orWhere('id', 'like', "%{$value}%");
                        })
                        ->orWhereHas('invoice', function ($iq) use ($value) {
                            $iq->where('invoice_number', 'like', "%{$value}%");
                        });
                });
            })
            ->addColumn('tanggal_display', function ($row) {
                return $row->tanggal ? $row->tanggal->format('j F Y H:i') : '-';
            })
            ->addColumn('pasien_display', function ($row) {
                $pasien = optional(optional($row->visitation)->pasien);
                if (!$pasien || empty($pasien->nama)) {
                    return '-';
                }

                $label = $pasien->nama;
                if (!empty($pasien->id)) {
                    $label .= ' (' . $pasien->id . ')';
                }

                return e($label);
            })
            ->addColumn('invoice_display', function ($row) {
                $invoiceNumber = $row->invoice && !empty($row->invoice->invoice_number)
                    ? $row->invoice->invoice_number
                    : null;

                return e($invoiceNumber ?: '-');
            })
            ->addColumn('jumlah_display', function ($row) {
                return '<div class="text-right"><strong>Rp ' . number_format((float) $row->jumlah, 0, ',', '.') . '</strong></div>';
            })
            ->addColumn('jenis_transaksi_display', function ($row) {
                $jenis = strtolower((string) ($row->jenis_transaksi ?? 'in'));
                $cls = $jenis === 'out' ? 'badge-danger' : 'badge-success';
                $label = $jenis === 'out' ? 'Out' : 'In';
                return '<span class="badge ' . $cls . '">' . e($label) . '</span>';
            })
            ->addColumn('metode_bayar_display', function ($row) {
                return $row->metode_bayar ? e($row->metode_bayar) : '-';
            })
            ->rawColumns(['pasien_display', 'invoice_display', 'jumlah_display', 'jenis_transaksi_display'])
            ->make(true);
    }

    public function previewBackfillChangeTransactions(Request $request)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['Admin']), 403);

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'cash_only' => ['nullable', 'boolean'],
        ]);

        $cashOnly = (bool) ($validated['cash_only'] ?? false);

        $invoices = $this->buildBackfillChangeQuery($validated['start_date'], $validated['end_date'], $cashOnly)
            ->orderByRaw('COALESCE(payment_date, created_at) asc')
            ->orderBy('id')
            ->get();

        $rows = $invoices->map(function (Invoice $invoice) {
            $pasien = optional(optional($invoice->visitation)->pasien);

            return [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number ?: '-',
                'visitation_id' => $invoice->visitation_id,
                'patient_name' => $pasien && !empty($pasien->nama) ? $pasien->nama : '-',
                'patient_id' => $pasien && !empty($pasien->id) ? $pasien->id : null,
                'payment_method' => $invoice->payment_method ?: '-',
                'payment_date_display' => optional($invoice->payment_date ?: $invoice->created_at)->format('d M Y H:i'),
                'change_amount' => (float) ($invoice->change_amount ?? 0),
                'description' => 'Kembalian billing invoice ' . ($invoice->invoice_number ?? $invoice->id),
            ];
        })->values();

        return response()->json([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'cash_only' => $cashOnly,
            'total_count' => $rows->count(),
            'total_change_amount' => (float) $rows->sum('change_amount'),
            'transactions' => $rows,
        ]);
    }

    public function processBackfillChangeTransactions(Request $request)
    {
        abort_unless($request->user() && $request->user()->hasAnyRole(['Admin']), 403);

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'cash_only' => ['nullable', 'boolean'],
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['integer'],
        ]);

        $cashOnly = (bool) ($validated['cash_only'] ?? false);

        $invoiceIds = collect($validated['invoice_ids'])
            ->map(function ($value) {
                return (int) $value;
            })
            ->filter()
            ->unique()
            ->values();

        if ($invoiceIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada invoice valid untuk diproses.',
            ], 422);
        }

        $invoices = $this->buildBackfillChangeQuery($validated['start_date'], $validated['end_date'], $cashOnly)
            ->whereIn('id', $invoiceIds->all())
            ->orderBy('id')
            ->get();

        if ($invoices->isEmpty()) {
            return response()->json([
                'success' => true,
                'created_count' => 0,
                'total_change_amount' => 0,
                'message' => 'Tidak ada transaksi kembalian yang perlu dibackfill.',
            ]);
        }

        $createdCount = 0;
        $createdTotal = 0.0;

        DB::transaction(function () use ($invoices, &$createdCount, &$createdTotal) {
            $recorder = app(TransactionRecorderService::class);

            foreach ($invoices as $invoice) {
                $changeAmount = (float) ($invoice->change_amount ?? 0);
                if ($changeAmount <= 0) {
                    continue;
                }

                $created = $recorder->recordInvoicePayment(
                    $invoice,
                    $changeAmount,
                    $invoice->payment_method,
                    'Kembalian billing invoice ' . ($invoice->invoice_number ?? $invoice->id),
                    $invoice->payment_date ?: $invoice->created_at,
                    'out'
                );

                if ($created) {
                    $createdCount++;
                    $createdTotal += $changeAmount;
                }
            }
        });

        return response()->json([
            'success' => true,
            'created_count' => $createdCount,
            'total_change_amount' => (float) $createdTotal,
            'message' => 'Backfill kembalian berhasil diproses.',
        ]);
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = FinanceTransaction::query()
            ->with(['invoice', 'visitation.pasien'])
            ->select('finance_transactions.*');

        $start = $request->input('start_date');
        $end = $request->input('end_date');
        if ($start && $end) {
            $query->whereBetween('tanggal', [$start . ' 00:00:00', $end . ' 23:59:59']);
        }

        $jenis = trim((string) $request->input('jenis_transaksi', ''));
        if ($jenis !== '') {
            $query->where('jenis_transaksi', $jenis);
        }

        $metode = trim((string) $request->input('metode_bayar', ''));
        if ($metode !== '') {
            $query->where('metode_bayar', $metode);
        }

        return $query;
    }

    private function buildBackfillChangeQuery(string $startDate, string $endDate, bool $cashOnly = false)
    {
        $query = Invoice::query()
            ->with(['visitation.pasien'])
            ->where('change_amount', '>', 0)
            ->whereBetween(DB::raw('COALESCE(payment_date, created_at)'), [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('finance_transactions')
                    ->whereColumn('finance_transactions.invoice_id', 'finance_invoices.id')
                    ->where('finance_transactions.jenis_transaksi', 'out')
                    ->whereRaw('ABS(finance_transactions.jumlah - finance_invoices.change_amount) < 0.01');
            });

        if ($cashOnly) {
            $query->where('payment_method', 'cash');
        }

        return $query;
    }
}