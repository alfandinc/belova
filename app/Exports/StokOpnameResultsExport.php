<?php

namespace App\Exports;

use App\Models\ERM\StokOpnameItem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class StokOpnameResultsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $stokOpnameId;

    public function __construct($stokOpnameId)
    {
        $this->stokOpnameId = $stokOpnameId;
    }

    protected $netTemuan = [];

    public function collection()
    {
        $items = StokOpnameItem::with(['obat'])
            ->where('stok_opname_id', $this->stokOpnameId)
            ->orderBy('obat_id')
            ->get();

        // Net temuan per item (computed, stored selisih of old records may be stale)
        $this->netTemuan = DB::table('erm_stok_opname_temuan')
            ->where('stok_opname_id', $this->stokOpnameId)
            ->groupBy('stok_opname_item_id')
            ->select('stok_opname_item_id', DB::raw("COALESCE(SUM(CASE WHEN jenis IN ('lebih','minus') THEN qty WHEN jenis IN ('kurang','plus') THEN -qty ELSE 0 END),0) as net"))
            ->pluck('net', 'stok_opname_item_id')
            ->all();

        return $items;
    }

    public function map($item): array
    {
        // sisa belum terjelaskan = (fisik - sistem) + net temuan; net temuan is the negative of the
        // explained part. Signs: + = fisik lebih, - = fisik kurang.
        $selisih = (float) $item->stok_fisik - (float) $item->stok_sistem;
        $sisa = $selisih + (float) ($this->netTemuan[$item->id] ?? 0);

        return [
            $item->obat ? $item->obat->nama : '-',
            $item->obat ? ($item->obat->kode ?? '') : '',
            $item->batch_name ?? '',
            $item->expiration_date ?? '',
            $item->stok_sistem ?? 0,
            $item->stok_fisik ?? 0,
            $selisih,
            $selisih - $sisa,
            $sisa,
            $item->notes ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'Nama Obat',
            'Kode Obat',
            'Batch',
            'Expiration Date',
            'Stok Sistem',
            'Stok Fisik',
            'Selisih (Fisik - Sistem)',
            'Terjelaskan Temuan',
            'Sisa Belum Terjelaskan',
            'Notes',
        ];
    }
}
