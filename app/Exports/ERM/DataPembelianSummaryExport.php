<?php

namespace App\Exports\ERM;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DataPembelianSummaryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private Collection $rows;
    private string $groupBy;

    public function __construct(Collection $rows, string $groupBy)
    {
        $this->rows = $rows->values();
        $this->groupBy = $groupBy === 'principal' ? 'principal' : 'pemasok';
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        $entityLabel = $this->groupBy === 'principal' ? 'Principal' : 'Pemasok';

        return [
            'Nama ' . $entityLabel,
            'Alamat',
            'Telepon',
            'Total Nominal Pembelian',
            'Pembelian Terakhir',
            'Qty Jenis Item',
            'Jumlah Faktur',
        ];
    }

    public function map($row): array
    {
        return [
            $row['nama_pemasok'] ?? '-',
            $row['alamat'] ?? '-',
            $row['telepon'] ?? '-',
            (float) ($row['total_nominal'] ?? 0),
            $row['pembelian_terakhir'] ?? '-',
            (int) ($row['qty_jenis_item'] ?? 0),
            (int) ($row['jumlah_faktur'] ?? 0),
        ];
    }
}
