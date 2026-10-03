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
use App\Services\Finance\TransactionRecorderService;
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

    /**
     * AJAX: DataTable for the Retur Pembelian modal on the Billing page.
     * Non-AJAX: the old standalone page now lives in Billing, so redirect there and open the modal.
     */
    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('finance.billing.index', ['open' => 'retur']);
        }

        $returns = ReturPembelian::with(['invoice.visitation.pasien', 'user', 'items'])
            ->select('finance_retur_pembelian.*')
            ->when(in_array($request->input('status'), [ReturPembelian::STATUS_PENDING, ReturPembelian::STATUS_APPROVED, ReturPembelian::STATUS_REJECTED], true), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->orderBy('created_at', 'desc');

        return DataTables::of($returns)
            ->addColumn('invoice_number', function ($row) {
                return optional($row->invoice)->invoice_number ?? '-';
            })
            ->addColumn('patient', function ($row) {
                return optional(optional(optional($row->invoice)->visitation)->pasien)->nama ?? '-';
            })
            ->addColumn('user_name', function ($row) {
                return optional($row->user)->name ?? '-';
            })
            ->addColumn('items_count', function ($row) {
                return $row->items->count() . ' item';
            })
            ->addColumn('tanggal', function ($row) {
                return optional($row->created_at)->format('d/m/Y H:i') ?? '-';
            })
            ->editColumn('total_amount', function ($row) {
                return 'Rp ' . number_format($row->total_amount, 0, ',', '.');
            })
            // The modal only reads the computed columns above; don't ship the full related models.
            ->removeColumn('invoice', 'items', 'user', 'reason', 'notes', 'rejected_reason')
            ->make(true);
    }

    /**
     * Pending counts for the Billing header button badge.
     */
    public function pendingCount()
    {
        return response()->json([
            'pending' => ReturPembelian::where('status', ReturPembelian::STATUS_PENDING)->count(),
        ]);
    }

    public function getInvoices(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $q = trim((string) $request->input('q', ''));

        $invoices = Invoice::with(['visitation:id,pasien_id', 'visitation.pasien:id,nama'])
            ->select('id', 'invoice_number', 'visitation_id', 'total_amount', 'created_at')
            ->when($startDate, function ($query) use ($startDate) {
                return $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->whereDate('created_at', '<=', $endDate);
            })
            // Quick search (retur modal): invoice number, patient name or RM.
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('invoice_number', 'like', '%' . $q . '%')
                        ->orWhereHas('visitation.pasien', function ($p) use ($q) {
                            $p->where('nama', 'like', '%' . $q . '%')->orWhere('id', 'like', '%' . $q . '%');
                        });
                });
            })
            ->whereNotNull('amount_paid')
            ->orderBy('created_at', 'desc')
            ->limit($q !== '' || $startDate || $endDate ? 50 : 20)
            ->get();

        return response()->json($invoices);
    }

    public function getInvoiceItems($invoiceId)
    {
        $invoice = Invoice::with(['items', 'visitation:id,pasien_id', 'visitation.pasien:id,nama'])->findOrFail($invoiceId);

        // Quantity already held by pending or approved returs, for all items in one query.
        $heldByItem = ReturPembelianItem::query()
            ->whereIn('invoice_item_id', $invoice->items->pluck('id'))
            ->whereHas('returPembelian', fn ($q) => $q->notRejected())
            ->groupBy('invoice_item_id')
            ->selectRaw('invoice_item_id, SUM(quantity_returned) AS held')
            ->pluck('held', 'invoice_item_id');

        $items = $invoice->items->map(function ($item) use ($invoice, $heldByItem) {
            // Reuse the loaded invoice + its lines in paidUnitPrice (no extra queries per item).
            $item->setRelation('invoice', $invoice);
            $totalReturned = (float) ($heldByItem[$item->id] ?? 0);

            $remainingQty = $item->quantity - $totalReturned;

            return [
                'id' => $item->id,
                'name' => $item->name,
                'original_quantity' => $item->quantity,
                'returned_quantity' => $totalReturned,
                'remaining_quantity' => $remainingQty,
                'unit_price' => self::paidUnitPrice($item),
                'billable_type' => $item->billable_type,
                'billable_id' => $item->billable_id,
                'can_return' => $remainingQty > 0
            ];
        });

        return response()->json([
            // Only what the retur modal shows (avoids serializing items <-> invoice back-references).
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'created_at' => $invoice->created_at,
                'total_amount' => $invoice->total_amount,
                'visitation' => $invoice->visitation ? [
                    'pasien' => $invoice->visitation->pasien
                        ? ['id' => $invoice->visitation->pasien->id, 'nama' => $invoice->visitation->pasien->nama]
                        : null,
                ] : null,
            ],
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

            // Create the retur as PENDING: stock and cash are only touched on approve().
            $retur = ReturPembelian::create([
                'invoice_id' => $request->invoice_id,
                'retur_number' => ReturPembelian::generateReturNumber(),
                'reason' => $request->reason,
                'notes' => $request->notes,
                'user_id' => Auth::id(),
                'processed_date' => null,
                'status' => ReturPembelian::STATUS_PENDING,
                'total_amount' => 0 // Will be calculated below
            ]);

            $totalAmount = 0;

            foreach ($request->items as $itemData) {
                $invoiceItem = InvoiceItem::findOrFail($itemData['invoice_item_id']);
                $quantityReturned = (float) $itemData['quantity_returned'];

                if ((string) $invoiceItem->invoice_id !== (string) $request->invoice_id) {
                    throw new \Exception("Item {$invoiceItem->name} bukan bagian dari invoice ini.");
                }

                // Pending + approved returs both hold quantity, so the same item can't be requested twice.
                $maxReturnable = $invoiceItem->quantity - $this->heldQuantity($invoiceItem->id);

                if ($quantityReturned > $maxReturnable) {
                    throw new \Exception("Jumlah retur melebihi sisa yang bisa diretur untuk item: {$invoiceItem->name}");
                }

                // Calculate price with percentage cut
                $originalPrice = self::paidUnitPrice($invoiceItem); // final price paid (item + invoice discount/tax)
                $percentageCut = $request->percentage_cut;
                $reducedPrice = $originalPrice * (1 - ($percentageCut / 100));
                
                $itemTotal = $quantityReturned * $reducedPrice;
                $totalAmount += $itemTotal;

                // Create retur item record
                ReturPembelianItem::create([
                    'retur_pembelian_id' => $retur->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'name' => $invoiceItem->name,
                    'quantity_returned' => $quantityReturned,
                    'original_unit_price' => $originalPrice,
                    'percentage_cut' => $percentageCut,
                    'unit_price' => $reducedPrice,
                    'total_amount' => $itemTotal,
                    'billable_type' => $invoiceItem->billable_type,
                    'billable_id' => $invoiceItem->billable_id
                ]);
            }

            // Update total amount
            $retur->update(['total_amount' => $totalAmount]);

            // Admin / Finance can save and approve in one step.
            $approvedNow = $request->boolean('approve_now') && $this->canApprove();
            if ($approvedNow) {
                $this->applyApproval($retur);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'approved' => $approvedNow,
                'message' => $approvedNow
                    ? 'Retur disimpan dan di-approve. Stok dan kas sudah disesuaikan.'
                    : 'Retur pembelian diajukan dan menunggu approval.',
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

    /**
     * Approve a pending retur: return stock, adjust resep farmasi, record the refund (cash out).
     */
    public function approve($id)
    {
        if (!$this->canApprove()) {
            return response()->json(['success' => false, 'message' => 'Hanya Admin / Finance yang dapat meng-approve retur.'], 403);
        }

        try {
            DB::beginTransaction();

            $retur = ReturPembelian::with('items')->lockForUpdate()->findOrFail($id);

            if (!$retur->isPending()) {
                throw new \Exception('Retur ini sudah diproses (' . $retur->status . ').');
            }

            $this->applyApproval($retur);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Retur ' . $retur->retur_number . ' di-approve. Stok dan kas sudah disesuaikan.']);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Approval side effects (call inside a DB transaction): mark approved, return stock,
     * adjust resep farmasi and record the refund as a cash-out transaction.
     */
    private function applyApproval(ReturPembelian $retur): void
    {
        $retur->loadMissing('items');

        $retur->forceFill([
            'status' => ReturPembelian::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'processed_date' => now(),
        ])->save();

        foreach ($retur->items as $returItem) {
            $invoiceItem = InvoiceItem::find($returItem->invoice_item_id);
            if (!$invoiceItem) {
                continue;
            }

            $quantityReturned = (float) $returItem->quantity_returned;

            // Re-check against what other approved returs already took back.
            $approvedElsewhere = ReturPembelianItem::where('invoice_item_id', $invoiceItem->id)
                ->where('retur_pembelian_id', '!=', $retur->id)
                ->whereHas('returPembelian', fn ($q) => $q->approved())
                ->sum('quantity_returned');

            if ($quantityReturned > ($invoiceItem->quantity - $approvedElsewhere)) {
                throw new \Exception("Jumlah retur melebihi sisa yang bisa diretur untuk item: {$invoiceItem->name}");
            }

            // Handle stock return if it's an Obat (medicine) or ResepFarmasi
            if ($invoiceItem->billable_type === 'App\Models\ERM\Obat' && $invoiceItem->billable_id) {
                $this->handleStockReturn($invoiceItem->billable_id, $quantityReturned, $retur);
            } elseif ($invoiceItem->billable_type === 'App\Models\ERM\ResepFarmasi' && $invoiceItem->billable_id) {
                // For ResepFarmasi, get the obat_id from the ResepFarmasi record (older returs may have soft-deleted it)
                $resepFarmasi = ResepFarmasi::withTrashed()->find($invoiceItem->billable_id);
                if ($resepFarmasi && $resepFarmasi->obat_id) {
                    $this->handleStockReturn($resepFarmasi->obat_id, $quantityReturned, $retur);
                }

                // Reduce / delete the resep only. The invoice item stays as sold (shown with a
                // "diretur" marker in Rincian Billing) and the retur is listed in Item Diretur.
                if ($resepFarmasi && !$resepFarmasi->trashed()) {
                    $this->reduceResepWithoutTouchingInvoice($resepFarmasi, $quantityReturned);
                }
            }
        }

        $invoice = Invoice::find($retur->invoice_id);
        $totalAmount = (float) $retur->total_amount;

        // Keep the invoice's retur total in sync. Plain query: no model events and no updated_at
        // bump (reports use invoices.updated_at as the invoice date), items/totals untouched.
        if ($invoice && $totalAmount > 0) {
            DB::table('finance_invoices')->where('id', $invoice->id)->increment('retur_amount', $totalAmount);
        }

        if ($invoice && $totalAmount > 0) {
            $returnedItemDescriptions = $retur->items
                ->map(function ($item) {
                    $qty = rtrim(rtrim(number_format((float) ($item->quantity_returned ?? 0), 2, '.', ''), '0'), '.');
                    return trim(($item->name ?? 'Item') . ' x' . $qty);
                })
                ->filter()
                ->implode(', ');

            $description = 'No retur: ' . $retur->retur_number;
            if ($returnedItemDescriptions !== '') {
                $description .= ' | Item diretur: ' . $returnedItemDescriptions;
            }

            app(TransactionRecorderService::class)->recordInvoicePayment(
                $invoice,
                $totalAmount,
                $invoice->payment_method,
                $description,
                $retur->processed_date,
                'out'
            );
        }
    }

    /**
     * Reject a pending retur. No stock / cash effect; its quantity becomes returnable again.
     */
    public function reject(Request $request, $id)
    {
        if (!$this->canApprove()) {
            return response()->json(['success' => false, 'message' => 'Hanya Admin / Finance yang dapat menolak retur.'], 403);
        }

        $request->validate(['rejected_reason' => 'required|string|max:500']);

        $retur = ReturPembelian::findOrFail($id);
        if (!$retur->isPending()) {
            return response()->json(['success' => false, 'message' => 'Retur ini sudah diproses (' . $retur->status . ').'], 422);
        }

        $retur->forceFill([
            'status' => ReturPembelian::STATUS_REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejected_reason' => trim($request->rejected_reason),
        ])->save();

        return response()->json(['success' => true, 'message' => 'Retur ' . $retur->retur_number . ' ditolak.']);
    }

    /**
     * Unit price the patient actually paid for an invoice item: the item's final amount (after
     * the item discount), adjusted by the invoice-level discount and tax in the same proportion.
     */
    public static function paidUnitPrice(InvoiceItem $item): float
    {
        $quantity = (float) $item->quantity;
        $unit = ($quantity > 0 && $item->final_amount !== null)
            ? (float) $item->final_amount / $quantity
            : (float) $item->unit_price;

        // Fee lines (Biaya Administrasi / Biaya Ongkir) are added after the invoice discount and tax,
        // so they are refunded as-is.
        if (self::isFeeItem($item)) {
            return round($unit, 2);
        }

        // Invoice-level discount / tax apply to the non-fee items only (billing totals:
        // total = (subtotal - discount + tax) + fees). Scale by (subtotal - discount + tax) / subtotal,
        // with the subtotal taken from the non-fee item lines themselves.
        $invoice = $item->relationLoaded('invoice') ? $item->invoice : Invoice::find($item->invoice_id);
        if ($invoice) {
            $lines = $invoice->relationLoaded('items') ? $invoice->items : InvoiceItem::where('invoice_id', $invoice->id)->get();
            $itemsSubtotal = (float) $lines
                ->reject(fn ($line) => self::isFeeItem($line))
                ->sum('final_amount');
            $discount = (float) ($invoice->discount ?? 0);
            $tax = (float) ($invoice->tax ?? 0);
            if ($itemsSubtotal > 0 && ($discount != 0.0 || $tax != 0.0)) {
                $factor = ($itemsSubtotal - $discount + $tax) / $itemsSubtotal;
                if ($factor > 0) {
                    $unit *= $factor;
                }
            }
        }

        return round($unit, 2);
    }

    /**
     * Fee lines appended by billing after discount/tax (same rule as BillingController).
     */
    private static function isFeeItem(InvoiceItem $item): bool
    {
        $name = strtolower(trim((string) $item->name));

        return str_contains($name, 'biaya administrasi') || str_contains($name, 'biaya ongkir');
    }

    /**
     * Reduce the resep quantity, or soft-delete it when nothing is left, WITHOUT firing the
     * ResepFarmasi model events: those events rewrite / delete the matching invoice items and
     * recalculate the invoice total, but a retur must leave the sold invoice intact.
     */
    private function reduceResepWithoutTouchingInvoice(ResepFarmasi $resep, float $quantityReturned): void
    {
        $quantityField = $resep->racikan_ke ? 'bungkus' : 'jumlah';
        $remaining = max(0, (float) ($resep->{$quantityField} ?? 0) - $quantityReturned);

        if ($remaining <= 0) {
            $resep->deleteQuietly();
            return;
        }

        $resep->{$quantityField} = $remaining;
        $resep->total = max(0, ((float) ($resep->harga ?? 0) * $remaining) - (float) ($resep->diskon ?? 0));
        $resep->saveQuietly();
    }

    private function canApprove(): bool
    {
        $user = Auth::user();

        return $user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Admin', 'Finance']);
    }

    /**
     * Quantity of an invoice item held by pending or approved returs.
     */
    private function heldQuantity($invoiceItemId): float
    {
        return (float) ReturPembelianItem::where('invoice_item_id', $invoiceItemId)
            ->whereHas('returPembelian', fn ($q) => $q->notRejected())
            ->sum('quantity_returned');
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
        $retur = ReturPembelian::with(['invoice.visitation.pasien', 'items.invoiceItem', 'user', 'approver'])
            ->findOrFail($id);

        return response()->json($retur->toArray() + [
            'patient_name' => optional(optional(optional($retur->invoice)->visitation)->pasien)->nama,
            'can_approve' => $retur->isPending() && $this->canApprove(),
        ]);
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