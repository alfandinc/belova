<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;
use App\Models\ERM\StokOpnameTemuan;
use App\Models\User;

class StokOpnameTemuanExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $stokOpnameId;

    public function __construct($stokOpnameId)
    {
        $this->stokOpnameId = $stokOpnameId;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $rows = StokOpnameTemuan::where('stok_opname_id', $this->stokOpnameId)
            ->with(['item.obat'])
            ->orderBy('created_at', 'asc')
            ->get();

        $data = $rows->map(function ($r) {
            $userName = null;
            if (!empty($r->created_by)) {
                $u = User::find($r->created_by);
                $userName = $u ? $u->name : null;
            }
            return [
                'Tanggal' => $r->created_at ? $r->created_at->format('Y-m-d H:i:s') : '',
                'Obat' => $r->item && $r->item->obat ? $r->item->obat->nama : null,
                'Jenis' => $r->jenis,
                'Qty' => $r->qty,
                'Process Status' => $r->process_status,
                'Keterangan' => $r->keterangan,
                'Created By' => $userName,
            ];
        });

        return new Collection($data->toArray());
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Obat',
            'Jenis',
            'Qty',
            'Process Status',
            'Keterangan',
            'Created By',
        ];
    }
}
