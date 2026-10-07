<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Shared master for pemasok (distributor) and principal, managed from a "Kelola" modal
 * (erm.partials.supplier-manager): principal on Master Obat, pemasok on Master Pembelian.
 *
 * Several foreign keys to these tables cascade (deleting a pemasok also deletes its faktur beli,
 * master faktur and permintaan items), so delete is only allowed when the record is not used anywhere;
 * duplicates are merged instead.
 */
abstract class SupplierMasterController extends Controller
{
    /** Model class (Pemasok / Principal) */
    abstract protected function modelClass(): string;

    /** Label shown in messages, e.g. "Pemasok" */
    abstract protected function label(): string;

    /**
     * Tables that reference this master: key => [table, column, label].
     * Used for the usage counts, the "dipakai" filter, the delete guard and merge.
     */
    abstract protected static function references(): array;

    /**
     * Union of (owner_id, obat_id) rows linking the given ids to obat.
     */
    abstract protected static function obatLinks(array $ids): Builder;

    /** Page whose "Kelola" modal manages this master; non-AJAX visits are sent there. */
    abstract protected function managePageUrl(): string;

    /** Obat linked to the given ids, as a query with columns owner_id and obat_id. */
    public static function obatIdsQuery(array $ids): Builder
    {
        return DB::query()->fromSub(static::obatLinks($ids), 'links');
    }

