<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Schema;
use App\Models\Finance\Piutang;

class PiutangController extends Controller
{
    public function index()
    {
        return view('finance.piutang.index');
    }

    public function data(Request $request)
    {
        $query = Piutang::with(['visitation.pasien', 'invoice'])->select('finance_piutangs.*');

        // Apply date range filter on visitation date (tanggal_visitation or tanggal_visit)
        $start = $request->input('start_date');
        $end = $request->input('end_date');
        if ($start && $end) {
            $startAt = $start . ' 00:00:00';
            $endAt = $end . ' 23:59:59';
            $query->whereHas('visitation', function ($q) use ($startAt, $endAt) {
                $q->where(function ($q2) use ($startAt, $endAt) {
                    $hasVisitation = Schema::hasColumn('erm_visitations', 'tanggal_visitation');
                    $hasVisit = Schema::hasColumn('erm_visitations', 'tanggal_visit');
                    if ($hasVisitation && $hasVisit) {
                        $q2->whereBetween('tanggal_visitation', [$startAt, $endAt])
                           ->orWhereBetween('tanggal_visit', [$startAt, $endAt]);
                    } elseif ($hasVisitation) {
                        $q2->whereBetween('tanggal_visitation', [$startAt, $endAt]);
                    } elseif ($hasVisit) {
                        $q2->whereBetween('tanggal_visit', [$startAt, $endAt]);
                    } else {
                        // no known date columns; force no results
                        $q2->whereRaw('0 = 1');
                    }
                });
            });
        }

        // Filter by piutang payment status (paid/partial/unpaid)
        $statusFilter = $request->input('status_filter');
        if ($statusFilter) {
            $query->where('payment_status', $statusFilter);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $search = $request->get('search');
                $value = is_array($search) && isset($search['value']) ? trim($search['value']) : null;

                if ($value === null || $value === '') {
                    return;
                }

                $query->where(function ($q) use ($value) {
                    // Search basic piutang fields
                    $q->where('finance_piutangs.id', 'like', "%{$value}%")
                      ->orWhere('finance_piutangs.amount', 'like', "%{$value}%")
                      ->orWhere('finance_piutangs.payment_status', 'like', "%{$value}%");

                    // Search related invoice number
                    $q->orWhereHas('invoice', function ($iq) use ($value) {
                        $iq->where('invoice_number', 'like', "%{$value}%");
                    });

                    // Search related patient name / RM
                    $q->orWhereHas('visitation.pasien', function ($pq) use ($value) {
                        $pq->where('nama', 'like', "%{$value}%")
                           ->orWhere('id', 'like', "%{$value}%");
                    });
                });
            })
            ->addColumn('nama_pasien', function ($row) {
                if ($row->visitation && $row->visitation->pasien) {
                    $name = $row->visitation->pasien->nama;
                    $rm = $row->visitation->pasien->id ?? '';
                    $html = '<div class="font-weight-bold">' . e($name) . '</div>';
                    if ($rm) $html .= '<small class="text-muted d-block">' . e($rm) . '</small>';
                    return $html;
                }
                return '-';
            })
            ->addColumn('invoice_number', function ($row) {
                $inv = $row->invoice ? ($row->invoice->invoice_number) : '-';
                $dateStr = '';
                $vis = $row->visitation ?? null;
                if ($vis) {
                    $raw = $vis->tanggal_visitation ?? ($vis->tanggal_visit ?? null);
                    if ($raw) {
                        try {
                            $dateStr = \Carbon\Carbon::parse($raw)->format('j F Y');
                        } catch (\Exception $e) {
                            $dateStr = $raw;
                        }
                    }
                }
                $html = '<div class="font-weight-bold">' . e($inv) . '</div>';
                if ($dateStr) $html .= '<div class="small text-muted mt-1">' . e($dateStr) . '</div>';
                return $html;
            })
            ->addColumn('amount_display', function ($row) {
                return '<div class="text-right"><strong>Rp ' . number_format($row->amount, 0, ',', '.') . '</strong></div>';
            })
            ->addColumn('payment_status', function ($row) {
                $status = strtolower($row->payment_status ?? 'unpaid');
                $textMap = [
                    'paid' => 'Sudah Bayar',
                    'partial' => 'Belum Lunas',
                    'unpaid' => 'Belum Dibayar'
                ];
                $label = $textMap[$status] ?? ucfirst($status);
                $cls = 'badge-secondary';
                if ($status === 'paid') $cls = 'badge-success';
                elseif ($status === 'partial') $cls = 'badge-warning';
                elseif ($status === 'unpaid') $cls = 'badge-danger';
                $dateStr = $row->payment_date ? $row->payment_date->format('j F Y H:i') : '';
                $badge = '<span class="badge ' . $cls . '">' . e($label) . '</span>';
                $dateHtml = $dateStr ? '<div class="small text-muted mt-1">Dibayar pada : ' . e($dateStr) . '</div>' : '';
                return '<div>' . $badge . $dateHtml . '</div>';
            })
            ->addColumn('payment_date_display', function ($row) {
                return $row->payment_date ? $row->payment_date->format('j F Y H:i') : '-';
            })
            ->addColumn('action', function ($row) {
                $acceptBtn = '<button class="btn btn-sm btn-success btn-terima-pembayaran" data-id="' . $row->id . '" data-amount="' . $row->amount . '" data-invoice="' . ($row->invoice ? $row->invoice->invoice_number : '') . '">Terima Pembayaran</button>';
                $printBtns = '';
                if ($row->invoice) {
                    $printBtns .= ' <a href="' . route('finance.invoice.print-nota', $row->invoice->id) . '" class="btn btn-sm btn-primary ml-1" target="_blank">Lihat Nota</a>';
                }
                // If already paid, only show print buttons
                if (isset($row->payment_status) && strtolower($row->payment_status) === 'paid') {
                    return $printBtns;
                }
                return $acceptBtn . $printBtns;
            })
            ->rawColumns(['nama_pasien', 'invoice_number', 'amount_display', 'payment_status', 'action'])
            ->make(true);
    }

    public function receivePayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string'
        ]);

        $piutang = Piutang::findOrFail($id);
        $invoice = $piutang->invoice;

        \DB::beginTransaction();
        try {
            $amount = floatval($request->input('amount'));
            $paymentDate = $request->input('payment_date');
            $paymentMethod = $request->input('payment_method');

            // Update invoice amount_paid if invoice exists
            if ($invoice) {
                $currentPaid = floatval($invoice->amount_paid ?? 0);
                $newPaid = $currentPaid + $amount;
                $invoice->amount_paid = $newPaid;
                // recompute change/shortage
                $total = floatval($invoice->total_amount ?? 0);
                if ($newPaid >= $total) {
                    $invoice->change_amount = $newPaid - $total;
                    $invoice->shortage_amount = 0;
                } else {
                    $invoice->change_amount = 0;
                    $invoice->shortage_amount = $total - $newPaid;
                }
                $invoice->save();
            }

            // Update piutang record
            $piutang->payment_date = $paymentDate;
            $piutang->payment_method = $paymentMethod;
            $piutang->user_id = auth()->id();

            // Determine payment_status based on invoice (if available)
            if ($invoice) {
                $paid = floatval($invoice->amount_paid ?? 0);
                $total = floatval($invoice->total_amount ?? 0);
                if ($paid <= 0) $piutang->payment_status = 'unpaid';
                elseif ($paid > 0 && $paid < $total) $piutang->payment_status = 'partial';
                else $piutang->payment_status = 'paid';
            } else {
                // If no invoice, mark as paid if amount >= piutang amount
                if ($amount >= floatval($piutang->amount)) $piutang->payment_status = 'paid';
                elseif ($amount > 0) $piutang->payment_status = 'partial';
                else $piutang->payment_status = 'unpaid';
            }

            $piutang->save();

            \DB::commit();

            return response()->json(['success' => true, 'message' => 'Pembayaran tercatat']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
