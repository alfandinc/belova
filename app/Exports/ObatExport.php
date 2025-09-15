<?php
namespace App\Exports;

use App\Models\ERM\Obat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ObatExport implements FromCollection, WithHeadings, WithMapping
{
    protected $request;
    public function __construct($request)
    {
        $this->request = $request;
    }
    public function collection()
    {
        // Always export only active medications
        $query = Obat::where('status_aktif', 1)->with('metodeBayar');

        // Preserve optional filters if provided (kategori, metode_bayar_id)
        if ($this->request->has('kategori') && !empty($this->request->kategori)) {
            $query->where('kategori', $this->request->kategori);
        }
        if ($this->request->has('metode_bayar_id') && !empty($this->request->metode_bayar_id)) {
            $query->where('metode_bayar_id', $this->request->metode_bayar_id);
        }

        // Return full models so WithMapping can format the output
        return $query->get();
    }
    public function headings(): array
    {
    return ['ID', 'Kode Obat', 'Nama', 'HPP', 'HPP Jual', 'Harga Non-Fornas', 'Metode Bayar', 'Kategori', 'Dosis', 'Satuan'];
    }

    /**
     * Map each Obat model to the desired row format for the Excel.
     */
    public function map($obat): array
    {
        return [
            $obat->id,
            $obat->kode_obat,
            $obat->nama,
            $obat->hpp,
            $obat->hpp_jual,
            $obat->harga_nonfornas,
            optional($obat->metodeBayar)->nama ?: '-',
            $obat->kategori,
            $obat->dosis,
            $obat->satuan,
        ];
    }
}
