<?php

namespace App\Exports;

use App\Models\ERM\Principal;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PrincipalExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Principal::select('nama', 'alamat', 'telepon', 'email')->get();
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
