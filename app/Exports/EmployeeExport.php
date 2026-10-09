<?php

namespace App\Exports;

use App\Models\HRD\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Data karyawan as shown in the list (with its filters). Salary groups are left out on purpose. */
class EmployeeExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(private Collection $employees)
    {
    }

    public function headings(): array
    {
        return [
            'No Induk', 'NIK', 'Nama', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Posisi', 'Divisi',
            'Perusahaan', 'Status', 'Tanggal Masuk', 'Masa Kerja', 'Kontrak Berakhir', 'No HP', 'Email', 'Pendidikan', 'Finger ID',
            'Tanggal Nonaktif', 'Alasan Nonaktif', 'Data Belum Lengkap',
        ];
    }

    public function collection()
    {
        $date = fn($d) => $d ? $d->format('d/m/Y') : '';

        return $this->employees->map(fn(Employee $e) => [
            $e->no_induk,
            $e->nik,
            $e->nama,
            ['L' => 'Laki-laki', 'P' => 'Perempuan'][strtoupper((string) $e->jenis_kelamin)] ?? '',
            $e->tempat_lahir,
            $date($e->tanggal_lahir),
            $e->positions->pluck('name')->implode(', '),
            $e->positions->flatMap(fn($p) => $p->divisions->pluck('name'))->unique()->implode(', '),
            implode(', ', $e->daftarPerusahaan()),
            ucfirst((string) $e->status),
            $date($e->tanggal_masuk),
            $e->masaKerja(),
            $e->status === 'kontrak' ? $date($e->kontrak_berakhir) : '',
            $e->no_hp,
            $e->email,
            $e->pendidikan,
            $e->finger_id,
            $e->status === 'tidak aktif' ? $date($e->nonaktif_tanggal) : '',
            $e->status === 'tidak aktif' ? (Employee::NONAKTIF_ALASAN[$e->nonaktif_alasan] ?? '') : '',
            implode(', ', $e->dataBelumLengkap()),
        ]);
    }
}
