<?php

namespace App\Services\Finance;

use App\Models\ERM\Pasien;
use App\Models\ERM\Visitation;
use App\Models\Marketing\MarketingEvent;

/**
 * Status / remaining amount / referral of a billed visit, shared by the Billing list and the Invoice Excel export
 * so both show the same values.
 */
class BillingRowFormatter
{
    /**
     * Billing status: Terhapus, Belum Transaksi, Lunas, Belum Lunas or Piutang.
     * $totalBillings / $trashedBillings are the visit's finance_billing row counts (incl. / only trashed).
     */
    public static function statusLabel($invoice, int $totalBillings, int $trashedBillings): string
    {
        // All billings of the visit are trashed (if there are any)
        if ($totalBillings > 0 && $trashedBillings === $totalBillings) {
            return 'Terhapus';
        }

        // No invoice created -> Belum Transaksi
        if (!$invoice) {
            return 'Belum Transaksi';
        }

        $amountPaid = floatval($invoice->amount_paid ?? 0);
        $totalAmount = floatval($invoice->total_amount ?? 0);
        $paymentMethod = strtolower((string)($invoice->payment_method ?? ''));

        // Fully paid (normal or after piutang settlement), or a zero total (e.g. free voucher) that was processed
        if (($totalAmount > 0 && $amountPaid >= $totalAmount) || ($totalAmount <= 0 && $invoice->status === 'paid')) {
            return 'Lunas';
        }

        // Piutang-like flow: insurance follows piutang settlement status.
        if ($paymentMethod === 'piutang' || str_starts_with($paymentMethod, 'asuransi_')) {
            $piutang = $invoice->relationLoaded('piutangs') ? $invoice->piutangs->first() : null;
            $status = $piutang && isset($piutang->payment_status) ? strtolower((string)$piutang->payment_status) : null;

            if ($status === 'paid') {
                return 'Lunas';
            }
            if ($status === 'partial') {
                return 'Belum Lunas';
            }

            return $paymentMethod === 'piutang' ? 'Piutang' : 'Belum Lunas';
        }

        // Partially paid (non-piutang)
        if ($amountPaid > 0 && $amountPaid < $totalAmount) {
            return 'Belum Lunas';
        }

        // Invoice exists but no payment yet
        return 'Belum Transaksi';
    }

    /**
     * Remaining unpaid amount of an invoice (same rule as the Kekurangan column on the billing list).
     */
    public static function remainingAmount($invoice): float
    {
        if (!$invoice || floatval($invoice->total_amount ?? 0) <= 0) {
            return 0.0;
        }

        $piutang = $invoice->relationLoaded('piutangs') ? $invoice->piutangs->first() : null;
        if ($piutang) {
            $remaining = floatval($piutang->amount ?? 0) - floatval($piutang->paid_amount ?? 0);
        } elseif (floatval($invoice->shortage_amount ?? 0) > 0) {
            $remaining = floatval($invoice->shortage_amount);
        } else {
            $remaining = floatval($invoice->total_amount ?? 0) - floatval($invoice->amount_paid ?? 0);
        }

        return max(0.0, $remaining);
    }

    /**
     * Referral of a visit as [type label, detail or null], e.g. ['Pasien', 'Budi (RM: 000123)'].
     * Uses the visit's own (transaction) referral, or the patient's source referral for legacy visits.
     * Eager load referralable / pasien.referralable (with Dokter user) to avoid per-row queries.
     */
    public static function referralParts(?Visitation $visitation): array
    {
        $owner = $visitation && !empty($visitation->referral_type) ? $visitation : optional($visitation)->pasien;

        return self::referralPartsOf($owner);
    }

    private static function referralPartsOf(Visitation|Pasien|null $owner): array
    {
        $referralType = (string) (($owner->referral_type ?? null) ?: Pasien::REFERRAL_TYPE_WALK_IN);

        $typeLabel = match ($referralType) {
            Pasien::REFERRAL_TYPE_WALK_IN => 'Walk-in',
            Pasien::REFERRAL_TYPE_PASIEN => 'Pasien',
            Pasien::REFERRAL_TYPE_DOKTER => 'Dokter',
            Pasien::REFERRAL_TYPE_EMPLOYEE => 'Karyawan',
            Pasien::REFERRAL_TYPE_SOCIAL_MEDIA => 'Social Media',
            Pasien::REFERRAL_TYPE_MARKETPLACE => 'Marketplace',
            Pasien::REFERRAL_TYPE_EVENT => 'Event',
            Pasien::REFERRAL_TYPE_WEBSITE => 'Website',
            Pasien::REFERRAL_TYPE_PARTNERSHIP => 'B2B Partnership',
            Pasien::REFERRAL_TYPE_GOOGLE_MAPS => 'Google Maps',
            default => 'Walk-in',
        };

        $source = $owner ? $owner->referralable : null;
        $referralDetail = $owner->referral_detail ?? null;
        $detail = null;

        if ($referralType === Pasien::REFERRAL_TYPE_PASIEN && $source instanceof Pasien) {
            $detail = $source->nama . ' (RM: ' . $source->id . ')';
        } elseif ($referralType === Pasien::REFERRAL_TYPE_EMPLOYEE && $source instanceof \App\Models\HRD\Employee) {
            $detail = $source->nama;
        } elseif ($referralType === Pasien::REFERRAL_TYPE_DOKTER && $source instanceof \App\Models\ERM\Dokter) {
            $detail = optional($source->user)->name ?: 'Dokter ID ' . $source->id;
        } elseif ($referralType === Pasien::REFERRAL_TYPE_EVENT) {
            $detail = $source instanceof MarketingEvent
                ? $source->nama_event
                : (!empty($referralDetail) ? (MarketingEvent::where('kode_event', $referralDetail)->value('nama_event') ?: $referralDetail) : null);
        } elseif (!empty($referralDetail)) {
            $detail = ucwords(str_replace('_', ' ', (string) $referralDetail));
        }

        return [$typeLabel, $detail];
    }
}
