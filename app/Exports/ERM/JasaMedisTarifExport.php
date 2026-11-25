<?php

namespace App\Exports\ERM;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Master tarif jasa medis. The first four columns follow the Import CSV layout
 * (tindakan, tarif dokter, tarif petugas, keterangan), so a sheet saved as CSV can be imported back.
 */
class JasaMedisTarifExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithColumnFormatting, WithStrictNullComparison
{
    public function __construct(private Collection $rows)
    {
    }

    public function array(): array
    {
        return $this->rows->map(fn ($r) => [
            $r->nama, $r->tarif_dokter, $r->tarif_employee, $r->keterangan,
            $r->kode, $r->is_active ? 'Aktif' : 'Nonaktif', $r->paket_aktif, $r->updated_at, $r->updated_by,
        ])->all();
    }

    public function headings(): array
    {
        return ['Tindakan', 'Tarif Dokter', 'Tarif Petugas', 'Keterangan', 'Kode', 'Status', 'Paket Aktif', 'Diubah', 'Oleh'];
    }

    public function columnFormats(): array
    {
        return ['B' => '#,##0', 'C' => '#,##0'];
    }

    public function title(): string
    {
        return 'Tarif Jasa Medis';
    }
}
