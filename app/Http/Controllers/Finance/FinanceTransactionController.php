<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceTransaction;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class FinanceTransactionController extends Controller
{
    public function index()
    {
        return view('finance.transactions.index');
    }

    public function data(Request $request)
    {
        $query = FinanceTransaction::query()->with(['invoice', 'visitation.pasien'])->select('finance_transactions.*');

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
}