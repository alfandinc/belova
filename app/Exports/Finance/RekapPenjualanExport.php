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
            'Nama Item',
            'Qty',
            'Harga',
            'Total Harga',
            'Diskon',
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
        return [
            optional($visitation)->tanggal_visitation,
            optional($invoice)->updated_at,
            optional($pasien)->id,
            optional($pasien)->nama,
            $dokter,
            $klinik,
            $item->name,
            $item->quantity,
            $item->unit_price,
            $item->quantity * $item->unit_price,
            $item->discount,
            $status,
            $invoice ? $invoice->payment_method : null,
        ];
    }
}
