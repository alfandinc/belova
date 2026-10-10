<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/** Slip gaji karyawan for one month, as filtered in the payroll list. */
class SlipGajiExport implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnFormatting
{
    public function __construct(private Collection $slips)
    {
    }

    public function headings(): array
    {
        return [
            'No Induk', 'Nama', 'Divisi', 'Bulan', 'Hari Masuk',
            'Gaji Pokok', 'Tunjangan Jabatan', 'Tunjangan Masa Kerja', 'Uang Makan', 'Uang KPI', 'KPI Poin',
            'Jam Lembur', 'Uang Lembur', 'Jasa Medis', 'Pendapatan Tambahan', 'Total Pendapatan',
            'Potongan Pinjaman', 'Potongan BPJS Kesehatan', 'Potongan Jamsostek', 'Potongan Penalty', 'Potongan Lain', 'Total Potongan',
            'Total Benefit', 'Total Gaji', 'Status',
        ];
    }

    public function collection()
    {
        $num = fn($v) => (float) ($v ?? 0);

        return $this->slips->map(function ($s) use ($num) {
            $tambahan = is_array($s->pendapatan_tambahan)
                ? array_sum(array_map('floatval', array_column($s->pendapatan_tambahan, 'amount')))
                : 0;
            $status = strtolower(trim((string) $s->status_gaji));

            return [
                $s->employee_no_induk,
                $s->employee_nama,
                $s->division_name_join,
                $s->bulan,
                (int) ($s->total_hari_masuk ?? 0),
                $num($s->gaji_pokok),
                $num($s->tunjangan_jabatan),
                $num($s->tunjangan_masa_kerja),
                $num($s->uang_makan),
                $num($s->uang_kpi),
                $num($s->kpi_poin),
                round($num($s->total_jam_lembur) / 60, 2), // stored in minutes
                $num($s->uang_lembur),
                $num($s->jasa_medis),
                $tambahan,
                $num($s->total_pendapatan),
                $num($s->potongan_pinjaman),
                $num($s->potongan_bpjs_kesehatan),
                $num($s->potongan_jamsostek),
                $num($s->potongan_penalty),
                $num($s->potongan_lain),
                $num($s->total_potongan),
                $num($s->total_benefit),
                $num($s->total_gaji),
                ucfirst($status === 'diapprove' ? 'approved' : ($status ?: 'draft')),
            ];
        });
    }

    public function columnFormats(): array
    {
        $money = '#,##0.00';
        $formats = [];
        foreach (['F', 'G', 'H', 'I', 'J', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X'] as $col) {
            $formats[$col] = $money;
        }
        $formats['K'] = NumberFormat::FORMAT_NUMBER_00;
        $formats['L'] = NumberFormat::FORMAT_NUMBER_00;
        return $formats;
    }
}
