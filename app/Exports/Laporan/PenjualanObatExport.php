<?php
namespace App\Exports\Laporan;

use App\Models\Finance\InvoiceItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PenjualanObatExport implements FromCollection, WithHeadings, WithMapping
{
    protected $dateRange;
    public function __construct($dateRange = null)
    {
        $this->dateRange = $dateRange;
    }

    public function collection()
    {
        $query = InvoiceItem::with([
            'billable', 
            'billable.obat', 
            'invoice', 
            'invoice.visitation',
            'invoice.visitation.pasien',
            'billable.resepDetail'
        ])
            ->where('billable_type', 'App\\Models\\ERM\\ResepFarmasi');
        if ($this->dateRange) {
            $dates = explode(' - ', $this->dateRange);
            if (count($dates) === 2) {
                $start = $dates[0] . ' 00:00:00';
                $end = $dates[1] . ' 23:59:59';
                $query->whereHas('invoice', function($q) use ($start, $end) {
                    $q->whereBetween('payment_date', [$start, $end]);
                });
            }
        }
        return $query->get();
    }

    public function headings(): array
    {
        return [
            'No Invoice',
            'Nama Pasien',
            'No Resep',
            'Nama Obat',
            'Harga Jual',
            'Quantity',
            'Diskon Nominal',
            'Diskon (%)',
            'Diskon Obat Saat Pelayanan',
        ];
    }

    public function map($item): array
    {
        $billable = $item->billable;
        $obat = optional($billable)->obat;
        $hargaJual = $item->unit_price ?? 0;
        $quantity = $item->quantity ?? 1;
        $discount = $item->discount ?? 0;
        $discountType = $item->discount_type ?? 'nominal';
        $qty = $item->quantity ?? 1;
        $base = ($item->unit_price ?? 0) * $qty;
        $dt = strtolower(trim((string) $discountType));
        $isPercent = in_array($dt, ['persen', 'percent', '%', 'pct', 'pc', 'per']);
        $discountValue = $isPercent ? ($base * $discount / 100) : $discount;
        $hasDiscount = ($discount ?? 0) > 0 ? 'Ada' : 'Tidak';
        
        // Get additional data
        $invoiceNumber = $item->invoice ? $item->invoice->invoice_number : '-';
        $namaPasien = $item->invoice && $item->invoice->visitation && $item->invoice->visitation->pasien 
            ? $item->invoice->visitation->pasien->nama 
            : '-';
        
        $noResep = '-';
        if ($billable && $billable->visitation_id) {
            $resepDetail = \App\Models\ERM\ResepDetail::where('visitation_id', $billable->visitation_id)->first();
            $noResep = $resepDetail ? $resepDetail->no_resep : '-';
        }
        
        return [
            $invoiceNumber,
            $namaPasien,
            $noResep,
            optional($obat)->nama,
            $hargaJual,
            $quantity,
            number_format($discountValue, 2),
            $isPercent ? $discount : '',
            $hasDiscount,
        ];
    }
}
