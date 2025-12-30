<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StokGudangExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected $rows;

    public function __construct(array $rows = [])
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Nama Obat',
            'Total Stok',
            'HPP',
            'Nilai Stok',
            'Total Masuk',
            'Total Keluar',
            'Gudang'
        ];
    }
}
