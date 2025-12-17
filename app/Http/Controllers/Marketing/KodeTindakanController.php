<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\KodeTindakan;
use Yajra\DataTables\DataTables;
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
                'hpp_jual' => $obat->hpp_jual ?? null,
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
        if ($q) {
            $query->where('nama', 'like', "%{$q}%")->orWhere('kode', 'like', "%{$q}%");
        }
        $results = $query->orderBy('nama')->limit(20)->get(['id', 'nama', 'kode']);
        return response()->json([
            'results' => $results->map(function($item) {
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'kode' => $item->kode,
                    'text' => $item->nama . ' (' . $item->kode . ')',
                ];
            })
        ]);
    }
    /**
     * Display the main view for kode tindakan management.
     */
    public function index()
    {
        return view('marketing.kode_tindakan.index');
    }

    /**
     * Return DataTable JSON for kode tindakan.
     */
    public function data(Request $request)
    {
        $query = KodeTindakan::with(['obats' => function($q) {
            // include `satuan` so the listing can show units when building the summary
            $q->select('erm_obat.id', 'nama', 'satuan');
        }])->select('erm_kode_tindakan.*')->withCount('obats');

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

        return DataTables::of($query)
            ->addColumn('obats_summary', function($row) {
                // Build summary like: "Paracetamol (0.5 Pcs), Ibuprofen (1 Tablet)"
                $parts = [];
                if ($row->obats && $row->obats->count()) {
                    foreach ($row->obats as $obat) {
                        $pivot = $obat->pivot;
                        $qty = isset($pivot->qty) ? (float)$pivot->qty : 0;
                        $satuan = $pivot->satuan_dosis ?? ($obat->satuan ?? '');
                        // Normalize unit to lowercase for display (e.g., 'Pcs' -> 'pcs', 'Ml' -> 'ml')
                        $unitDisplay = $satuan ? strtolower(trim($satuan)) : '';
                        // Format qty: remove trailing zeros (1.00 -> 1, 0.50 -> 0.5)
                        $qtyStr = rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
                        $label = htmlspecialchars($obat->nama, ENT_QUOTES, 'UTF-8');
                        if ($unitDisplay) {
                            $parts[] = $label . ' (' . $qtyStr . ' ' . htmlspecialchars($unitDisplay, ENT_QUOTES, 'UTF-8') . ')';
                        } else {
                            $parts[] = $label . ' (' . $qtyStr . ')';
                        }
                    }
                }
                // Return HTML with <br> separators so each obat appears on its own line
                return implode('<br>', $parts);
            })
            ->addColumn('status', function($row) {
                if ($row->is_active) {
                    return '<span class="badge badge-success">Aktif</span>';
                }
                return '<span class="badge badge-secondary">Nonaktif</span>';
            })
            ->editColumn('nama', function($row) {
                // show blinking warning icon if this kode tindakan has no obat connected
                $name = htmlspecialchars($row->nama, ENT_QUOTES, 'UTF-8');
                if (isset($row->obats_count) && intval($row->obats_count) === 0) {
                    return '<i class="fa fa-exclamation-triangle text-warning blink-warning" title="Belum ada obat terhubung"></i> ' . $name;
                }
                return $name;
            })
            ->rawColumns(['obats_summary','status','nama'])
            ->make(true);
    }

    /**
     * Store a new kode tindakan (AJAX).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|unique:erm_kode_tindakan,kode',
            'nama' => 'required|string',
            'is_active' => 'nullable|boolean',
            'hpp' => 'nullable|numeric',
            'harga_jasmed' => 'nullable|numeric',
            'harga_jual' => 'nullable|numeric',
            'harga_bottom' => 'nullable|numeric',
            'obats' => 'array',
            'obats.*.obat_id' => 'required|exists:erm_obat,id',
            'obats.*.qty' => 'required|numeric|min:0.01',
            'obats.*.dosis' => 'nullable|string',
            'obats.*.satuan_dosis' => 'nullable|string',
        ]);
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
        return response()->json(['success' => true, 'data' => $kodeTindakan]);
    }

    /**
     * Update an existing kode tindakan (AJAX).
     */
    public function update(Request $request, $id)
    {
        $kodeTindakan = KodeTindakan::findOrFail($id);
        $validated = $request->validate([
            'kode' => 'required|string|unique:erm_kode_tindakan,kode,' . $id,
            'nama' => 'required|string',
            'is_active' => 'nullable|boolean',
            'hpp' => 'nullable|numeric',
            'harga_jasmed' => 'nullable|numeric',
            'harga_jual' => 'nullable|numeric',
            'harga_bottom' => 'nullable|numeric',
            'obats' => 'array',
            'obats.*.obat_id' => 'required|exists:erm_obat,id',
            'obats.*.qty' => 'required|numeric|min:0.01',
            'obats.*.dosis' => 'nullable|string',
            'obats.*.satuan_dosis' => 'nullable|string',
        ]);
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
        return response()->json(['success' => true, 'data' => $kodeTindakan]);
    }

    /**
     * Set all kode tindakan as inactive (AJAX).
     */
    public function makeAllInactive(Request $request)
    {
        // mass update: set is_active = false for all records
        KodeTindakan::query()->update(['is_active' => false]);
        return response()->json(['success' => true, 'message' => 'Semua kode tindakan telah dinonaktifkan']);
    }

    /**
     * Set all kode tindakan as active (AJAX).
     */
    public function makeAllActive(Request $request)
    {
        // mass update: set is_active = true for all records
        KodeTindakan::query()->update(['is_active' => true]);
        return response()->json(['success' => true, 'message' => 'Semua kode tindakan telah diaktifkan']);
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
        $renamed_items = [];

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
                    // avoid duplicate by exact name — if exists, rename the old one with prefix '-old '
                    $exists = KodeTindakan::where('nama', $nama)->first();
                    if ($exists) {
                        $originalName = $exists->nama;
                        $newName = '-old ' . $originalName;
                        $counter = 0;
                        while (KodeTindakan::where('nama', $newName)->exists()) {
                            $counter++;
                            $newName = '-old ' . $originalName . ' ' . $counter;
                        }
                        try {
                            $oldId = $exists->id;
                            $exists->update(['nama' => $newName]);
                            $renamed_items[] = ['old_id' => $oldId, 'old_name' => $originalName, 'new_name' => $newName];
                        } catch (\Exception $e) {
                            $errors[] = "Row {$rowIndex}: Failed to rename existing record: " . $e->getMessage();
                            $skipped++;
                            $skipped_items[] = ['row' => $rowIndex, 'nama' => $nama, 'reason' => 'Failed to rename existing: '.$e->getMessage()];
                            continue;
                        }
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
     * Return kode tindakan created within a date range for preview (AJAX)
     */
    public function getByDate(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');

        $query = KodeTindakan::query();
        if ($start && $end) {
            try {
                $s = Carbon::parse($start)->startOfDay();
                $e = Carbon::parse($end)->endOfDay();
                $query->whereBetween('created_at', [$s, $e]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Tanggal tidak valid'], 422);
            }
        }

        $list = $query->orderBy('created_at', 'asc')->get(['id', 'nama', 'created_at', 'is_active']);
        return response()->json(['success' => true, 'data' => $list]);
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
            return response()->json(['success' => true, 'updated' => $updated]);
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
        }])->findOrFail($id);
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
        $kodeTindakan->delete();
        return response()->json(['success' => true]);
    }
}
