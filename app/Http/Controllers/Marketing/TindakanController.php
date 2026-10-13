<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\Tindakan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class TindakanController extends Controller
{
    /**
     * Display a listing of tindakan.
     */
    public function index()
    {
        return view('marketing.tindakan.index', [
            'actionCounts' => $this->actionCounts(),
            'statusCounts' => $this->statusCounts(),
            'spesialisasi' => \App\Models\ERM\Spesialisasi::orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    /** Aktif / nonaktif / semua, for the status buttons */
    private function statusCounts(): array
    {
        $row = Tindakan::query()->selectRaw('COUNT(*) as semua, COALESCE(SUM(is_active = 1), 0) as aktif')->first();

        return ['active' => (int) $row->aktif, 'inactive' => (int) $row->semua - (int) $row->aktif, 'all' => (int) $row->semua];
    }

    /**
     * "Data yang perlu action" for active paket: shown as cards; each card filters the table.
     * key => [label, hint, filter]
     */
    private function actionChecks(): array
    {
        return [
            'tanpa_isi' => ['Paket aktif belum ada tindakan', 'obat/BHP tidak tercatat saat dipakai',
                fn ($q) => $q->where('is_active', true)->doesntHave('kodeTindakans')],
            'isi_nonaktif' => ['Paket aktif berisi tindakan nonaktif', 'ganti atau keluarkan tindakan tersebut',
                fn ($q) => $q->where('is_active', true)->whereHas('kodeTindakans', fn ($k) => $k->where('is_active', false))],
            'tanpa_harga' => ['Paket aktif belum ada harga', 'tertagih Rp 0; ajukan harga',
                fn ($q) => $q->where('is_active', true)->where(fn ($h) => $h->whereNull('harga')->orWhere('harga', '<=', 0))],
            'nama_lama' => ['Nama "(menu lama)" / "- copy"', 'sisa import lama; nonaktifkan atau ganti nama',
                fn ($q) => $q->where(fn ($n) => $n->where('nama', 'like', '%(menu lama)%')->orWhere('nama', 'like', '% - copy%'))],
        ];
    }

    private function actionCounts(): array
    {
        $out = [];
        foreach ($this->actionChecks() as $key => [$label, $hint, $filter]) {
            $out[$key] = ['label' => $label, 'hint' => $hint, 'count' => $filter(Tindakan::query())->count()];
        }
        return $out;
    }

    /**
     * Paket list for the DataTable: plain data, the page renders it. Search covers nama, spesialis and isi;
     * the card / status counts are only sent when asked (?counts=1: first load and after a change).
     */
    public function getTindakanData(Request $request)
    {
        $query = Tindakan::query()
            ->with(['spesialis:id,nama', 'kodeTindakans:id,nama,is_active'])
            ->select('erm_tindakan.*')
            ->withCount('kodeTindakans')
            ->addSelect(['pengajuan_harga' => \App\Models\ERM\TindakanHargaPengajuan::select('harga_baru')
                ->whereColumn('tindakan_id', 'erm_tindakan.id')->where('status', 'menunggu')->latest('id')->limit(1)]);

        if ($request->filled('spesialis_id')) {
            $query->where('spesialis_id', $request->input('spesialis_id'));
        }
        match ($request->input('status')) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            default => null,
        };
        match ($request->input('jenis')) {
            'slimming' => $query->where('is_slimming', true),
            'vaksin' => $query->where('is_vaksin', true),
            'multivisit' => $query->where('multi_visit_total', '>', 1),
            'diskon' => $query->where('diskon_active', true)->where('harga_diskon', '>', 0),
            default => null,
        };
        // "Data yang perlu action" card filter
        $checks = $this->actionChecks();
        if (isset($checks[$request->input('action')])) {
            $checks[$request->input('action')][2]($query);
        }
        $q = trim((string) $request->input('search.value'));
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('erm_tindakan.nama', 'like', "%{$q}%")
                    ->orWhereHas('spesialis', fn ($s) => $s->where('nama', 'like', "%{$q}%"))
                    ->orWhereHas('kodeTindakans', fn ($k) => $k->where('erm_kode_tindakan.nama', 'like', "%{$q}%"));
            });
        }

        $table = DataTables::eloquent($query)
            ->only(['id', 'nama', 'spesialis_nama', 'harga', 'harga_diskon', 'diskon_active', 'harga_paket_visit', 'multi_visit_total',
                'is_slimming', 'is_vaksin', 'is_active', 'pengajuan_harga', 'isi', 'isi_count', 'isi_nonaktif'])
            ->addColumn('spesialis_nama', fn ($row) => optional($row->spesialis)->nama)
            ->addColumn('isi', fn ($row) => $row->kodeTindakans->pluck('nama')->sort()->values()->take(6)->all())
            ->addColumn('isi_count', fn ($row) => (int) $row->kode_tindakans_count)
            ->addColumn('isi_nonaktif', fn ($row) => $row->kodeTindakans->where('is_active', false)->count())
            ->orderColumn('nama', 'erm_tindakan.nama $1')
            ->orderColumn('spesialis_nama', '(SELECT nama FROM erm_spesialisasis WHERE erm_spesialisasis.id = erm_tindakan.spesialis_id) $1')
            ->orderColumn('harga', 'erm_tindakan.harga $1')
            ->orderColumn('is_active', 'erm_tindakan.is_active $1')
            // Rendered and escaped by the page
            ->escapeColumns([]);

        if ($request->boolean('counts')) {
            $table->with('action_counts', $this->actionCounts())->with('status_counts', $this->statusCounts());
        }

        return $table->make(true);
    }

    /**
     * Store a newly created tindakan or update an existing one.
     */
    public function store(Request $request)
    {
        $isNew = !$request->filled('id');
        // Rows added in the form but left empty send an empty id; ignore them instead of failing validation
        $request->merge(['kode_tindakan_ids' => array_values(array_unique(array_filter((array) $request->input('kode_tindakan_ids', []))))]);
        // Prices only go through approval (Harga Paket Tindakan); a new paket's prices become its first proposal
        $hargaRules = $isNew ? [
            'harga' => 'required|numeric|min:1|max:9999999999',
            'harga_diskon' => 'nullable|numeric|min:1|max:9999999999|lt:harga|required_if_accepted:diskon_active',
            'diskon_active' => 'nullable|boolean',
            'harga_paket_visit' => 'nullable|numeric|min:1|max:9999999999',
        ] : [];
        $request->validate(array_merge([
            'id' => 'nullable|exists:erm_tindakan,id',
            'nama' => ['required', 'string', 'max:255',
                Rule::unique('erm_tindakan')->ignore($request->id)],
            'deskripsi' => 'nullable|string',
            'spesialis_id' => 'required|exists:erm_spesialisasis,id',
            // A paket is what gets sold, so it must contain at least one tindakan (even if only one)
            'kode_tindakan_ids' => 'required|array|min:1',
            'kode_tindakan_ids.*' => 'exists:erm_kode_tindakan,id',
            'is_active' => 'nullable|boolean',
            'is_slimming' => 'nullable|boolean',
            'is_vaksin' => 'nullable|boolean',
            'multi_visit_total' => 'nullable|integer|min:2|max:20',
            'total_rf' => 'nullable|integer|min:0',
            'total_inject' => 'nullable|integer|min:0',
        ], $hargaRules), array_merge([
            'kode_tindakan_ids.required' => 'Paket tindakan harus berisi minimal satu tindakan.',
            'kode_tindakan_ids.min' => 'Paket tindakan harus berisi minimal satu tindakan.',
        ], HargaTindakanController::itemMessages('')));

        $isSlimming = $request->boolean('is_slimming');

        try {
            DB::beginTransaction();
            $tindakan = Tindakan::updateOrCreate(
                ['id' => $request->id],
                array_merge($isNew ? [
                    // Not billable at a price until the first proposal is approved
                    'harga' => 0,
                    'harga_diskon' => null,
                    'diskon_active' => 0,
                    'harga_paket_visit' => null,
                ] : [],
                // The form has no deskripsi field: keep the stored one unless it is sent
                $request->has('deskripsi') ? ['deskripsi' => $request->deskripsi] : [], [
                    'nama' => $request->nama,
                    'spesialis_id' => $request->spesialis_id,
                        // An unchecked checkbox is not sent, so absent means inactive
                        'is_active' => $request->boolean('is_active'),
                        'is_slimming' => $isSlimming,
                        'is_vaksin' => $request->boolean('is_vaksin'),
                        'multi_visit_total' => $request->filled('multi_visit_total') ? $request->integer('multi_visit_total') : null,
                        // Only slimming tindakan carry RF/inject quota
                        'total_rf' => $isSlimming && $request->filled('total_rf') ? $request->integer('total_rf') : null,
                        'total_inject' => $isSlimming && $request->filled('total_inject') ? $request->integer('total_inject') : null,
                ])
            );

            // Sync kode tindakan
            $kodeTindakanIds = $request->input('kode_tindakan_ids', []);
            $tindakan->kodeTindakans()->sync($kodeTindakanIds);

            if ($isNew) {
                HargaTindakanController::ajukan($tindakan, $request->only(['harga', 'harga_diskon', 'diskon_active', 'harga_paket_visit']), 'Harga awal paket tindakan baru');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isNew
                    ? 'Paket tindakan dibuat. Harganya diajukan dan baru berlaku setelah disetujui Admin / Finance.'
                    : 'Paket tindakan diperbarui.',
                'data' => $tindakan
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle active status for a single tindakan.
     */
    public function toggleActive($id)
    {
        try {
            $t = Tindakan::findOrFail($id);
            $t->is_active = !$t->is_active;
            $t->save();
            return response()->json(['success' => true, 'is_active' => (bool)$t->is_active]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get tindakan data by ID.
     */
    public function getTindakan($id)
    {
        $tindakan = Tindakan::with(['spesialis', 'kodeTindakans.obats'])->findOrFail($id);
        $result = $tindakan->toArray();
        $result['kode_tindakan_ids'] = $tindakan->kodeTindakans->pluck('id')->toArray();
        // Add kode_tindakans array for Select2 population
        $result['kode_tindakans'] = $tindakan->kodeTindakans->map(function($kt) {
            // Get connected obats for this kode tindakan
            $obats = $kt->obats->map(function($obat) {
                return [
                    'id' => $obat->id,
                    'nama' => $obat->nama,
                    'kode' => $obat->kode ?? '',
                    'qty' => $obat->pivot->qty ?? null,
                    'dosis' => $obat->pivot->dosis ?? null,
                    'satuan_dosis' => $obat->pivot->satuan_dosis ?? null
                ];
            })->toArray();
            return [
                'id' => $kt->id,
                'text' => $kt->nama ? ($kt->kode ? $kt->kode.' - '.$kt->nama : $kt->nama) : ($kt->kode ?? ''),
                'obats' => $obats
            ];
        })->toArray();
        return response()->json($result);
    }

    /**
     * Import tindakan from CSV.
     * Expected columns: name/nama, harga normal, harga diskon, harga 3x, specialist (name or id)
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

        DB::beginTransaction();
        try {
            if (($handle = fopen($path, 'r')) !== false) {
                $rowIndex = 0;
                $headers = null;
                while (($data = fgetcsv($handle, 0, ',')) !== false) {
                    $rowIndex++;
                    // skip empty rows
                    if (!isset($data[0]) || trim($data[0]) === '') {
                        continue;
                    }

                    // detect header row
                    if ($rowIndex === 1) {
                        $lowerCols = array_map(function($c){ return strtolower(trim($c)); }, $data);
                        // if first row contains known header keywords, treat as header
                        $known = ['nama', 'name', 'harga normal', 'harga_normal', 'harga', 'harga diskon', 'harga_diskon', 'harga paket', 'harga_paket_visit', 'harga 3x', 'harga_3_kali', 'spesialis', 'specialist', 'spesialisasi', 'is_active', 'active', 'aktif', 'is_slimming', 'slimming', 'is_vaksin', 'vaksin', 'multi_visit_total'];
                        $intersect = array_intersect($lowerCols, $known);
                        if (count($intersect) >= 2) {
                            $headers = $lowerCols;
                            continue;
                        }
                    }

                    // Map columns
                    if ($headers) {
                        $cols = $headers;
                        $get = function($names) use ($cols, $data) {
                            foreach ($names as $n) {
                                $idx = array_search($n, $cols);
                                if ($idx !== false && isset($data[$idx])) return trim($data[$idx]);
                            }
                            return null;
                        };
                        $nama = $get(['nama','name','nama tindakan','nama_tindakan']);
                        $harga = $get(['harga normal','harga_normal','harga']);
                        $harga_diskon = $get(['harga diskon','harga_diskon','diskon']);
                        $harga_paket_visit = $get(['harga paket','harga_paket_visit','harga 3x','harga_3_kali','harga3x','harga_3x']);
                        $spesialisRaw = $get(['spesialis','specialist','spesialisasi','spesialis_id']);
                        $multiVisitTotalRaw = $get(['multi_visit_total','jumlah visit paket','visit total']);
                        $isSlimmingRaw = $get(['is_slimming','slimming']);
                        $isVaksinRaw = $get(['is_vaksin','vaksin']);
                        $isActiveRaw = $get(['is_active','active','aktif']);
                    } else {
                        // expected order: nama, harga, harga_diskon, harga_paket_visit, spesialis
                        $nama = isset($data[0]) ? trim($data[0]) : null;
                        $harga = isset($data[1]) ? trim($data[1]) : null;
                        $harga_diskon = isset($data[2]) ? trim($data[2]) : null;
                        $harga_paket_visit = isset($data[3]) ? trim($data[3]) : null;
                        $spesialisRaw = isset($data[4]) ? trim($data[4]) : null;
                        $multiVisitTotalRaw = isset($data[5]) ? trim($data[5]) : null;
                        $isSlimmingRaw = isset($data[6]) ? trim($data[6]) : null;
                        $isVaksinRaw = isset($data[7]) ? trim($data[7]) : null;
                        $isActiveRaw = isset($data[5]) ? trim($data[5]) : null;
                    }

                    if (!$nama) { $skipped++; continue; }

                    // We'll attempt to create even if the same name exists.
                    // If DB enforces uniqueness, we'll append a suffix until it succeeds.
                    // parse number helper: remove currency symbols and thousand separators
                    $parseNumber = function($str) {
                        if ($str === null || $str === '') return null;
                        $s = preg_replace('/[^0-9,\.\-]/', '', $str);
                        // If contains comma and dot, assume dot thousand sep, comma decimal (e.g. 1.234,56)
                        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
                            $s = str_replace('.', '', $s);
                            $s = str_replace(',', '.', $s);
                        } else {
                            // remove thousand dots
                            $s = str_replace('.', '', $s);
                            $s = str_replace(',', '.', $s);
                        }
                        return is_numeric($s) ? floatval($s) : null;
                    };

                    // parse boolean helper: accept 1/0, true/false, yes/no, aktif/nonaktif
                    $parseBool = function($v) {
                        if ($v === null || $v === '') return null;
                        $s = strtolower(trim($v));
                        if (in_array($s, ['1', 'true', 'yes', 'y', 'aktif', 'active'])) return 1;
                        if (in_array($s, ['0', 'false', 'no', 'n', 'nonaktif', 'inactive'])) return 0;
                        return null;
                    };

                    $hargaVal = $parseNumber($harga);
                    $hargaDiskonVal = $parseNumber($harga_diskon);
                    $hargaPaketVisitVal = $parseNumber($harga_paket_visit);
                    $multiVisitTotalVal = null;
                    if ($multiVisitTotalRaw !== null && $multiVisitTotalRaw !== '') {
                        $multiVisitTotalVal = max((int) $multiVisitTotalRaw, 2);
                    }
                    $isSlimmingVal = $parseBool($isSlimmingRaw);
                    $isVaksinVal = $parseBool($isVaksinRaw);

                    // resolve specialist to id
                    $spesialis_id = null;
                    if ($spesialisRaw) {
                        $spesialisRawTrim = trim($spesialisRaw);
                        if (ctype_digit($spesialisRawTrim)) {
                            $sp = \App\Models\ERM\Spesialisasi::find(intval($spesialisRawTrim));
                            if ($sp) $spesialis_id = $sp->id;
                        }
                        if (!$spesialis_id) {
                            $sp = \App\Models\ERM\Spesialisasi::whereRaw('LOWER(nama) = ?', [strtolower($spesialisRawTrim)])->first();
                            if ($sp) $spesialis_id = $sp->id;
                        }
                    }

                    // parse is_active value
                    $isActiveVal = $parseBool($isActiveRaw);

                    if (!$spesialis_id) {
                        $errors[] = "Row {$rowIndex}: Specialist not found (" . ($spesialisRaw ?? '') . ")";
                        $skipped++;
                        continue;
                    }

                    // Prices from the CSV become a proposal; the paket is created without a price until it is approved
                    $ajukanHarga = function (Tindakan $paket, int $row) use ($hargaVal, $hargaDiskonVal, $hargaPaketVisitVal, &$errors) {
                        if (!($hargaVal > 0)) {
                            $errors[] = "Row {$row}: harga normal kosong, paket dibuat tanpa harga (ajukan di Harga Paket Tindakan)";
                            return;
                        }
                        $diskon = $hargaDiskonVal > 0 && $hargaDiskonVal < $hargaVal ? $hargaDiskonVal : null;
                        if ($hargaDiskonVal !== null && $diskon === null) {
                            $errors[] = "Row {$row}: harga diskon diabaikan (harus > 0 dan lebih kecil dari harga normal)";
                        }
                        HargaTindakanController::ajukan($paket, [
                            'harga' => $hargaVal,
                            'harga_diskon' => $diskon,
                            'diskon_active' => $diskon !== null,
                            'harga_paket_visit' => $hargaPaketVisitVal > 0 ? $hargaPaketVisitVal : null,
                        ], 'Import CSV paket tindakan');
                    };

                    $baseName = $nama;

                    // Skip names that already exist: the existing paket keeps its isi, riwayat and billing
                    if (Tindakan::where('nama', $baseName)->exists()) {
                        $errors[] = "Row {$rowIndex}: Paket '{$baseName}' sudah ada, dilewati (ubah harganya lewat Harga Paket Tindakan)";
                        $skipped++;
                        continue;
                    }

                    // Try to create the new tindakan with the original name
                    try {
                        $baru = Tindakan::create([
                            'nama' => $baseName,
                            'deskripsi' => null,
                            'harga' => 0,
                            'harga_diskon' => null,
                                'diskon_active' => 0,
                            'spesialis_id' => $spesialis_id,
                                'is_active' => ($isActiveVal !== null) ? $isActiveVal : 1,
                            'harga_paket_visit' => null,
                            'multi_visit_total' => $multiVisitTotalVal,
                            'is_slimming' => ($isSlimmingVal !== null) ? $isSlimmingVal : 0,
                            'is_vaksin' => ($isVaksinVal !== null) ? $isVaksinVal : 0,
                        ]);
                        $created++;
                        $ajukanHarga($baru, $rowIndex);
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowIndex}: " . $e->getMessage();
                        $skipped++;
                    }
                }
                fclose($handle);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'created' => $created, 'skipped' => $skipped, 'errors' => $errors]);
    }

    /**
     * Import Tindakan <-> KodeTindakan relations from CSV.
     * Expected CSV: two columns - left: tindakan name, right: kode tindakan (kode or nama)
     */
    public function importRelationsCsv(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv');
        $path = $file->getRealPath();

        $created = 0;
        $skipped = 0;
        $errors = [];

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

                    // Accept two-column CSV: tindakan_name, kode_tindakan_identifier
                    $tindakanName = trim($data[0]);
                    $kodeIdentifier = isset($data[1]) ? trim($data[1]) : null;

                    if (!$tindakanName || !$kodeIdentifier) {
                        $errors[] = "Row {$rowIndex}: Missing tindakan name or kode tindakan";
                        $skipped++;
                        continue;
                    }

                    // find tindakan by exact name
                    $tindakan = Tindakan::where('nama', $tindakanName)->first();
                    if (!$tindakan) {
                        $errors[] = "Row {$rowIndex}: Tindakan not found: '{$tindakanName}'";
                        $skipped++;
                        continue;
                    }
                    // ensure tindakan is active
                    if (isset($tindakan->is_active) && !$tindakan->is_active) {
                        $errors[] = "Row {$rowIndex}: Tindakan is inactive, skipping: '{$tindakanName}'";
                        $skipped++;
                        continue;
                    }

                    // Try to find KodeTindakan by kode first, then by nama
                    $kode = \App\Models\ERM\KodeTindakan::where('kode', $kodeIdentifier)->first();
                    if (!$kode) {
                        $kode = \App\Models\ERM\KodeTindakan::where('nama', $kodeIdentifier)->first();
                    }
                    if (!$kode) {
                        $errors[] = "Row {$rowIndex}: KodeTindakan not found: '{$kodeIdentifier}'";
                        $skipped++;
                        continue;
                    }
                    // ensure kode tindakan is active
                    if (isset($kode->is_active) && !$kode->is_active) {
                        $errors[] = "Row {$rowIndex}: KodeTindakan is inactive, skipping: '{$kodeIdentifier}'";
                        $skipped++;
                        continue;
                    }

                    // Attach relation without detaching existing ones
                    try {
                        $tindakan->kodeTindakans()->syncWithoutDetaching([$kode->id]);
                        $created++;
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowIndex}: Failed to attach relation: " . $e->getMessage();
                        $skipped++;
                    }
                }
                fclose($handle);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Import relations failed: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'created' => $created, 'skipped' => $skipped, 'errors' => $errors]);
    }

    /**
     * Bulk set `is_active` by ids or date range
     */
    public function bulkSetActive(Request $request)
    {
        $request->validate([
            'set_active' => 'required|boolean',
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $set = $request->input('set_active') ? 1 : 0;
        DB::beginTransaction();
        try {
            $query = Tindakan::query();
            if ($request->filled('ids')) {
                $ids = $request->input('ids');
                $count = $query->whereIn('id', $ids)->update(['is_active' => $set]);
            } elseif ($request->filled('date_from') && $request->filled('date_to')) {
                $from = \Carbon\Carbon::parse($request->input('date_from'))->startOfDay();
                $to = \Carbon\Carbon::parse($request->input('date_to'))->endOfDay();
                $count = $query->whereBetween('created_at', [$from, $to])->update(['is_active' => $set]);
            } else {
                return response()->json(['success' => false, 'message' => 'Either ids or date range required'], 400);
            }
            DB::commit();
            return response()->json(['success' => true, 'updated' => $count]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a tindakan.
     */
    public function destroy($id)
    {
        try {
            $tindakan = Tindakan::findOrFail($id);

            // Riwayat, SPK, inform consent and SOP rows cascade on delete, and billing points at the paket:
            // a paket that was ever used must be deactivated instead so patient history stays intact
            $usage = [
                'riwayat tindakan' => DB::table('erm_riwayat_tindakan')->where('tindakan_id', $tindakan->id)->count(),
                'SPK' => DB::table('erm_spk')->where('tindakan_id', $tindakan->id)->count(),
                'inform consent' => DB::table('erm_inform_consent')->where('tindakan_id', $tindakan->id)->count(),
                'billing' => DB::table('finance_billing')->where('billable_type', Tindakan::class)->where('billable_id', $tindakan->id)->count(),
                'paket multi-visit' => DB::table('erm_multi_visit_usages')->where('tindakan_id', $tindakan->id)->count(),
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
                    'message' => 'Paket tindakan sudah dipakai di ' . implode(', ', $usedIn) . '. Nonaktifkan saja agar riwayat pasien tetap utuh.'
                ], 422);
            }

            DB::transaction(function () use ($tindakan) {
                \App\Models\ERM\TindakanHargaPengajuan::where('tindakan_id', $tindakan->id)->delete();
                $tindakan->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Paket tindakan dihapus.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function searchTindakan(Request $request)
{
    $search = $request->input('q');
    $spesialisasiId = $request->input('spesialisasi_id');
    
    $query = \App\Models\ERM\Tindakan::with('spesialis')
            ->where('is_active', true)
        ->when($spesialisasiId, function($q) use ($spesialisasiId) {
            $q->where('spesialis_id', $spesialisasiId);
        })
        ->when($search, function ($q) use ($search) {
            $q->where('nama', 'like', "%{$search}%");
        })
        ->orderBy('nama')
        ->limit(20)
        ->get();

    $results = [];
    foreach ($query as $tindakan) {
        $results[] = [
            'id' => $tindakan->id,
            'text' => $tindakan->nama, // Only display the tindakan name
            'harga' => $tindakan->harga, // <-- add harga to result
        ];
    }
    return response()->json(['results' => $results]);
}
}
