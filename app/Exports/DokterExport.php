<?php

namespace App\Exports;

use App\Models\ERM\Dokter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Data dokter as shown in the list (with its filters). */
class DokterExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(private Collection $dokters)
    {
    }

    public function headings(): array
    {
        return [
            'Nama', 'Email', 'NIK', 'No HP', 'Alamat', 'Spesialisasi', 'Klinik Utama', 'Klinik Praktik', 'Status',
            'No SIP', 'SIP Berlaku Sampai', 'No STR', 'STR Berlaku Sampai', 'TTD', 'Aktif', 'Tanggal Nonaktif', 'Keterangan Nonaktif',
        ];
    }

    public function collection()
    {
        $date = fn($d) => $d ? Carbon::parse($d)->format('d/m/Y') : '';

        return $this->dokters->map(fn(Dokter $d) => [
            optional($d->user)->name,
            optional($d->user)->email,
            $d->nik,
            $d->no_hp,
            $d->alamat,
            optional($d->spesialisasi)->nama,
            optional($d->klinik)->nama,
            $d->kliniks->pluck('nama')->implode(', '),
            $d->status,
            $d->sip,
            $date($d->due_date_sip),
            $d->str,
            $date($d->due_date_str),
            $d->ttd ? 'Ada' : 'Belum',
            $d->is_active ? 'Aktif' : 'Nonaktif',
            $d->is_active ? '' : $date($d->nonaktif_tanggal),
            $d->is_active ? '' : $d->nonaktif_keterangan,
        ]);
    }
}
