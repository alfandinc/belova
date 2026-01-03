<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\ReturPembelian;
use App\Models\Finance\ReturPembelianItem;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceItem;
use App\Models\ERM\Obat;
use App\Models\ERM\ResepFarmasi;
use App\Models\ERM\KartuStok;
use App\Models\ERM\GudangMapping;
use App\Services\ERM\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class ReturPembelianController extends Controller
{
    protected $stokService;

    public function __construct(StokService $stokService)
    {
        $this->stokService = $stokService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $returns = ReturPembelian::with(['invoice.visitation.pasien', 'user', 'items'])
                ->orderBy('created_at', 'desc');

            return DataTables::of($returns)
                ->addColumn('patient', function ($row) {
                    return optional($row->invoice->visitation->pasien)->nama ?? '-';
                })
                ->addColumn('action', function ($row) {
                        $detailBtn = '<button type="button" class="btn btn-info btn-sm" onclick="viewReturDetail(' . $row->id . ')">Detail</button>';
                        $printUrl = route('finance.retur-pembelian.print', $row->id);
                        $printBtn = '<a href="' . $printUrl . '" target="_blank" class="btn btn-secondary btn-sm" style="margin-left:4px;">Print</a>';
                        return $detailBtn . ' ' . $printBtn;
                })
                ->addColumn('items_count', function ($row) {
                    return $row->items->count() . ' item(s)';
                })
                ->editColumn('total_amount', function ($row) {
                    return 'Rp ' . number_format($row->total_amount, 0, ',', '.');
                })
                ->editColumn('processed_date', function ($row) {
                    return $row->processed_date ? $row->processed_date->format('d/m/Y H:i') : '-';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('finance.retur-pembelian.index');
    }

    public function getInvoices(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $invoices = Invoice::with(['visitation.pasien', 'items'])
            ->when($startDate, function ($query) use ($startDate) {
                return $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->whereDate('created_at', '<=', $endDate);
            })
            ->whereNotNull('amount_paid')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($invoices);
    }

    public function getInvoiceItems($invoiceId)
    {
        $invoice = Invoice::with(['items.billable'])->findOrFail($invoiceId);
        
        $items = $invoice->items->map(function ($item) {
            // Check if this item has any returns already
            $totalReturned = ReturPembelianItem::where('invoice_item_id', $item->id)
                ->sum('quantity_returned');
            
            $remainingQty = $item->quantity - $totalReturned;
            
            return [
                'id' => $item->id,
                'name' => $item->name,
                'original_quantity' => $item->quantity,
                'returned_quantity' => $totalReturned,
                'remaining_quantity' => $remainingQty,
                'unit_price' => $item->unit_price,
                'billable_type' => $item->billable_type,
                'billable_id' => $item->billable_id,
                'can_return' => $remainingQty > 0
            ];
        });

        return response()->json([
            'invoice' => $invoice,
            'items' => $items
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:finance_invoices,id',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'percentage_cut' => 'required|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.invoice_item_id' => 'required|exists:finance_invoice_items,id',
            'items.*.quantity_returned' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            // Create the main retur record
            $retur = ReturPembelian::create([
                'invoice_id' => $request->invoice_id,
                'retur_number' => ReturPembelian::generateReturNumber(),
                'reason' => $request->reason,
                'notes' => $request->notes,
                'user_id' => Auth::id(),
                'processed_date' => now(),
                'total_amount' => 0 // Will be calculated below
            ]);

            $totalAmount = 0;

            foreach ($request->items as $itemData) {
                $invoiceItem = InvoiceItem::findOrFail($itemData['invoice_item_id']);
                
                // Validate quantity
                $alreadyReturned = ReturPembelianItem::where('invoice_item_id', $invoiceItem->id)
                    ->sum('quantity_returned');
                
                $maxReturnable = $invoiceItem->quantity - $alreadyReturned;
                
                if ($itemData['quantity_returned'] > $maxReturnable) {
                    throw new \Exception("Quantity to return exceeds available quantity for item: {$invoiceItem->name}");
                }

                // Calculate price with percentage cut
                $originalPrice = $invoiceItem->unit_price;
                $percentageCut = $request->percentage_cut;
                $reducedPrice = $originalPrice * (1 - ($percentageCut / 100));
                
                $itemTotal = $itemData['quantity_returned'] * $reducedPrice;
                $totalAmount += $itemTotal;

                // Create retur item record
                ReturPembelianItem::create([
                    'retur_pembelian_id' => $retur->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'name' => $invoiceItem->name,
                    'quantity_returned' => $itemData['quantity_returned'],
                    'original_unit_price' => $originalPrice,
                    'percentage_cut' => $percentageCut,
                    'unit_price' => $reducedPrice,
                    'total_amount' => $itemTotal,
                    'billable_type' => $invoiceItem->billable_type,
                    'billable_id' => $invoiceItem->billable_id
                ]);

                // Handle stock return if it's an Obat (medicine) or ResepFarmasi
                if ($invoiceItem->billable_type === 'App\Models\ERM\Obat' && $invoiceItem->billable_id) {
                    $this->handleStockReturn($invoiceItem->billable_id, $itemData['quantity_returned'], $retur);
                } elseif ($invoiceItem->billable_type === 'App\Models\ERM\ResepFarmasi' && $invoiceItem->billable_id) {
                    // For ResepFarmasi, get the obat_id from the ResepFarmasi record
                    $resepFarmasi = ResepFarmasi::find($invoiceItem->billable_id);
                    if ($resepFarmasi && $resepFarmasi->obat_id) {
                        $this->handleStockReturn($resepFarmasi->obat_id, $itemData['quantity_returned'], $retur);
                    }
                }
            }

            // Update total amount
            $retur->update(['total_amount' => $totalAmount]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Retur pembelian berhasil disimpan',
                'retur_number' => $retur->retur_number
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 422);
        }
    }

    private function handleStockReturn($obatId, $quantity, $retur)
    {
        // For return, we need to add stock back
        // Resolve target gudang via GudangMapping for 'retur_pembelian'
        // Fallback to gudang_id = 1 when no mapping configured
        $gudangId = GudangMapping::getDefaultGudangId('retur_pembelian') ?? 1;
        
        // Try to find the original batch that was used when this item was sold
        // Look for the original "keluar" transaction in kartu_stok for this invoice
        $originalBatch = DB::table('erm_kartu_stok')
            ->where('obat_id', $obatId)
            ->where('gudang_id', $gudangId)
            ->where('tipe', 'keluar')
            ->where('ref_type', 'App\\Models\\Finance\\Invoice')
            ->where('ref_id', $retur->invoice_id)
            ->orderBy('created_at', 'desc')
            ->value('batch');
        
        // If we can't find the original batch, use the most recent available batch
        if (!$originalBatch) {
            $originalBatch = DB::table('erm_obat_stok_gudang')
                ->where('obat_id', $obatId)
                ->where('gudang_id', $gudangId)
                ->where('stok', '>', 0)
                ->orderBy('created_at', 'desc')
                ->value('batch');
        }
        
        $this->stokService->returPembelianViaTransaksi(
            $obatId,
            $gudangId,
            $quantity,
            $retur->id,
            $retur->retur_number,
            $originalBatch // Pass the original or most recent batch
        );
    }

    public function show($id)
    {
        $retur = ReturPembelian::with(['invoice', 'items.invoiceItem', 'user'])
            ->findOrFail($id);

        return response()->json($retur);
    }

    /**
     * Print (PDF) view for retur pembelian
     */
    public function print($id)
    {
        $retur = ReturPembelian::with(['invoice.visitation.pasien', 'items', 'user'])->findOrFail($id);

        $pdf = Pdf::loadView('finance.retur-pembelian.pdf', compact('retur'))
            // Use thermal receipt-like paper settings similar to printNota
            ->setPaper([0, 0, 120, 1000]) // ~57mm width with dynamic height
            ->setOptions([
                'defaultFont' => 'helvetica',
                'fontHeightRatio' => 0.8,
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'dpi' => 203,
                'defaultMediaType' => 'print',
                'enable_javascript' => false,
                'no_background' => false,
                'margin_top' => 5,
                'margin_right' => 5,
                'margin_bottom' => 5,
                'margin_left' => 5,
            ]);

        return $pdf->stream('Retur-' . ($retur->retur_number ?? $retur->id) . '.pdf');
    }
}