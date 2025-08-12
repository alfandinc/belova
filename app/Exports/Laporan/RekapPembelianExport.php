<?php

namespace App\Exports\Laporan;

use App\Models\ERM\FakturBeliItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Log;

class RekapPembelianExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = FakturBeliItem::with(['fakturbeli.pemasok', 'obat'])
            ->whereHas('fakturbeli', function($q) {
                $q->where('status', 'diapprove');
            });
        $items = $query->get();
        Log::info('RekapPembelianExport items count: ' . $items->count());
        Log::info('First item:', $items->first() ? $items->first()->toArray() : []);
        return $items;
    }

    public function headings(): array
    {
        return [
            'Nama Pemasok',
            'Nama Obat',
            'Harga Beli/Satuan',
            'Diskon',
            'Harga Jadi (Setelah Diskon + PPN)'
        ];
    }

    public function map($item): array
    {
        $harga = $item->harga;
        $diskon = $item->diskon ?? 0;
        $diskonType = $item->diskon_type ?? 'nominal';
        $tax = $item->tax ?? 0;
        $taxType = $item->tax_type ?? 'nominal';
        $qty = $item->qty ?? 1;
        $base = $harga * $qty;
        $diskonValue = $diskonType === 'persen' ? ($base * $diskon / 100) : $diskon;
        $taxValue = $taxType === 'persen' ? ($base * $tax / 100) : $tax;
        $hargaJadi = $base - $diskonValue + $taxValue;
        return [
            optional($item->fakturbeli->pemasok)->nama,
            optional($item->obat)->nama,
            $harga,
            $diskon . ($diskonType === 'persen' ? '%' : ''),
            $hargaJadi
        ];
    }
}
