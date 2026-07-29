<?php

namespace App\Exports\ERM;

use App\Models\ERM\MasterFaktur;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MasterFakturByPrincipalExport implements FromCollection, WithHeadings
{
    public function __construct(protected ?int $principalId = null)
    {
    }

    public function collection()
    {
        return MasterFaktur::with(['obat', 'pemasok', 'principal'])
            ->when($this->principalId, function ($query) {
                $query->where('principal_id', $this->principalId);
            })
            ->get()
            ->map(function ($masterFaktur) {
                return [
                    'obat' => $masterFaktur->obat->nama ?? '-',
                    'pemasok' => $masterFaktur->pemasok->nama ?? '-',
                    'principal' => $masterFaktur->principal->nama ?? '-',
                    'harga' => $masterFaktur->harga,
                    'qty_per_box' => $masterFaktur->qty_per_box,
                    'diskon' => $masterFaktur->diskon,
                    'diskon_type' => $masterFaktur->diskon_type,
                    'notes' => $masterFaktur->notes,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Obat',
            'Pemasok',
            'Principal',
            'Harga',
            'Qty/Box',
            'Diskon',
            'Tipe Diskon',
            'Notes',
        ];
    }
}