<?php

namespace App\Exports\ERM;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DataPembelianEntityExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private Collection $rows;
    private string $entityType;
    private string $entityName;

    public function __construct(Collection $rows, string $entityType, string $entityName)
    {
        $this->rows = $rows->values();
        $this->entityType = $entityType === 'principal' ? 'principal' : 'pemasok';
        $this->entityName = $entityName;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        if ($this->entityType === 'principal') {
            return [
                'Principal',
                'No Faktur',
                'Pemasok',
                'Tanggal Terima',
                'Jatuh Tempo',
                'Jumlah Item',
                'Total Qty',
                'Total Principal',
                'Status',
            ];
        }

        return [
            'Pemasok',
            'No Faktur',
            'Tanggal Terima',
            'Jatuh Tempo',
            'Jumlah Item',
            'Subtotal',
            'Diskon',
            'Pajak',
            'Total',
            'Status',
        ];
    }

    public function map($row): array
    {
        if ($this->entityType === 'principal') {
            return [
                $this->entityName,
                $row['no_faktur'] ?? '-',
                $row['pemasok_nama'] ?? '-',
                $row['received_date'] ?? '-',
                $row['due_date'] ?? '-',
                (int) ($row['jumlah_item'] ?? 0),
                (float) ($row['qty_total'] ?? 0),
                (float) ($row['total_principal'] ?? 0),
                $row['status'] ?? '-',
            ];
        }

        return [
            $this->entityName,
            $row['no_faktur'] ?? '-',
            $row['received_date'] ?? '-',
            $row['due_date'] ?? '-',
            (int) ($row['jumlah_item'] ?? 0),
            (float) ($row['subtotal'] ?? 0),
            (float) ($row['global_diskon'] ?? 0),
            (float) ($row['global_pajak'] ?? 0),
            (float) ($row['total'] ?? 0),
            $row['status'] ?? '-',
        ];
    }
}
