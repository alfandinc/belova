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

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        return Invoice::query()
            ->whereHas('visitation', function($q) {
                $q->whereBetween('tanggal_visitation', [$this->startDate, $this->endDate]);
            })
            ->with(['visitation.pasien']);
    }

    public function headings(): array
    {
        return [
            'Tanggal Visit',
            'Tanggal Dibayar',
            'No RM',
            'Nama Pasien',
            'Subtotal',
            'Discount',
            'Tax',
            'Total Amount',
            'Amount Paid',
            'Change Amount',
            'Paid Method',
        ];
    }

    public function map($invoice): array
    {
        $visitation = $invoice->visitation;
        $pasien = $visitation ? $visitation->pasien : null;
        return [
            optional($visitation)->tanggal_visitation,
            optional($invoice)->payment_date,
            optional($pasien)->id,
            optional($pasien)->nama,
            $invoice->subtotal,
            $invoice->discount,
            $invoice->tax,
            $invoice->total_amount,
            $invoice->amount_paid,
            $invoice->change_amount,
            $invoice->payment_method,
        ];
    }
}
