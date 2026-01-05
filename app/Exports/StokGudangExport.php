<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StokGudangExport implements FromArray, WithHeadings, ShouldAutoSize, WithColumnFormatting
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

    public function columnFormats(): array
    {
        return [
            // Ensure decimals are preserved in Excel output
            'B' => '0.0000',          // Total Stok
            'C' => '0.0000',          // HPP
            'D' => '#,##0.0000',      // Nilai Stok
            'E' => '0.0000',          // Total Masuk
            'F' => '0.0000',          // Total Keluar
        ];
    }
}
