<?php

namespace App\Services\ERM;

use App\Models\ERM\FakturBeli;
use App\Models\ERM\MasterFaktur;
use App\Models\ERM\Obat;
use App\Models\ERM\PermintaanItem;

/**
 * Master Pembelian (erm_master_faktur) is the single source for "obat X from pemasok Y":
 * harga per satuan (excl PPN), isi per box and diskon.
 *
 *  - The principal belongs to the obat (erm_obat.principal_id, set in Master Obat); permintaan and
 *    faktur items take it from there when none is given and keep their own copy as history.
 *  - An approved faktur writes its prices back here, so the next permintaan starts from the latest price.
 */
class MasterPembelianService
{
    /** Principal of an obat, from Master Obat. */
    public function principalFor(?int $obatId): ?int
    {
        if (!$obatId) {
            return null;
        }

        $principalId = Obat::withInactive()->whereKey($obatId)->value('principal_id');

        return $principalId ? (int) $principalId : null;
    }

    /**
     * Principal for a faktur item: the one already chosen (old item / permintaan item), otherwise Master Obat.
     */
    public function principalForItem(?int $obatId, ?int $permintaanItemId = null, ?int $current = null): ?int
    {
        if ($current) {
            return $current;
        }
        if ($permintaanItemId) {
            $fromPermintaan = PermintaanItem::whereKey($permintaanItemId)->value('principal_id');
            if ($fromPermintaan) {
                return (int) $fromPermintaan;
            }
        }

        return $this->principalFor($obatId);
    }

    /**
     * After a faktur is approved: the price paid becomes the Master Pembelian price for that obat + pemasok.
     * Existing rows get harga / diskon; missing pairs are added.
     * Free lines (harga 0, bonus) and lines not received are skipped. Returns [created, updated].
     */
    public function syncFromFaktur(FakturBeli $faktur): array
    {
        $counts = ['created' => 0, 'updated' => 0];
        if (!$faktur->pemasok_id) {
            return $counts;
        }

        $faktur->loadMissing('items');
        foreach ($faktur->items as $item) {
            if ((float) $item->qty <= 0 || (float) $item->harga <= 0 || !$item->obat_id) {
                continue;
            }

            $master = MasterFaktur::firstOrNew(['obat_id' => $item->obat_id, 'pemasok_id' => $faktur->pemasok_id]);
            $master->harga = round((float) $item->harga, 2);
            $master->diskon = (float) ($item->diskon ?? 0);
            $master->diskon_type = $item->diskon_type === 'percent' ? 'percent' : 'nominal';

            if (!$master->exists) {
                $master->qty_per_box = $this->qtyPerBoxFromPermintaan($item->permintaan_item_id);
                $master->notes = 'Otomatis dari faktur ' . $faktur->no_faktur;
            }

            $counts[$master->exists ? 'updated' : 'created']++;
            $master->save();
        }

        return $counts;
    }

    /** Isi per box from the permintaan (qty_total / jumlah_box); 1 when unknown. */
    private function qtyPerBoxFromPermintaan(?int $permintaanItemId): int
    {
        $pi = $permintaanItemId ? PermintaanItem::find($permintaanItemId) : null;
        if ($pi && (int) $pi->jumlah_box > 0 && (int) $pi->qty_total > 0 && $pi->qty_total % $pi->jumlah_box === 0) {
            return (int) ($pi->qty_total / $pi->jumlah_box);
        }

        return 1;
    }
}
