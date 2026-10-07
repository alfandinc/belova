<?php

namespace App\Exports\ERM;

use App\Models\ERM\MasterFaktur;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Master Pembelian rows, optionally for one principal (the obat's principal, set in Master Obat). */
class MasterFakturByPrincipalExport implements FromCollection, WithHeadings
{
    public function __construct(protected ?int $principalId = null)
    {
    }

    public function collection()
    {
        return MasterFaktur::with(['obat' => fn ($q) => $q->withInactive()->with('principal'), 'pemasok'])
            ->when($this->principalId, function ($query) {
                $query->whereHas('obat', fn ($q) => $q->withInactive()->where('principal_id', $this->principalId));
            })
            ->get()
            ->map(function ($masterFaktur) {
                return [
                    'obat' => $masterFaktur->obat->nama ?? '-',
                    'pemasok' => $masterFaktur->pemasok->nama ?? '-',
                    'principal' => $masterFaktur->obat->principal->nama ?? '-',
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
