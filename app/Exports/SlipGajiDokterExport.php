<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Slip gaji dokter, as filtered in the Payroll Dokter list. */
class SlipGajiDokterExport implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnFormatting
{
    public function __construct(private Collection $slips)
    {
    }

    public function headings(): array
    {
        return [
            'Dokter', 'Bulan',
            'Jasa Konsultasi', 'Jasa Tindakan', 'Uang Duduk', 'Tunjangan Jabatan', 'Peresepan Obat', 'Rujuk Lab',
            'Pembuatan Konten', 'Overtime', 'Pendapatan Tambahan', 'Rincian Pendapatan Tambahan', 'Total Pendapatan',
            'Bagi Hasil', 'Pot Pajak', 'Potongan Lain', 'Total Potongan', 'Total Gaji', 'Status',
        ];
    }

    public function collection()
    {
        $num = fn($v) => (float) ($v ?? 0);

        return $this->slips->map(function ($s) use ($num) {
            $items = is_array($s->pendapatan_tambahan) ? $s->pendapatan_tambahan : [];
            $tambahan = array_sum(array_map('floatval', array_column($items, 'amount')));
            $rincian = collect($items)->map(fn($it) => ($it['label'] ?? '-') . ': ' . number_format((float) ($it['amount'] ?? 0), 0, ',', '.'))->implode('; ');

            return [
                optional(optional($s->dokter)->user)->name ?? ('Dokter ' . $s->dokter_id),
                $s->bulan,
                $num($s->jasa_konsultasi),
                $num($s->jasa_tindakan),
                $num($s->uang_duduk),
                $num($s->tunjangan_jabatan),
                $num($s->peresepan_obat),
                $num($s->rujuk_lab),
                $num($s->pembuatan_konten),
                $num($s->overtime),
                $tambahan,
                $rincian,
                $num($s->total_pendapatan),
                $num($s->bagi_hasil),
                $num($s->pot_pajak),
                $num($s->potongan_lain),
                $num($s->total_potongan),
                $num($s->total_gaji),
                ucfirst(strtolower(trim((string) $s->status_gaji)) ?: 'draft'),
            ];
        });
    }

    public function columnFormats(): array
    {
        $formats = [];
        foreach (['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'M', 'N', 'O', 'P', 'Q', 'R'] as $col) {
            $formats[$col] = '#,##0.00';
        }
        return $formats;
    }
}
