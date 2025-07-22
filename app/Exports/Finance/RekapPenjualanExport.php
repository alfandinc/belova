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

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        return InvoiceItem::query()
            ->whereHas('invoice.visitation', function($q) {
                $q->whereBetween('tanggal_visitation', [$this->startDate, $this->endDate]);
            })
            ->with(['invoice.visitation.pasien']);
    }

    public function headings(): array
    {
        return [
            'Tanggal Visit',
            'Tanggal Invoice',
            'No RM',
            'Nama Pasien',
            'Nama Item',
            'Qty',
            'Harga',
            'Total Harga',
            'Diskon',
        ];
    }

    public function map($item): array
    {
        $invoice = $item->invoice;
        $visitation = $invoice->visitation;
        $pasien = $visitation ? $visitation->pasien : null;
        return [
            optional($visitation)->tanggal_visitation,
            optional($invoice)->updated_at,
            optional($pasien)->id,
            optional($pasien)->nama,
            $item->name,
            $item->quantity,
            $item->unit_price,
            $item->quantity * $item->unit_price,
            $item->discount,
        ];
    }
}
