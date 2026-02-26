<?php

namespace App\Exports\Finance;

use App\Models\Finance\Invoice;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Support\Responsable;

class InvoiceExport implements FromQuery, WithHeadings, WithMapping, Responsable
{
    use \Maatwebsite\Excel\Concerns\Exportable;

    private $startDate;
    private $endDate;
    private $klinikId;
    private $dokterId;

    public function __construct($startDate, $endDate, $klinikId = null, $dokterId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->klinikId = $klinikId;
        $this->dokterId = $dokterId;
    }

    public function query()
    {
        return Invoice::query()
            ->whereHas('visitation', function($q) {
                $q->whereBetween('tanggal_visitation', [$this->startDate, $this->endDate]);
                if ($this->klinikId) {
                    $q->where('klinik_id', $this->klinikId);
                }
                if ($this->dokterId) {
                    $q->where('dokter_id', $this->dokterId);
                }
            })
            ->with([
                'visitation.pasien',
                'visitation.dokter.user',
                'visitation.klinik',
                'piutangs',
            ]);
    }

    public function headings(): array
    {
        return [
            'Tanggal Visit',
            'Tanggal Dibayar',
            'No RM',
            'Nama Pasien',
            'Nama Dokter',
            'Nama Klinik',
            'Subtotal',
            'Discount',
            'Tax',
            'Total Amount',
            'Amount Paid',
            'Change Amount',
            'Paid Method',
            'Notes',
        ];
    }

    public function map($invoice): array
    {
        $visitation = $invoice->visitation;
        $pasien = $visitation ? $visitation->pasien : null;
        $dokter = $visitation && $visitation->dokter ? $visitation->dokter->user->name ?? $visitation->dokter->id : null;
        $klinik = $visitation && $visitation->klinik ? $visitation->klinik->nama : null;

        $paidMethod = $invoice->payment_method;
        $notes = '';

        $totalAmount = floatval($invoice->total_amount ?? 0);
        $amountPaid = floatval($invoice->amount_paid ?? 0);
        $isInvoiceFullyPaid = ($totalAmount > 0) && ($amountPaid >= $totalAmount);

        $piutangs = $invoice->piutangs ?? collect();

        $latestPiutang = $piutangs
            ->sortByDesc(function ($p) {
                return $p->payment_date ?? $p->updated_at ?? $p->created_at;
            })
            ->first();

        $piutangStatus = $latestPiutang ? strtolower(trim((string)($latestPiutang->payment_status ?? ''))) : '';
        $piutangAmount = $latestPiutang ? floatval($latestPiutang->amount ?? 0) : 0;
        $piutangPaid = $latestPiutang ? floatval($latestPiutang->paid_amount ?? 0) : 0;
        $piutangRemaining = max(0, $piutangAmount - $piutangPaid);

        $invoiceRemaining = max(0, $totalAmount - $amountPaid);

        $formatRupiah = function ($value) {
            return 'Rp ' . number_format(floatval($value), 0, ',', '.');
        };

        $settledPiutang = $piutangs->first(function ($piutang) {
            if (!$piutang) return false;
            $status = strtolower(trim((string)($piutang->payment_status ?? '')));
            if (in_array($status, ['paid', 'lunas', 'sudah bayar', 'sudah dibayar'], true)) return true;
            $amount = floatval($piutang->amount ?? 0);
            $paidAmount = floatval($piutang->paid_amount ?? 0);
            return $amount > 0 && $paidAmount >= $amount;
        });

        $isPiutangInvoice = ($invoice->payment_method === 'piutang');
        $isPiutangSettled = $isPiutangInvoice && ($settledPiutang || $isInvoiceFullyPaid);

        if ($isPiutangInvoice) {
            if ($isPiutangSettled) {
                // Use piutang payment method only when a real payment method is recorded
                $piutangForMethod = $piutangs
                    ->filter(function ($p) {
                        return $p && !empty($p->payment_method);
                    })
                    ->sortByDesc(function ($p) {
                        return $p->payment_date ?? $p->updated_at ?? $p->created_at;
                    })
                    ->first();

                if ($piutangForMethod && !empty($piutangForMethod->payment_method)) {
                    $paidMethod = $piutangForMethod->payment_method;
                }

                $notes = 'Lunas via piutang';
            } else {
                // Not settled yet
                if (in_array($piutangStatus, ['unpaid', 'belum bayar', 'belum dibayar', ''], true) && $piutangPaid <= 0) {
                    $notes = 'Piutang belum bayar';
                } else {
                    $remaining = $piutangRemaining > 0 ? $piutangRemaining : $invoiceRemaining;
                    $notes = 'Kekurangan: ' . $formatRupiah($remaining);
                    // If there is a payment method recorded for partial payments, show it
                    if ($latestPiutang && !empty($latestPiutang->payment_method)) {
                        $paidMethod = $latestPiutang->payment_method;
                    }
                }
            }
        } else {
            // Non-piutang invoices
            if (!$isInvoiceFullyPaid && $invoiceRemaining > 0) {
                $notes = 'Kekurangan: ' . $formatRupiah($invoiceRemaining);
            }
        }

        return [
            optional($visitation)->tanggal_visitation,
            optional($invoice)->payment_date,
            optional($pasien)->id,
            optional($pasien)->nama,
            $dokter,
            $klinik,
            $invoice->subtotal,
            $invoice->discount,
            $invoice->tax,
            $invoice->total_amount,
            $invoice->amount_paid,
            $invoice->change_amount,
            $paidMethod,
            $notes,
        ];
    }
}
