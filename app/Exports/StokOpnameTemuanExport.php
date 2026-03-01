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

            // HPP used in stok opname create page totals: obat.hpp_jual
            $hppJual = 0.0;
            if ($r->item && $r->item->obat) {
                $hppJual = (float) ($r->item->obat->hpp_jual ?? 0);
            }

            $qty = (float) ($r->qty ?? 0);
            // Export convention requested: 'kurang' is +, 'lebih' is -
            $sign = ($r->jenis === 'lebih') ? -1 : 1;
            $nilaiNominal = $hppJual * $qty * $sign;

            $jenisSelisih = $r->jenis;
            if ($r->jenis === 'kurang') {
                $jenisSelisih = 'plus';
            } elseif ($r->jenis === 'lebih') {
                $jenisSelisih = 'minus';
            }

            $processStatusLabel = (!empty($r->process_status) && (int) $r->process_status === 1)
                ? 'Diproses'
                : 'Belum diproses';

            return [
                'Tanggal' => $r->created_at ? $r->created_at->format('Y-m-d H:i:s') : '',
                'Obat' => $r->item && $r->item->obat ? $r->item->obat->nama : null,
                'Jenis Selisih' => $jenisSelisih,
                'Qty' => $r->qty,
                'HPP' => $hppJual,
                'Nilai Nominal' => $nilaiNominal,
                'Process Status' => $processStatusLabel,
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
            'Jenis Selisih',
            'Qty',
            'HPP',
            'Nilai Nominal',
            'Process Status',
            'Keterangan',
            'Created By',
        ];
    }
}
