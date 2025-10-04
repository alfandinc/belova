<?php

namespace App\Exports;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StokGudangExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Nama Obat',
            'Total Stok',
            'HPP',
            'HPP Jual',
            'Kategori',
            'Nilai Stok',
            'Nama Gudang'
        ];
    }
}
