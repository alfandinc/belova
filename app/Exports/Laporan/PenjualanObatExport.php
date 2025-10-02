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
        $query = InvoiceItem::with(['billable', 'billable.obat', 'invoice'])
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
            'Nama Obat',
            'Harga Jual',
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
        $discount = $item->discount ?? 0;
        $discountType = $item->discount_type ?? 'nominal';
        $qty = $item->quantity ?? 1;
        $base = ($item->unit_price ?? 0) * $qty;
        $dt = strtolower(trim((string) $discountType));
        $isPercent = in_array($dt, ['persen', 'percent', '%', 'pct', 'pc', 'per']);
        $discountValue = $isPercent ? ($base * $discount / 100) : $discount;
        $hasDiscount = ($discount ?? 0) > 0 ? 'Ada' : 'Tidak';
        return [
            optional($obat)->nama,
            $hargaJual,
            number_format($discountValue, 2),
            $isPercent ? $discount : '',
            $hasDiscount,
        ];
    }
}
