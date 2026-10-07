<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\ZatAktif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Zat aktif master (managed from the Master Obat page).
 * Deleting a zat aktif cascades into erm_kandungan_obat and erm_alergi (patient allergies),
 * so delete is only allowed when it is not used anywhere; duplicates are merged instead.
 * Every change (add, rename, merge, delete) is Admin only: a zat aktif is shared by many obat and patient allergies.
 */
class ZatAktifController extends Controller
{
    private const MESSAGES = [
        'nama.required' => 'Nama zat aktif wajib diisi.',
        'nama.max' => 'Nama zat aktif terlalu panjang (maksimal :max karakter).',
    ];

    private function isAdmin(): bool
    {
        return (bool) optional(Auth::user())->hasAnyRole(['Admin']);
    }

    /**
     * Ids of zat aktif whose name has the same comparison key as another one.
     */
    private function duplicateIds(): array
    {
        return ZatAktif::query()->get(['id', 'nama'])
            ->groupBy(fn ($z) => ZatAktif::compareKey((string) $z->nama))
            ->filter(fn ($group, $key) => $key === '' || $group->count() > 1)
            ->flatten()
            ->pluck('id')
            ->all();
    }

    /**
     * Server-side list for the DataTable and for select2 (search[value], start, length).
     * Optional filter `pemakaian`: dipakai | tidak_dipakai | ganda.
     */
    public function index(Request $request)
    {
        $query = ZatAktif::query()
            ->withCount([
                'obats as obat_count' => fn ($q) => $q->withoutGlobalScope('active'),
                'alergi as alergi_count',
            ]);

        $total = ZatAktif::count();

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $query->where('nama', 'LIKE', "%{$search}%");
        }

        // Count inactive obat too (Obat's global scope would hide them), same as the delete guard
        $allObat = fn ($q) => $q->withoutGlobalScope('active');
        switch ($request->input('pemakaian')) {
            case 'dipakai':
                $query->where(fn ($q) => $q->whereHas('obats', $allObat)->orHas('alergi'));
                break;
            case 'tidak_dipakai':
                $query->whereDoesntHave('obats', $allObat)->doesntHave('alergi');
                break;
            case 'ganda':
                $query->whereIn('id', $this->duplicateIds());
                break;
        }

        $recordsFiltered = (clone $query)->count();

        $orderable = [1 => 'nama', 2 => 'obat_count'];
        $orderCol = (int) $request->input('order.0.column', 1);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($orderable[$orderCol] ?? 'nama', $orderDir);
        if (($orderable[$orderCol] ?? 'nama') !== 'nama') {
            $query->orderBy('nama');
        }

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length > 0 ? min($length, 200) : 10;

