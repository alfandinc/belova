<?php

namespace App\Exports;

use App\Models\ERM\Pemasok;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PemasokExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Pemasok::select('nama', 'alamat', 'telepon', 'email')->get();
    }

    public function headings(): array
    {
        return [
            'Nama',
            'Alamat',
            'Telepon',
            'Email',
        ];
    }
}
