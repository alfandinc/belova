<?php

namespace App\Services\ERM;

use App\Models\ERM\Merchandise;
use App\Models\ERM\MerchandiseKartuStok;
use App\Models\ERM\MerchandisePemasok;
use App\Models\ERM\MerchandisePembelian;
use App\Models\ERM\Pasien;
use App\Models\ERM\PasienMerchandise;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The single path for merchandise stock movements. Every movement locks the master row
 * first, so simultaneous requests cannot read the same balance.
 */
class MerchandiseStockService
{
    public const MANAGE_ROLES = ['Marketing', 'Admin'];

    public const GIVE_ROLES = ['Kasir', 'Pendaftaran', 'Perawat', 'Beautician', 'Finance', 'Marketing', 'Admin'];

    public const VIEW_ROLES = [
        'Dokter', 'Perawat', 'Pendaftaran', 'Admin', 'Farmasi', 'Beautician', 'Lab', 'Finance',
        'Kasir', 'Marketing', 'Manager', 'Employee', 'Head Manager', 'Hrd',
    ];

    public function currentStock(int $merchandiseId): int
    {
        return MerchandiseKartuStok::getLatestCurrentStock($merchandiseId);
    }

    /**
     * Items that can be picked in forms. Inactive items only appear for a patient who still holds one,
     * so the record can be corrected or cancelled.
     */
    public function catalog(?string $pasienId = null, bool $includeInactive = false): Collection
    {
        return Merchandise::query()
            ->unless($includeInactive, function ($query) use ($pasienId) {
                $query->where(function ($query) use ($pasienId) {
                    $query->where('is_active', true);

                    if ($pasienId) {
                        $query->orWhereIn('id', PasienMerchandise::active()->where('pasien_id', $pasienId)->select('merchandise_id'));
                    }
                });
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Merchandise $item) => [
                'id' => $item->id,
                'kode' => $item->kode,
                'name' => $item->name,
                'description' => $item->description,
                'image_url' => $item->image_url,
                'is_active' => (bool) $item->is_active,
                'current_stock' => $this->currentStock($item->id),
            ]);
    }

