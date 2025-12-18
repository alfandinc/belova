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
            ->with(['invoice.visitation.pasien', 'invoice.visitation.dokter.user', 'invoice.visitation.klinik']);
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
        ];
    }

    public function map($item): array
    {
        $invoice = $item->invoice;
        $visitation = $invoice->visitation;
        $pasien = $visitation ? $visitation->pasien : null;
        $dokter = $visitation && $visitation->dokter ? $visitation->dokter->user->name ?? $visitation->dokter->id : null;
        $klinik = $visitation && $visitation->klinik ? $visitation->klinik->nama : null;
        $status = ($invoice && $invoice->amount_paid > 0) ? 'Sudah Dibayar' : 'Belum Dibayar';
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
            $invoice ? $invoice->payment_method : null,
        ];
    }
}
