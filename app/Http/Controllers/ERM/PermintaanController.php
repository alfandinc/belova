<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Permintaan;
use App\Models\ERM\PermintaanItem;
use App\Models\ERM\Obat;
use App\Models\ERM\FakturBeli;
use App\Models\ERM\FakturBeliItem;
use App\Models\ERM\MasterFaktur;
use App\Models\ERM\Principal;
use App\Services\ERM\MasterPembelianService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Permintaan Pembelian: waiting_approval -> approved (a faktur "diminta" is created per pemasok) or rejected.
 * Only a waiting permintaan can be edited or deleted; after approval the faktur carries the purchase.
 */
class PermintaanController extends Controller
{
    private const STATUS_LABEL = [
        'waiting_approval' => 'Menunggu approval',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    private function itemRules(): array
    {
        return [
            'request_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.pemasok_id' => 'required|exists:erm_pemasok,id',
            'items.*.principal_id' => 'nullable|exists:erm_principals,id',
            'items.*.jumlah_box' => 'required|integer|min:0',
            'items.*.qty_total' => 'required|integer|min:1',
        ];
    }

    private function ppnRate(): float
    {
        return (float) config('pembelian.ppn_persen', 11);
    }

    public function index()
    {
        $principals = Principal::orderBy('nama')->get(['id', 'nama']);

        return view('erm.permintaan.index', compact('principals'));
    }

    // The form lives in a modal on the Permintaan page; old links land there with the modal opened
    public function create()
    {
        return redirect()->route('erm.permintaan.index', ['baru' => 1]);
    }

    public function edit($id)
    {
        $permintaan = Permintaan::findOrFail($id);
        if ($permintaan->status !== 'waiting_approval') {
            return redirect()->route('erm.permintaan.index')
                ->with('error', 'Permintaan ' . $permintaan->no_permintaan . ' sudah diproses dan tidak bisa diubah lagi.');
        }

        return redirect()->route('erm.permintaan.index', ['ubah' => $permintaan->id]);
    }

    /** A waiting permintaan for the edit modal, as JSON. */
    public function formData($id)
    {
        $permintaan = Permintaan::with(['items.obat' => fn ($q) => $q->withInactive(), 'items.pemasok:id,nama', 'items.principal:id,nama'])->findOrFail($id);
        if ($permintaan->status !== 'waiting_approval') {
            return response()->json(['success' => false, 'message' => 'Permintaan ' . $permintaan->no_permintaan . ' sudah diproses dan tidak bisa diubah lagi.'], 422);
        }

        return response()->json([
            'id' => $permintaan->id,
            'no_permintaan' => $permintaan->no_permintaan,
            'request_date' => $permintaan->request_date,
            'notes' => $permintaan->notes,
            'items' => $permintaan->items->map(fn ($item) => [
                'obat_id' => $item->obat_id,
                'obat_nama' => optional($item->obat)->nama,
                'pemasok_id' => $item->pemasok_id,
                'pemasok_nama' => optional($item->pemasok)->nama,
                'principal_id' => $item->principal_id,
                'principal_nama' => optional($item->principal)->nama,
                'jumlah_box' => (int) $item->jumlah_box,
                'qty_total' => (int) $item->qty_total,
            ])->values(),
            'info' => $this->buildObatInfo($permintaan->items->pluck('obat_id')->unique()->values()->all()),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->itemRules());
        if ($msg = Obat::satuanStokRequiredMessage(array_column($request->items, 'obat_id'))) {
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        // One permintaan per pemasok: each becomes its own faktur after approval
        $nomor = DB::transaction(function () use ($request) {
            $created = [];
            foreach (collect($request->items)->groupBy('pemasok_id') as $items) {
                $permintaan = Permintaan::create([
                    'no_permintaan' => $this->generateNoPermintaan(),
                    'request_date' => $request->request_date,
                    'status' => 'waiting_approval',
                    'created_by' => Auth::id(),
                    'notes' => $request->notes ?: null,
                ]);
                $this->saveItems($permintaan, $items->all());
                $created[] = $permintaan->no_permintaan;
            }
            return $created;
        });

        return response()->json([
            'success' => true,
            'message' => count($nomor) > 1
                ? count($nomor) . ' permintaan dibuat (satu per pemasok): ' . implode(', ', $nomor) . '.'
                : 'Permintaan ' . $nomor[0] . ' dibuat dan menunggu approval.',
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->itemRules());
        if ($msg = Obat::satuanStokRequiredMessage(array_column($request->items, 'obat_id'))) {
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        try {
            DB::transaction(function () use ($request, $id) {
                $permintaan = Permintaan::lockForUpdate()->findOrFail($id);
                // After approval the faktur items point to these permintaan items
                if ($permintaan->status !== 'waiting_approval') {
                    throw new \DomainException('Permintaan ' . $permintaan->no_permintaan . ' sudah diproses dan tidak bisa diubah lagi.');
                }
                $permintaan->update([
                    'request_date' => $request->request_date,
                    'notes' => $request->notes ?: null,
                ]);
                PermintaanItem::where('permintaan_id', $permintaan->id)->delete();
                $this->saveItems($permintaan, $request->items);
            });
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Permintaan berhasil diperbarui.']);
    }

    private function saveItems(Permintaan $permintaan, array $items): void
    {
        $masterPembelian = app(MasterPembelianService::class);
        foreach ($items as $item) {
            PermintaanItem::create([
                'permintaan_id' => $permintaan->id,
                'obat_id' => $item['obat_id'],
                'pemasok_id' => $item['pemasok_id'],
                'principal_id' => ($item['principal_id'] ?? null) ?: $masterPembelian->principalFor((int) $item['obat_id']),
                'jumlah_box' => $item['jumlah_box'],
                'qty_total' => $item['qty_total'],
            ]);
        }
    }

    /**
     * PRYYYYMMDD-NNN. Called inside a transaction: the last number of the day is read locked,
     * and the unique index on no_permintaan stops any remaining collision.
     */
    protected function generateNoPermintaan(): string
    {
        $prefix = 'PR' . date('Ymd') . '-';
        $last = Permintaan::where('no_permintaan', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('no_permintaan')
            ->value('no_permintaan');
        $num = ($last && preg_match('/-(\d+)$/', $last, $m)) ? ((int) $m[1] + 1) : 1;

        return $prefix . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
    }

    // DataTables AJAX endpoint
    public function data(Request $request)
    {
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $search = trim((string) $request->input('search.value'));

        // Period, principal and search; the status tab is applied on top so the tab counts follow them
        $filtered = function ($query) use ($request, $search) {
            $range = explode(' - ', (string) $request->input('request_date_range'));
            if (count($range) === 2) {
                try {
                    $query->whereBetween('request_date', [
                        Carbon::createFromFormat('Y-m-d', trim($range[0]))->toDateString(),
                        Carbon::createFromFormat('Y-m-d', trim($range[1]))->toDateString(),
                    ]);
                } catch (\Exception $e) {
                    // invalid range: no period filter
                }
            } elseif (!$request->filled('request_date_range')) {
                $query->whereBetween('request_date', [Carbon::now()->subMonthsNoOverflow(3)->toDateString(), Carbon::now()->toDateString()]);
            }

            if ($principalId = $request->input('principal_id')) {
                $query->whereHas('items', function ($itemQuery) use ($principalId) {
                    $itemQuery->where('principal_id', $principalId)
                        ->orWhere(function ($fallback) use ($principalId) {
                            $fallback->whereNull('principal_id')
                                ->whereHas('obat', fn ($o) => $o->withInactive()->where('principal_id', $principalId));
                        });
                });
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('no_permintaan', 'like', "%{$search}%")
                        ->orWhereHas('items.pemasok', fn ($s) => $s->where('nama', 'like', "%{$search}%"))
                        ->orWhereHas('items.obat', fn ($s) => $s->withInactive()->where('nama', 'like', "%{$search}%"));
                });
            }
        };

        $counts = Permintaan::query()->where($filtered)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $status = in_array($request->input('status'), array_keys(self::STATUS_LABEL), true) ? $request->input('status') : null;
        $base = Permintaan::query()->where($filtered)->when($status, fn ($q) => $q->where('status', $status));

        $filteredCount = (clone $base)->count();
        $totalNilai = $this->totalNilaiPembelian((clone $base)->select('erm_permintaan.id'));

        $permintaans = (clone $base)
            ->with([
                'items.obat' => fn ($q) => $q->withInactive()->with('principal:id,nama'),
                'items.pemasok:id,nama',
                'items.principal:id,nama',
                'items.fakturBeliItems:id,permintaan_item_id,qty',
                'creator:id,name',
                'approver:id,name',
                'rejecter:id,name',
                'fakturBelis:id,permintaan_id,no_faktur,status',
            ])
            ->orderByRaw("CASE WHEN status = 'waiting_approval' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->skip($start)
            ->take($length > 0 ? $length : 25)
            ->get();

        $masterFakturs = $this->buildMasterFakturMap($permintaans);
        $legacyFakturItems = $this->buildLegacyFakturItemsMap($permintaans);

        $data = $permintaans->values()->map(function ($p, $i) use ($start, $masterFakturs, $legacyFakturItems) {
            $obatList = $p->items->map(function ($item) {
                $principalName = optional($item->principal)->nama ?? optional(optional($item->obat)->principal)->nama;
                $jenis = optional($item->obat)->is_generik ? 'Generik' : 'Paten';
                return '<div class="permintaan-stack-row">'
                    . '<div class="font-weight-medium">' . e(optional($item->obat)->nama ?? '-') . '</div>'
                    . '<div class="mt-1">'
                        . ($principalName ? '<span class="badge badge-info mr-1">' . e($principalName) . '</span>' : '')
                        . '<span class="badge ' . ($jenis === 'Generik' ? 'badge-success' : 'badge-primary') . '">' . $jenis . '</span>'
                    . '</div></div>';
            })->implode('') ?: '-';

            $qtyDiminta = $p->items->map(function ($item) {
                return '<div class="permintaan-stack-row text-right"><span class="font-weight-medium">'
                    . number_format((float) $item->qty_total, 0, ',', '.') . '</span>'
                    . '<small class="text-muted">' . number_format((float) $item->jumlah_box, 0, ',', '.') . ' box</small></div>';
            })->implode('') ?: '-';

            $qtyTerpenuhi = $p->items->map(function ($item) use ($p, $legacyFakturItems) {
                $terpenuhi = $this->calculatePermintaanItemTerpenuhi($p, $item, $legacyFakturItems);
                $class = $terpenuhi < (float) $item->qty_total ? 'text-danger' : 'text-success';
                return '<div class="permintaan-stack-row text-right"><span class="font-weight-medium ' . $class . '">'
                    . number_format($terpenuhi, 0, ',', '.') . '</span></div>';
            })->implode('') ?: '-';

            return [
                'id' => $p->id,
                'no' => $start + $i + 1,
                'no_permintaan' => $p->no_permintaan,
                'pemasok' => optional(optional($p->items->first())->pemasok)->nama ?? '-',
                'obats' => $obatList,
                'qty_diminta' => $qtyDiminta,
                'qty_terpenuhi' => $qtyTerpenuhi,
                'request_date' => $p->request_date,
                'total_nilai_pembelian' => $this->calculatePermintaanPurchaseValue($p, $masterFakturs),
                'status' => $p->status,
                'status_label' => self::STATUS_LABEL[$p->status] ?? $p->status,
                'dibuat_oleh' => optional($p->creator)->name,
                'approved_by_name' => optional($p->approver)->name,
                'approved_date' => optional($p->approved_date)->format('d/m/Y H:i'),
                'rejected_by_name' => optional($p->rejecter)->name,
                'rejected_reason' => $p->rejected_reason,
                'notes' => $p->notes,
                'fakturs' => $p->fakturBelis->map(fn ($f) => [
                    'id' => $f->id,
                    'no_faktur' => $f->no_faktur,
                    'status' => $f->status,
                ])->values(),
                'jumlah_item' => $p->items->count(),
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => Permintaan::count(),
            'recordsFiltered' => $filteredCount,
            'data' => $data,
            'total_nilai_pembelian_filtered' => $totalNilai,
            'counts' => [
                'all' => (int) $counts->sum(),
                'waiting_approval' => (int) ($counts['waiting_approval'] ?? 0),
                'approved' => (int) ($counts['approved'] ?? 0),
                'rejected' => (int) ($counts['rejected'] ?? 0),
            ],
        ]);
    }

    /**
     * Estimated value of the given permintaan (subquery of ids) at Master Pembelian prices, in SQL:
     * qty x harga minus discount (percent of the line, or a nominal amount per line), plus PPN.
     */
    private function totalNilaiPembelian($idQuery): float
    {
        $line = 'GREATEST(mf.harga * pi.qty_total - CASE WHEN LOWER(mf.diskon_type) IN (\'persen\', \'percent\', \'%\') '
            . 'THEN mf.harga * pi.qty_total * mf.diskon / 100 ELSE COALESCE(mf.diskon, 0) END, 0)';

        $sum = DB::table('erm_permintaan_items as pi')
            ->join('erm_master_faktur as mf', function ($join) {
                $join->on('mf.obat_id', '=', 'pi.obat_id')->on('mf.pemasok_id', '=', 'pi.pemasok_id');
            })
            ->whereIn('pi.permintaan_id', $idQuery)
            ->sum(DB::raw($line));

        return round((float) $sum * (1 + $this->ppnRate() / 100), 2);
    }

    protected function buildMasterFakturMap($permintaans)
    {
        $obatIds = collect($permintaans)->flatMap(fn ($p) => collect($p->items)->pluck('obat_id'))->filter()->unique()->values();
        $pemasokIds = collect($permintaans)->flatMap(fn ($p) => collect($p->items)->pluck('pemasok_id'))->filter()->unique()->values();

        if ($obatIds->isEmpty() || $pemasokIds->isEmpty()) {
            return collect();
        }

        return MasterFaktur::query()
            ->whereIn('obat_id', $obatIds)
            ->whereIn('pemasok_id', $pemasokIds)
            ->get(['obat_id', 'pemasok_id', 'harga', 'diskon', 'diskon_type'])
            ->keyBy(fn ($master) => $master->obat_id . '|' . $master->pemasok_id);
    }

    protected function calculatePermintaanPurchaseValue($permintaan, $masterFakturs)
    {
        return (float) collect($permintaan->items)->sum(fn ($item) => $this->calculateItemPurchaseValue($item, $masterFakturs));
    }

    protected function calculateItemPurchaseValue($item, $masterFakturs)
    {
        $master = $masterFakturs->get($item->obat_id . '|' . $item->pemasok_id);
        $harga = (float) ($master->harga ?? 0);
        $diskon = (float) ($master->diskon ?? 0);
        $diskonType = strtolower(trim((string) ($master->diskon_type ?? 'nominal')));
        $subtotal = $harga * (float) ($item->qty_total ?? 0);
        $diskonValue = in_array($diskonType, ['persen', 'percent', '%'], true) ? ($subtotal * $diskon / 100) : $diskon;
        $setelahDiskon = max($subtotal - $diskonValue, 0);

        return $setelahDiskon * (1 + $this->ppnRate() / 100);
    }

    // Fakturs made before permintaan_item_id existed are matched by no_permintaan + obat + pemasok
    protected function buildLegacyFakturItemsMap($permintaans)
    {
        $noPermintaans = collect($permintaans)->pluck('no_permintaan')->filter()->unique()->values();
        if ($noPermintaans->isEmpty()) {
            return collect();
        }

        return FakturBeli::query()
            ->with('items')
            ->whereIn('no_permintaan', $noPermintaans)
            ->get()
            ->groupBy('no_permintaan')
            ->map(function ($fakturs) {
                return $fakturs->flatMap(function ($faktur) {
                    return collect($faktur->items)->map(fn ($item) => [
                        'obat_id' => $item->obat_id,
                        'principal_id' => $item->principal_id,
                        'pemasok_id' => $faktur->pemasok_id,
                        'qty' => (float) ($item->qty ?? 0),
                    ]);
                })->values();
            });
    }

    protected function calculatePermintaanItemTerpenuhi($permintaan, $item, $legacyFakturItemsByPermintaan)
    {
        $linked = collect($item->fakturBeliItems);
        if ($linked->isNotEmpty()) {
            return (float) $linked->sum(fn ($fakturItem) => (float) ($fakturItem->qty ?? 0));
        }

        return (float) collect($legacyFakturItemsByPermintaan->get($permintaan->no_permintaan, []))
            ->filter(function ($legacy) use ($item) {
                if ((int) $legacy['obat_id'] !== (int) $item->obat_id || (int) $legacy['pemasok_id'] !== (int) $item->pemasok_id) {
                    return false;
                }
                return empty($item->principal_id) || empty($legacy['principal_id']) || (int) $legacy['principal_id'] === (int) $item->principal_id;
            })
            ->sum('qty');
    }

    public function getMasterFaktur(Request $request)
    {
        $request->validate([
            'obat_id' => 'required|exists:erm_obat,id',
            'pemasok_id' => 'required|exists:erm_pemasok,id',
        ]);
        $master = MasterFaktur::where('obat_id', $request->obat_id)
            ->where('pemasok_id', $request->pemasok_id)
            ->first();
        if (!$master) {
            return response()->json(['found' => false]);
        }
        // Principal belongs to the obat (Master Obat), the same for every pemasok
        $principal = Obat::withInactive()->with('principal:id,nama')->find($request->obat_id)?->principal;

        return response()->json([
            'found' => true,
            'harga' => $master->harga,
            'qty_per_box' => $master->qty_per_box,
            'diskon' => $master->diskon,
            'diskon_type' => $master->diskon_type,
            'principal_id' => $principal?->id,
            'principal_nama' => $principal?->nama,
        ]);
    }

    public function getObatOptions(Request $request)
    {
        $request->validate([
            'obat_id' => 'required|exists:erm_obat,id',
        ]);

        $pemasoks = MasterFaktur::query()
            ->with(['pemasok:id,nama'])
            ->where('obat_id', $request->obat_id)
            ->orderBy('pemasok_id')
            ->get()
            ->filter(fn ($master) => $master->pemasok_id && $master->pemasok)
            ->unique('pemasok_id')
            ->map(fn ($master) => ['id' => $master->pemasok_id, 'text' => $master->pemasok->nama])
            ->values();

        // The principal is set on the obat (Master Obat), so it is the only option for every pemasok
        $principal = Obat::withInactive()->with('principal:id,nama')->find($request->obat_id)?->principal;
        $principals = $principal ? collect([['id' => $principal->id, 'text' => $principal->nama]]) : collect();

        return response()->json([
            'pemasoks' => $pemasoks,
            'principals' => $principals,
            'principals_by_pemasok' => $pemasoks->mapWithKeys(fn ($p) => [(string) $p['id'] => $principals]),
        ]);
    }

    /**
     * Everything a permintaan form row shows, for many obat in one go: the pemasok that sell it with their
     * Master Pembelian price, the principal, and stock / usage. The form needs no further request per row.
     */
    public function obatInfo(Request $request)
    {
        $ids = collect((array) $request->input('obat_ids', []))->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

        return response()->json(['info' => $this->buildObatInfo($ids, (int) $request->input('period_months', 3))]);
    }

    private function buildObatInfo(array $obatIds, int $periodMonths = 3): array
    {
        if (!$obatIds) {
            return [];
        }
        $periodMonths = in_array($periodMonths, [1, 3, 6, 12], true) ? $periodMonths : 3;

        $masters = MasterFaktur::with('pemasok:id,nama')->whereIn('obat_id', $obatIds)->orderBy('pemasok_id')->get()->groupBy('obat_id');
        $obats = Obat::withInactive()->with('principal:id,nama')->whereIn('id', $obatIds)->get(['id', 'nama', 'principal_id'])->keyBy('id');
        $metrics = collect($this->forecastPreview(Request::create('/', 'GET', ['period_months' => $periodMonths, 'obat_ids' => $obatIds]))->getData(true)['rows'] ?? [])
            ->keyBy('obat_id');

        $info = [];
        foreach ($obatIds as $obatId) {
            $obat = $obats->get($obatId);
            $info[$obatId] = [
                'principal' => $obat && $obat->principal ? ['id' => $obat->principal->id, 'text' => $obat->principal->nama] : null,
                'pemasoks' => ($masters->get($obatId) ?? collect())
                    ->filter(fn ($m) => $m->pemasok)
                    ->unique('pemasok_id')
                    ->map(fn ($m) => [
                        'id' => $m->pemasok_id,
                        'text' => $m->pemasok->nama,
                        'harga' => (float) $m->harga,
                        'qty_per_box' => (int) $m->qty_per_box,
                        'diskon' => (float) $m->diskon,
                        'diskon_type' => $m->diskon_type,
                    ])->values()->all(),
                'metric' => $metrics->get($obatId),
            ];
        }

        return $info;
    }

    public function forecastPreview(Request $request)
    {
        $request->validate([
            'period_months' => 'nullable|integer|in:1,3,6,12',
        ]);

        $periodMonths = (int) $request->input('period_months', 3);
        $periodMonths = in_array($periodMonths, [1, 3, 6, 12], true) ? $periodMonths : 3;

        $rawObatIds = $request->input('obat_ids', []);
        if (!is_array($rawObatIds)) {
            $rawObatIds = [$rawObatIds];
        }

        $obatIds = collect($rawObatIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($obatIds->isNotEmpty()) {
            $valid = Obat::withInactive()->whereIn('id', $obatIds)->pluck('id')->map(fn ($id) => (int) $id);
            $obatIds = $obatIds->intersect($valid)->values();
        }

        $forecastService = app(\App\Services\ERM\ForecastService::class);
        [$periodStart, $periodEnd] = $forecastService->period($periodMonths);
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        if ($obatIds->isEmpty()) {
            return response()->json([
                'period_months' => $periodMonths,
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'rows' => [],
            ]);
        }

        // Same demand and stock definitions as the Forecast Stok page
        $demandPerObat = $forecastService->demandStats($periodStart, $periodEnd, $periodMonths, $obatIds->all());
        $stockPerObat = $forecastService->stockPerObat($obatIds->all());

        $orderedThisMonth = PermintaanItem::query()
            ->select('obat_id', DB::raw('SUM(qty_total) as total_qty_ordered'), DB::raw('SUM(jumlah_box) as total_box_ordered'))
            ->whereIn('obat_id', $obatIds)
            ->whereHas('permintaan', function ($query) use ($currentMonthStart, $currentMonthEnd) {
                $query->whereBetween('request_date', [$currentMonthStart->toDateString(), $currentMonthEnd->toDateString()])
                    ->where('status', '!=', 'rejected');
            })
            ->groupBy('obat_id')
            ->get()
            ->keyBy('obat_id');

        $rows = Obat::withInactive()
            ->whereIn('id', $obatIds)
            ->get(['id', 'nama'])
            ->map(function ($obat) use ($demandPerObat, $stockPerObat, $orderedThisMonth) {
                $demand = $demandPerObat[$obat->id] ?? null;
                $ordered = $orderedThisMonth->get($obat->id);

                return [
                    'obat_id' => $obat->id,
                    'obat_nama' => $obat->nama,
                    'total_stock' => round((float) ($stockPerObat[$obat->id] ?? 0), 2),
                    'obat_keluar' => round((float) ($demand['total'] ?? 0), 2),
                    'average_monthly_keluar' => round((float) ($demand['mean_monthly'] ?? 0), 2),
                    'ordered_this_month_qty' => round((float) ($ordered->total_qty_ordered ?? 0), 2),
                    'ordered_this_month_box' => round((float) ($ordered->total_box_ordered ?? 0), 2),
                ];
            })
            ->values();

        return response()->json([
            'period_months' => $periodMonths,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'rows' => $rows,
        ]);
    }

    /**
     * "Isi dari Forecast": obat whose forecast says order now (Kritis / Perlu Pesan) with the suggested
     * quantity (whole boxes) and the cheapest pemasok in Master Pembelian. Obat already in a permintaan
     * waiting for approval are left out, the forecast does not count those yet.
     */
    public function saranForecast()
    {
        $forecastService = app(\App\Services\ERM\ForecastService::class);
        $forecast = array_filter($forecastService->computeAll($forecastService->defaultParams()), function ($f) {
            return in_array($f['status'], ['kritis', 'pesan'], true) && (float) $f['qty_pesan'] > 0;
        });
        if (!$forecast) {
            return response()->json(['rows' => [], 'tanpa_pemasok' => [], 'sudah_diminta' => [], 'info' => []]);
        }

        $obatIds = array_keys($forecast);
        $waiting = PermintaanItem::whereIn('obat_id', $obatIds)
            ->whereHas('permintaan', fn ($q) => $q->where('status', 'waiting_approval'))
            ->pluck('obat_id')->unique()->flip();

        // Cheapest price after discount per obat; the most recently updated wins a tie
        $masters = MasterFaktur::with('pemasok:id,nama')
            ->whereIn('obat_id', $obatIds)
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('obat_id')
            ->map(function ($list) {
                return $list->sortBy(function ($m) {
                    $harga = (float) $m->harga;
                    $diskon = (float) $m->diskon;
                    return in_array(strtolower((string) $m->diskon_type), ['persen', 'percent', '%'], true) ? $harga * (1 - $diskon / 100) : $harga - $diskon;
                })->first();
            });

        $obats = Obat::withInactive()->with('principal:id,nama')->whereIn('id', $obatIds)->get(['id', 'nama', 'principal_id'])->keyBy('id');

        $rows = [];
        $tanpaPemasok = [];
        $sudahDiminta = [];
        foreach ($forecast as $obatId => $f) {
            $obat = $obats->get($obatId);
            if (!$obat) {
                continue;
            }
            if (isset($waiting[$obatId])) {
                $sudahDiminta[] = $obat->nama;
                continue;
            }
            $master = $masters->get($obatId);
            if (!$master || !$master->pemasok) {
                $tanpaPemasok[] = $obat->nama;
                continue;
            }
            $isiBox = (int) ($f['isi_per_box'] ?: ($master->qty_per_box ?: 1));
            $box = max(1, (int) ceil((float) $f['qty_pesan'] / max(1, $isiBox)));
            $rows[] = [
                'obat_id' => $obatId,
                'obat_nama' => $obat->nama,
                'pemasok_id' => $master->pemasok_id,
                'pemasok_nama' => $master->pemasok->nama,
                'principal_id' => $obat->principal_id,
                'principal_nama' => optional($obat->principal)->nama,
                'jumlah_box' => $box,
                'qty_total' => $box * max(1, $isiBox),
                'status' => $f['status'],
            ];
        }

        // Kritis first
        usort($rows, fn ($a, $b) => [$a['status'] !== 'kritis', $a['obat_nama']] <=> [$b['status'] !== 'kritis', $b['obat_nama']]);

        return response()->json([
            'rows' => $rows,
            'tanpa_pemasok' => $tanpaPemasok,
            'sudah_diminta' => $sudahDiminta,
            'info' => $this->buildObatInfo(array_column($rows, 'obat_id')),
        ]);
    }

    public function approve($id)
    {
        try {
            $result = DB::transaction(function () use ($id) {
                // Locked and checked inside the transaction: a double click must not create the faktur twice
                $permintaan = Permintaan::with('items')->lockForUpdate()->findOrFail($id);
                if ($permintaan->status !== 'waiting_approval') {
                    throw new \DomainException('Permintaan ' . $permintaan->no_permintaan . ' sudah diproses (status: ' . (self::STATUS_LABEL[$permintaan->status] ?? $permintaan->status) . ').');
                }
                if ($permintaan->items->isEmpty()) {
                    throw new \DomainException('Permintaan tidak punya item.');
                }

                $masterPembelian = app(MasterPembelianService::class);
                $fakturCount = 0;
                foreach ($permintaan->items->groupBy('pemasok_id') as $pemasokId => $items) {
                    $faktur = FakturBeli::create([
                        'permintaan_id' => $permintaan->id,
                        'pemasok_id' => $pemasokId,
                        'no_faktur' => null,
                        'no_permintaan' => $permintaan->no_permintaan,
                        'requested_date' => $permintaan->request_date,
                        'status' => 'diminta',
                        'created_by' => Auth::id(),
                    ]);
                    foreach ($items as $item) {
                        // Price, discount from Master Pembelian; adjusted on the faktur once the invoice arrives
                        $master = MasterFaktur::where('obat_id', $item->obat_id)->where('pemasok_id', $item->pemasok_id)->first();
                        FakturBeliItem::create([
                            'fakturbeli_id' => $faktur->id,
                            'permintaan_item_id' => $item->id,
                            'obat_id' => $item->obat_id,
                            'principal_id' => $item->principal_id ?? $masterPembelian->principalFor((int) $item->obat_id),
                            'qty' => 0,
                            'sisa' => $item->qty_total,
                            'harga' => $master->harga ?? 0,
                            'diskon' => $master->diskon ?? 0,
                            'diskon_type' => ($master && in_array(strtolower((string) $master->diskon_type), ['persen', 'percent', '%'], true)) ? 'percent' : 'nominal',
                            'diminta' => $item->qty_total,
                        ]);
                    }
                    $fakturCount++;
                }

                $permintaan->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_date' => now(),
                ]);

                return [$permintaan->no_permintaan, $fakturCount];
            });
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        [$nomor, $fakturCount] = $result;
        return response()->json([
            'success' => true,
            'message' => "Permintaan {$nomor} disetujui. {$fakturCount} faktur dibuat di Faktur Pembelian (status diminta) dan siap diisi saat barang datang.",
        ]);
    }

    public function reject(Request $request, $id)
    {
        $request->validate(
            ['reason' => 'required|string|max:500'],
            ['reason.required' => 'Alasan penolakan wajib diisi.']
        );

        try {
            $nomor = DB::transaction(function () use ($request, $id) {
                $permintaan = Permintaan::lockForUpdate()->findOrFail($id);
                if ($permintaan->status !== 'waiting_approval') {
                    throw new \DomainException('Hanya permintaan yang menunggu approval yang bisa ditolak.');
                }
                $permintaan->update([
                    'status' => 'rejected',
                    'rejected_by' => Auth::id(),
                    'rejected_at' => now(),
                    'rejected_reason' => trim($request->reason),
                ]);
                return $permintaan->no_permintaan;
            });
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => "Permintaan {$nomor} ditolak."]);
    }

    public function nilaiPembelian($id)
    {
        $permintaan = Permintaan::with(['items.obat' => fn ($q) => $q->withInactive()->with('principal'), 'items.pemasok', 'items.principal'])->findOrFail($id);
        $ppnRate = $this->ppnRate();
        $masters = $this->buildMasterFakturMap(collect([$permintaan]));

        $details = collect($permintaan->items)->map(function ($item) use ($ppnRate, $masters) {
            $master = $masters->get($item->obat_id . '|' . $item->pemasok_id);
            $harga = (float) ($master->harga ?? 0);
            $qty = (float) ($item->qty_total ?? 0);
            $diskon = (float) ($master->diskon ?? 0);
            $diskonType = strtolower(trim((string) ($master->diskon_type ?? 'nominal')));
            $subtotal = $harga * $qty;
            $diskonValue = in_array($diskonType, ['persen', 'percent', '%'], true) ? ($subtotal * $diskon / 100) : $diskon;
            $setelahDiskon = max($subtotal - $diskonValue, 0);
            $ppnValue = $setelahDiskon * $ppnRate / 100;

            return [
                'nama_obat' => optional($item->obat)->nama ?? '-',
                'principal_name' => optional($item->principal)->nama ?? optional(optional($item->obat)->principal)->nama ?? '-',
                'jenis_obat' => optional($item->obat)->is_generik ? 'Generik' : 'Paten',
                'qty' => $qty,
                'box' => (float) ($item->jumlah_box ?? 0),
                'harga' => $harga,
                'harga_ditemukan' => (bool) $master,
                'diskon' => $diskon,
                'diskon_type' => $diskonType,
                'subtotal' => $subtotal,
                'setelah_diskon' => $setelahDiskon,
                'ppn_rate' => $ppnRate,
                'ppn' => $ppnValue,
                'total_harga' => $setelahDiskon + $ppnValue,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'no_permintaan' => $permintaan->no_permintaan,
            'request_date' => $permintaan->request_date,
            'items' => $details,
            'summary' => [
                'subtotal' => (float) $details->sum('subtotal'),
                'setelah_diskon' => (float) $details->sum('setelah_diskon'),
                'ppn' => (float) $details->sum('ppn'),
                'total_harga' => (float) $details->sum('total_harga'),
            ],
        ]);
    }

    public function destroy($id)
    {
        try {
            $nomor = DB::transaction(function () use ($id) {
                $permintaan = Permintaan::lockForUpdate()->findOrFail($id);
                if ($permintaan->status === 'approved') {
                    throw new \DomainException('Permintaan yang sudah disetujui tidak bisa dihapus.');
                }
                $permintaan->items()->delete();
                $permintaan->delete();
                return $permintaan->no_permintaan;
            });
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => "Permintaan {$nomor} dihapus."]);
    }

    /**
     * Print Surat Permintaan as PDF using mPDF
     */
    public function printSuratPermintaan($id)
    {
        $permintaan = Permintaan::with(['items', 'items.obat' => fn ($q) => $q->withInactive(), 'items.pemasok'])->findOrFail($id);
        $mpdf = new \Mpdf\Mpdf(['format' => 'A4-L']);
        $html = view('erm.permintaan.print', compact('permintaan'))->render();
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="SuratPermintaan-' . $permintaan->no_permintaan . '.pdf"',
        ]);
    }

    public function printSuratPemesanan($id)
    {
        $permintaan = Permintaan::findOrFail($id);

        if ($permintaan->status !== 'approved') {
            abort(403, 'Surat pemesanan hanya tersedia untuk permintaan yang sudah approved.');
        }

        $rowsPerPage = 12;
        $items = $permintaan->items()->with(['obat' => fn ($q) => $q->withInactive(), 'pemasok'])->get()->values();

        $printItems = $items->map(fn ($item) => [
            'nama_obat' => optional($item->obat)->nama ?? '-',
            'jumlah' => rtrim(rtrim(number_format((float) $item->jumlah_box, 2, ',', '.'), '0'), ','),
        ]);

        $pages = $printItems->chunk($rowsPerPage)->map(fn ($chunk) => $chunk->pad($rowsPerPage, null)->values());
        if ($pages->isEmpty()) {
            $pages = collect([collect()->pad($rowsPerPage, null)->values()]);
        }

        return view('erm.permintaan.print-surat-pemesanan', [
            'permintaan' => $permintaan,
            'pages' => $pages,
            'pemasokName' => optional(optional($items->first())->pemasok)->nama ?? '-',
            'requestDate' => $permintaan->request_date ? Carbon::parse($permintaan->request_date)->translatedFormat('d/m/Y') : '-',
            'rowsPerPage' => $rowsPerPage,
        ]);
    }
}