    public function history(Merchandise $merchandise): Collection
    {
        return MerchandiseKartuStok::query()
            ->with(['user:id,name', 'pembelian:id,kode'])
            ->where('merchandise_id', $merchandise->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MerchandiseKartuStok $row) => [
                'id' => $row->id,
                'tanggal' => optional($row->tanggal)->format('Y-m-d H:i:s'),
                'type' => $row->type,
                'qty' => (int) $row->qty,
                'current_stock' => (int) ($row->current_stock ?? 0),
                'notes' => $row->notes,
                'sumber' => $row->sumber,
                'pembelian_kode' => $row->pembelian->kode ?? null,
                'user_name' => $row->user->name ?? null,
            ]);
    }

    /**
     * Every stock entry: recorded purchases and grants, plus older stock that has no purchase data yet.
     *
     * @return array{purchases: Collection, openings: Collection}
     */
    public function purchaseHistory(Merchandise $merchandise): array
    {
        return [
            'purchases' => $merchandise->pembelian()
                ->with(['pemasok:id,nama', 'user:id,name'])
                ->orderByDesc('tanggal_beli')
                ->orderByDesc('id')
                ->get()
                ->map(fn (MerchandisePembelian $row) => [
                    'id' => $row->id,
                    'kode' => $row->kode,
                    'sumber' => $row->sumber,
                    'tanggal_beli' => $row->tanggal_beli->format('Y-m-d'),
                    'pemasok' => $row->pemasok->nama ?? null,
                    'no_nota' => $row->no_nota,
                    'nota_url' => $row->nota_url,
                    'qty' => (int) $row->qty,
                    'harga_satuan' => $row->harga_satuan === null ? null : (float) $row->harga_satuan,
                    'ongkir' => (float) $row->ongkir,
                    'biaya_lain' => (float) $row->biaya_lain,
                    'total' => $row->total,
                    'catatan' => $row->catatan,
                    'user_name' => $row->user->name ?? null,
                ]),
            'openings' => MerchandiseKartuStok::query()
                ->where('merchandise_id', $merchandise->id)
                ->where('sumber', MerchandisePembelian::SUMBER_STOK_AWAL)
                ->whereNull('pembelian_id')
                ->orderByDesc('tanggal')
                ->get()
                ->map(fn (MerchandiseKartuStok $row) => [
                    'id' => $row->id,
                    'tanggal' => optional($row->tanggal)->format('Y-m-d'),
                    'qty' => (int) $row->qty,
                    'notes' => $row->notes,
                ]),
        ];
    }

    public function pemasokNames(): Collection
    {
        return MerchandisePemasok::query()->orderBy('nama')->pluck('nama');
    }

    /**
     * Records incoming stock with its purchase data. The stock card keeps today's date so balances stay in order;
     * the real purchase date lives on the purchase.
     */
    public function receiveStock(int $merchandiseId, int $qty, array $data): MerchandisePembelian
    {
        return DB::transaction(function () use ($merchandiseId, $qty, $data) {
            $merchandise = $this->lockMerchandise($merchandiseId);
            $this->ensureActive($merchandise);

            $pembelian = $merchandise->pembelian()->create($this->purchaseAttributes($data) + [
                'qty' => $qty,
                'user_id' => Auth::id(),
            ]);

            $label = $pembelian->sumber === MerchandisePembelian::SUMBER_HIBAH ? 'Hibah/sponsor ' : 'Pembelian ';
            $this->record($merchandise, 'in', $qty, $label . $pembelian->kode, $pembelian->sumber, $pembelian->id);

            return $pembelian;
        });
    }

    /**
     * Quantity and source cannot change here, because the stock card already depends on them.
     */
    public function updatePurchase(MerchandisePembelian $pembelian, array $data): MerchandisePembelian
    {
        $attributes = $this->purchaseAttributes($data + ['sumber' => $pembelian->sumber]);
        $pembelian->update($attributes + ['updated_by' => Auth::id()]);

        return $pembelian;
    }

    /**
     * Attaches purchase data to older stock. Stock does not change: the quantity is already counted.
     */
    public function completeOpeningStock(MerchandiseKartuStok $kartu, array $data): MerchandisePembelian
    {
        return DB::transaction(function () use ($kartu, $data) {
            $locked = MerchandiseKartuStok::query()->whereKey($kartu->id)->lockForUpdate()->firstOrFail();

            if ($locked->sumber !== MerchandisePembelian::SUMBER_STOK_AWAL || $locked->pembelian_id) {
                $this->reject('Stok ini sudah punya data pembelian.');
            }

            $pembelian = MerchandisePembelian::create($this->purchaseAttributes($data) + [
                'merchandise_id' => $locked->merchandise_id,
                'qty' => (int) $locked->qty,
                'user_id' => Auth::id(),
            ]);

            $locked->update(['sumber' => $pembelian->sumber, 'pembelian_id' => $pembelian->id]);

            return $pembelian;
        });
    }

    /**
     * Applies a physical count. A count that matches is recorded too, so the stock card shows when it was checked.
     * All or nothing, and it refuses counts made against a stock figure that has since moved.
     *
     * @param  array<int, array{id: int, fisik: int, stok_sistem: int, alasan?: string|null}>  $items
     * @return array{counted: int, changed: int}
     */
    public function applyOpname(array $items): array
    {
        return DB::transaction(function () use ($items) {
            $changed = 0;

            foreach (collect($items)->sortBy('id') as $item) {
                $merchandise = $this->lockMerchandise((int) $item['id']);
                $current = $this->currentStock($merchandise->id);
                $fisik = (int) $item['fisik'];
                $delta = $fisik - $current;
                $reason = trim((string) ($item['alasan'] ?? ''));

                if ((int) $item['stok_sistem'] !== $current) {
                    $this->reject('Stok ' . $merchandise->name . ' berubah menjadi ' . $current . ' saat opname berlangsung. Muat ulang lalu periksa lagi.');
                }

                if ($delta !== 0 && $reason === '') {
                    $this->reject('Alasan wajib diisi untuk ' . $merchandise->name . ' karena ada selisih.');
                }

                $note = 'Stok opname: sistem ' . $current . ', fisik ' . $fisik . ($reason !== '' ? ' | Alasan: ' . $reason : '');
                $this->record($merchandise, $delta < 0 ? 'out' : 'in', abs($delta), $note, MerchandiseKartuStok::SUMBER_OPNAME);

                if ($delta !== 0) {
                    $changed++;
                }
            }

            return ['counted' => count($items), 'changed' => $changed];
        });
    }

    private function purchaseAttributes(array $data): array
    {
        $isPurchase = $data['sumber'] === MerchandisePembelian::SUMBER_PEMBELIAN;
        $pemasok = trim((string) ($data['pemasok'] ?? ''));

        $attributes = [
            'sumber' => $data['sumber'],
            'tanggal_beli' => $data['tanggal_beli'],
            'pemasok_id' => $pemasok !== '' ? MerchandisePemasok::firstOrCreate(['nama' => $pemasok])->id : null,
            'no_nota' => $data['no_nota'] ?? null,
            'harga_satuan' => $isPurchase ? $data['harga_satuan'] : null,
            'ongkir' => $isPurchase ? (float) ($data['ongkir'] ?? 0) : 0,
            'biaya_lain' => $isPurchase ? (float) ($data['biaya_lain'] ?? 0) : 0,
            'catatan' => $data['catatan'] ?? null,
        ];

        if (array_key_exists('foto_nota', $data)) {
            $attributes['foto_nota'] = $data['foto_nota'];
        }

        return $attributes;
    }

    public function stockOut(int $merchandiseId, int $qty, string $reason, ?string $notes): void
    {
        DB::transaction(function () use ($merchandiseId, $qty, $reason, $notes) {
            $merchandise = $this->lockMerchandise($merchandiseId);
            $this->ensureActive($merchandise);
            $this->ensureStockAvailable($merchandise, $qty);

            $text = 'Pengeluaran merchandise untuk ' . trim($reason);
            if (!empty($notes)) {
                $text .= ' | Catatan: ' . trim($notes);
            }

            $this->record($merchandise, 'out', $qty, $text);
        });
    }

    public function giveToPasien(Pasien $pasien, int $merchandiseId, int $qty, ?string $notes): PasienMerchandise
    {
        return DB::transaction(function () use ($pasien, $merchandiseId, $qty, $notes) {
            $merchandise = $this->lockMerchandise($merchandiseId);
            $this->ensureActive($merchandise);
            $this->ensureStockAvailable($merchandise, $qty);

            $record = PasienMerchandise::create([
                'pasien_id' => $pasien->id,
                'merchandise_id' => $merchandise->id,
                'quantity' => $qty,
                'stock_qty' => $qty,
                'notes' => $notes,
                'given_by_user_id' => Auth::id(),
                'given_at' => now(),
            ]);

            $this->record(
                $merchandise,
                'out',
                $qty,
                'Pemberian merchandise ke pasien ' . $this->pasienLabel($pasien) . (!empty($notes) ? ' | Catatan: ' . trim($notes) : '')
            );

            return $record;
        });
    }

    /**
     * @param  array{notes?: string|null}  $attributes  notes is only changed when the key is present
     */
    public function updatePasienRecord(Pasien $pasien, PasienMerchandise $record, int $newQty, array $attributes = []): PasienMerchandise
    {
        return DB::transaction(function () use ($pasien, $record, $newQty, $attributes) {
            $merchandise = $this->lockMerchandise($record->merchandise_id);
            $record = $this->lockActiveRecord($record);
            $delta = $newQty - (int) $record->quantity;

            $note = 'Penyesuaian merchandise pasien ' . $this->pasienLabel($pasien);

            if ($delta > 0) {
                $this->ensureActive($merchandise);
                $this->ensureStockAvailable($merchandise, $delta);
                $this->record($merchandise, 'out', $delta, $note);
                $record->stock_qty += $delta;
            } elseif ($delta < 0) {
                // Only what this gift really took from stock can go back
                $returned = min(abs($delta), (int) $record->stock_qty);
                if ($returned > 0) {
                    $this->record($merchandise, 'in', $returned, $note);
                    $record->stock_qty -= $returned;
                }
            }

            $record->quantity = $newQty;
            if (array_key_exists('notes', $attributes)) {
                $record->notes = $attributes['notes'];
            }
            $record->save();

            return $record;
        });
    }

    /**
     * Keeps the patient record for audit. Stock only goes back when the item physically did,
     * and never more than this gift took from stock (older gifts took nothing).
     *
     * @return int quantity returned to stock
     */
    public function cancelPasienRecord(Pasien $pasien, PasienMerchandise $record, string $reason, bool $returnStock): int
    {
        return DB::transaction(function () use ($pasien, $record, $reason, $returnStock) {
            $merchandise = $this->lockMerchandise($record->merchandise_id);
            $record = $this->lockActiveRecord($record);
            $returned = $returnStock ? (int) $record->stock_qty : 0;

            if ($returned > 0) {
                $this->record(
                    $merchandise,
                    'in',
                    $returned,
                    'Pembatalan merchandise pasien ' . $this->pasienLabel($pasien) . ' | Alasan: ' . trim($reason)
                );
            }

            $record->update([
                'stock_qty' => (int) $record->stock_qty - $returned,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancel_reason' => trim($reason),
            ]);

            return $returned;
        });
    }

    private function lockMerchandise(int $merchandiseId): Merchandise
    {
        return Merchandise::query()->whereKey($merchandiseId)->lockForUpdate()->firstOrFail();
    }

    private function lockActiveRecord(PasienMerchandise $record): PasienMerchandise
    {
        $locked = PasienMerchandise::active()->whereKey($record->id)->lockForUpdate()->first();

        if (!$locked) {
            $this->reject('Data merchandise pasien ini sudah dibatalkan.');
        }

        return $locked;
    }

    private function ensureActive(Merchandise $merchandise): void
    {
        if (!$merchandise->is_active) {
            $this->reject('Merchandise ' . $merchandise->name . ' sudah nonaktif.');
        }
    }

    private function ensureStockAvailable(Merchandise $merchandise, int $qty): void
    {
        $stock = $this->currentStock($merchandise->id);

        if ($qty > $stock) {
            $this->reject('Qty merchandise melebihi stok yang tersedia. Sisa stok saat ini: ' . $stock);
        }
    }

    private function record(Merchandise $merchandise, string $type, int $qty, string $notes, ?string $sumber = null, ?int $pembelianId = null): void
    {
        MerchandiseKartuStok::create([
            'merchandise_id' => $merchandise->id,
            'tanggal' => now(),
            'type' => $type,
            'qty' => $qty,
            'current_stock' => MerchandiseKartuStok::calculateCurrentStock($merchandise->id, $type, $qty),
            'notes' => $notes,
            'user_id' => Auth::id(),
            'sumber' => $sumber,
            'pembelian_id' => $pembelianId,
        ]);
    }

    private function pasienLabel(Pasien $pasien): string
    {
        return $pasien->id . ' - ' . ($pasien->nama ?? '-');
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['quantity' => $message]);
    }
}
