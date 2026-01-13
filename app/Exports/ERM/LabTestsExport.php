<?php

namespace App\Exports\ERM;

use App\Models\ERM\LabTest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LabTestsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return LabTest::query()->with('labKategori:id,nama');
    }

    public function headings(): array
    {
        return ['Nama Test', 'Kategori', 'Harga'];
    }

    /**
     * @param \App\Models\ERM\LabTest $row
     */
    public function map($row): array
    {
        return [
            $row->nama,
            optional($row->labKategori)->nama,
            $row->harga,
        ];
    }
}
