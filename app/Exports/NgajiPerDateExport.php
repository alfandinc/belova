<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\Belova\NgajiNilai;

class NgajiPerDateExport implements FromCollection, WithHeadings
{
    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $rows = NgajiNilai::with('employee')
            ->where('date', $this->date)
            ->orderBy('employee_id')
            ->get()
            ->map(function($r) {
                return [
                    'Nama' => optional($r->employee)->nama,
                    'Tanggal' => $r->date ? $r->date->format('Y-m-d') : '',
                    'Makhroj' => $r->nilai_makhroj,
                    'Tajwid' => $r->nilai_tajwid,
                    'Panjang/Pendek' => $r->nilai_panjang_pendek,
                    'Kelancaran' => $r->nilai_kelancaran,
                    'Total' => $r->total_nilai,
                    'Catatan' => $r->catatan,
                ];
            });

        return collect($rows);
    }

    public function headings(): array
    {
        return ['Nama','Tanggal','Makhroj','Tajwid','Panjang/Pendek','Kelancaran','Total','Catatan'];
    }
}
