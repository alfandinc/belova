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

    /**
     * Master Pembelian holds one price per obat + pemasok (unique index), so an obat priced at both
     * pemasok keeps one row: the most recently updated (the last price entered), with empty
     * principal_id / notes filled from the other. Same rule as the duplicate clean-up migration.
     */
    protected static function beforeMerge(int $sourceId, int $targetId): void
    {
        $targetRows = DB::table('erm_master_faktur')->where('pemasok_id', $targetId)->lockForUpdate()->get()->keyBy('obat_id');
        if ($targetRows->isEmpty()) {
            return;
        }
        $sourceRows = DB::table('erm_master_faktur')->where('pemasok_id', $sourceId)
            ->whereIn('obat_id', $targetRows->keys())->lockForUpdate()->get();

        foreach ($sourceRows as $source) {
            $target = $targetRows[$source->obat_id];
            // Rows with a date win over rows without, then the newest; ties keep the target
            $sourceNewer = $source->updated_at !== null
                && ($target->updated_at === null || $source->updated_at > $target->updated_at);
            [$keep, $drop] = $sourceNewer ? [$source, $target] : [$target, $source];

            $fill = [];
            if (empty($keep->principal_id) && !empty($drop->principal_id)) {
                $fill['principal_id'] = $drop->principal_id;
            }
            if (trim((string) $keep->notes) === '' && trim((string) $drop->notes) !== '') {
                $fill['notes'] = $drop->notes;
            }

            DB::table('erm_master_faktur')->where('id', $drop->id)->delete();
            if ($fill) {
                DB::table('erm_master_faktur')->where('id', $keep->id)->update($fill);
            }
        }
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
