<?php

namespace App\Exports;

use App\Models\AttendanceRekap;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceRekapExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return AttendanceRekap::with('employee')->get()->map(function ($rekap) {
            return [
                'Nama' => $rekap->employee->nama ?? '',
                'Tanggal' => $rekap->date,
                'Jam Masuk' => $rekap->jam_masuk,
                'Shift Start' => $rekap->shift_start,
                'Jam Keluar' => $rekap->jam_keluar,
                'Shift End' => $rekap->shift_end,
                'Work Hour' => $rekap->work_hour,
                'Terlambat?' => $rekap->terlambat ? 'Ya' : 'Tidak',
                'Terlambat (menit)' => $rekap->menit_terlambat,
                'Overtime (menit)' => $rekap->overtime,
                'Raw jam_masuk' => $rekap->jam_masuk,
                'Raw shift_start' => $rekap->shift_start,
                'Raw jam_keluar' => $rekap->jam_keluar,
                'Raw shift_end' => $rekap->shift_end,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nama',
            'Tanggal',
            'Jam Masuk',
            'Shift Start',
            'Jam Keluar',
            'Shift End',
            'Work Hour',
            'Terlambat?',
            'Terlambat (menit)',
            'Overtime (menit)',
            'Raw jam_masuk',
            'Raw shift_start',
            'Raw jam_keluar',
            'Raw shift_end',
        ];
    }
}
