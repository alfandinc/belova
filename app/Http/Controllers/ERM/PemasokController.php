<?php

namespace App\Http\Controllers\ERM;

use App\Exports\PemasokExport;
use App\Models\ERM\Pemasok;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Pemasok (distributor) master, managed from the Master Pembelian page. See SupplierMasterController.
 */
class PemasokController extends SupplierMasterController
{
    protected function modelClass(): string
    {
        return Pemasok::class;
    }

    protected function label(): string
    {
        return 'Pemasok';
    }

    protected function managePageUrl(): string
    {
        return route('erm.masterfaktur.index', ['kelola' => 'pemasok']);
    }

    protected static function references(): array
    {
        return [
            'faktur' => ['erm_fakturbeli', 'pemasok_id', 'faktur beli'],
            'master_faktur' => ['erm_master_faktur', 'pemasok_id', 'master faktur'],
            'permintaan' => ['erm_permintaan_items', 'pemasok_id', 'item permintaan'],
            'retur' => ['erm_fakturretur', 'pemasok_id', 'retur'],
        ];
    }

    protected static function obatLinks(array $ids): Builder
    {
        $fromFaktur = DB::table('erm_fakturbeli_items as fi')
            ->join('erm_fakturbeli as f', 'f.id', '=', 'fi.fakturbeli_id')
            ->whereIn('f.pemasok_id', $ids)
            ->select('f.pemasok_id as owner_id', 'fi.obat_id');

        return DB::table('erm_master_faktur')
            ->whereIn('pemasok_id', $ids)
            ->select('pemasok_id as owner_id', 'obat_id')
            ->union($fromFaktur);
    }

    public function exportExcel()
    {
        return Excel::download(new PemasokExport, 'pemasok.xlsx');
    }
}
