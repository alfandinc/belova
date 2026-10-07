<?php

namespace App\Http\Controllers\ERM;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\ERM\KartuStok;
use App\Models\ERM\Obat;
use App\Models\ERM\ObatMapping;
use App\Models\ERM\Supplier;
use App\Models\ERM\ZatAktif;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Visitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class ObatController extends Controller
{
    private function normalizeCsvDecimal($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(["\xc2\xa0", ' '], '', $value);
        $value = preg_replace('/[^0-9,.-]/', '', $value);

        if ($value === '' || $value === '-' || $value === ',' || $value === '.') {
            return null;
        }

        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-');

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');
        $decimalSeparator = null;
        $commaCount = substr_count($value, ',');
        $dotCount = substr_count($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
        } elseif ($lastComma !== false) {
            $decimalSeparator = $commaCount === 1 ? ',' : null;
        } elseif ($lastDot !== false) {
            $decimalSeparator = $dotCount === 1 ? '.' : null;
        }

        if ($decimalSeparator !== null) {
            $parts = explode($decimalSeparator, $value);
            $fraction = array_pop($parts);
            $integer = implode('', $parts);
            $integer = str_replace([',', '.'], '', $integer);
            $normalized = $integer . '.' . preg_replace('/[^0-9]/', '', $fraction);
        } else {
            $normalized = str_replace([',', '.'], '', $value);
        }

        if ($negative) {
            $normalized = '-' . $normalized;
        }

        return is_numeric($normalized) ? $normalized : null;
    }

    private function normalizeCsvBoolean($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $normalized = strtolower($value);

        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'ya', 'aktif', 'generik'], true)) {
            return 1;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'tidak', 'non', 'non-generik'], true)) {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function csvValueChanged($existing, $incoming, $numeric = false)
    {
        if ($incoming === null || $incoming === '') {
            return false;
        }

        if ($numeric) {
            if ($existing === null || $existing === '') {
                return true;
            }

            return abs((float) $existing - (float) $incoming) > 0.00001;
        }

        return (string) $incoming !== (string) ($existing ?? '');
    }

    /**
     * Fields the master obat form may write. HPP comes from purchases (StokService) and HNA is always
     * HPP + PPN (Obat model), stok lives in erm_obat_stok_gudang, harga_net / harga_fornas are no longer edited here.
     */
    private const EDITABLE_FIELDS = [
        'nama', 'dosis', 'satuan', 'satuan_stok', 'harga_nonfornas',
        'kategori', 'is_generik', 'metode_bayar_id', 'status_aktif', 'principal_id',
    ];

    /**
     * @param bool $partial true for PUT (only validate fields that are sent)
     */
    private function obatRules(bool $partial): array
    {
        $req = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'nama' => array_merge($req, ['string', 'max:191']),
            'dosis' => 'nullable|string|max:191',
            'satuan' => ['nullable', Rule::in(Obat::satuanDosisOptions())],
            'satuan_stok' => array_merge($req, [Rule::in(Obat::SATUAN_STOK_LIST)]),
            'kategori' => array_merge($req, [Rule::in(Obat::KATEGORI_LIST)]),
            'metode_bayar_id' => array_merge($req, ['exists:erm_metode_bayar,id']),
            'is_generik' => 'sometimes|boolean',
            'principal_id' => 'nullable|integer|exists:erm_principals,id',
            'status_aktif' => 'sometimes|in:0,1',
            'harga_nonfornas' => 'nullable|numeric|min:0',
            'zataktif_id' => 'nullable|array',
            'zataktif_id.*' => 'integer|exists:erm_zataktif,id',
        ];
    }

    private const OBAT_MESSAGES = [
        'required' => ':attribute wajib diisi.',
        'in' => ':attribute tidak valid, pilih dari daftar.',
        'numeric' => ':attribute harus berupa angka.',
        'min' => ':attribute tidak boleh negatif.',
        'max' => ':attribute terlalu panjang (maksimal :max karakter).',
        'exists' => ':attribute tidak ditemukan.',
        'boolean' => ':attribute tidak valid.',
        'array' => ':attribute tidak valid.',
        'integer' => ':attribute tidak valid.',
        'string' => ':attribute tidak valid.',
    ];

    private const OBAT_ATTRIBUTES = [
        'nama' => 'Nama Obat',
        'kategori' => 'Kategori',
        'dosis' => 'Dosis',
        'is_generik' => 'Jenis',
        'principal_id' => 'Principal',
        'status_aktif' => 'Status',
        'harga_nonfornas' => 'Harga Jual',
        'metode_bayar_id' => 'Metode Bayar',
        'satuan' => 'Satuan Dosis',
        'satuan_stok' => 'Satuan Stok/Jual',
        'zataktif_id.*' => 'Zat Aktif',
    ];

    public const FILTER_KOSONG = '__kosong__';

    /**
     * kategori / metode_bayar_id / satuan_stok filters; FILTER_KOSONG matches rows where the field is not filled.
     */
    public function applyMasterFilters($query, Request $request): void
    {
        foreach (['kategori', 'metode_bayar_id', 'satuan_stok'] as $field) {
            $value = $request->input($field);
            if ($value === null || $value === '') {
                continue;
            }
            if ($value === self::FILTER_KOSONG) {
                $query->where(function ($q) use ($field) {
                    $q->whereNull($field)->orWhere($field, '');
                });
            } else {
                $query->where($field, $value);
            }
        }
    }

    private function syncZatAktif(Request $request, Obat $obat): void
    {
        // A multi-select with nothing selected sends no field at all, so the master form
        // sends sync_zataktif=1 to allow clearing. Partial updates (e.g. stok opname) leave it alone.
        if ($request->has('zataktif_id') || $request->boolean('sync_zataktif')) {
            $obat->zatAktifs()->sync(array_filter((array) $request->input('zataktif_id', [])));
        }
    }

    /**
     * Update the specified Obat in storage. Only fields present in the request are changed.
     */
    public function update(Request $request, $id)
    {
        $request->validate($this->obatRules(true), self::OBAT_MESSAGES, self::OBAT_ATTRIBUTES);

        $obat = Obat::withInactive()->findOrFail($id);

        try {
            DB::transaction(function () use ($request, $obat) {
                $up = [];
                foreach (self::EDITABLE_FIELDS as $f) {
                    if ($request->has($f)) {
                        $up[$f] = $f === 'is_generik' ? $request->boolean($f) : $request->input($f);
                    }
                }
                if (!empty($up)) {
                    $obat->update($up);
                }
                $this->syncZatAktif($request, $obat);
            });

            return response()->json(['success' => true, 'message' => 'Obat berhasil diperbarui']);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui obat: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Update harga_nonfornas (harga jual) via AJAX.
     */
    public function updateHargaJual(Request $request, $id)
    {
        $request->validate([
            'harga_nonfornas' => 'required|numeric|min:0',
        ]);
        try {
            $obat = Obat::withInactive()->findOrFail($id);
            $obat->harga_nonfornas = $request->harga_nonfornas;
            $obat->save();
            return response()->json(['success' => true, 'message' => 'Harga jual berhasil diperbarui']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui harga jual: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Display the Monitor Profit page with DataTable.
     */
    public function monitorProfit(Request $request)
    {
        if ($request->ajax()) {
            $PPN = 11;
            // Use default global scope (only active obat) so monitor profit shows active items only
            $obats = Obat::select(['id', 'kode_obat', 'nama', 'hpp', 'harga_nonfornas'])
                // profit_percent_value: profit sebelum PPN
                ->selectRaw('(CASE WHEN hpp > 0 THEN (((harga_nonfornas / (1 + '.$PPN.'/100)) - hpp) / hpp) * 100 ELSE NULL END) as profit_percent_value')
                // profit_percent_setelah_ppn: profit setelah PPN
                ->selectRaw('(CASE WHEN hpp > 0 THEN (((harga_nonfornas - hpp) / hpp) * 100) ELSE NULL END) as profit_percent_setelah_ppn');
            $defaultProfit = 30; // Default profit percent
            return DataTables::of($obats)
                ->addColumn('profit_percent', function ($obat) {
                    if (isset($obat->profit_percent_value)) {
                        $percent = $obat->profit_percent_value;
                        $text = number_format($percent, 2) . '%';
                        if ($percent < 30) {
                            $text .= ' <span class="text-warning blink-warning" title="Profit di bawah 30%"><i class="fas fa-exclamation-triangle"></i></span>';
                        }
                        return $text;
                    }
                    return '-';
                })
                ->addColumn('profit_percent_setelah_ppn', function ($obat) {
                    if (isset($obat->profit_percent_setelah_ppn)) {
                        return number_format($obat->profit_percent_setelah_ppn, 2) . '%';
                    }
                    return '-';
                })
                ->addColumn('saran_harga_jual', function ($obat) use ($PPN, $defaultProfit) {
                    $hpp = floatval($obat->hpp);
                    $profitPercent = $defaultProfit;
                    // Calculate suggested selling price WITHOUT PPN
                    $saran = $hpp * ((100 + $profitPercent) / 100);
                    return $hpp > 0 ? number_format($saran, 0) : '-';
                })
                ->orderColumn('profit_percent', 'profit_percent_value $1')
                ->editColumn('hpp', function ($obat) {
                    return number_format($obat->hpp, 0);
                })
                ->editColumn('harga_nonfornas', function ($obat) {
                    return number_format($obat->harga_nonfornas, 0);
                })
                ->addColumn('aksi', function ($obat) {
                    $btn = '<button type="button" class="btn btn-sm btn-warning btn-edit-harga" data-id="'.$obat->id.'" data-nama="'.e($obat->nama).'" data-harga="'.$obat->harga_nonfornas.'"><i class="fas fa-edit"></i> Edit</button>';
                    return $btn;
                })
                ->rawColumns(['aksi', 'profit_percent'])
                ->make(true);
        }
        return view('erm.obat.monitor_profit');
    }
    /**
     * Filters shared by the master obat table and its summary counts.
     */
    public function applyIndexFilters($query, Request $request): void
    {
        $this->applyMasterFilters($query, $request);

        if ($request->filled('zataktif_id')) {
            $zatAktifId = $request->zataktif_id;
            $query->whereHas('zatAktifs', function ($zatQuery) use ($zatAktifId) {
                $zatQuery->where('erm_zataktif.id', $zatAktifId);
            });
        }
        // Obat supplied by a pemasok / principal ("N obat" link in the Pemasok & Principal modal)
        if ($request->filled('pemasok_id')) {
            $ids = PemasokController::obatIdsQuery([(int) $request->pemasok_id])->select('obat_id');
            $query->whereIn('erm_obat.id', $ids);
        }
        if ($request->filled('principal_id')) {
            $ids = PrincipalController::obatIdsQuery([(int) $request->principal_id])->select('obat_id');
            $query->whereIn('erm_obat.id', $ids);
        }
        if ($request->filled('status_aktif')) {
            $query->where('status_aktif', $request->status_aktif);
        }
        if (in_array((string) $request->input('is_generik'), ['0', '1'], true)) {
            $query->where('is_generik', (int) $request->is_generik);
        }

        switch ($request->input('kelengkapan')) {
            case 'harga_jual':
                $query->where(fn ($q) => $q->whereNull('harga_nonfornas')->orWhere('harga_nonfornas', '<=', 0));
                break;
            case 'hpp':
                $query->where(fn ($q) => $q->whereNull('hpp')->orWhere('hpp', '<=', 0));
                break;
            case 'zat_aktif':
                $query->whereDoesntHave('zatAktifs');
                break;
        }
    }

    /**
     * Free-text search: kode, kode lama, nama, zat aktif, principal.
     */
    public function applySearch($query, string $term): void
    {
        $term = trim($term);
        if ($term === '') {
            return;
        }

        $query->where(function ($subQuery) use ($term) {
            $subQuery->where('kode_obat', 'LIKE', "%{$term}%")
                ->orWhere('kode_obat_lama', 'LIKE', "%{$term}%")
                ->orWhere('nama', 'LIKE', "%{$term}%")
                ->orWhereHas('zatAktifs', function ($zatQuery) use ($term) {
                    $zatQuery->where('nama', 'LIKE', "%{$term}%");
                })
                ->orWhereHas('principal', function ($principalQuery) use ($term) {
                    $principalQuery->where('nama', 'LIKE', "%{$term}%");
                })
                // principal or distributor (pemasok) on faktur beli
                ->orWhereExists(function ($q) use ($term) {
                    $q->selectRaw(1)
                        ->from('erm_fakturbeli_items as fi')
                        ->join('erm_fakturbeli as f', 'f.id', '=', 'fi.fakturbeli_id')
                        ->leftJoin('erm_pemasok as pm', 'pm.id', '=', 'f.pemasok_id')
                        ->leftJoin('erm_principals as pr', 'pr.id', '=', 'fi.principal_id')
                        ->whereColumn('fi.obat_id', 'erm_obat.id')
                        ->where(fn ($w) => $w->where('pm.nama', 'LIKE', "%{$term}%")->orWhere('pr.nama', 'LIKE', "%{$term}%"));
                });
        });
    }

    /**
     * Counts of active obat with incomplete master data ("Data perlu dilengkapi" chips).
     */
    private function incompleteSummary(): array
    {
        $kosong = fn ($field) => fn ($q) => $q->whereNull($field)->orWhere($field, '');
        $nol = fn ($field) => fn ($q) => $q->whereNull($field)->orWhere($field, '<=', 0);
        $base = fn () => Obat::query(); // global scope = active only

        return [
            'kategori' => $base()->where($kosong('kategori'))->count(),
            'metode_bayar' => $base()->where(fn ($q) => $q->whereNull('metode_bayar_id'))->count(),
            'satuan_stok' => $base()->where($kosong('satuan_stok'))->count(),
            'harga_jual' => $base()->where($nol('harga_nonfornas'))->count(),
            'hpp' => $base()->where($nol('hpp'))->count(),
        ];
    }

    public function index(Request $request)
    {
        if ($request->ajax() && $request->boolean('summary')) {
            return response()->json($this->incompleteSummary());
        }

        if ($request->ajax()) {
            // stok_total_sum: do not alias as total_stok, the getTotalStokAttribute accessor would
            // override it and run one SUM query per row.
            // Distributor = pemasok on faktur beli ('||'-joined, split in addColumn); principal = the obat's own
            // principal_id (same source as the "Pemasok & Principal" popup).
            $distributorSql = "SELECT GROUP_CONCAT(DISTINCT pm.nama ORDER BY pm.nama SEPARATOR '||')
                FROM erm_fakturbeli_items fi
                JOIN erm_fakturbeli f ON f.id = fi.fakturbeli_id
                JOIN erm_pemasok pm ON pm.id = f.pemasok_id
                WHERE fi.obat_id = erm_obat.id";
            $principalSql = "SELECT pr.nama FROM erm_principals pr WHERE pr.id = erm_obat.principal_id";

            $query = Obat::withInactive()
                ->select('erm_obat.*')
                ->selectSub($distributorSql, 'distributor_list')
                ->selectSub($principalSql, 'principal_list')
                ->with(['zatAktifs:erm_zataktif.id,erm_zataktif.nama', 'metodeBayar:id,nama'])
                ->withSum('stokGudang as stok_total_sum', 'stok');

            $this->applyIndexFilters($query, $request);

            return DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    $this->applySearch($query, (string) $request->input('search.value', ''));
                })
                // Plain data only; the page renders and escapes everything
                ->addColumn('metode_bayar', fn ($obat) => optional($obat->metodeBayar)->nama)
                ->addColumn('zat_aktif', fn ($obat) => $obat->zatAktifs->pluck('nama')->values()->all())
                ->addColumn('principal', fn ($obat) => $obat->principal_list ? [$obat->principal_list] : [])
                ->addColumn('distributor', fn ($obat) => $obat->distributor_list ? explode('||', $obat->distributor_list) : [])
                ->addColumn('stok_total', fn ($obat) => (float) ($obat->stok_total_sum ?? 0))
                ->removeColumn('zat_aktifs', 'stok_total_sum', 'principal_list', 'distributor_list')
                // The page escapes every value itself; server-side escaping would double-escape
                // ("A & B" -> "A &amp; B") and turns arrays into strings.
                ->escapeColumns([])
                ->make(true);
        }

        $kategoris = Obat::KATEGORI_LIST;
        $metodeBayars = MetodeBayar::orderBy('nama')->get(['id', 'nama']);
        $satuanStokList = Obat::SATUAN_STOK_LIST;
        $satuanDosisList = Obat::SATUAN_DOSIS_LIST;
        $canDelete = (bool) optional(Auth::user())->hasAnyRole(['Admin']);

        return view('erm.obat.index', compact('kategoris', 'metodeBayars', 'satuanStokList', 'satuanDosisList', 'canDelete'));
    }

    public function forecastIndex()
    {
        return view('erm.obat.forecast-index');
    }

    public function toggleFavorite($id)
    {
        $obat = Obat::findOrFail($id);
        $obat->is_favorite = ! $obat->is_favorite;
        $obat->save();

        return response()->json([
            'success' => true,
            'id' => $obat->id,
            'is_favorite' => (bool) $obat->is_favorite,
        ]);
    }

    private function resolveForecastKeluarPeriod(string $period): array
    {
        $today = Carbon::today();

        switch ($period) {
            case 'week':
                return [
                    'period' => 'week',
                    'start' => $today->copy()->startOfDay(),
                    'end' => $today->copy()->endOfWeek()->endOfDay(),
                    'label' => 'Hari ini s/d akhir minggu',
                ];
            case 'month':
                return [
                    'period' => 'month',
                    'start' => $today->copy()->startOfDay(),
                    'end' => $today->copy()->endOfMonth()->endOfDay(),
                    'label' => 'Hari ini s/d akhir bulan',
                ];
            case 'next_month':
                return [
                    'period' => 'next_month',
                    'start' => $today->copy()->startOfDay(),
                    'end' => $today->copy()->addMonthNoOverflow()->endOfMonth()->endOfDay(),
                    'label' => 'Hari ini s/d akhir bulan depan',
                ];
            case 'today':
            default:
                return [
                    'period' => 'today',
                    'start' => $today->copy()->startOfDay(),
                    'end' => $today->copy()->endOfDay(),
                    'label' => 'Hari ini',
                ];
        }
    }

    private function buildForecastKeluarSourceVisitationQuery(Carbon $periodStart, Carbon $periodEnd)
    {
        return DB::table('erm_visitations as target_visit')
            ->select(
                'target_visit.id as target_visitation_id',
                'target_visit.pasien_id',
                'target_visit.tanggal_visitation as target_tanggal_visitation'
            )
            ->selectSub(function ($query) {
                $query->from('erm_visitations as previous_visit')
                    ->select('previous_visit.id')
                    ->whereColumn('previous_visit.pasien_id', 'target_visit.pasien_id')
                    ->whereColumn('previous_visit.tanggal_visitation', '<', 'target_visit.tanggal_visitation')
                    ->whereExists(function ($resepQuery) {
                        $resepQuery->select(DB::raw(1))
                            ->from('erm_resepfarmasi as source_resep')
                            ->whereColumn('source_resep.visitation_id', 'previous_visit.id');
                    })
                    ->orderByDesc('previous_visit.tanggal_visitation')
                    ->orderByDesc('previous_visit.id')
                    ->limit(1);
            }, 'source_visitation_id')
            ->whereNotNull('target_visit.pasien_id')
            ->whereBetween('target_visit.tanggal_visitation', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ]);
    }

    public function similarObats($id)
    {
        $obat = Obat::withInactive()
            ->with(['zatAktifs', 'masterFakturs', 'principal'])
            ->findOrFail($id);

        $selectedMasterFaktur = $obat->masterFakturs
            ->sortByDesc('id')
            ->first();

        $stockPerObat = \App\Models\ERM\ObatStokGudang::query()
            ->whereHas('gudang', function ($query) {
                $query->where('nama', '!=', 'Gudang ED');
            })
            ->select('obat_id', DB::raw('SUM(stok) as total_stock'))
            ->groupBy('obat_id')
            ->pluck('total_stock', 'obat_id');

        $zatAktifIds = $obat->zatAktifs->pluck('id')->filter()->values();

        $selectedHarga = (float) ($selectedMasterFaktur->harga ?? 0);
        $selectedDiskon = (float) ($selectedMasterFaktur->diskon ?? 0);
        $selectedDiskonType = (string) ($selectedMasterFaktur->diskon_type ?? 'nominal');
        $selectedSetelahDiskon = $this->calculateMasterFakturNetPrice($selectedHarga, $selectedDiskon, $selectedDiskonType);

        if ($zatAktifIds->isEmpty()) {
            return response()->json([
                'obat_id' => $obat->id,
                'obat_nama' => $obat->nama,
                'total_stock' => round((float) ($stockPerObat[$obat->id] ?? 0), 2),
                'isi_per_box' => $selectedMasterFaktur ? round((float) ($selectedMasterFaktur->qty_per_box ?? 0), 2) : null,
                'harga' => $selectedMasterFaktur ? $selectedHarga : null,
                'diskon' => $selectedMasterFaktur ? $selectedDiskon : null,
                'diskon_type' => $selectedMasterFaktur ? $selectedDiskonType : null,
                'setelah_diskon' => $selectedMasterFaktur ? $selectedSetelahDiskon : null,
                'shared_zat_aktif' => [],
                'rows' => [],
                'message' => 'Obat ini belum memiliki zat aktif.',
            ]);
        }

        $sharedZatAktif = $obat->zatAktifs
            ->pluck('nama')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $rows = Obat::query()
            ->with(['zatAktifs', 'masterFakturs', 'principal:id,nama'])
            ->whereKeyNot($obat->id)
            ->whereHas('zatAktifs', function ($query) use ($zatAktifIds) {
                $query->whereIn('erm_zataktif.id', $zatAktifIds);
            })
            ->orderBy('nama')
            ->get(['id', 'nama', 'is_generik', 'harga_nonfornas', 'principal_id'])
            ->map(function ($similarObat) use ($zatAktifIds, $stockPerObat) {
                $principalNames = $similarObat->principal ? [$similarObat->principal->nama] : [];

                $latestMasterFaktur = $similarObat->masterFakturs
                    ->sortByDesc('id')
                    ->first();

                $harga = (float) ($latestMasterFaktur->harga ?? 0);
                $diskon = (float) ($latestMasterFaktur->diskon ?? 0);
                $diskonType = (string) ($latestMasterFaktur->diskon_type ?? 'nominal');
                $setelahDiskon = $this->calculateMasterFakturNetPrice($harga, $diskon, $diskonType);

                $matchedZatAktif = $similarObat->zatAktifs
                    ->whereIn('id', $zatAktifIds)
                    ->pluck('nama')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'obat_id' => $similarObat->id,
                    'obat_nama' => $similarObat->nama,
                    'is_generik' => $similarObat->is_generik,
                    'principal_names' => $principalNames,
                    'total_stock' => round((float) ($stockPerObat[$similarObat->id] ?? 0), 2),
                    'isi_per_box' => $latestMasterFaktur ? round((float) ($latestMasterFaktur->qty_per_box ?? 0), 2) : null,
                    'harga' => $latestMasterFaktur ? $harga : null,
                    'diskon' => $latestMasterFaktur ? $diskon : null,
                    'diskon_type' => $latestMasterFaktur ? $diskonType : null,
                    'setelah_diskon' => $latestMasterFaktur ? $setelahDiskon : null,
                    'matched_zat_aktif' => $matchedZatAktif,
                ];
            })
            ->values();

        return response()->json([
            'obat_id' => $obat->id,
            'obat_nama' => $obat->nama,
            'total_stock' => round((float) ($stockPerObat[$obat->id] ?? 0), 2),
            'isi_per_box' => $selectedMasterFaktur ? round((float) ($selectedMasterFaktur->qty_per_box ?? 0), 2) : null,
            'harga' => $selectedMasterFaktur ? $selectedHarga : null,
            'diskon' => $selectedMasterFaktur ? $selectedDiskon : null,
            'diskon_type' => $selectedMasterFaktur ? $selectedDiskonType : null,
            'setelah_diskon' => $selectedMasterFaktur ? $selectedSetelahDiskon : null,
            'shared_zat_aktif' => $sharedZatAktif,
            'rows' => $rows,
        ]);
    }

    protected function calculateMasterFakturNetPrice(float $harga, float $diskon, string $diskonType): float
    {
        $normalizedType = strtolower(trim($diskonType));
        $diskonValue = in_array($normalizedType, ['persen', 'percent', '%', 'pct', 'pc', 'per'])
            ? ($harga * $diskon / 100)
            : $diskon;

        return max($harga - $diskonValue, 0);
    }

    public function forecastKeluar(Request $request)
    {
        $periodConfig = $this->resolveForecastKeluarPeriod((string) $request->input('period', 'today'));
        $period = $periodConfig['period'];
        $periodStart = $periodConfig['start'];
        $periodEnd = $periodConfig['end'];
        $periodLabel = $periodConfig['label'];

        $sourceVisitations = $this->buildForecastKeluarSourceVisitationQuery($periodStart, $periodEnd);

        $rawRows = DB::query()
            ->fromSub($sourceVisitations, 'fv')
            ->join('erm_resepfarmasi as rf', 'fv.source_visitation_id', '=', 'rf.visitation_id')
            ->join('erm_obat as o', 'rf.obat_id', '=', 'o.id')
            ->select(
                'rf.obat_id',
                'o.nama as obat_nama',
                DB::raw('SUM(COALESCE(rf.jumlah, 0)) as total_keluar'),
                DB::raw('COUNT(rf.id) as jumlah_resep'),
                DB::raw('COUNT(DISTINCT fv.target_visitation_id) as jumlah_kunjungan'),
                DB::raw('MAX(fv.target_tanggal_visitation) as tanggal_terakhir')
            )
            ->whereNotNull('fv.source_visitation_id')
            ->groupBy('rf.obat_id', 'o.nama')
            ->orderByDesc('total_keluar')
            ->orderBy('o.nama')
            ->get();

        $obatIds = $rawRows->pluck('obat_id')->filter()->unique()->values();
        $stockPerObat = \App\Models\ERM\ObatStokGudang::query()
            ->whereHas('gudang', function ($query) {
                $query->where('nama', '!=', 'Gudang ED');
            })
            ->whereIn('obat_id', $obatIds)
            ->select('obat_id', DB::raw('SUM(stok) as total_stock'))
            ->groupBy('obat_id')
            ->pluck('total_stock', 'obat_id');

        $obatMeta = Obat::withoutGlobalScope('active')
            ->with(['principal:id,nama'])
            ->whereIn('id', $obatIds)
            ->get(['id', 'is_generik', 'principal_id'])
            ->keyBy('id');

        $rows = $rawRows->map(function ($row) use ($obatMeta, $stockPerObat) {
            $obat = $obatMeta->get($row->obat_id);
            $principalNames = $obat && $obat->principal ? [$obat->principal->nama] : [];

            $isGenerik = $obat ? $obat->is_generik : null;
            $totalStock = (float) ($stockPerObat[$row->obat_id] ?? 0);

            return [
                'obat_id' => $row->obat_id,
                'obat_nama' => $row->obat_nama,
                'is_generik' => $isGenerik,
                'principal_names' => $principalNames,
                'total_stock' => round($totalStock, 2),
                'dibutuhkan' => round((float) $row->total_keluar, 2),
                'jumlah_resep' => (int) $row->jumlah_resep,
                'jumlah_kunjungan' => (int) $row->jumlah_kunjungan,
                'tanggal_terakhir' => $row->tanggal_terakhir,
            ];
        })->values();

        return response()->json([
            'period' => $period,
            'period_label' => $periodLabel,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'rows' => $rows,
        ]);
    }

    public function forecastKeluarDetail(Request $request, $id)
    {
        $periodConfig = $this->resolveForecastKeluarPeriod((string) $request->input('period', 'today'));
        $periodStart = $periodConfig['start'];
        $periodEnd = $periodConfig['end'];

        $obat = Obat::withInactive()->findOrFail($id, ['id', 'nama']);

        $sourceVisitations = $this->buildForecastKeluarSourceVisitationQuery($periodStart, $periodEnd);

        $rows = DB::query()
            ->fromSub($sourceVisitations, 'fv')
            ->join('erm_resepfarmasi as rf', 'fv.source_visitation_id', '=', 'rf.visitation_id')
            ->join('erm_visitations as target_visit', 'fv.target_visitation_id', '=', 'target_visit.id')
            ->leftJoin('erm_visitations as source_visit', 'fv.source_visitation_id', '=', 'source_visit.id')
            ->leftJoin('erm_pasiens as p', 'target_visit.pasien_id', '=', 'p.id')
            ->where('rf.obat_id', $obat->id)
            ->whereNotNull('fv.source_visitation_id')
            ->select(
                'fv.target_visitation_id',
                'fv.target_tanggal_visitation',
                'p.nama as pasien_nama',
                'fv.source_visitation_id as visitation_id',
                'source_visit.tanggal_visitation as tanggal_visitation',
                DB::raw('SUM(COALESCE(rf.jumlah, 0)) as jumlah')
            )
            ->groupBy(
                'p.nama',
                'fv.target_visitation_id',
                'fv.target_tanggal_visitation',
                'fv.source_visitation_id',
                'source_visit.tanggal_visitation'
            )
            ->orderByDesc('source_visit.tanggal_visitation')
            ->orderByDesc('fv.source_visitation_id')
            ->get()
            ->map(function ($resep) {
                return [
                    'visitation_id' => $resep->visitation_id,
                    'tanggal_visitation' => $resep->tanggal_visitation,
                    'pasien_nama' => $resep->pasien_nama ?? '-',
                    'target_visitation_id' => $resep->target_visitation_id,
                    'target_tanggal_visitation' => $resep->target_tanggal_visitation,
                    'jumlah' => round((float) ($resep->jumlah ?? 0), 2),
                ];
            })
            ->sortByDesc('tanggal_visitation')
            ->values();

        return response()->json([
            'obat_id' => $obat->id,
            'obat_nama' => $obat->nama,
            'period' => $periodConfig['period'],
            'period_label' => $periodConfig['label'],
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'rows' => $rows,
        ]);
    }

    public function forecastAll(Request $request)
    {
        $periodMonths = (int) $request->input('period_months', 3);
        $periodMonths = in_array($periodMonths, [1, 3, 6, 12], true) ? $periodMonths : 3;

        $pengadaanFrequency = (string) $request->input('pengadaan_frequency', 'monthly');
        $frequencyDivisors = [
            'monthly' => 1,
            'weekly' => 4,
            'twice_weekly' => 8,
        ];
        $divisor = $frequencyDivisors[$pengadaanFrequency] ?? $frequencyDivisors['monthly'];

        // Use previous full months (exclude current month).
        // Example: if today is July, and periodMonths=3 => use Apr 1 - Jun 30
        $periodEnd = Carbon::now()->startOfMonth()->subDay()->endOfDay();
        $periodStart = (clone $periodEnd)->subMonthsNoOverflow($periodMonths - 1)->startOfMonth()->startOfDay();

        $keluarPerObat = KartuStok::query()
            ->select('obat_id', DB::raw('SUM(qty) as total_keluar'))
            ->where('tipe', 'keluar')
            ->where('ref_type', 'invoice_penjualan')
            ->whereBetween('tanggal', [$periodStart, $periodEnd])
            ->groupBy('obat_id')
            ->pluck('total_keluar', 'obat_id');

        $stockPerObat = \App\Models\ERM\ObatStokGudang::query()
            ->whereHas('gudang', function ($query) {
                $query->where('nama', '!=', 'Gudang ED');
            })
            ->select('obat_id', DB::raw('SUM(stok) as total_stock'))
            ->groupBy('obat_id')
            ->pluck('total_stock', 'obat_id');

        $approvedOutstandingPermintaanPerObat = \App\Models\ERM\FakturBeliItem::query()
            ->join('erm_fakturbeli', 'erm_fakturbeli.id', '=', 'erm_fakturbeli_items.fakturbeli_id')
            ->whereNotNull('erm_fakturbeli.permintaan_id')
            ->whereIn('erm_fakturbeli.status', ['diminta', 'diterima'])
            ->select(
                'erm_fakturbeli_items.obat_id',
                DB::raw('SUM(CASE WHEN COALESCE(erm_fakturbeli_items.sisa, 0) > 0 THEN COALESCE(erm_fakturbeli_items.sisa, 0) ELSE GREATEST(COALESCE(erm_fakturbeli_items.diminta, 0) - COALESCE(erm_fakturbeli_items.qty, 0), 0) END) as total_outstanding')
            )
            ->groupBy('erm_fakturbeli_items.obat_id')
            ->pluck('total_outstanding', 'erm_fakturbeli_items.obat_id');

        $obats = Obat::query()
            ->with(['masterFakturs', 'principal:id,nama', 'zatAktifs'])
            ->orderBy('nama')
            ->get(['id', 'nama', 'is_generik', 'is_favorite', 'principal_id']);

        $rows = $obats->map(function ($obat) use ($periodMonths, $divisor, $keluarPerObat, $stockPerObat, $approvedOutstandingPermintaanPerObat) {
            $totalStock = (float) ($stockPerObat[$obat->id] ?? 0);
            $approvedOutstandingPermintaan = (float) ($approvedOutstandingPermintaanPerObat[$obat->id] ?? 0);
            $totalStockWithApprovedPermintaan = $totalStock + $approvedOutstandingPermintaan;
            $obatKeluar = (float) ($keluarPerObat[$obat->id] ?? 0);
            $averageMonthlyKeluarRaw = $periodMonths > 0 ? ($obatKeluar / $periodMonths) : 0;
            $limitStokRaw = $averageMonthlyKeluarRaw / $divisor;
            $qtyPesanRaw = $limitStokRaw * 3;

            $averageMonthlyKeluar = ceil($averageMonthlyKeluarRaw);
            $limitStok = ceil($limitStokRaw);
            $qtyPesan = ceil($qtyPesanRaw);

            $principalNames = $obat->principal ? [$obat->principal->nama] : [];

            $zatAktifNames = $obat->zatAktifs
                ->pluck('nama')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $latestMasterFaktur = $obat->masterFakturs
                ->sortByDesc('id')
                ->first();

            $qtyPerBox = $latestMasterFaktur ? (float) ($latestMasterFaktur->qty_per_box ?? 0) : 0;
            $jumlahPesanBox = $qtyPerBox > 0 ? ceil($qtyPesan / $qtyPerBox) : null;

            return [
                'obat_id' => $obat->id,
                'obat_nama' => $obat->nama,
                'is_generik' => $obat->is_generik,
                'principal_names' => $principalNames,
                'zat_aktif_names' => $zatAktifNames,
                'search_keywords' => trim(implode(' ', array_filter(array_merge([
                    $obat->nama,
                ], $zatAktifNames)))),
                'is_favorite' => (bool) $obat->is_favorite,
                'master_faktur_notes' => $latestMasterFaktur?->notes,
                'total_stock' => round($totalStock, 2),
                'approved_outstanding_permintaan' => round($approvedOutstandingPermintaan, 2),
                'total_stock_with_approved_permintaan' => round($totalStockWithApprovedPermintaan, 2),
                'obat_keluar' => round($obatKeluar, 2),
                'average_monthly_keluar' => $averageMonthlyKeluar,
                'limit_stok' => $limitStok,
                'qty_pesan' => $qtyPesan,
                'isi_per_box' => $qtyPerBox > 0 ? round($qtyPerBox, 2) : null,
                'jumlah_pesan_box' => $jumlahPesanBox,
            ];
        })->values();

        return response()->json([
            'period_months' => $periodMonths,
            'pengadaan_frequency' => $pengadaanFrequency,
            'formula_label' => '1/' . $divisor . ' x rata-rata keluar ' . $periodMonths . ' bulan',
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'rows' => $rows,
        ]);
    }

    public function exportForecastDisplayed(Request $request)
    {
        $validated = $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*' => 'array',
        ]);

        $headings = [
            'Nama Obat',
            'Total Stok',
            'Total Stok + Pending',
            'Pending Approved Belum Terpenuhi',
            'Obat Keluar',
            'Rata-rata Keluar / Bulan',
            'Kuota',
            'Limit Stok',
            'Qty Pesan',
            'Isi per Box',
            'Jumlah Pesan Box',
        ];

        $exportArray = collect($validated['rows'])
            ->map(function ($row) {
                return [
                    $row[0] ?? '',
                    $row[1] ?? 0,
                    $row[2] ?? 0,
                    $row[3] ?? 0,
                    $row[4] ?? 0,
                    $row[5] ?? 0,
                    $row[6] ?? 0,
                    $row[7] ?? 0,
                    $row[8] ?? 0,
                    $row[9] ?? '',
                    $row[10] ?? '',
                ];
            })
            ->values()
            ->all();

        $export = new class($exportArray, $headings) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            private $array;
            private $headings;

            public function __construct(array $array, array $headings)
            {
                $this->array = $array;
                $this->headings = $headings;
            }

            public function array(): array
            {
                return $this->array;
            }

            public function headings(): array
            {
                return $this->headings;
            }
        };

        return \Maatwebsite\Excel\Facades\Excel::download($export, 'forecast_pembelian_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function forecast(Request $request, $id)
    {
        $periodMonths = (int) $request->input('period_months', 3);
        $periodMonths = in_array($periodMonths, [1, 3, 6, 12], true) ? $periodMonths : 3;

        $pengadaanFrequency = (string) $request->input('pengadaan_frequency', 'monthly');
        $frequencyDivisors = [
            'monthly' => 1,
            'weekly' => 4,
            'twice_weekly' => 8,
        ];
        $divisor = $frequencyDivisors[$pengadaanFrequency] ?? $frequencyDivisors['monthly'];

        $obat = Obat::withInactive()->findOrFail($id);

        // Use previous full months (exclude current month). See comment above.
        $periodEnd = Carbon::now()->startOfMonth()->subDay()->endOfDay();
        $periodStart = (clone $periodEnd)->subMonthsNoOverflow($periodMonths - 1)->startOfMonth()->startOfDay();

        $totalStock = (float) $obat->stokGudang()
            ->whereHas('gudang', function ($query) {
                $query->where('nama', '!=', 'Gudang ED');
            })
            ->sum('stok');

        $obatKeluar = (float) KartuStok::query()
            ->where('obat_id', $obat->id)
            ->where('tipe', 'keluar')
            ->where('ref_type', 'invoice_penjualan')
            ->whereBetween('tanggal', [$periodStart, $periodEnd])
            ->sum('qty');

        $averageMonthlyKeluarRaw = $periodMonths > 0 ? ($obatKeluar / $periodMonths) : 0;
        $limitStokRaw = $averageMonthlyKeluarRaw / $divisor;
        $qtyPesanRaw = $limitStokRaw * 3;

        $averageMonthlyKeluar = ceil($averageMonthlyKeluarRaw);
        $limitStok = ceil($limitStokRaw);
        $qtyPesan = ceil($qtyPesanRaw);

        return response()->json([
            'obat_id' => $obat->id,
            'obat_nama' => $obat->nama,
            'period_months' => $periodMonths,
            'pengadaan_frequency' => $pengadaanFrequency,
            'total_stock' => round($totalStock, 2),
            'obat_keluar' => round($obatKeluar, 2),
            'average_monthly_keluar' => $averageMonthlyKeluar,
            'limit_stok' => $limitStok,
            'qty_pesan' => $qtyPesan,
            'formula_label' => '1/' . $divisor . ' x rata-rata keluar ' . $periodMonths . ' bulan',
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
        ]);
    }

    /**
     * Name for duplicate checks: uppercase, punctuation and spaces removed ("Paracetamol 500mg" == "PARACETAMOL 500 MG").
     */
    private function compactNama(string $nama): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($nama));
    }

    /**
     * Same or similar obat names, for the warning shown while typing in the obat form.
     * GET ?nama=...&exclude_id=... -> { exact: bool, matches: [...] } (max 5, inactive included).
     */
    public function checkNama(Request $request)
    {
        $nama = trim((string) $request->input('nama', ''));
        $compact = $this->compactNama($nama);
        if (strlen($compact) < 3) {
            return response()->json(['exact' => false, 'matches' => []]);
        }

        // Candidates: any of the 3 longest words (>= 3 chars) appears in the name
        $words = collect(preg_split('/[^A-Z0-9]+/', strtoupper($nama)))
            ->filter(fn ($w) => strlen($w) >= 3)
            ->sortByDesc(fn ($w) => strlen($w))
            ->take(3)
            ->values();
        if ($words->isEmpty()) {
            $words = collect([$compact]);
        }

        $candidates = Obat::withInactive()
            ->when($request->filled('exclude_id'), fn ($q) => $q->where('id', '!=', $request->exclude_id))
            ->where(function ($q) use ($words) {
                foreach ($words as $w) {
                    $q->orWhere('nama', 'LIKE', "%{$w}%");
                }
            })
            ->limit(300)
            ->get(['id', 'kode_obat', 'nama', 'kategori', 'status_aktif']);

        $matches = $candidates->map(function ($obat) use ($compact) {
            $other = $this->compactNama((string) $obat->nama);
            if ($other === '') {
                return null;
            }
            $exact = $other === $compact;
            similar_text($compact, $other, $percent);
            // One name contained in the other ("AMLODIPIN 5" vs "AMLODIPIN 5 MG TAB") also counts as similar
            $shorter = min(strlen($compact), strlen($other));
            if (!$exact && $shorter >= 5 && (str_contains($other, $compact) || str_contains($compact, $other))) {
                $percent = max($percent, 85);
            }
            if (!$exact && $percent < 75) {
                return null;
            }

            return [
                'id' => $obat->id,
                'kode_obat' => $obat->kode_obat,
                'nama' => $obat->nama,
                'kategori' => $obat->kategori,
                'status_aktif' => (int) $obat->status_aktif,
                'exact' => $exact,
                'score' => $exact ? 100 : (int) round($percent),
            ];
        })->filter()->sortByDesc('score')->take(5)->values();

        return response()->json([
            'exact' => $matches->contains('exact', true),
            'matches' => $matches,
        ]);
    }

    public function create()
    {
        // Single entry form lives in the master obat modal
        return redirect()->route('erm.obat.index', ['tambah' => 1]);
    }

    public function store(Request $request)
    {
        $request->validate($this->obatRules(false), self::OBAT_MESSAGES, self::OBAT_ATTRIBUTES);

        try {
            $obat = DB::transaction(function () use ($request) {
                $data = $request->only(self::EDITABLE_FIELDS);
                $data['is_generik'] = $request->boolean('is_generik');
                $data['status_aktif'] = $request->input('status_aktif', 1);

                $obat = Obat::create($data);
                $this->syncZatAktif($request, $obat);

                return $obat;
            });

            return response()->json(['success' => true, 'message' => 'Obat berhasil ditambahkan', 'id' => $obat->id]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan obat: ' . $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        if (!request()->ajax() && !request()->wantsJson()) {
            return redirect()->route('erm.obat.index');
        }

        $obat = Obat::withInactive()->with(['zatAktifs', 'principal:id,nama'])->findOrFail($id);

        // Stock shown to the frontend comes from the resep gudang (or all gudang when unmapped)
        $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');
        $stokGudang = $gudangId ? (float) $obat->getStokByGudang($gudangId) : (float) $obat->total_stok;

        return response()->json([
            'id' => $obat->id,
            'kode_obat' => $obat->kode_obat,
            'kode_obat_lama' => $obat->kode_obat_lama,
            'nama' => $obat->nama,
            'hpp' => $obat->hpp,
            'hna' => $obat->hna,
            'harga_nonfornas' => $obat->harga_nonfornas,
            'metode_bayar_id' => $obat->metode_bayar_id,
            'kategori' => $obat->kategori,
            'is_generik' => $obat->is_generik,
            'principal_id' => $obat->principal_id,
            'principal_nama' => optional($obat->principal)->nama,
            'zataktif_id' => $obat->zatAktifs->pluck('id')->toArray(),
            'zataktif' => $obat->zatAktifs->map(fn ($z) => ['id' => $z->id, 'nama' => $z->nama])->values(),
            'dosis' => $obat->dosis,
            'satuan' => $obat->satuan,
            'satuan_stok' => $obat->satuan_stok,
            'status_aktif' => $obat->status_aktif,
            'stok' => $stokGudang,
            'stok_gudang' => $stokGudang,
        ]);
    }

    public function search(Request $request)
    {
        $query = trim((string) $request->get('q'));
        $metodeBayarId = $request->get('metode_bayar_id');
        $visitationId = $request->get('visitation_id');
        $searchTerms = $this->buildObatSearchTerms($query);
        $relatedZatAktifIds = $this->findRelatedZatAktifIds($searchTerms);

        // Search by obat name, dosis, satuan, or zat aktif name, and filter by metode_bayar_id if provided
        $obatsQuery = Obat::where('status_aktif', 1)
            ->where(function($q) use ($searchTerms, $relatedZatAktifIds) {
                foreach ($searchTerms as $term) {
                    $q->orWhere('nama', 'LIKE', "%{$term}%")
                      ->orWhere('dosis', 'LIKE', "%{$term}%")
                      ->orWhere('satuan', 'LIKE', "%{$term}%")
                      ->orWhereHas('zatAktifs', function($z) use ($term) {
                          $z->where('nama', 'LIKE', "%{$term}%");
                      });
                }

                if (!empty($relatedZatAktifIds)) {
                    $q->orWhereHas('zatAktifs', function ($z) use ($relatedZatAktifIds) {
                        $z->whereIn('erm_zataktif.id', $relatedZatAktifIds);
                    });
                }
            });
        // If visitation_id provided, exclude obat that contain any zat aktif the patient is allergic to
        if ($visitationId) {
            $visitation = Visitation::find($visitationId);
            if ($visitation && $visitation->pasien_id) {
                $zatAlergi = DB::table('erm_alergi')
                    ->where('pasien_id', $visitation->pasien_id)
                    ->whereNotNull('zataktif_id')
                    ->pluck('zataktif_id')
                    ->filter()
                    ->toArray();

                if (!empty($zatAlergi)) {
                    $obatsQuery->whereDoesntHave('zatAktifs', function ($q) use ($zatAlergi) {
                        $q->whereIn('erm_zataktif.id', $zatAlergi);
                    });
                }
            }
        }
        if ($metodeBayarId) {
            // Allow obat for the same metode_bayar_id OR obat whose metode_bayar_id
            // is mapped to this visitation metode_bayar via `erm_obat_mappings`.
            $mapped = ObatMapping::where('visitation_metode_bayar_id', $metodeBayarId)
                ->where('is_active', true)
                ->pluck('obat_metode_bayar_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            // Include the visitation metode bayars itself
            $allowedMetodeBayarIds = array_merge([$metodeBayarId], $mapped);

            $obatsQuery->whereIn('metode_bayar_id', $allowedMetodeBayarIds);
        }
        $obats = $obatsQuery->limit(20)->get();

        // Return the data in Select2 format (with 'results' key)
        // Get mapped gudang for resep
        $gudangId = \App\Models\ERM\GudangMapping::getDefaultGudangId('resep');

        $results = $obats->map(function ($obat) use ($gudangId) {
            $zatAktifNames = $obat->zatAktifs->pluck('nama')->map(function($nama) {
                return ucwords(strtolower($nama));
            })->implode(', ');
            $text = $obat->nama;
            if ($zatAktifNames) {
                $text .= ' [' . $zatAktifNames . ']';
            }
            $text .= ' - ' . $obat->dosis . ' ' . $obat->satuan;

            // Get stok for mapped gudang (guard against missing relation)
            $stokGudang = ($gudangId && $obat) ? (int) $obat->getStokByGudang($gudangId) : 0;

            return [
                'id' => $obat->id,
                'text' => $text,
                'nama' => $obat->nama,
                'zat_aktif' => $zatAktifNames,
                'dosis' => $obat->dosis,
                'satuan' => $obat->satuan,
                'satuan_stok' => $obat->satuan_stok,
                // Use gudang stock as the authoritative 'stok' for frontend checks
                'stok' => $stokGudang,
                'stok_gudang' => $stokGudang,
                'harga_nonfornas' => $obat->harga_nonfornas,
                'harga_nonfornas_formatted' => $obat->harga_nonfornas !== null
                    ? 'Rp ' . number_format((float) $obat->harga_nonfornas, 0, ',', '.')
                    : null,
            ];
        })->values();
        return response()->json(['results' => $results]);
    }

    private function buildObatSearchTerms(string $query): array
    {
        if ($query === '') {
            return [''];
        }

        $normalized = Str::lower($query);

        return collect([
            $query,
            $normalized,
            $this->normalizeObatSearchTerm($normalized),
            Str::endsWith($normalized, 'in') ? $normalized . 'e' : null,
            Str::endsWith($normalized, 'ine') ? substr($normalized, 0, -1) : null,
        ])
            ->filter(fn ($term) => filled($term))
            ->unique()
            ->values()
            ->all();
    }

    private function findRelatedZatAktifIds(array $searchTerms): array
    {
        if (empty($searchTerms)) {
            return [];
        }

        $matchedObatIds = Obat::query()
            ->where(function ($query) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $query->orWhere('nama', 'LIKE', "%{$term}%")
                        ->orWhere('dosis', 'LIKE', "%{$term}%")
                        ->orWhere('satuan', 'LIKE', "%{$term}%");
                }
            })
            ->pluck('id');

        $zatAktifIds = ZatAktif::query()
            ->where(function ($query) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $query->orWhere('nama', 'LIKE', "%{$term}%");
                }
            })
            ->pluck('id');

        if ($matchedObatIds->isNotEmpty()) {
            $obatZatAktifIds = DB::table('erm_kandungan_obat')
                ->whereIn('obat_id', $matchedObatIds)
                ->pluck('zataktif_id');

            $zatAktifIds = $zatAktifIds->merge($obatZatAktifIds);
        }

        return $zatAktifIds
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeObatSearchTerm(string $term): string
    {
        $normalized = str_replace(['ph', 'y'], ['f', 'i'], $term);

        if (Str::endsWith($normalized, 'ine')) {
            return substr($normalized, 0, -1);
        }

        if (Str::endsWith($normalized, 'in')) {
            return $normalized . 'e';
        }

        return $normalized;
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['Admin'])) {
            return response()->json(['success' => false, 'message' => 'Hanya Admin yang dapat menghapus obat.'], 403);
        }

        $obat = Obat::withInactive()->findOrFail($id);

        // erm_obat FKs cascade into resep, stok gudang, bundles etc. — never delete a used obat.
        if ($obat->isUsed()) {
            return response()->json([
                'success' => false,
                'message' => 'Obat sudah dipakai di transaksi/master lain dan tidak bisa dihapus. Ubah status menjadi Tidak Aktif.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($obat) {
                $obat->zatAktifs()->detach();
                $obat->delete();
            });

            return response()->json(['success' => true, 'message' => 'Obat berhasil dihapus']);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Gagal menghapus obat: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Return unique pemasok and principal lists for a given obat based on faktur items.
     */
    public function relations($id)
    {
        // For pemasok: count distinct faktur per pemasok where fakturbeli_items.obat_id = $id
        $pemasoks = \App\Models\ERM\FakturBeliItem::where('obat_id', $id)
            ->join('erm_fakturbeli as f', 'erm_fakturbeli_items.fakturbeli_id', '=', 'f.id')
            ->join('erm_pemasok as pm', 'f.pemasok_id', '=', 'pm.id')
            ->select('pm.id', 'pm.nama', DB::raw('COUNT(DISTINCT f.id) as jumlah_faktur'))
            ->groupBy('pm.id', 'pm.nama')
            ->orderBy('jumlah_faktur', 'desc')
            ->get();

        // For principals: count distinct faktur per principal where fakturbeli_items.obat_id = $id
        $principals = \App\Models\ERM\FakturBeliItem::where('obat_id', $id)
            ->whereNotNull('principal_id')
            ->join('erm_principals as pr', 'erm_fakturbeli_items.principal_id', '=', 'pr.id')
            ->join('erm_fakturbeli as f2', 'erm_fakturbeli_items.fakturbeli_id', '=', 'f2.id')
            ->select('pr.id', 'pr.nama', DB::raw('COUNT(DISTINCT f2.id) as jumlah_faktur'))
            ->groupBy('pr.id', 'pr.nama')
            ->orderBy('jumlah_faktur', 'desc')
            ->get();

        return response()->json([
            'pemasoks' => $pemasoks,
            'principals' => $principals,
        ]);
    }

    /**
     * Columns the CSV import can update, keyed by obat field. Header matching ignores case,
     * spaces and underscores, so the headers written by ObatExport are accepted as-is.
     */
    private const CSV_COLUMNS = [
        'nama' => ['label' => 'Nama', 'type' => 'string', 'headers' => ['nama', 'name']],
        'dosis' => ['label' => 'Dosis', 'type' => 'string', 'headers' => ['dosis']],
        'satuan' => ['label' => 'Satuan Dosis', 'type' => 'satuan_dosis', 'headers' => ['satuandosis', 'satuan']],
        'satuan_stok' => ['label' => 'Satuan Stok', 'type' => 'satuan_stok', 'headers' => ['satuanstok', 'satuanstokjual', 'satuanjual']],
        'is_generik' => ['label' => 'Generik', 'type' => 'boolean', 'headers' => ['generik', 'isgenerik']],
        'kategori' => ['label' => 'Kategori', 'type' => 'kategori', 'headers' => ['kategori']],
        'metode_bayar_id' => ['label' => 'Metode Bayar', 'type' => 'metode_bayar', 'headers' => ['metodebayar']],
        'harga_nonfornas' => ['label' => 'Harga Jual', 'type' => 'decimal', 'headers' => ['hargajual', 'harganonfornas']],
    ];

    private function normalizeCsvHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);

        return strtolower(preg_replace('/[\s_\-]+/', '', trim($header)));
    }

    private function toUtf8(string $value): string
    {
        return mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }

    /**
     * Parse an obat CSV into per-row change sets without writing anything.
     *
     * @return array{rows: array, ignored_columns: string[], error?: string}
     */
    private function parseObatCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ['rows' => [], 'ignored_columns' => [], 'error' => 'File tidak dapat dibaca.'];
        }

        // Excel with Indonesian locale saves CSV with ';'
        $firstLine = (string) fgets($handle);
        rewind($handle);
        $counts = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        arsort($counts);
        $delimiter = array_key_first($counts);

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return ['rows' => [], 'ignored_columns' => [], 'error' => 'File kosong.'];
        }
        $header = array_map(fn ($h) => $this->normalizeCsvHeader((string) $h), $header);

        $idIndex = array_search('id', $header, true);
        if ($idIndex === false) {
            fclose($handle);
            return ['rows' => [], 'ignored_columns' => [], 'error' => 'Kolom ID tidak ditemukan di header.'];
        }

        $columnIndex = [];
        foreach (self::CSV_COLUMNS as $field => $def) {
            foreach ($def['headers'] as $alias) {
                $idx = array_search($alias, $header, true);
                if ($idx !== false) {
                    $columnIndex[$field] = $idx;
                    break;
                }
            }
        }
        $knownHeaders = array_merge(['id'], ...array_column(self::CSV_COLUMNS, 'headers'));
        $ignored = array_values(array_filter($header, fn ($h) => $h !== '' && !in_array($h, $knownHeaders, true)));

        $metodeByName = MetodeBayar::all()->mapWithKeys(fn ($m) => [strtolower(trim($m->nama)) => $m->id]);
        $kategoriByName = collect(Obat::KATEGORI_LIST)->mapWithKeys(fn ($k) => [strtolower($k) => $k]);

        $rows = [];
        $seenIds = [];
        $line = 1;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if ($row === [null] || count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $errors = [];
            // Trailing empty cells are common in spreadsheet exports; anything else is a broken row
            if (count($row) > count($header)) {
                $extra = array_slice($row, count($header));
                if (count(array_filter($extra, fn ($v) => trim((string) $v) !== '')) > 0) {
                    $errors[] = 'Jumlah kolom tidak sesuai header';
                }
            }
            $row = array_pad($row, count($header), '');

            $id = trim((string) $row[$idIndex]);
            if ($id === '' || !ctype_digit($id)) {
                $rows[] = ['line' => $line, 'id' => $id, 'found' => false, 'existing' => [], 'new' => [], 'changes' => false, 'errors' => ['ID tidak valid']];
                continue;
            }
            if (isset($seenIds[$id])) {
                $errors[] = 'ID duplikat (baris ' . $seenIds[$id] . ')';
            }
            $seenIds[$id] = $line;

            $obat = Obat::withInactive()->find($id);
            if (!$obat) {
                $errors[] = 'ID tidak ditemukan';
            }

            $new = [];
            foreach ($columnIndex as $field => $idx) {
                $raw = trim($this->toUtf8((string) $row[$idx]));
                if ($raw === '') {
                    continue; // empty cell = keep existing value
                }
                $label = self::CSV_COLUMNS[$field]['label'];
                switch (self::CSV_COLUMNS[$field]['type']) {
                    case 'decimal':
                        $value = $this->normalizeCsvDecimal($raw);
                        if ($value === null || (float) $value < 0) {
                            $errors[] = "$label tidak valid: $raw";
                            continue 2;
                        }
                        break;
                    case 'boolean':
                        $value = $this->normalizeCsvBoolean($raw);
                        if ($value === null || !in_array($value, [0, 1], true)) {
                            $errors[] = "$label harus 1/0 atau Ya/Tidak: $raw";
                            continue 2;
                        }
                        break;
                    case 'kategori':
                        $value = $kategoriByName[strtolower($raw)] ?? null;
                        if ($value === null) {
                            $errors[] = "Kategori tidak dikenal: $raw";
                            continue 2;
                        }
                        break;
                    case 'satuan_dosis':
                    case 'satuan_stok':
                        $value = Obat::normalizeSatuan($raw);
                        $allowed = self::CSV_COLUMNS[$field]['type'] === 'satuan_stok'
                            ? Obat::SATUAN_STOK_LIST
                            : Obat::satuanDosisOptions();
                        if (!in_array($value, $allowed, true)) {
                            $errors[] = "$label tidak dikenal: $raw";
                            continue 2;
                        }
                        break;
                    case 'metode_bayar':
                        $value = $metodeByName[strtolower($raw)] ?? null;
                        if ($value === null) {
                            $errors[] = "Metode Bayar tidak dikenal: $raw";
                            continue 2;
                        }
                        break;
                    default:
                        $value = $raw;
                }
                $new[$field] = $value;
            }

            $existing = [];
            $changed = [];
            foreach (array_keys(self::CSV_COLUMNS) as $field) {
                $existing[$field] = $obat ? $obat->getAttributes()[$field] ?? null : null;
                if ($obat && array_key_exists($field, $new)
                    && $this->csvValueChanged($existing[$field], $new[$field], self::CSV_COLUMNS[$field]['type'] === 'decimal')) {
                    $changed[$field] = $new[$field];
                }
            }

            $rows[] = [
                'line' => $line,
                'id' => (int) $id,
                'found' => (bool) $obat,
                'existing' => $existing,
                'new' => $new,
                'changed' => $changed,
                'changes' => !empty($changed),
                'errors' => $errors,
            ];
        }
        fclose($handle);

        return ['rows' => $rows, 'ignored_columns' => $ignored];
    }

    private function csvColumnsMeta(): array
    {
        $meta = [];
        foreach (self::CSV_COLUMNS as $field => $def) {
            $meta[] = ['key' => $field, 'label' => $def['label'], 'type' => $def['type']];
        }

        return $meta;
    }

    public function importCsvPreview(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $parsed = $this->parseObatCsv($request->file('csv_file')->getRealPath());
        if (isset($parsed['error'])) {
            return response()->json(['message' => $parsed['error']], 422);
        }

        return response()->json([
            'rows' => $parsed['rows'],
            'columns' => $this->csvColumnsMeta(),
            'ignored_columns' => $parsed['ignored_columns'],
            'metode_bayar' => MetodeBayar::pluck('nama', 'id'),
        ]);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $parsed = $this->parseObatCsv($request->file('csv_file')->getRealPath());
        if (isset($parsed['error'])) {
            return response()->json(['success' => false, 'message' => $parsed['error']], 422);
        }

        $updated = 0;
        $skipped = [];
        try {
            DB::transaction(function () use ($parsed, &$updated, &$skipped) {
                foreach ($parsed['rows'] as $row) {
                    if (!empty($row['errors'])) {
                        $skipped[] = 'Baris ' . $row['line'] . ' (ID ' . $row['id'] . '): ' . implode('; ', $row['errors']);
                        continue;
                    }
                    if (!$row['changes']) {
                        continue;
                    }
                    Obat::withInactive()->whereKey($row['id'])->first()->update($row['changed']);
                    $updated++;
                }
            });
        } catch (\Exception $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Import gagal, tidak ada data yang diubah: ' . $e->getMessage()], 500);
        }

        $message = "Import selesai. Diperbarui: {$updated} obat.";
        if (!empty($skipped)) {
            $message .= ' Dilewati: ' . count($skipped) . ' baris.';
        }

        return response()->json(['success' => true, 'message' => $message, 'updated' => $updated, 'skipped' => $skipped]);
    }

    /**
     * Export Obat data to Excel
     */
    public function exportExcel(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ObatExport($request), 'data_obat.xlsx');
    }
}
