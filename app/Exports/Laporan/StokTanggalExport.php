<?php

namespace App\Exports\Laporan;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StokTanggalExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected $data;
    protected $selectedDate;

    public function __construct($data, $selectedDate)
    {
        $this->data = $data;
        $this->selectedDate = $selectedDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->data);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Kode Obat',
            'Nama Obat',
            'Kategori',
            'Satuan',
            'Stok pada Tanggal (' . $this->selectedDate . ')',
            'Stok Saat Ini',
            'Status',
            'Selisih'
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        $selisih = $row['stok_current'] - $row['stok_on_date'];
        $selisihText = $selisih > 0 ? '+' . number_format($selisih, 0) : number_format($selisih, 0);
        
        $status = '';
        if ($row['stok_on_date'] <= 0) {
            $status = 'Kosong';
        } elseif ($row['stok_on_date'] < 10) {
            $status = 'Rendah';
        } else {
            $status = 'Tersedia';
        }

        return [
            $row['kode_obat'],
            $row['nama_obat'],
            $row['kategori'],
            $row['satuan'],
            number_format($row['stok_on_date'], 0),
            number_format($row['stok_current'], 0),
            $status,
            $selisihText
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Stok Obat ' . $this->selectedDate;
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
        ];
    }
}