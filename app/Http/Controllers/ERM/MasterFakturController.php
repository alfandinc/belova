<?php

namespace App\Http\Controllers\ERM;

use App\Exports\ERM\MasterFakturByPrincipalExport;
use App\Http\Controllers\Controller;
use App\Models\ERM\MasterFaktur;
use App\Models\ERM\Obat;
use App\Models\ERM\Pemasok;
use App\Models\ERM\Principal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MasterFakturController extends Controller
{
    public function exportExcel(Request $request)
    {
        return $this->downloadExport($request);
    }

    protected function buildFilteredQuery(Request $request)
    {
        return MasterFaktur::with(['obat' => fn ($q) => $q->withInactive()->with('principal:id,nama'), 'pemasok'])
            ->when($request->filled('obat_id'), function ($query) use ($request) {
                $query->where('obat_id', $request->input('obat_id'));
            })
            ->when($request->filled('pemasok_id'), function ($query) use ($request) {
                $query->where('pemasok_id', $request->input('pemasok_id'));
            })
            ->when($request->filled('principal_id'), function ($query) use ($request) {
                // Principal is set on the obat (Master Obat)
                $query->whereHas('obat', fn ($o) => $o->withInactive()->where('principal_id', $request->input('principal_id')));
            });
    }

    protected function downloadExport(Request $request)
    {
        $principalId = $request->filled('principal_id') ? (int) $request->input('principal_id') : null;
        $principalName = 'semua-principal';

        if ($principalId) {
            $principal = Principal::find($principalId);
            if ($principal) {
                $principalName = str($principal->nama)->slug('-')->toString();
            }
        }

        return Excel::download(
            new MasterFakturByPrincipalExport($principalId),
            'master_faktur_' . $principalName . '.xlsx'
        );
    }

    // AJAX for select2 Obat
    public function ajaxObat(Request $request)
    {
        $q = $request->q;
        $data = Obat::where(fn ($w) => $w->where('nama', 'like', "%$q%")->orWhere('kode_obat', 'like', "%$q%"))
            ->orderByRaw('nama LIKE ? DESC', ["{$q}%"])
            ->orderBy('nama')
            ->limit(20)
            ->get(['id', 'nama as text']);
        return response()->json($data);
    }

    // AJAX for select2 Pemasok
    public function ajaxPemasok(Request $request)
    {
        $q = $request->q;
        $data = Pemasok::where('nama', 'like', "%$q%")
            ->limit(20)
            ->get(['id', 'nama as text']);
        return response()->json($data);
    }

    // AJAX for select2 Principal
    public function ajaxPrincipal(Request $request)
    {
        $q = $request->q;
        $data = Principal::where('nama', 'like', "%$q%")
            ->limit(20)
            ->get(['id', 'nama as text']);
        return response()->json($data);
    }
    public function show($id)
    {
        $masterFaktur = MasterFaktur::with(['obat' => fn ($q) => $q->withInactive()->with('principal:id,nama'), 'pemasok'])->findOrFail($id);
        return response()->json([
            'id' => $masterFaktur->id,
            'obat_id' => $masterFaktur->obat_id,
            'obat_nama' => $masterFaktur->obat->nama ?? '',
            'pemasok_id' => $masterFaktur->pemasok_id,
            'pemasok_nama' => $masterFaktur->pemasok->nama ?? '',
            'principal_id' => $masterFaktur->obat->principal_id ?? null,
            'principal_nama' => $masterFaktur->obat->principal->nama ?? '',
            'harga' => $masterFaktur->harga,
            'qty_per_box' => $masterFaktur->qty_per_box,
            'diskon' => $masterFaktur->diskon,
            'diskon_type' => $masterFaktur->diskon_type,
            'notes' => $masterFaktur->notes,
        ]);
    }
    public function index()
    {
        // For AJAX DataTables, the view just loads the table and JS.
        // canDelete: merge / delete in Kelola Pemasok (same rule as SupplierMasterController)
        $canDelete = (bool) optional(\Illuminate\Support\Facades\Auth::user())->hasAnyRole(['Admin']);

        return view('erm.masterfaktur.index', compact('canDelete'));
    }
    public function form(Request $request, $id = null)
    {
        $masterFaktur = null;
        if (is_numeric($id) && $id > 0) {
            $masterFaktur = MasterFaktur::findOrFail($id);
        }
        $obats = \App\Models\ERM\Obat::all();
        $pemasoks = \App\Models\ERM\Pemasok::all();
        return view('erm.masterfaktur.partials.form', compact('masterFaktur', 'obats', 'pemasoks'))->render();
    }

    /**
     * Price per satuan stok after a percent discount. A nominal discount is taken off the whole faktur line
     * (see FakturBeliController), so it cannot be spread per unit and the price is returned as is.
     */
    public static function nettoPerSatuan(float $harga, float $diskon, ?string $diskonType): float
    {
        return $diskonType === 'percent' ? round($harga * (1 - $diskon / 100), 2) : $harga;
    }

    /**
     * Server-side list for the DataTable. harga is per satuan stok, without PPN.
     */
    public function data(Request $request)
    {
        if ($request->input('export') === 'excel') {
            return $this->downloadExport($request);
        }

        $total = MasterFaktur::count();
        $query = $this->buildFilteredQuery($request);

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('obat', fn ($o) => $o->withInactive()->where(fn ($w) => $w->where('nama', 'like', "%{$search}%")->orWhere('kode_obat', 'like', "%{$search}%")))
                    ->orWhereHas('pemasok', fn ($p) => $p->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('obat', fn ($o) => $o->withInactive()->whereHas('principal', fn ($p) => $p->where('nama', 'like', "%{$search}%")))
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        $filtered = (clone $query)->count();

        // DataTables column name => sort expression (names sort through a subquery so no join is needed)
        $sortable = [
            'kode_obat' => DB::raw('(SELECT kode_obat FROM erm_obat WHERE erm_obat.id = erm_master_faktur.obat_id)'),
            'obat' => DB::raw('(SELECT nama FROM erm_obat WHERE erm_obat.id = erm_master_faktur.obat_id)'),
            'is_generik' => DB::raw('(SELECT is_generik FROM erm_obat WHERE erm_obat.id = erm_master_faktur.obat_id)'),
            'pemasok' => DB::raw('(SELECT nama FROM erm_pemasok WHERE erm_pemasok.id = erm_master_faktur.pemasok_id)'),
            'principal' => DB::raw('(SELECT pr.nama FROM erm_obat o JOIN erm_principals pr ON pr.id = o.principal_id WHERE o.id = erm_master_faktur.obat_id)'),
            'qty_per_box' => 'qty_per_box',
            'harga' => 'harga',
            'harga_box' => DB::raw('harga * qty_per_box'),
            'hpp' => DB::raw('(SELECT hpp FROM erm_obat WHERE erm_obat.id = erm_master_faktur.obat_id)'),
            'updated_at' => 'updated_at',
        ];
        $orderIdx = (int) $request->input('order.0.column', -1);
        $orderName = (string) $request->input("columns.{$orderIdx}.name", 'updated_at');
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortable[$orderName] ?? 'updated_at', $orderDir)->orderBy('id', 'desc');

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length > 0 ? min($length, 200) : 10;

        $data = $query->skip($start)->take($length)->get()->map(function ($mf) {
            $harga = (float) $mf->harga;
            return [
                'id' => $mf->id,
                'obat_id' => $mf->obat_id,
                'obat' => $mf->obat->nama ?? '-',
                'kode_obat' => $mf->obat->kode_obat ?? null,
                'is_generik' => (bool) ($mf->obat->is_generik ?? false),
                'satuan' => $mf->obat->satuan_stok ?? null,
                'obat_aktif' => (bool) ($mf->obat->status_aktif ?? false),
                'hpp' => $mf->obat && $mf->obat->hpp !== null ? (float) $mf->obat->hpp : null,
                'pemasok' => $mf->pemasok->nama ?? '-',
                'principal' => $mf->obat->principal->nama ?? null,
                'harga' => $harga,
                'harga_box' => round($harga * (int) $mf->qty_per_box, 2),
                'hna' => Obat::hnaFromHpp($harga),
                'qty_per_box' => (int) $mf->qty_per_box,
                'diskon' => (float) $mf->diskon,
                'diskon_type' => $mf->diskon_type,
                'netto' => self::nettoPerSatuan($harga, (float) $mf->diskon, $mf->diskon_type),
                'notes' => $mf->notes,
                'updated_at' => optional($mf->updated_at)->format('Y-m-d'),
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    /**
     * Side-by-side comparison of obat (Bandingkan Obat modal): master data, prices and every pemasok price.
     * Prices per pemasok are per satuan stok without PPN, cheapest (after percent diskon) first.
     */
    public function bandingkan(Request $request)
    {
        $request->validate([
            'obat_ids' => 'required|array|min:1|max:4',
            'obat_ids.*' => 'integer',
        ]);
        $obatIds = array_values(array_unique(array_map('intval', $request->obat_ids)));

        $obats = Obat::withInactive()->with(['zatAktifs:id,nama', 'metodeBayar:id,nama', 'principal:id,nama'])->withSum('stokGudang as total_stok_sum', 'stok')
            ->whereIn('id', $obatIds)->get()->keyBy('id');
        $masters = MasterFaktur::with(['pemasok:id,nama'])->whereIn('obat_id', $obatIds)->get()->groupBy('obat_id');

        $result = [];
        foreach ($obatIds as $id) {
            $obat = $obats->get($id);
            if (!$obat) {
                continue;
            }
            $pemasok = $masters->get($id, collect())->map(function ($m) {
                $harga = (float) $m->harga;
                return [
                    'pemasok' => $m->pemasok->nama ?? '-',
                    'qty_per_box' => (int) $m->qty_per_box,
                    'harga' => $harga,
                    'harga_box' => round($harga * (int) $m->qty_per_box, 2),
                    'diskon' => (float) $m->diskon,
                    'diskon_type' => $m->diskon_type,
                    'netto' => self::nettoPerSatuan($harga, (float) $m->diskon, $m->diskon_type),
                    'hna' => Obat::hnaFromHpp($harga),
                    'updated_at' => optional($m->updated_at)->format('Y-m-d'),
                ];
            })->sortBy('netto')->values();

            $result[] = [
                'id' => $obat->id,
                'nama' => $obat->nama,
                'kode_obat' => $obat->kode_obat,
                'is_generik' => (bool) $obat->is_generik,
                'kategori' => $obat->kategori,
                'aktif' => (bool) $obat->status_aktif,
                'dosis' => $obat->dosis,
                'satuan_dosis' => $obat->satuan,
                'satuan_stok' => $obat->satuan_stok,
                'zat_aktif' => $obat->zatAktifs->pluck('nama')->values(),
                'metode_bayar' => $obat->metodeBayar->nama ?? null,
                'principal' => $obat->principal->nama ?? null,
                'stok' => (float) ($obat->total_stok_sum ?? 0),
                'hpp' => $obat->hpp !== null ? (float) $obat->hpp : null,
                'hna' => $obat->hna !== null ? (float) $obat->hna : null,
                'harga_jual' => $obat->harga_nonfornas !== null ? (float) $obat->harga_nonfornas : null,
                'pemasok' => $pemasok,
            ];
        }

        return response()->json(['data' => $result]);
    }

    // ---------- Input penawaran (many obat from one pemasok at once) ----------

    /**
     * Details for the penawaran grid, per obat: satuan, HPP, the current price from this pemasok,
     * and the cheapest price from other pemasok (to compare offers).
     */
    public function penawaranInfo(Request $request)
    {
        $request->validate([
            'obat_ids' => 'required|array|max:300',
            'obat_ids.*' => 'integer',
            'pemasok_id' => 'nullable|integer',
        ]);
        $obatIds = array_values(array_unique(array_map('intval', $request->obat_ids)));
        $pemasokId = $request->filled('pemasok_id') ? (int) $request->pemasok_id : null;

        $obats = Obat::withInactive()->whereIn('id', $obatIds)
            ->get(['id', 'nama', 'kode_obat', 'satuan_stok', 'satuan', 'hpp', 'status_aktif']);
        $masters = MasterFaktur::with(['pemasok:id,nama'])->whereIn('obat_id', $obatIds)->get()->groupBy('obat_id');

        $result = [];
        foreach ($obats as $obat) {
            $rows = $masters->get($obat->id, collect());
            $current = $pemasokId ? $rows->firstWhere('pemasok_id', $pemasokId) : null;
            $best = $rows->where('pemasok_id', '!=', $pemasokId)
                ->map(fn ($m) => ['m' => $m, 'netto' => self::nettoPerSatuan((float) $m->harga, (float) $m->diskon, $m->diskon_type)])
                ->sortBy('netto')
                ->first();

            $result[$obat->id] = [
                'id' => $obat->id,
                'nama' => $obat->nama,
                'kode_obat' => $obat->kode_obat,
                'satuan' => $obat->satuan_stok,
                'hpp' => $obat->hpp !== null ? (float) $obat->hpp : null,
                'aktif' => (bool) $obat->status_aktif,
                'current' => $current ? [
                    'id' => $current->id,
                    'harga' => (float) $current->harga,
                    'qty_per_box' => (int) $current->qty_per_box,
                    'diskon' => (float) $current->diskon,
                    'diskon_type' => $current->diskon_type,
                    'netto' => self::nettoPerSatuan((float) $current->harga, (float) $current->diskon, $current->diskon_type),
                    'notes' => $current->notes,
                    'updated_at' => optional($current->updated_at)->format('Y-m-d'),
                ] : null,
                'best_other' => $best ? [
                    'pemasok' => $best['m']->pemasok->nama ?? '-',
                    'harga' => (float) $best['m']->harga,
                    'netto' => $best['netto'],
                    'qty_per_box' => (int) $best['m']->qty_per_box,
                ] : null,
            ];
        }

        return response()->json(['data' => $result]);
    }

    private const MATCH_STOPWORDS = [
        'TAB', 'TABLET', 'TABS', 'KAPSUL', 'CAPS', 'CAP', 'KAPLET', 'SYR', 'SIRUP', 'SYRUP', 'INJ', 'INJEKSI',
        'AMP', 'AMPUL', 'VIAL', 'BOTOL', 'BOX', 'STRIP', 'PCS', 'TUBE', 'SACHET', 'SALEP', 'CREAM', 'KRIM',
        'GEL', 'DROP', 'DROPS', 'SUSP', 'GRAM', 'GR', 'MG', 'ML', 'MCG', 'FORTE', 'PLUS',
    ];

    /** Uppercase, letters/digits only, single spaces: for comparing obat names typed by vendors. */
    private static function normalizeObatName(string $nama): string
    {
        $nama = mb_strtoupper($nama);
        $nama = preg_replace('/[^A-Z0-9]+/u', ' ', $nama);
        $nama = preg_replace('/(\d)([A-Z])/', '$1 $2', $nama); // "500MG" => "500 MG"

        return trim(preg_replace('/\s+/', ' ', $nama));
    }

    /**
     * Match obat names pasted from a vendor offer to master obat (active obat only).
     * Returns per name the matched obat (when sure enough) and up to 5 candidates to choose from.
     */
    public function penawaranMatch(Request $request)
    {
        $request->validate(['names' => 'required|array|max:300', 'names.*' => 'nullable|string|max:255']);

        $catalog = Obat::query()->get(['id', 'nama', 'kode_obat'])
            ->map(fn ($o) => ['id' => $o->id, 'nama' => $o->nama, 'kode' => $o->kode_obat, 'key' => self::normalizeObatName((string) $o->nama)]);
        $byKode = $catalog->filter(fn ($o) => $o['kode'])->keyBy(fn ($o) => mb_strtoupper($o['kode']));

        $results = [];
        foreach ($request->names as $raw) {
            $name = trim((string) $raw);
            $key = self::normalizeObatName($name);
            if ($key === '') {
                $results[] = ['name' => $name, 'match' => null, 'candidates' => []];
                continue;
            }

            // Kode obat typed instead of a name
            if ($hit = $byKode->get(mb_strtoupper($name))) {
                $results[] = ['name' => $name, 'match' => ['id' => $hit['id'], 'text' => $hit['nama']], 'candidates' => []];
                continue;
            }

            // Dosage-form / unit words appear in most names, so they do not pick candidates
            $tokens = array_filter(explode(' ', $key), fn ($t) => strlen($t) >= 3 && !ctype_digit($t) && !in_array($t, self::MATCH_STOPWORDS, true));
            $compact = str_replace(' ', '', $key);
            $scored = $catalog
                ->map(function ($o) {
                    $o['compact'] = str_replace(' ', '', $o['key']);
                    return $o;
                })
                ->filter(function ($o) use ($tokens, $key, $compact) {
                    if ($o['key'] === $key) {
                        return true;
                    }
                    // "C-03-Y TAB" vs "C-03-Y": one name contained in the other once spacing/punctuation is gone
                    if (strlen($o['compact']) >= 4 && strlen($compact) >= 4
                        && (str_contains($compact, $o['compact']) || str_contains($o['compact'], $compact))) {
                        return true;
                    }
                    foreach ($tokens as $t) {
                        if (str_contains($o['key'], $t)) {
                            return true;
                        }
                        // Spelling variants: PARASETAMOL / PARACETAMOL
                        if (strlen($t) >= 6) {
                            foreach (explode(' ', $o['key']) as $w) {
                                if (abs(strlen($w) - strlen($t)) <= 2 && levenshtein($t, $w) <= 2) {
                                    return true;
                                }
                            }
                        }
                    }
                    return false;
                })
                ->map(function ($o) use ($key, $compact) {
                    similar_text($key, $o['key'], $pct);
                    // Every word of the pasted name present in the obat name counts strongly
                    $words = explode(' ', $key);
                    $present = count(array_filter($words, fn ($w) => str_contains(' ' . $o['key'] . ' ', ' ' . $w . ' ')));
                    $score = round($pct * 0.6 + ($present / max(count($words), 1)) * 40, 1);
                    if ($o['compact'] === $compact) {
                        $score = 100;
                    } elseif (strlen($o['compact']) >= 4 && str_contains($compact, $o['compact'])) {
                        $score = max($score, 90);
                    }
                    $o['score'] = $score;
                    return $o;
                })
                ->filter(fn ($o) => $o['score'] >= 40)
                ->sortByDesc('score')
                ->take(5)
                ->values();

            $top = $scored->first();
            $second = $scored->get(1);
            $sure = $top && ($top['score'] >= 100 || ($top['score'] >= 80 && (!$second || $top['score'] - $second['score'] >= 8)));

            $results[] = [
                'name' => $name,
                'match' => $sure ? ['id' => $top['id'], 'text' => $top['nama']] : null,
                'candidates' => $scored->map(fn ($o) => ['id' => $o['id'], 'text' => $o['nama'], 'score' => $o['score']])->all(),
            ];
        }

        return response()->json(['data' => $results]);
    }

    /**
     * Save the penawaran grid. harga is already per satuan stok without PPN (converted in the form).
     * Rows with an id update that record; other rows update the existing obat + pemasok record, or create one.
     */
    public function penawaranSave(Request $request)
    {
        $request->validate([
            'pemasok_id' => 'required|exists:erm_pemasok,id',
            'notes' => 'nullable|string|max:1000',
            'rows' => 'required|array|min:1|max:300',
            'rows.*.id' => 'nullable|integer|exists:erm_master_faktur,id',
            'rows.*.obat_id' => 'required|integer|exists:erm_obat,id',
            'rows.*.harga' => 'required|numeric|min:0',
            'rows.*.qty_per_box' => 'required|integer|min:1',
            'rows.*.diskon' => 'nullable|numeric|min:0',
            'rows.*.diskon_type' => 'required|in:percent,nominal',
        ], [
            'pemasok_id.required' => 'Pilih pemasok terlebih dahulu.',
            'rows.required' => 'Belum ada obat yang diisi.',
            'rows.*.obat_id.required' => 'Ada baris yang obatnya belum dipilih.',
            'rows.*.harga.required' => 'Ada baris yang harganya belum diisi.',
            'rows.*.qty_per_box.required' => 'Ada baris yang isi per box-nya belum diisi.',
            'rows.*.qty_per_box.min' => 'Isi per box minimal 1.',
        ]);

        $pemasokId = (int) $request->pemasok_id;
        $rows = collect($request->rows);

        if ($msg = Obat::satuanStokRequiredMessage($rows->pluck('obat_id')->all())) {
            return response()->json(['message' => $msg], 422);
        }

        $dupObat = $rows->pluck('obat_id')->map(fn ($id) => (int) $id)->duplicates();
        if ($dupObat->isNotEmpty()) {
            $names = Obat::withInactive()->whereIn('id', $dupObat->unique())->pluck('nama')->implode(', ');
            return response()->json(['message' => "Obat dimasukkan lebih dari sekali: {$names}."], 422);
        }
        foreach ($rows as $row) {
            if (($row['diskon_type'] ?? '') === 'percent' && (float) ($row['diskon'] ?? 0) > 100) {
                return response()->json(['message' => 'Diskon persen tidak boleh lebih dari 100%.'], 422);
            }
        }

        $notes = trim((string) $request->input('notes', ''));
        $counts = ['created' => 0, 'updated' => 0];

        DB::transaction(function () use ($rows, $pemasokId, $notes, &$counts) {
            foreach ($rows as $row) {
                $obatId = (int) $row['obat_id'];
                $existing = MasterFaktur::where('obat_id', $obatId)->where('pemasok_id', $pemasokId)->first();

                if (!empty($row['id'])) {
                    $record = MasterFaktur::findOrFail($row['id']);
                    if ($existing && $existing->id !== $record->id) {
                        $nama = Obat::withInactive()->whereKey($obatId)->value('nama');
                        throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                            'message' => "Harga {$nama} dari pemasok ini sudah ada di data lain. Edit data tersebut saja.",
                        ], 422));
                    }
                } else {
                    $record = $existing ?: new MasterFaktur(['obat_id' => $obatId, 'pemasok_id' => $pemasokId]);
                }

                $record->fill([
                    'obat_id' => $obatId,
                    'pemasok_id' => $pemasokId,
                    'harga' => round((float) $row['harga'], 2),
                    'qty_per_box' => (int) $row['qty_per_box'],
                    'diskon' => (float) ($row['diskon'] ?? 0),
                    'diskon_type' => $row['diskon_type'],
                ]);
                if ($notes !== '') {
                    $record->notes = $notes;
                }

                $counts[$record->exists ? 'updated' : 'created']++;
                $record->save();
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Tersimpan: {$counts['created']} baru, {$counts['updated']} diperbarui.",
            'counts' => $counts,
        ]);
    }

    public function create()
    {
        $obats = Obat::all();
        $pemasoks = Pemasok::all();
        return view('erm.masterfaktur.create', compact('obats', 'pemasoks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'obat_id' => 'required|exists:erm_obat,id',
            'pemasok_id' => 'required|exists:erm_pemasok,id',
            'harga' => 'required|numeric',
            'qty_per_box' => 'required|integer',
            'diskon' => 'required|numeric',
            'diskon_type' => 'required|in:percent,nominal',
            'notes' => 'nullable|string',
        ]);

        if ($msg = Obat::satuanStokRequiredMessage([$request->obat_id])) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withErrors(['obat_id' => $msg])->withInput();
        }

        // Check for duplicate combination
        $exists = MasterFaktur::where('obat_id', $request->obat_id)
            ->where('pemasok_id', $request->pemasok_id)
            ->exists();
        if ($exists) {
            $msg = 'Kombinasi Obat dan Pemasok sudah ada.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withErrors(['obat_id' => $msg])->withInput();
        }

        $payload = $request->only([
            'obat_id',
            'pemasok_id',
            'harga',
            'qty_per_box',
            'diskon',
            'diskon_type',
            'notes',
        ]);

        $mf = MasterFaktur::create($payload);
        if ($request->ajax()) {
            return response()->json(['success' => true, 'id' => $mf->id]);
        }
        return redirect()->route('erm.masterfaktur.index')->with('success', 'Master Faktur created!');
    }

    public function edit($id)
    {
        $masterFaktur = MasterFaktur::findOrFail($id);
        $obats = Obat::all();
        $pemasoks = Pemasok::all();
        return view('erm.masterfaktur.edit', compact('masterFaktur', 'obats', 'pemasoks'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'obat_id' => 'required|exists:erm_obat,id',
            'pemasok_id' => 'required|exists:erm_pemasok,id',
            'harga' => 'required|numeric',
            'qty_per_box' => 'required|integer',
            'diskon' => 'required|numeric',
            'diskon_type' => 'required|in:percent,nominal',
            'notes' => 'nullable|string',
        ]);
        $masterFaktur = MasterFaktur::findOrFail($id);

        if ($msg = Obat::satuanStokRequiredMessage([$request->obat_id])) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withErrors(['obat_id' => $msg])->withInput();
        }

        $duplicate = MasterFaktur::where('obat_id', $request->obat_id)
            ->where('pemasok_id', $request->pemasok_id)
            ->where('id', '!=', $masterFaktur->id)
            ->exists();
        if ($duplicate) {
            $msg = 'Kombinasi Obat dan Pemasok sudah ada.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withErrors(['obat_id' => $msg])->withInput();
        }

        $payload = $request->only([
            'obat_id',
            'pemasok_id',
            'harga',
            'qty_per_box',
            'diskon',
            'diskon_type',
            'notes',
        ]);

        $masterFaktur->update($payload);
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('erm.masterfaktur.index')->with('success', 'Master Faktur updated!');
    }

    public function destroy($id)
    {
        $masterFaktur = MasterFaktur::findOrFail($id);
        $masterFaktur->delete();
        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('erm.masterfaktur.index')->with('success', 'Master Faktur deleted!');
    }
}