        $items = $query->skip($start)->take($length)->get(['id', 'nama'])
            ->map(fn ($z) => [
                'id' => $z->id,
                'nama' => $z->nama,
                'obat_count' => (int) $z->obat_count,
                'alergi_count' => (int) $z->alergi_count,
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $total,
            'recordsFiltered' => $recordsFiltered,
            'data' => $items,
            'duplicate_count' => $request->boolean('with_summary') ? count($this->duplicateIds()) : null,
        ]);
    }

    /**
     * Add a zat aktif. Rejects names that already exist after normalization.
     */
    public function store(Request $request)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Hanya Admin yang dapat menambah zat aktif.'], 403);
        }
        $request->validate(['nama' => 'required|string|max:191'], self::MESSAGES);
        $nama = ZatAktif::cleanNama($request->nama);

        if ($nama === '' || ZatAktif::compareKey($nama) === '') {
            return response()->json(['message' => 'Nama zat aktif tidak valid.', 'errors' => ['nama' => ['Nama zat aktif tidak valid.']]], 422);
        }
        if ($same = ZatAktif::findSameName($nama)) {
            $msg = "Zat aktif \"{$same->nama}\" sudah ada.";
            return response()->json(['message' => $msg, 'errors' => ['nama' => [$msg]], 'existing' => $same], 422);
        }

        $zat = ZatAktif::create(['nama' => $nama]);

        return response()->json(['success' => true, 'message' => "Zat aktif \"{$zat->nama}\" ditambahkan.", 'data' => $zat]);
    }

    /**
     * Rename. The new name is shown on every obat and allergy that uses it.
     */
    public function update(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Hanya Admin yang dapat mengubah nama zat aktif.'], 403);
        }
        $zat = ZatAktif::findOrFail($id);
        $request->validate(['nama' => 'required|string|max:191'], self::MESSAGES);
        $nama = ZatAktif::cleanNama($request->nama);

        if ($nama === '' || ZatAktif::compareKey($nama) === '') {
            return response()->json(['message' => 'Nama zat aktif tidak valid.'], 422);
        }
        if ($same = ZatAktif::findSameName($nama, $zat->id)) {
            return response()->json([
                'message' => "Nama \"{$same->nama}\" sudah dipakai zat aktif lain. Gunakan Gabungkan jika keduanya sama.",
            ], 422);
        }

        $zat->update(['nama' => $nama]);

        return response()->json(['success' => true, 'message' => 'Nama zat aktif diperbarui.', 'data' => $zat]);
    }

    /**
     * Merge a duplicate into another zat aktif: its obat and allergy links move to the target, then it is removed.
     */
    public function merge(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Hanya Admin yang dapat menggabungkan zat aktif.'], 403);
        }
        $request->validate(['target_id' => 'required|integer|exists:erm_zataktif,id'], [
            'target_id.required' => 'Pilih zat aktif tujuan.',
            'target_id.exists' => 'Zat aktif tujuan tidak ditemukan.',
        ]);

        $source = ZatAktif::findOrFail($id);
        $target = ZatAktif::findOrFail($request->target_id);
        if ($source->id === $target->id) {
            return response()->json(['message' => 'Zat aktif tujuan harus berbeda.'], 422);
        }

        $moved = DB::transaction(function () use ($source, $target) {
            // Obat links: add target where missing, then drop the source links
            $obatIds = DB::table('erm_kandungan_obat')->where('zataktif_id', $source->id)->pluck('obat_id');
            $already = DB::table('erm_kandungan_obat')->where('zataktif_id', $target->id)->whereIn('obat_id', $obatIds)->pluck('obat_id');
            $now = now();
            $insert = $obatIds->diff($already)->unique()->map(fn ($obatId) => [
                'obat_id' => $obatId, 'zataktif_id' => $target->id, 'created_at' => $now, 'updated_at' => $now,
            ])->values()->all();
            if (!empty($insert)) {
                DB::table('erm_kandungan_obat')->insert($insert);
            }
            DB::table('erm_kandungan_obat')->where('zataktif_id', $source->id)->delete();

            // Patient allergies point to the target from now on. A patient who already has the target keeps
            // that row and loses the source one, otherwise they would have the same allergy twice.
            $pasienWithTarget = DB::table('erm_alergi')->where('zataktif_id', $target->id)->whereNotNull('pasien_id')->pluck('pasien_id');
            DB::table('erm_alergi')->where('zataktif_id', $source->id)->whereIn('pasien_id', $pasienWithTarget)->delete();
            $alergi = DB::table('erm_alergi')->where('zataktif_id', $source->id)->update(['zataktif_id' => $target->id]);

            $source->delete();

            return ['obat' => $obatIds->count(), 'alergi' => $alergi];
        });

        return response()->json([
            'success' => true,
            'message' => "\"{$source->nama}\" digabung ke \"{$target->nama}\" ({$moved['obat']} obat, {$moved['alergi']} alergi dipindahkan).",
        ]);
    }

    /**
     * Delete only when unused: removing a used zat aktif would also delete obat contents and patient allergies.
     */
    public function destroy($id)
    {
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Hanya Admin yang dapat menghapus zat aktif.'], 403);
        }
        $zat = ZatAktif::withCount(['obats as obat_count' => fn ($q) => $q->withoutGlobalScope('active'), 'alergi as alergi_count'])
            ->findOrFail($id);

        if ($zat->obat_count > 0 || $zat->alergi_count > 0) {
            return response()->json([
                'message' => "Zat aktif masih dipakai di {$zat->obat_count} obat dan {$zat->alergi_count} data alergi pasien, tidak bisa dihapus. Gunakan Gabungkan jika ini nama ganda.",
            ], 422);
        }

        $zat->delete();

        return response()->json(['success' => true, 'message' => "Zat aktif \"{$zat->nama}\" dihapus."]);
    }
}
