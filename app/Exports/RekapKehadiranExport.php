<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RekapKehadiranExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'No Induk', 'Nama', 'Sakit', 'Izin', 'Cuti', 'Sisa Cuti', 'Jumlah Hari Masuk', 'On Time', 'Overtime', 'Terlambat', 'Menit Terlambat'
        ];
    }
}
