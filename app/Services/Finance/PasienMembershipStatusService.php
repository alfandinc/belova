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

    private function detectMembershipStatus(Invoice $invoice): ?string
    {
        foreach ($invoice->items as $item) {
            $memberStatus = $this->detectMembershipStatusFromItem($item);
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