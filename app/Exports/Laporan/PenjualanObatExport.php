<?php
namespace App\Exports\Laporan;

use App\Models\ERM\FakturBeliItem;
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
        $query = FakturBeliItem::with(['obat', 'fakturbeli'])
            ->whereHas('fakturbeli', function($q) {
                $q->where('status', 'diapprove');
            });
        if ($this->dateRange) {
            $dates = explode(' - ', $this->dateRange);
            if (count($dates) === 2) {
                $start = $dates[0] . ' 00:00:00';
                $end = $dates[1] . ' 23:59:59';
                $query->whereHas('fakturbeli', function($q) use ($start, $end) {
                    $q->whereBetween('received_date', [$start, $end]);
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
            'Diskon Obat Saat Pelayanan',
        ];
    }

    public function map($item): array
    {
        $hargaJual = $item->obat->harga_nonfornas ?? $item->obat->harga_net ?? 0;
        $diskon = ($item->diskon ?? 0) > 0 ? 'Ada' : 'Tidak';
        return [
            optional($item->obat)->nama,
            $hargaJual,
            $diskon
        ];
    }
}
