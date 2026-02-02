<?php

namespace App\Exports\Laporan;

use App\Models\ERM\FakturBeliItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class RekapPembelianExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = FakturBeliItem::with(['fakturbeli.pemasok', 'obat.principals'])
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
            'Principal',
            'Nama Obat',
            'Received Date',
            'Due Date',
            'Harga Beli/Satuan',
            'Quantity',
            'Diskon Nominal',
            'Diskon (%)',
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
    $dt = strtolower(trim((string) $diskonType));
    $isPercent = in_array($dt, ['persen', 'percent', '%', 'pct', 'pc', 'per']);
    $diskonValue = $isPercent ? ($base * $diskon / 100) : $diskon;
        $taxValue = $taxType === 'persen' ? ($base * $tax / 100) : $tax;
        $hargaJadi = $base - $diskonValue + $taxValue;
        return [
            optional($item->fakturbeli->pemasok)->nama,
            // Principals: join many-to-many names if available
            (function($obat) {
                if (!$obat) return '';
                try {
                    $names = $obat->principals->pluck('nama')->filter()->values()->all();
                    return is_array($names) ? implode(', ', $names) : '';
                } catch (\Exception $e) {
                    return '';
                }
            })(optional($item->obat)),
            optional($item->obat)->nama,
            // Received date (handle string or DateTime)
            (function($fb) {
                if (!$fb) return '';
                $rd = $fb->received_date ?? null;
                if (!$rd) return '';
                try {
                    return Carbon::parse($rd)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    return (string) $rd;
                }
            })(optional($item->fakturbeli)),
            // Due date
            (function($fb) {
                if (!$fb) return '';
                $dd = $fb->due_date ?? null;
                if (!$dd) return '';
                try {
                    return Carbon::parse($dd)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    return (string) $dd;
                }
            })(optional($item->fakturbeli)),
            $harga,
            $qty,
            // Diskon nominal as number
            number_format($diskonValue, 2),
            // Diskon percent (only set when original type was percent)
            ($isPercent ? $diskon : ''),
            $hargaJadi
        ];
    }
}
