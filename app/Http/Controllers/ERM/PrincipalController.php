<?php

namespace App\Http\Controllers\ERM;

use App\Exports\PrincipalExport;
use App\Models\ERM\Principal;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Principal master, managed from the Master Obat page. See SupplierMasterController.
 */
class PrincipalController extends SupplierMasterController
{
    protected function modelClass(): string
    {
        return Principal::class;
    }

    protected function label(): string
    {
        return 'Principal';
    }

    protected function managePageUrl(): string
    {
        return route('erm.obat.index', ['kelola' => 'principal']);
    }

    protected static function references(): array
    {
        return [
            // Shown as the "N obat" count on the page (obatLinks), listed here for the delete guard and merge
            'master_obat' => ['erm_obat', 'principal_id', 'obat'],
            'master_faktur' => ['erm_master_faktur', 'principal_id', 'master faktur'],
            'faktur_item' => ['erm_fakturbeli_items', 'principal_id', 'item faktur beli'],
            'permintaan' => ['erm_permintaan_items', 'principal_id', 'item permintaan'],
        ];
    }

    protected static function obatLinks(array $ids): Builder
    {
        // The principal is set on the obat itself (Master Obat)
        return DB::table('erm_obat')
            ->whereIn('principal_id', $ids)
            ->select('principal_id as owner_id', 'id as obat_id');
    }

    public function exportExcel()
    {
        return Excel::download(new PrincipalExport, 'principal.xlsx');
    }
}
