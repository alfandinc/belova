<?php

namespace App\Exports\ERM;

use App\Services\ERM\JasaMedisService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Rekap jasa medis: sheet Ringkasan (per petugas, counted rows only) and Detail (every row, with its status) */
class JasaMedisRekapExport implements WithMultipleSheets
{
    public function __construct(private Collection $rows, private string $from, private string $to)
    {
    }

    public function sheets(): array
    {
        $ringkasan = JasaMedisService::ringkasan($this->rows)->map(fn ($r) => [
            $r->penerima, ($r->tipe === 'dokter' ? 'Dokter' : 'Petugas'), $r->pasien, $r->jumlah, $r->total,
        ])->all();

        $detail = $this->rows->map(fn ($r) => [
            $r->tanggal, $r->klinik, $r->pasien_id, $r->pasien_nama, $r->paket, $r->tindakan,
            $r->penerima, ($r->tipe === 'dokter' ? 'Dokter' : 'Petugas'), $r->tarif, $r->jumlah_petugas, $r->nominal,
            $r->eligible ? 'Dihitung' : 'Tertunda: ' . $r->invoice,
        ])->all();

        return [
            $this->sheet('Ringkasan', ['Nama', 'Tipe', 'Pasien', 'Jumlah Tindakan', 'Total Jasa Medis'], $ringkasan),
            $this->sheet('Detail', ['Tanggal', 'Klinik', 'No RM', 'Pasien', 'Paket', 'Tindakan', 'Nama', 'Tipe', 'Tarif', 'Jumlah Orang', 'Jasa Medis', 'Status'], $detail),
        ];
    }

    private function sheet(string $title, array $headings, array $rows)
    {
        return new class($title, $headings, $rows) implements FromArray, WithHeadings, WithTitle, ShouldAutoSize {
            public function __construct(private string $title, private array $headings, private array $rows)
            {
            }

            public function array(): array
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return $this->headings;
            }

            public function title(): string
            {
                return $this->title;
            }
        };
    }
}