    protected function messages(): array
    {
        return [
            'nama.required' => 'Nama ' . strtolower($this->label()) . ' wajib diisi.',
            'nama.max' => 'Nama terlalu panjang (maksimal :max karakter).',
            'email.email' => 'Format email tidak valid.',
        ];
    }

    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ];
    }

    private function isAdmin(): bool
    {
        return (bool) optional(Auth::user())->hasAnyRole(['Admin']);
    }

    private function newQuery()
    {
        return ($this->modelClass())::query();
    }

    /** Uppercase, single spaces. */
    public static function cleanNama(?string $nama): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtoupper((string) $nama)));
    }

    /**
     * Comparison key for spotting duplicates: ignores case, punctuation, spacing and company forms
     * ("PT. Kimia Farma" = "KIMIA FARMA, PT").
     */
    public static function compareKey(?string $nama): string
    {
        $nama = mb_strtoupper((string) $nama);
        $nama = preg_replace('/\b(PT|CV|UD|TBK|PERSERO)\b/u', ' ', $nama);

        return preg_replace('/[^A-Z0-9]/u', '', $nama);
    }

    private function findSameName(string $nama, ?int $exceptId = null): ?Model
    {
        $key = static::compareKey($nama);

        return $this->newQuery()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->get(['id', 'nama'])
            ->first(fn ($row) => static::compareKey($row->nama) === $key);
    }

    private function duplicateIds(): array
    {
        return $this->newQuery()->get(['id', 'nama'])
            ->groupBy(fn ($row) => static::compareKey($row->nama))
            ->filter(fn ($group, $key) => $key === '' || $group->count() > 1)
            ->flatten()
            ->pluck('id')
            ->all();
    }

    /** Restricts $query to rows referenced (or not) by any table in references(). */
    private function whereUsed($query, bool $used): void
    {
        $table = $query->getModel()->getTable();
        $method = $used ? 'orWhereExists' : 'whereNotExists';
        $query->where(function ($w) use ($table, $method) {
            foreach (static::references() as [$refTable, $column]) {
                $w->{$method}(fn ($q) => $q->selectRaw(1)->from($refTable)->whereColumn("{$refTable}.{$column}", "{$table}.id"));
            }
        });
    }

    /** Usage counts per id: [id => ['obat' => n, <reference key> => n, ...]] */
    private function usageCounts(array $ids): array
    {
        $counts = array_fill_keys($ids, array_merge(['obat' => 0], array_fill_keys(array_keys(static::references()), 0)));
        if (empty($ids)) {
            return $counts;
        }

        foreach (static::references() as $key => [$refTable, $column]) {
            DB::table($refTable)->whereIn($column, $ids)
                ->groupBy($column)
                ->selectRaw("{$column} as owner_id, COUNT(*) as c")
                ->get()
                ->each(function ($r) use (&$counts, $key) { $counts[$r->owner_id][$key] = (int) $r->c; });
        }

        static::obatIdsQuery($ids)
            ->groupBy('owner_id')
            ->selectRaw('owner_id, COUNT(DISTINCT obat_id) as c')
            ->get()
            ->each(function ($r) use (&$counts) { $counts[$r->owner_id]['obat'] = (int) $r->c; });

        return $counts;
    }

    private function isUsed(array $usage): bool
    {
        return collect(array_keys(static::references()))->contains(fn ($key) => ($usage[$key] ?? 0) > 0);
    }

    private function usageText(array $usage): string
    {
        return collect(static::references())
            ->filter(fn ($ref, $key) => ($usage[$key] ?? 0) > 0)
            ->map(fn ($ref, $key) => $usage[$key] . ' ' . $ref[2])
            ->implode(', ');
    }

    /**
     * Server-side list for the DataTable (search[value], start, length, order).
     * Optional filter `pemakaian`: dipakai | tidak_dipakai | ganda.
     * A non-AJAX visit (old menu link / bookmark) opens the Kelola modal on its page (managePageUrl).
     */
    public function index(Request $request)
    {
        if (!$request->ajax() && !$request->wantsJson()) {
            return redirect()->to($this->managePageUrl());
        }

        $query = $this->newQuery();
        $total = (clone $query)->count();

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                    ->orWhere('alamat', 'LIKE', "%{$search}%")
                    ->orWhere('telepon', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        switch ($request->input('pemakaian')) {
            case 'dipakai':
                $this->whereUsed($query, true);
                break;
            case 'tidak_dipakai':
                $this->whereUsed($query, false);
                break;
            case 'ganda':
                $query->whereIn('id', $this->duplicateIds());
                break;
        }

        $recordsFiltered = (clone $query)->count();

        $orderDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy('nama', $orderDir)->orderBy('id');

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length > 0 ? min($length, 200) : 10;

        $rows = $query->skip($start)->take($length)->get(['id', 'nama', 'alamat', 'telepon', 'email']);
        $usage = $this->usageCounts($rows->pluck('id')->all());

        $data = $rows->map(fn ($row) => [
            'id' => $row->id,
            'nama' => $row->nama,
            'alamat' => $row->alamat,
            'telepon' => $row->telepon,
            'email' => $row->email,
            'usage' => $usage[$row->id],
            'used' => $this->isUsed($usage[$row->id]),
        ]);

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $total,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'duplicate_count' => $request->boolean('with_summary') ? count($this->duplicateIds()) : null,
        ]);
    }

    /**
     * Add. Rejects names that already exist (ignoring case, punctuation and PT/CV).
     * Also used by the inline "tambah" on the Master Faktur page, which reads data.id.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $validated['nama'] = static::cleanNama($validated['nama']);

        if (static::compareKey($validated['nama']) === '') {
            return response()->json(['message' => 'Nama tidak valid.', 'errors' => ['nama' => ['Nama tidak valid.']]], 422);
        }
        if ($same = $this->findSameName($validated['nama'])) {
            $msg = $this->label() . " \"{$same->nama}\" sudah ada.";
            return response()->json(['message' => $msg, 'errors' => ['nama' => [$msg]], 'existing' => $same], 422);
        }

        $row = ($this->modelClass())::create($validated);

        return response()->json(['success' => true, 'message' => $this->label() . " \"{$row->nama}\" ditambahkan.", 'data' => $row]);
    }

    public function update(Request $request, $id)
    {
        $row = $this->newQuery()->findOrFail($id);
        $validated = $request->validate($this->rules(), $this->messages());
        $validated['nama'] = static::cleanNama($validated['nama']);

        if (static::compareKey($validated['nama']) === '') {
            return response()->json(['message' => 'Nama tidak valid.', 'errors' => ['nama' => ['Nama tidak valid.']]], 422);
        }
        if ($same = $this->findSameName($validated['nama'], $row->id)) {
            $msg = "Nama \"{$same->nama}\" sudah dipakai " . strtolower($this->label()) . ' lain. Gunakan Gabungkan jika keduanya sama.';
            return response()->json(['message' => $msg, 'errors' => ['nama' => [$msg]]], 422);
        }

        $row->update($validated);

        return response()->json(['success' => true, 'message' => $this->label() . ' diperbarui.', 'data' => $row]);
    }

    /** Extra reference handling before the plain column update (e.g. pivot tables with a unique key). */
    protected static function beforeMerge(int $sourceId, int $targetId): void
    {
    }

    /**
     * Merge a duplicate into another one: every reference moves to the target, empty contact fields
     * on the target are filled from the source, then the source is removed.
     */
    public function merge(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Hanya Admin yang dapat menggabungkan ' . strtolower($this->label()) . '.'], 403);
        }
        $class = $this->modelClass();
        $table = (new $class)->getTable();
        $request->validate(['target_id' => "required|integer|exists:{$table},id"], [
            'target_id.required' => 'Pilih ' . strtolower($this->label()) . ' tujuan.',
            'target_id.exists' => $this->label() . ' tujuan tidak ditemukan.',
        ]);

        $source = $this->newQuery()->findOrFail($id);
        $target = $this->newQuery()->findOrFail($request->target_id);
        if ($source->id === $target->id) {
            return response()->json(['message' => $this->label() . ' tujuan harus berbeda.'], 422);
        }

        $moved = DB::transaction(function () use ($source, $target) {
            static::beforeMerge($source->id, $target->id);

            $moved = 0;
            foreach (static::references() as [$refTable, $column]) {
                $moved += DB::table($refTable)->where($column, $source->id)->update([$column => $target->id]);
            }

            $fill = [];
            foreach (['alamat', 'telepon', 'email'] as $field) {
                if (blank($target->{$field}) && filled($source->{$field})) {
                    $fill[$field] = $source->{$field};
                }
            }
            if ($fill) {
                $target->update($fill);
            }

            $source->delete();

            return $moved;
        });

        return response()->json([
            'success' => true,
            'message' => "\"{$source->nama}\" digabung ke \"{$target->nama}\" ({$moved} data dipindahkan).",
        ]);
    }

    /**
     * Delete only when unused: the foreign keys would otherwise delete faktur / master faktur data with it.
     */
    public function destroy($id)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Hanya Admin yang dapat menghapus ' . strtolower($this->label()) . '.'], 403);
        }
        $row = $this->newQuery()->findOrFail($id);
        $usage = $this->usageCounts([$row->id])[$row->id];

        if ($this->isUsed($usage)) {
            return response()->json([
                'message' => $this->label() . " masih dipakai di {$this->usageText($usage)}, tidak bisa dihapus. Gunakan Gabungkan jika ini nama ganda.",
            ], 422);
        }

        $row->delete();

        return response()->json(['success' => true, 'message' => $this->label() . " \"{$row->nama}\" dihapus."]);
    }
}
