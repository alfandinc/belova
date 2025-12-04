<?php

namespace App\Exports;

use App\Models\ERM\StokOpnameItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class StokOpnameResultsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $stokOpnameId;

    public function __construct($stokOpnameId)
    {
        $this->stokOpnameId = $stokOpnameId;
    }

    public function collection()
    {
        return StokOpnameItem::with(['obat'])
            ->where('stok_opname_id', $this->stokOpnameId)
            ->orderBy('obat_id')
            ->get();
    }

    public function map($item): array
    {
        return [
            $item->obat ? $item->obat->nama : '-',
            $item->obat ? ($item->obat->kode ?? '') : '',
            $item->batch_name ?? '',
            $item->expiration_date ?? '',
            $item->stok_sistem ?? 0,
            $item->stok_fisik ?? 0,
            $item->selisih ?? 0,
            $item->notes ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'Nama Obat',
            'Kode Obat',
            'Batch',
            'Expiration Date',
            'Stok Sistem',
            'Stok Fisik',
            'Selisih',
            'Notes',
        ];
    }
}
