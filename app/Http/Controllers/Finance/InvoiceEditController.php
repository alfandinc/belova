<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceEditLog;
use App\Models\Finance\Piutang;
use App\Models\Finance\ReturPembelian;
use App\Services\Finance\PasienMembershipStatusService;
use App\Services\Finance\TransactionRecorderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Admin-only edit of a processed invoice: item unit price / discount and the invoice discount / tax.
 * Quantities and stock are never touched. amount_paid stays as received; kekurangan, kembalian, status
 * and the piutang follow the new total. Every edit is logged with a reason.
 */
class InvoiceEditController extends Controller
{
    private const FEE_ITEM_NAMES = ['Biaya Administrasi', 'Biaya Ongkir'];
    private const REFUND_DESCRIPTION = 'Pengembalian kelebihan bayar invoice';

    public function show($id)
    {
        $invoice = Invoice::with(['items', 'visitation.pasien'])->findOrFail($id);

        $logs = InvoiceEditLog::with('user:id,name')
            ->where('invoice_id', $invoice->id)
            ->latest()
            ->get()
            ->map(fn ($log) => [
                'tanggal' => $log->created_at->locale('id')->translatedFormat('j F Y H:i'),
                'user' => optional($log->user)->name ?? '-',
                'reason' => $log->reason,
                'old_total' => $log->old_values['total_amount'] ?? null,
                'new_total' => $log->new_values['total_amount'] ?? null,
                'items' => $this->changedItemsSummary($log->old_values['items'] ?? [], $log->new_values['items'] ?? []),
                // set when the log is a transaction date change (Laporan Omset)
                'old_transaction_date' => $log->old_values['transaction_date'] ?? null,
                'new_transaction_date' => $log->new_values['transaction_date'] ?? null,
                // set when the log is a retur date change (Laporan Omset)
                'retur_number' => $log->new_values['retur_number'] ?? null,
                'old_retur_date' => $log->old_values['retur_date'] ?? null,
                'new_retur_date' => $log->new_values['retur_date'] ?? null,
                // set when the log is a confirmed refund of an overpayment
                'refund_amount' => $log->new_values['refund_amount'] ?? null,
            ]);

        return response()->json([
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'pasien' => optional(optional($invoice->visitation)->pasien)->nama,
            'editable_error' => $this->notEditableReason($invoice),
            'refund_due' => $this->refundDue($invoice),
            'payment_method' => $invoice->payment_method,
            'amount_paid' => (float) $invoice->amount_paid,
            'total_amount' => (float) $invoice->total_amount,
            'discount_type' => $invoice->discount_type ?: '%',
            'discount_value' => (float) $invoice->discount_value,
            'tax_percentage' => (float) $invoice->tax_percentage,
            'items' => $invoice->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                // stored as a nominal line discount (older rows may still carry '%')
                'discount' => (float) $item->discount,
                'discount_type' => $item->discount_type === '%' ? '%' : 'nominal',
                'is_fee' => $this->isFeeItem($item),
            ])->values(),
            'logs' => $logs,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'reason' => 'required|string|min:5|max:1000',
            'discount_type' => 'required|in:%,nominal',
            'discount_value' => 'required|numeric|min:0',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'required|numeric|min:0',
            'items.*.discount_type' => 'required|in:%,nominal',
        ]);

        return DB::transaction(function () use ($data, $id) {
            $invoice = Invoice::with('items')->lockForUpdate()->findOrFail($id);

            if ($error = $this->notEditableReason($invoice)) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }

            $input = collect($data['items'])->keyBy('id');
            if ($input->keys()->diff($invoice->items->pluck('id'))->isNotEmpty()) {
                return response()->json(['success' => false, 'message' => 'Item tidak sesuai dengan invoice.'], 422);
            }

            $old = $this->snapshot($invoice);

            foreach ($invoice->items as $item) {
                $row = $input->get($item->id);
                if (!$row) {
                    continue;
                }
                $unitPrice = round((float) $row['unit_price'], 2);
                $gross = $unitPrice * (float) $item->quantity;
                $discount = (float) $row['discount'];
                if ($row['discount_type'] === '%') {
                    $discount = $gross * min(100, $discount) / 100;
                }
                // same rule as invoice creation: item discount is stored as a NOMINAL line discount
                // (kept as entered; the line total is floored at 0)
                $discount = round($discount, 2);

                $item->unit_price = $unitPrice;
                $item->discount = $discount;
                $item->discount_type = $discount > 0 ? 'nominal' : null;
                $item->final_amount = max(0, $gross - $discount);
            }

            // Same formula as the billing page: subtotal excludes admin / ongkir, tax applies after discount
            $subtotal = 0.0;
            $fees = 0.0;
            foreach ($invoice->items as $item) {
                if ($this->isFeeItem($item)) {
                    $fees += (float) $item->final_amount;
                } else {
                    $subtotal += (float) $item->final_amount;
                }
            }
            $discountValue = (float) $data['discount_value'];
            $discountAmount = $data['discount_type'] === '%'
                ? $subtotal * min(100, $discountValue) / 100
                : min($subtotal, $discountValue);
            $taxPercentage = (float) $data['tax_percentage'];
            $taxAmount = ($subtotal - $discountAmount) * $taxPercentage / 100;
            $oldTotal = (float) $invoice->total_amount;
            $newTotal = (float) ceil($subtotal - $discountAmount + $taxAmount + $fees);

            $invoice->subtotal = $subtotal;
            $invoice->discount = $discountAmount;
            $invoice->discount_type = $data['discount_type'];
            $invoice->discount_value = $discountValue;
            $invoice->tax = $taxAmount;
            $invoice->tax_percentage = $taxPercentage;
            $invoice->total_amount = $newTotal;

            // amount_paid is money already received: keep it, recompute what follows from the new total
            $paid = (float) $invoice->amount_paid;
            $invoice->change_amount = max(0, $paid - $newTotal);
            $invoice->shortage_amount = max(0, $newTotal - $paid);
            $paidInt = (int) ceil($paid);
            $totalInt = (int) ceil($newTotal);
            if ($totalInt <= 0 || $paidInt >= $totalInt) {
                $invoice->status = 'paid';
            } elseif ($paidInt > 0) {
                $invoice->status = 'partial';
            } else {
                $invoice->status = 'issued';
            }

            $new = $this->snapshot($invoice);
            if ($old === $new) {
                return response()->json(['success' => false, 'message' => 'Tidak ada perubahan.'], 422);
            }

            foreach ($invoice->items as $item) {
                if ($item->isDirty()) {
                    $item->save();
                }
            }
            $invoice->save();

            $this->syncPiutang($invoice, $newTotal - $oldTotal);
            app(PasienMembershipStatusService::class)->syncFromInvoice($invoice);

            InvoiceEditLog::create([
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
                'reason' => $data['reason'],
                'old_values' => $old,
                'new_values' => $new,
            ]);

            $message = 'Invoice berhasil diperbarui.';
            if ($invoice->change_amount > 0 && $newTotal < $oldTotal) {
                $message .= ' Total baru lebih kecil dari yang sudah dibayar: kelebihan Rp '
                    . number_format($this->refundDue($invoice), 0, ',', '.') . ' perlu dikembalikan. Setelah uangnya diserahkan, klik Konfirmasi Pengembalian di form ini.';
            } elseif ($invoice->shortage_amount > 0) {
                $message .= ' Kekurangan Rp ' . number_format($invoice->shortage_amount, 0, ',', '.') . ' dicatat sebagai piutang.';
            }

            return response()->json(['success' => true, 'message' => $message]);
        });
    }

    /**
     * The admin confirms the overpayment left by an edit was handed back: only now it counts as cash out.
     */
    public function confirmRefund(Request $request, $id)
    {
        $data = $request->validate(['note' => 'nullable|string|max:1000']);

        return DB::transaction(function () use ($data, $id) {
            $invoice = Invoice::lockForUpdate()->findOrFail($id);

            $amount = $this->refundDue($invoice);
            if ($amount <= 0) {
                return response()->json(['success' => false, 'message' => 'Tidak ada kelebihan bayar yang perlu dikembalikan.'], 422);
            }

            app(TransactionRecorderService::class)->recordInvoicePayment(
                $invoice,
                $amount,
                app(TransactionRecorderService::class)->refundMethodFor($invoice),
                self::REFUND_DESCRIPTION . ' ' . $invoice->invoice_number,
                now(),
                'out'
            );

            InvoiceEditLog::create([
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
                'reason' => $data['note'] ?? 'Konfirmasi pengembalian kelebihan bayar',
                'old_values' => [],
                'new_values' => ['refund_amount' => $amount],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengembalian Rp ' . number_format($amount, 0, ',', '.') . ' tercatat sebagai kas keluar.',
            ]);
        });
    }

    /**
     * Overpayment created by admin edits and not handed back yet. Taken from the edit logs, so change given
     * at the kasir (already recorded as cash out when it was paid) is left out.
     */
    private function refundDue(Invoice $invoice): float
    {
        $changes = InvoiceEditLog::where('invoice_id', $invoice->id)
            ->orderBy('id')
            ->get(['old_values', 'new_values'])
            ->filter(fn ($log) => isset($log->old_values['change_amount'], $log->new_values['change_amount']));

        if ($changes->isEmpty()) {
            return 0.0;
        }

        $fromEdits = (float) $changes->last()->new_values['change_amount'] - (float) $changes->first()->old_values['change_amount'];

        return max(0.0, round($fromEdits - $this->refundedAmount($invoice), 2));
    }

    private function refundedAmount(Invoice $invoice): float
    {
        return (float) FinanceTransaction::where('invoice_id', $invoice->id)
            ->where('jenis_transaksi', 'out')
            ->where('deskripsi', 'like', self::REFUND_DESCRIPTION . '%')
            ->sum('jumlah');
    }

    /**
     * Null when the invoice can be edited, otherwise why not.
     */
    private function notEditableReason(Invoice $invoice): ?string
    {
        if (empty($invoice->payment_method)) {
            return 'Invoice belum diproses pembayaran: ubah harga / diskon lewat halaman Billing.';
        }
        // amount_paid still holds the refunded money, so a new total would be measured against money no longer here
        if ($this->refundedAmount($invoice) > 0) {
            return 'Kelebihan bayar invoice ini sudah dikembalikan, tidak dapat diedit lagi.';
        }
        if (ReturPembelian::where('invoice_id', $invoice->id)->exists()) {
            return 'Invoice ini sudah memiliki retur, tidak dapat diedit.';
        }
        if ($invoice->items->isEmpty()) {
            return 'Invoice tidak memiliki item.';
        }

        return null;
    }

    /**
     * Keep the piutang in line with the new total: it moves by the same amount as the invoice total
     * (payments already made on it stay), and a new kekurangan without a piutang gets one.
     */
    private function syncPiutang(Invoice $invoice, float $totalDelta): void
    {
        $piutang = Piutang::where('invoice_id', $invoice->id)->orderByDesc('id')->first();

        if (!$piutang) {
            if ($invoice->shortage_amount <= 0) {
                return;
            }
            $piutang = new Piutang([
                'visitation_id' => $invoice->visitation_id,
                'invoice_id' => $invoice->id,
                'amount' => 0,
                'paid_amount' => 0,
                'user_id' => Auth::id(),
            ]);
        }

        $piutang->amount = max(0, (float) $piutang->amount + $totalDelta);
        if ($piutang->amount <= 0 && $invoice->shortage_amount > 0) {
            $piutang->amount = (float) $invoice->shortage_amount;
        }

        $paidPiutang = (float) ($piutang->paid_amount ?? 0);
        if ($paidPiutang >= (float) $piutang->amount) {
            $piutang->payment_status = 'paid';
            $piutang->payment_date = $piutang->payment_date ?: now();
        } elseif ($paidPiutang > 0) {
            $piutang->payment_status = 'partial';
        } else {
            $piutang->payment_status = 'unpaid';
            $piutang->payment_date = null;
        }

        $piutang->save();
    }

    private function snapshot(Invoice $invoice): array
    {
        return [
            'subtotal' => (float) $invoice->subtotal,
            'discount_type' => $invoice->discount_type,
            'discount_value' => (float) $invoice->discount_value,
            'discount' => round((float) $invoice->discount, 2),
            'tax_percentage' => (float) $invoice->tax_percentage,
            'tax' => round((float) $invoice->tax, 2),
            'total_amount' => (float) $invoice->total_amount,
            'change_amount' => (float) $invoice->change_amount,
            'shortage_amount' => (float) $invoice->shortage_amount,
            'status' => $invoice->status,
            'items' => $invoice->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount,
                'final_amount' => (float) $item->final_amount,
            ])->values()->all(),
        ];
    }

    /**
     * "Name: Rp a → Rp b" lines for the items whose amount changed in one log entry.
     */
    private function changedItemsSummary(array $oldItems, array $newItems): array
    {
        $old = collect($oldItems)->keyBy('id');
        $lines = [];
        foreach ($newItems as $item) {
            $before = $old->get($item['id']);
            if (!$before || ($before['unit_price'] == $item['unit_price'] && $before['discount'] == $item['discount'])) {
                continue;
            }
            $lines[] = [
                'name' => $item['name'],
                'old_final' => $before['final_amount'],
                'new_final' => $item['final_amount'],
            ];
        }

        return $lines;
    }

    private function isFeeItem($item): bool
    {
        return empty($item->billable_type) && in_array($item->name, self::FEE_ITEM_NAMES, true);
    }
}
