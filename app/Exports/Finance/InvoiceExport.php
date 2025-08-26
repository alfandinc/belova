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
            ->with(['visitation.pasien', 'visitation.dokter.user', 'visitation.klinik']);
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
        ];
    }

    public function map($invoice): array
    {
        $visitation = $invoice->visitation;
        $pasien = $visitation ? $visitation->pasien : null;
        $dokter = $visitation && $visitation->dokter ? $visitation->dokter->user->name ?? $visitation->dokter->id : null;
        $klinik = $visitation && $visitation->klinik ? $visitation->klinik->nama : null;
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
            $invoice->payment_method,
        ];
    }
}
