<?php

namespace App\Services\ERM;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HNA an obat gets from its latest approved faktur: harga per unit (before diskon, excl PPN) + its share of that
 * faktur's ongkir (split by line value qty x harga). Same numbers FakturBeliController::approveFaktur writes.
 */
class HnaFakturService
{
    /**
     * Latest priced, received faktur line per obat (received_date, then newest faktur / item).
     *
     * @param array<int>|null $obatIds null = all obat
     * @return Collection<int, object{obat_id:int, hna:float, harga:float, ongkir_per_unit:float, no_faktur:string}>
     */
    public function terakhir(?array $obatIds = null): Collection
    {
        $hasOngkir = Schema::hasColumn('erm_fakturbeli', 'ongkir');

        // Value of each faktur's priced, received lines: the base the ongkir is split over
        $nilai = DB::table('erm_fakturbeli_items')
            ->where('qty', '>', 0)
            ->where('harga', '>', 0)
            ->groupBy('fakturbeli_id')
            ->select('fakturbeli_id', DB::raw('SUM(qty * harga) as nilai'));

        return DB::table('erm_fakturbeli_items as i')
            ->join('erm_fakturbeli as f', 'f.id', '=', 'i.fakturbeli_id')
            ->leftJoinSub($nilai, 'n', 'n.fakturbeli_id', '=', 'f.id')
            ->whereIn('f.status', ['diapprove', 'diretur'])
            ->where('i.qty', '>', 0)
            ->where('i.harga', '>', 0) // bonus/free lines carry no price
            ->whereNotNull('i.obat_id')
            ->when($obatIds !== null, fn ($q) => $q->whereIn('i.obat_id', $obatIds ?: [0]))
            ->orderByRaw('COALESCE(f.received_date, f.created_at) DESC')
            ->orderByDesc('f.id')
            ->orderByDesc('i.id')
            ->get(array_merge(['i.obat_id', 'i.harga', 'f.no_faktur', 'n.nilai'], $hasOngkir ? ['f.ongkir'] : []))
            ->unique('obat_id')
            ->mapWithKeys(function ($row) {
                // ongkir per unit = faktur ongkir x (line value / faktur value) / qty = ongkir x harga / faktur value
                $ongkir = (float) ($row->ongkir ?? 0);
                $perUnit = $ongkir > 0 && (float) $row->nilai > 0 ? $ongkir * (float) $row->harga / (float) $row->nilai : 0.0;

                return [(int) $row->obat_id => (object) [
                    'obat_id' => (int) $row->obat_id,
                    'harga' => (float) $row->harga,
                    'ongkir_per_unit' => round($perUnit, 2),
                    'hna' => round((float) $row->harga + $perUnit, 2),
                    'no_faktur' => $row->no_faktur,
                ]];
            });
    }
}
