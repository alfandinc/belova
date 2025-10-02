<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StokOpnameTemplateExport implements FromArray, WithHeadings
{
    protected $obats;
    protected $gudangId;

    /**
     * $obats should be a collection of Obat models with stokGudang relation
     * $gudangId is the gudang id used for filtering
     */
    public function __construct($obats, $gudangId = null)
    {
        $this->obats = $obats;
        $this->gudangId = $gudangId;
    }

    public function headings(): array
    {
        return [
            'obat_id',
            'nama_obat',
            'batch',
            'expiration_date',
            'stok_sistem',
            'stok_fisik',
            'notes'
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->obats as $obat) {
            // stokGudang relation is eager-loaded and already filtered to the gudang in controller
            $stokGudangs = $obat->stokGudang ?? [];

            // If there are multiple batches, export each as its own row
            if (count($stokGudangs) > 0) {
                foreach ($stokGudangs as $stok) {
                    $rows[] = [
                        $obat->id,
                        $obat->nama,
                        $stok->batch,
                        optional($stok->expiration_date)->format('Y-m-d'),
                        (string) $stok->stok,
                        '', // stok_fisik - to be filled by user
                        ''  // notes
                    ];
                }
            } else {
                // fallback: export obat row without batch
                $rows[] = [
                    $obat->id,
                    $obat->nama,
                    null,
                    null,
                    '',
                    '',
                    ''
                ];
            }
        }

        return $rows;
    }
}
