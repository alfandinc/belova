<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceItem;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $invoices = Invoice::with(['visitation.pasien', 'visitation.klinik'])->get();

            return DataTables::of($invoices)
                ->addIndexColumn()
                ->addColumn('invoice_date', function ($invoice) {
                    return Carbon::parse($invoice->created_at)->format('d M Y');
                })
                ->addColumn('patient_name', function ($invoice) {
                    return $invoice->visitation->pasien->nama ?? '-';
                })
                ->addColumn('clinic_name', function ($invoice) {
                    return $invoice->visitation->klinik->nama ?? '-';
                })
                ->addColumn('status_badge', function ($invoice) {
                    $badges = [
                        'draft' => 'badge-secondary',
                        'issued' => 'badge-primary',
                        'paid' => 'badge-success',
                        'canceled' => 'badge-danger'
                    ];
                    $statusClass = $badges[$invoice->status] ?? 'badge-secondary';
                    return '<span class="badge ' . $statusClass . '">' . $invoice->status . '</span>';
                })
                ->addColumn('action', function ($invoice) {
                    $viewBtn = '<a href="' . route('finance.invoice.show', $invoice->id) . '" class="btn btn-sm btn-info mr-1">Lihat Detail</a>';
                    $printBtn = '<a href="' . route('finance.invoice.print', $invoice->id) . '" class="btn btn-sm btn-secondary mr-1" target="_blank">Cetak Invoice</a>';
                    $notaBtn = '<a href="' . route('finance.invoice.print-nota', $invoice->id) . '" class="btn btn-sm btn-primary" target="_blank">Cetak Nota</a>';
                    return $viewBtn . $printBtn . $notaBtn;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('finance.invoice.index');
    }

    /**
     * Display the invoice details
     */
    public function show($id)
    {
        $invoice = Invoice::with([
            'visitation.pasien',
            'visitation.klinik',
            'items'
        ])->findOrFail($id);

        return view('finance.invoice.show', compact('invoice'));
    }

    /**
     * Update invoice status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,issued,paid,canceled'
        ]);

        $invoice = Invoice::findOrFail($id);
        $invoice->status = $request->status;

        if ($request->status === 'paid' && !$invoice->payment_date) {
            $invoice->payment_date = now();
        }

        $invoice->save();

        return redirect()->back()->with('success', 'Status invoice berhasil diperbarui');
    }

    /**
     * Generate PDF invoice
     */
    public function printInvoice($id)
    {
        $invoice = Invoice::with([
            'visitation.pasien',
            'visitation.klinik',
            'items'
        ])->findOrFail($id);

        $pdf = PDF::loadView('finance.invoice.pdf', compact('invoice'))
            ->setPaper('a5', 'landscape')
            ->setOptions([
                'defaultFont' => 'helvetica',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'dpi' => 150,
                'defaultMediaType' => 'screen',
                'enable_javascript' => true,
                'no_background' => false,
                'margin_top' => 2,
                'margin_right' => 2,
                'margin_bottom' => 2,
                'margin_left' => 2
            ]);

        return $pdf->stream('Invoice-' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Generate PDF nota
     */
    public function printNota($id)
    {
        $invoice = Invoice::with([
            'visitation.pasien',
            'visitation.klinik',
            'items'
        ])->findOrFail($id);

        // Convert logo to base64 for reliable PDF rendering
        $logoPath = public_path('img/favicon-premiere.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        $pdf = PDF::loadView('finance.invoice.nota', compact('invoice', 'logoBase64'))
            ->setPaper([0, 0, 161.57, 1000]) // 57mm width (161.57 points) with dynamic height
            ->setOptions([
                'defaultFont' => 'helvetica',
                'fontHeightRatio' => 0.8,
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'dpi' => 203, // Thermal printer DPI
                'defaultMediaType' => 'print',
                'enable_javascript' => false,
                'no_background' => false,
                'margin_top' => 5,
                'margin_right' => 5,
                'margin_bottom' => 5,
                'margin_left' => 5
            ]);

        return $pdf->stream('Nota-' . $invoice->invoice_number . '.pdf');
    }
}
