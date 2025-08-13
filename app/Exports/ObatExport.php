<?php
namespace App\Exports;

use App\Models\ERM\Obat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ObatExport implements FromCollection, WithHeadings
{
    protected $request;
    public function __construct($request)
    {
        $this->request = $request;
    }
    public function collection()
    {
        $query = Obat::withInactive();
        if ($this->request->has('kategori') && !empty($this->request->kategori)) {
            $query->where('kategori', $this->request->kategori);
        }
        if ($this->request->has('metode_bayar_id') && !empty($this->request->metode_bayar_id)) {
            $query->where('metode_bayar_id', $this->request->metode_bayar_id);
        }
        if ($this->request->filled('status_aktif')) {
            $query->where('status_aktif', $this->request->status_aktif);
        }
    return $query->get(['id', 'kode_obat', 'nama', 'dosis', 'satuan', 'hpp', 'harga_net', 'harga_nonfornas', 'kategori', 'stok', 'status_aktif']);
    }
    public function headings(): array
    {
    return ['ID', 'Kode Obat', 'Nama', 'Dosis', 'Satuan', 'HPP', 'Harga Net', 'Harga Non-Fornas', 'Kategori', 'Stok', 'Status Aktif'];
    }
}
