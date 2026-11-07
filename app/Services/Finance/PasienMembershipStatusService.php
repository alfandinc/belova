<?php

namespace App\Services\Finance;

use App\Models\ERM\Konsultasi;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceItem;
use Illuminate\Support\Str;

class PasienMembershipStatusService
{
    private const MEMBER_STATUS_KEYWORDS = [
        'Black Card' => ['black card', 'blackcard'],
        'Familia' => ['familia'],
        'VIP' => ['vip'],
    ];

    public function syncFromInvoice(Invoice|int|null $invoice): ?string
    {
        $resolvedInvoice = $invoice instanceof Invoice
            ? $invoice->loadMissing(['visitation.pasien', 'items.billable'])
            : Invoice::with(['visitation.pasien', 'items.billable'])->find($invoice);

        if (!$resolvedInvoice || !$resolvedInvoice->visitation || !$resolvedInvoice->visitation->pasien) {
            return null;
        }

        // A piutang not settled yet doesn't give the membership; the payment that settles it does
        if (!$resolvedInvoice->isLunas()) {
            return null;
        }

        $memberStatus = $this->detectMembershipStatus($resolvedInvoice);
        if (!$memberStatus) {
            return null;
        }

        $pasien = $resolvedInvoice->visitation->pasien;
        if ((string) $pasien->status_pasien !== $memberStatus) {
            $pasien->status_pasien = $memberStatus;
            $pasien->save();
        }

        return $memberStatus;
    }

    /**
     * After a retur: the membership this invoice gave ends once its item is fully returned, unless another paid
     * invoice of the pasien still gives one. A status this invoice did not give (set by hand, or from another
     * invoice) is left alone.
     */
    public function reviewAfterRetur(Invoice $invoice): void
    {
        $invoice = Invoice::with(['visitation.pasien', 'items.billable'])->find($invoice->id);
        $pasien = $invoice && $invoice->visitation ? $invoice->visitation->pasien : null;
        if (!$pasien) {
            return;
        }

        $given = $invoice->items->map(fn ($item) => $this->detectMembershipStatusFromItem($item))->filter();
        if (!$given->contains((string) $pasien->status_pasien) || $this->detectMembershipStatus($invoice)) {
            return;
        }

        $other = Invoice::with('items.billable')
            ->whereHas('visitation', fn ($q) => $q->where('pasien_id', $pasien->id))
            ->where('id', '!=', $invoice->id)
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Invoice $candidate) => $candidate->isLunas())
            ->map(fn (Invoice $candidate) => $this->detectMembershipStatus($candidate))
            ->filter()
            ->first();

        $pasien->status_pasien = $other ?: 'Regular';
        $pasien->save();
    }

    private function isFullyReturned(InvoiceItem $item): bool
    {
        $returned = \App\Models\Finance\ReturPembelianItem::where('invoice_item_id', $item->id)
            ->whereHas('returPembelian', fn ($q) => $q->approved())
            ->sum('quantity_returned');

        return (float) $returned >= (float) $item->quantity;
    }

    private function detectMembershipStatus(Invoice $invoice): ?string
    {
        foreach ($invoice->items as $item) {
            $memberStatus = $this->detectMembershipStatusFromItem($item);
            if ($memberStatus && $this->isFullyReturned($item)) {
                continue;
            }
            if ($memberStatus) {
                return $memberStatus;
            }
        }

        return null;
    }

    private function detectMembershipStatusFromItem(InvoiceItem $item): ?string
    {
        $candidates = [
            $item->name,
            $item->description,
        ];

        if ($item->billable_type === Konsultasi::class && $item->billable) {
            $candidates[] = $item->billable->nama ?? null;
            $candidates[] = $item->billable->name ?? null;
        }

        foreach ($candidates as $candidate) {
            $memberStatus = $this->extractStatusFromText($candidate);
            if ($memberStatus) {
                return $memberStatus;
            }
        }

        return null;
    }

    private function extractStatusFromText(?string $text): ?string
    {
        if (!is_string($text) || trim($text) === '') {
            return null;
        }

        $normalized = Str::of($text)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();

        if (!Str::contains($normalized, 'member')) {
            return null;
        }

        foreach (self::MEMBER_STATUS_KEYWORDS as $status => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($normalized, $keyword)) {
                    return $status;
                }
            }
        }

        return null;
    }
}