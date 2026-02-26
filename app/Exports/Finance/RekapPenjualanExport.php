<?php

namespace App\Exports\Finance;

use App\Models\Finance\InvoiceItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Support\Responsable;

class RekapPenjualanExport implements FromQuery, WithHeadings, WithMapping, Responsable
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
        return InvoiceItem::query()
            ->whereHas('invoice.visitation', function($q) {
                $q->whereBetween('tanggal_visitation', [$this->startDate, $this->endDate]);
                if ($this->klinikId) {
                    $q->where('klinik_id', $this->klinikId);
                }
                if ($this->dokterId) {
                    $q->where('dokter_id', $this->dokterId);
                }
            })
            ->with([
                'invoice.visitation.pasien',
                'invoice.visitation.dokter.user',
                'invoice.visitation.klinik',
                'invoice.piutangs',
            ]);
    }

    public function headings(): array
    {
        return [
            'Tanggal Visit',
            'Tanggal Invoice',
            'No RM',
            'Nama Pasien',
            'Nama Dokter',
            'Nama Klinik',
            'Jenis',
            'Nama Item',
            'Qty',
            'Harga',
            'Harga Sebelum Diskon',
            'Diskon Nominal',
            'Diskon',
            'Harga Setelah Diskon',
            'Status',
            'Payment Method',
            'Notes',
        ];
    }

    public function map($item): array
    {
        $invoice = $item->invoice;
        $visitation = $invoice->visitation;
        $pasien = $visitation ? $visitation->pasien : null;
        $dokter = $visitation && $visitation->dokter ? $visitation->dokter->user->name ?? $visitation->dokter->id : null;
        $klinik = $visitation && $visitation->klinik ? $visitation->klinik->nama : null;

        $totalAmount = $invoice ? floatval($invoice->total_amount ?? 0) : 0;
        $amountPaid = $invoice ? floatval($invoice->amount_paid ?? 0) : 0;
        $invoiceRemaining = max(0, $totalAmount - $amountPaid);
        $isInvoiceFullyPaid = ($totalAmount > 0) && ($amountPaid >= $totalAmount);

        $paidMethod = $invoice ? $invoice->payment_method : null;
        $notes = '';

        $formatRupiah = function ($value) {
            return 'Rp ' . number_format(floatval($value), 0, ',', '.');
        };

        $piutangs = ($invoice && $invoice->relationLoaded('piutangs')) ? ($invoice->piutangs ?? collect()) : collect();
        $latestPiutang = $piutangs
            ->sortByDesc(function ($p) {
                return $p->payment_date ?? $p->updated_at ?? $p->created_at;
            })
            ->first();

        $piutangStatus = $latestPiutang ? strtolower(trim((string)($latestPiutang->payment_status ?? ''))) : '';
        $piutangAmount = $latestPiutang ? floatval($latestPiutang->amount ?? 0) : 0;
        $piutangPaid = $latestPiutang ? floatval($latestPiutang->paid_amount ?? 0) : 0;
        $piutangRemaining = max(0, $piutangAmount - $piutangPaid);

        $settledPiutang = $piutangs->first(function ($piutang) {
            if (!$piutang) return false;
            $status = strtolower(trim((string)($piutang->payment_status ?? '')));
            if (in_array($status, ['paid', 'lunas', 'sudah bayar', 'sudah dibayar'], true)) return true;
            $amount = floatval($piutang->amount ?? 0);
            $paidAmount = floatval($piutang->paid_amount ?? 0);
            return $amount > 0 && $paidAmount >= $amount;
        });

        $isPiutangInvoice = ($invoice && $invoice->payment_method === 'piutang');
        $isPiutangSettled = $isPiutangInvoice && ($settledPiutang || $isInvoiceFullyPaid);

        // Status + payment method + notes
        if ($isPiutangInvoice) {
            if ($isPiutangSettled) {
                $status = 'Sudah Dibayar';
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
                if (in_array($piutangStatus, ['partial'], true)) {
                    $status = 'Belum Lunas';
                } else {
                    $status = 'Belum Dibayar';
                }

                if (in_array($piutangStatus, ['unpaid', 'belum bayar', 'belum dibayar', ''], true) && $piutangPaid <= 0) {
                    $notes = 'Piutang belum bayar';
                } else {
                    $remaining = $piutangRemaining > 0 ? $piutangRemaining : $invoiceRemaining;
                    $notes = 'Kekurangan: ' . $formatRupiah($remaining);
                    if ($latestPiutang && !empty($latestPiutang->payment_method)) {
                        $paidMethod = $latestPiutang->payment_method;
                    }
                }
            }
        } else {
            if ($isInvoiceFullyPaid) {
                $status = 'Sudah Dibayar';
            } elseif ($amountPaid > 0 && $invoiceRemaining > 0) {
                $status = 'Belum Lunas';
                $notes = 'Kekurangan: ' . $formatRupiah($invoiceRemaining);
            } else {
                $status = 'Belum Dibayar';
            }
        }

        // Compute total price and diskon nominal
        $qty = $item->quantity ?? 1;
        $unit = $item->unit_price ?? 0;
        $totalPrice = $qty * $unit;

        $diskon = $item->discount ?? 0;
        $diskonType = strtolower(trim((string) ($item->discount_type ?? 'nominal')));
        $isPercent = in_array($diskonType, ['persen', 'percent', '%', 'pct', 'pc', 'per']);
    $diskonNominal = $isPercent ? ($totalPrice * $diskon / 100) : $diskon;
    $hargaSebelumDiskon = $totalPrice;
    $hargaSetelahDiskon = $totalPrice - $diskonNominal;

        // Determine Jenis based on billable_type or item name
        $billableType = $item->billable_type ?? '';
        $itemNameLower = strtolower($item->name ?? '');
        $jenis = 'Lain-lain';
        if (stripos($billableType, 'Resep') !== false || stripos($billableType, 'Obat') !== false || str_contains($itemNameLower, 'obat') || str_contains($itemNameLower, 'resep')) {
            $jenis = 'Obat/Produk';
        } elseif (stripos($billableType, 'Tindakan') !== false || str_contains($itemNameLower, 'tindakan')) {
            $jenis = 'Tindakan';
        } elseif (stripos($billableType, 'Lab') !== false || stripos($billableType, 'Laboratorium') !== false || str_contains($itemNameLower, 'lab') || str_contains($itemNameLower, 'laboratorium')) {
            $jenis = 'Laboratorium';
        }

        return [
            optional($visitation)->tanggal_visitation,
            optional($invoice)->updated_at,
            optional($pasien)->id,
            optional($pasien)->nama,
            $dokter,
            $klinik,
            $jenis,
            $item->name,
            $qty,
            $unit,
            $hargaSebelumDiskon,
            $diskonNominal,
            $item->discount,
            $hargaSetelahDiskon,
            $status,
            $paidMethod,
            $notes,
        ];
    }
}
