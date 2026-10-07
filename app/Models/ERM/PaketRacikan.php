<?php

namespace App\Models\ERM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaketRacikan extends Model
{
    use HasFactory;

    protected $table = 'erm_paket_racikan';

    protected $fillable = [
        'nama_paket',
        'deskripsi',
        'wadah_id',
        'bungkus_default',
        'aturan_pakai_default',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function wadah()
    {
        return $this->belongsTo(WadahObat::class, 'wadah_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(PaketRacikanDetail::class);
    }

    /**
     * Isi paket as an order-free key of obat_id|dosis. Resep and billing do not store which paket was used:
     * they recognise a racikan by this content, so two pakets with the same isi cannot be told apart.
     *
     * @param iterable $items rows/arrays with obat_id and dosis
     */
    public static function compositionKey(iterable $items): string
    {
        $keys = [];
        foreach ($items as $item) {
            // read attributes directly: toArray() on ResepFarmasi would run its appended accessor
            $obatId = is_array($item) ? ($item['obat_id'] ?? 0) : $item->obat_id;
            $dosis = is_array($item) ? ($item['dosis'] ?? '') : $item->dosis;
            $keys[] = (int) $obatId . '|' . PaketRacikanDetail::normalizeDosis($dosis);
        }
        sort($keys);

        return implode(';', $keys);
    }

    /** Another paket (active or not) whose isi is exactly the given obat + dosis list. */
    public static function findSameComposition(array $obats, $exceptId = null): ?self
    {
        $key = self::compositionKey($obats);
        $obatIds = array_map(fn ($o) => (int) $o['obat_id'], $obats);

        return self::with('details')
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->has('details', '=', count($obats))
            ->whereHas('details', fn ($q) => $q->whereIn('obat_id', $obatIds))
            ->get()
            ->first(fn ($paket) => self::compositionKey($paket->details) === $key);
    }

    /**
     * Name for a racikan (its resep rows) in lists and history: the paket stored on the rows when it was
     * made from a paket, otherwise the paket with exactly this isi. Pakets are loaded once per resolver.
     */
    public static function racikanNameResolver(): \Closure
    {
        $namesByKey = null;

        return function ($items) use (&$namesByKey) {
            $items = collect($items);
            $stored = $items->pluck('paket_racikan_nama')->filter()->first();
            if ($stored || $items->isEmpty()) {
                return $stored ?: null;
            }
            $namesByKey ??= self::with('details')->get()
                ->filter(fn ($p) => $p->details->isNotEmpty())
                ->mapWithKeys(fn ($p) => [self::compositionKey($p->details) => $p->nama_paket])
                ->all();

            return $namesByKey[self::compositionKey($items)] ?? null;
        };
    }

    /**
     * After a racikan's isi was edited: keep its paket link only while the isi is still exactly the paket
     * (rows added during the edit get the link too); otherwise the racikan is custom now and the link is cleared.
     *
     * Pass the link read before the edit ($before): an edit may delete and recreate every row of the racikan.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $resepModel ResepDokter or ResepFarmasi
     * @param array|null $before ['paket_racikan_id' => ..., 'paket_racikan_nama' => ...] from before the edit
     */
    public static function syncRacikanLink(string $resepModel, $visitationId, $racikanKe, ?array $before = null): void
    {
        if (!$visitationId || !$racikanKe) {
            return;
        }
        $rows = $resepModel::where('visitation_id', $visitationId)->where('racikan_ke', $racikanKe)->get();
        $paketId = ($before['paket_racikan_id'] ?? null) ?: $rows->pluck('paket_racikan_id')->filter()->first();
        if (!$paketId || $rows->isEmpty()) {
            return;
        }

        $paket = self::with('details')->find($paketId);
        $stillPaket = $paket && self::compositionKey($rows) === self::compositionKey($paket->details);
        $nama = ($before['paket_racikan_nama'] ?? null) ?: $rows->pluck('paket_racikan_nama')->filter()->first();
        $link = $stillPaket
            ? ['paket_racikan_id' => $paket->id, 'paket_racikan_nama' => $nama ?: $paket->nama_paket]
            : ['paket_racikan_id' => null, 'paket_racikan_nama' => null];

        // query update: no model events, so billing/invoice sync hooks on resep rows are not re-triggered
        $resepModel::where('visitation_id', $visitationId)->where('racikan_ke', $racikanKe)->update($link);
        ResepFarmasi::forgetPaketNameCache();
    }

    /** The paket link a racikan has right now, to hand to syncRacikanLink() after editing it. */
    public static function racikanLink(string $resepModel, $visitationId, $racikanKe): ?array
    {
        $row = $resepModel::where('visitation_id', $visitationId)->where('racikan_ke', $racikanKe)
            ->whereNotNull('paket_racikan_id')->first(['paket_racikan_id', 'paket_racikan_nama']);

        return $row ? ['paket_racikan_id' => $row->paket_racikan_id, 'paket_racikan_nama' => $row->paket_racikan_nama] : null;
    }

    /**
     * racikan_ke for a new racikan: the requested number when this visitation does not use it yet, otherwise
     * the next free one. Call inside a transaction (locks the visitation's racikan rows), so two screens or a
     * paket applied meanwhile cannot merge into the same racikan.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $resepModel ResepDokter or ResepFarmasi
     */
    public static function freeRacikanKe(string $resepModel, $visitationId, $requested = null): int
    {
        $used = $resepModel::where('visitation_id', $visitationId)->whereNotNull('racikan_ke')
            ->lockForUpdate()->pluck('racikan_ke')->map(fn ($k) => (int) $k)->all();
        $requested = (int) $requested;

        return ($requested > 0 && !in_array($requested, $used, true)) ? $requested : (empty($used) ? 1 : max($used) + 1);
    }

    /** Throws a validation error when another paket already has exactly this isi. */
    public static function assertUniqueComposition(array $obats, $exceptId = null): void
    {
        $same = self::findSameComposition($obats, $exceptId);
        if ($same) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'obats' => "Isi paket ini (obat & dosis) sama persis dengan paket \"{$same->nama_paket}\""
                    . ($same->is_active ? '' : ' (nonaktif)')
                    . '. Gunakan paket tersebut, atau ubah obat/dosisnya agar riwayat resep tidak tertukar.',
            ]);
        }
    }
}
