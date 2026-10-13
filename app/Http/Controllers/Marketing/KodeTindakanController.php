<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\KodeTindakan;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KodeTindakanController extends Controller
{
    /**
     * Get connected obats for a kode tindakan (AJAX)
     */
    public function getObats($id)
    {
        $kodeTindakan = \App\Models\ERM\KodeTindakan::with(['obats'])->findOrFail($id);
        $obats = [];
        foreach ($kodeTindakan->obats as $obat) {
            $pivot = $obat->pivot;
            $obats[] = [
                'id' => $obat->id,
                'nama' => $obat->nama,
                'qty' => $pivot->qty,
                'dosis' => $pivot->dosis,
                'satuan_dosis' => $pivot->satuan_dosis,
                'hpp' => $obat->hpp ?? null,
            ];
        }
        return response()->json($obats);
    }
    /**
     * AJAX search for kode tindakan (for Select2)
     */
    public function search(Request $request)
    {
        $q = $request->input('q');
        $query = KodeTindakan::query();
        // Inactive kode are hidden from pickers; filters (e.g. gallery) can opt in
        if (!$request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }
        if ($q) {
            $query->where(function($w) use ($q) {
                $w->where('nama', 'like', "%{$q}%")->orWhere('kode', 'like', "%{$q}%");
            });
        }
        $results = $query->orderBy('nama')->limit(20)->get(['id', 'nama', 'kode']);
        return response()->json([
            'results' => $results->map(function($item) {
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'kode' => $item->kode,
                    'text' => $item->kode ? $item->nama . ' (' . $item->kode . ')' : $item->nama,
                ];
            })
        ]);
    }
    /**
     * Display the main view for kode tindakan management.
     */
    public function index()
    {
        return view('marketing.kode_tindakan.index', ['actionCounts' => $this->actionCounts(), 'statusCounts' => $this->statusCounts()]);
    }

    /** Aktif / nonaktif / semua, for the status buttons */
    private function statusCounts(): array
    {
        $row = KodeTindakan::query()->selectRaw('COUNT(*) as semua, COALESCE(SUM(is_active = 1), 0) as aktif')->first();

        return ['active' => (int) $row->aktif, 'inactive' => (int) $row->semua - (int) $row->aktif, 'all' => (int) $row->semua];
    }

    /**
     * Switch one tindakan aktif / nonaktif from the list.
     */
    public function toggleActive($id)
    {
        $kodeTindakan = KodeTindakan::findOrFail($id);
        $kodeTindakan->update(['is_active' => !$kodeTindakan->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => (bool) $kodeTindakan->is_active,
            'warning' => $kodeTindakan->is_active ? null : self::paketAktifWarning([$kodeTindakan->id]),
        ]);
    }

    /**
     * "Data yang perlu action": data problems shown as cards above the table. Nothing is deleted
     * automatically; each card filters the table so the user can fix or deactivate the rows.
     * key => [label, hint, filter]
     */
    private function actionChecks(): array
    {
        return [
            'old' => ['Nama "-old ..."', 'sisa import lama; gabungkan atau nonaktifkan',
                fn ($q) => $q->where('nama', 'like', '-old %')],
            'aktif_tanpa_obat' => ['Aktif tanpa obat/BHP', 'biaya bahan paket jadi tidak lengkap',
                fn ($q) => $q->where('is_active', true)->doesntHave('obats')],
            'aktif_tanpa_paket' => ['Aktif belum dipakai paket', 'tidak bisa dijual sebelum masuk paket',
                fn ($q) => $q->where('is_active', true)->doesntHave('tindakans')],
            'nonaktif_di_paket' => ['Nonaktif tapi masih di paket aktif', 'keluarkan dari paket atau aktifkan lagi',
                fn ($q) => $q->where('is_active', false)->whereHas('tindakans', fn ($t) => $t->where('is_active', true))],
        ];
    }

    private function actionCounts(): array
    {
        $out = [];
        foreach ($this->actionChecks() as $key => [$label, $hint, $filter]) {
            $out[$key] = ['label' => $label, 'hint' => $hint, 'count' => $filter(KodeTindakan::query())->count()];
        }
        return $out;
    }

    /**
     * Tindakan list for the DataTable: plain data, the page renders it. Search covers nama, kode and obat;
     * the card / status counts are only sent when asked (?counts=1: first load and after a change).
     */
    public function data(Request $request)
    {
        $query = KodeTindakan::with(['obats' => function($q) {
            $q->select('erm_obat.id', 'nama', 'satuan');
        }, 'tindakans:id,nama,is_active'])->select('erm_kode_tindakan.*')->withCount(['obats', 'tindakans']);

        // Apply status filter if provided: 'active', 'inactive', or absent for all
        $status = $request->input('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        // Apply obat presence filter: 'has' => has obat, 'none' => no obat
        $obatsFilter = $request->input('obats_filter');
        if ($obatsFilter === 'has') {
            $query->has('obats');
        } elseif ($obatsFilter === 'none') {
            $query->doesntHave('obats');
        }
        // "Data yang perlu action" card filter
        $checks = $this->actionChecks();
        if (isset($checks[$request->input('action')])) {
            $checks[$request->input('action')][2]($query);
        }
        // Paket usage filter: 'used' => in at least one paket tindakan, 'unused' => in none
        $paketFilter = $request->input('paket_filter');
        if ($paketFilter === 'used') {
            $query->has('tindakans');
        } elseif ($paketFilter === 'unused') {
            $query->doesntHave('tindakans');
        }

        $q = trim((string) $request->input('search.value'));
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('erm_kode_tindakan.nama', 'like', "%{$q}%")
                    ->orWhere('erm_kode_tindakan.kode', 'like', "%{$q}%")
                    ->orWhereHas('obats', fn ($o) => $o->where('erm_obat.nama', 'like', "%{$q}%"));
            });
        }

        $table = DataTables::eloquent($query)
            ->only(['id', 'kode', 'nama', 'is_active', 'obat', 'paket', 'paket_count'])
            // qty without trailing zeros (1.00 -> 1, 0.50 -> 0.5); unit from the pivot, else the obat's
            ->addColumn('obat', fn ($row) => $row->obats->map(fn ($obat) => [
                'nama' => $obat->nama,
                'qty' => rtrim(rtrim(number_format((float) $obat->pivot->qty, 2, '.', ''), '0'), '.'),
                'satuan' => strtolower(trim((string) ($obat->pivot->satuan_dosis ?: $obat->satuan))),
            ])->values()->all())
            ->addColumn('paket', fn ($row) => $row->tindakans->sortBy('nama')->take(4)
                ->map(fn ($p) => ['nama' => $p->nama, 'aktif' => (bool) $p->is_active])->values()->all())
            ->addColumn('paket_count', fn ($row) => (int) $row->tindakans_count)
            ->orderColumn('kode', 'erm_kode_tindakan.kode $1')
            ->orderColumn('nama', 'erm_kode_tindakan.nama $1')
            ->orderColumn('paket_count', 'tindakans_count $1')
            ->orderColumn('is_active', 'erm_kode_tindakan.is_active $1')
            // Rendered and escaped by the page
            ->escapeColumns([]);

        if ($request->boolean('counts')) {
            $table->with('action_counts', $this->actionCounts())->with('status_counts', $this->statusCounts());
        }

        return $table->make(true);
    }

    /**
     * Store a new kode tindakan (AJAX).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'nullable|string|unique:erm_kode_tindakan,kode',
            'nama' => 'required|string',
            'is_active' => 'nullable|boolean',
            'obats' => 'array',
            'obats.*.obat_id' => 'required|distinct|exists:erm_obat,id',
            'obats.*.qty' => 'required|numeric|min:0.01',
            'obats.*.dosis' => 'nullable|string',
            'obats.*.satuan_dosis' => 'nullable|string',
            'kategori_ids' => 'array',
            'kategori_ids.*' => 'exists:erm_kategori_tindakan,id',
        ], [
            'obats.*.obat_id.distinct' => 'Obat yang sama tidak boleh ditambahkan lebih dari sekali.',
        ]);
        $kodeTindakan = DB::transaction(function() use ($request, $validated) {
            $kodeTindakan = KodeTindakan::create($validated);
            // Attach obat to pivot
            if ($request->has('obats')) {
                $pivotData = [];
                foreach ($request->obats as $obat) {
                    $pivotData[$obat['obat_id']] = [
                        'qty' => floatval($obat['qty']),
                        'dosis' => $obat['dosis'] ?? null,
                        'satuan_dosis' => $obat['satuan_dosis'] ?? null,
                    ];
                }
                $kodeTindakan->obats()->attach($pivotData);
            }
            // Attach categories if provided
            if ($request->has('kategori_ids')) {
                $kodeTindakan->kategoris()->sync($request->input('kategori_ids', []));
            }
            return $kodeTindakan;
        });
        return response()->json(['success' => true, 'data' => $kodeTindakan]);
    }

    /**
     * Update an existing kode tindakan (AJAX).
     */
    public function update(Request $request, $id)
    {
        $kodeTindakan = KodeTindakan::findOrFail($id);
        $validated = $request->validate([
            'kode' => 'nullable|string|unique:erm_kode_tindakan,kode,' . $id,
            'nama' => 'required|string',
            'is_active' => 'nullable|boolean',
            'obats' => 'array',
            'obats.*.obat_id' => 'required|distinct|exists:erm_obat,id',
            'obats.*.qty' => 'required|numeric|min:0.01',
            'obats.*.dosis' => 'nullable|string',
            'obats.*.satuan_dosis' => 'nullable|string',
            'kategori_ids' => 'array',
            'kategori_ids.*' => 'exists:erm_kategori_tindakan,id',
        ], [
            'obats.*.obat_id.distinct' => 'Obat yang sama tidak boleh ditambahkan lebih dari sekali.',
        ]);
        $wasActive = (bool) $kodeTindakan->is_active;
        DB::transaction(function() use ($request, $validated, $kodeTindakan) {
            $kodeTindakan->update($validated);
            // Sync obat to pivot
            $pivotData = [];
            if ($request->has('obats')) {
                foreach ($request->obats as $obat) {
                    $pivotData[$obat['obat_id']] = [
                        'qty' => floatval($obat['qty']),
                        'dosis' => $obat['dosis'] ?? null,
                        'satuan_dosis' => $obat['satuan_dosis'] ?? null,
                    ];
                }
            }
            $kodeTindakan->obats()->sync($pivotData);
            // An empty multi-select is not serialized, so a missing key means "no kategori"
            $kodeTindakan->kategoris()->sync($request->input('kategori_ids', []));
        });
        $warning = $wasActive && !$kodeTindakan->is_active ? self::paketAktifWarning([$kodeTindakan->id]) : null;
        return response()->json(['success' => true, 'data' => $kodeTindakan, 'warning' => $warning]);
    }

    /**
     * Deactivating a tindakan does not remove it from the paket that contain it: those paket keep using
     * it (obat and biaya bahan). Returns a warning naming the active paket, or null.
     */
    private static function paketAktifWarning(array $kodeIds): ?string
    {
        $paket = \App\Models\ERM\Tindakan::where('is_active', true)
            ->whereHas('kodeTindakans', fn($q) => $q->whereIn('erm_kode_tindakan.id', $kodeIds))
            ->orderBy('nama')
            ->pluck('nama');
        if ($paket->isEmpty()) {
            return null;
        }
        return 'Tindakan nonaktif ini masih menjadi isi ' . $paket->count() . ' paket tindakan aktif: '
            . $paket->take(10)->implode(', ') . ($paket->count() > 10 ? ', ...' : '')
            . '. Paket tersebut tetap memakai tindakan ini; keluarkan dari paket jika memang tidak dipakai lagi.';
    }

    /**
     * Import kode tindakan from CSV upload.
     * Expected CSV: single column containing the "nama" of kode tindakan (header optional).
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv');
        $path = $file->getRealPath();

        $created = 0;
        $skipped = 0;
        $errors = [];
        $created_items = [];
        $skipped_items = [];

        DB::beginTransaction();
        try {
            if (($handle = fopen($path, 'r')) !== false) {
                $rowIndex = 0;
                while (($data = fgetcsv($handle, 0, ',')) !== false) {
                    $rowIndex++;
                    // skip empty rows
                    if (!isset($data[0]) || trim($data[0]) === '') {
                        continue;
                    }
                    // Accept header names like 'nama' or 'nama kode tindakan' on first row
                    $namaRaw = trim($data[0]);
                    if ($rowIndex === 1) {
                        $lower = strtolower($namaRaw);
                        if (in_array($lower, ['nama', 'nama kode tindakan', 'nama_kode_tindakan', 'nama_kode'])) {
                            // header row: skip
                            continue;
                        }
                    }

                    $nama = $namaRaw;
                    // Skip names that already exist: the existing record keeps its obat/tindakan links
                    if (KodeTindakan::where('nama', $nama)->exists()) {
                        $skipped++;
                        $skipped_items[] = ['row' => $rowIndex, 'nama' => $nama, 'reason' => 'Nama sudah ada'];
                        continue;
                    }

                    try {
                        // The database column `kode` is nullable; for CSV import we leave it null
                        $model = KodeTindakan::create([
                            'kode' => null,
                            'nama' => $nama,
                            'is_active' => true,
                        ]);
                        $created++;
                        $created_items[] = ['row' => $rowIndex, 'id' => $model->id, 'nama' => $model->nama];
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowIndex}: " . $e->getMessage();
                        $skipped++;
                        $skipped_items[] = ['row' => $rowIndex, 'nama' => $nama, 'reason' => $e->getMessage()];
                    }
                }
                fclose($handle);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Import gagal: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'created_items' => $created_items,
            'skipped_items' => $skipped_items,
        ]);
    }

    /**
     * Bulk set is_active for selected ids or by date range
     */
    public function bulkSetActive(Request $request)
    {
        $ids = $request->input('ids', []);
        $setActive = filter_var($request->input('set_active'), FILTER_VALIDATE_BOOLEAN);
        $start = $request->input('start');
        $end = $request->input('end');

        DB::beginTransaction();
        try {
            if (is_array($ids) && count($ids)) {
                $updated = KodeTindakan::whereIn('id', $ids)->update(['is_active' => $setActive]);
            } else {
                if ($start && $end) {
                    $s = Carbon::parse($start)->startOfDay();
                    $e = Carbon::parse($end)->endOfDay();
                    $updated = KodeTindakan::whereBetween('created_at', [$s, $e])->update(['is_active' => $setActive]);
                } else {
                    return response()->json(['success' => false, 'message' => 'Tidak ada id terpilih dan rentang tanggal tidak diberikan'], 422);
                }
            }
            DB::commit();
            $warning = !$setActive && is_array($ids) && count($ids) ? self::paketAktifWarning($ids) : null;
            return response()->json(['success' => true, 'updated' => $updated, 'warning' => $warning]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show a kode tindakan with its related obat for edit modal
     */
    public function show($id)
    {
        $kodeTindakan = KodeTindakan::with(['obats' => function($q) {
            $q->select('erm_obat.id', 'nama', 'satuan');
        }, 'kategoris:id,nama'])->findOrFail($id);
        $obats = [];
        foreach ($kodeTindakan->obats as $obat) {
            $pivot = $obat->pivot;
            $obats[] = [
                'obat_id' => $obat->id,
                'obat_nama' => $obat->nama,
                'qty' => $pivot->qty,
                'dosis' => $pivot->dosis,
                'satuan_dosis' => $pivot->satuan_dosis,
                'satuan' => $obat->satuan ?? null,
            ];
        }
        $data = $kodeTindakan->toArray();
        $data['obats'] = $obats;
        return response()->json($data);
    }

    /**
     * Delete a kode tindakan (AJAX).
     */
    public function destroy($id)
    {
        $kodeTindakan = KodeTindakan::findOrFail($id);
        // FKs on these tables cascade, so deleting would silently wipe history
        $usage = [
            'paket tindakan' => $kodeTindakan->tindakans()->count(),
            'riwayat tindakan' => DB::table('erm_riwayat_tindakan_obat')->where('kode_tindakan_id', $id)->count(),
            'SPK' => DB::table('erm_spk_tindakan_items')->where('kode_tindakan_id', $id)->count(),
        ];
        $usedIn = [];
        foreach ($usage as $label => $count) {
            if ($count > 0) {
                $usedIn[] = "{$count} {$label}";
            }
        }
        if ($usedIn) {
            return response()->json([
                'success' => false,
                'message' => 'Tindakan masih dipakai di ' . implode(', ', $usedIn) . '. Nonaktifkan saja agar riwayat tetap utuh.',
            ], 422);
        }
        $kodeTindakan->delete();
        return response()->json(['success' => true]);
    }
}
