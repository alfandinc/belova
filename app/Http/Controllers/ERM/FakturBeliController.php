<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\FakturBeli;
use App\Models\ERM\FakturBeliItem;
use App\Models\ERM\FakturRetur;
use App\Models\ERM\Pemasok;
use App\Models\ERM\Gudang;
use App\Models\ERM\Obat;
use App\Models\ERM\Permintaan;
use App\Models\ERM\Principal;
use App\Services\ERM\MasterPembelianService;
use App\Services\ERM\StokService;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class FakturBeliController extends Controller
{
    protected $stokService;
    protected $masterPembelian;

    public function __construct(StokService $stokService, MasterPembelianService $masterPembelian)
    {
        $this->stokService = $stokService;
        $this->masterPembelian = $masterPembelian;
    }

    private const STATUS_LABEL = [
        'diminta' => 'Diminta',
        'diterima' => 'Diterima',
        'diapprove' => 'Disetujui',
        'diretur_sebagian' => 'Diretur sebagian',
        'diretur' => 'Diretur',
    ];

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $isAdmin = Auth::check() && Auth::user()->hasAnyRole(['Admin']);

            // Period and search; the status tab is applied on top so the tab counts follow them
            $filtered = function ($query) use ($request) {
                if ($request->filled('tanggal_terima_range')) {
                    $range = explode(' - ', $request->input('tanggal_terima_range'));
                    if (count($range) === 2) {
                        $query->whereDate(DB::raw('COALESCE(received_date, requested_date, DATE(created_at))'), '>=', $range[0])
                            ->whereDate(DB::raw('COALESCE(received_date, requested_date, DATE(created_at))'), '<=', $range[1]);
                    }
                }
                if ($request->filled('q')) {
                    $q = trim($request->input('q'));
                    $query->where(function ($sub) use ($q) {
                        $sub->where('no_faktur', 'like', "%{$q}%")
                            ->orWhere('no_permintaan', 'like', "%{$q}%")
                            ->orWhereHas('pemasok', fn ($p) => $p->where('nama', 'like', "%{$q}%"))
                            ->orWhereHas('items.obat', fn ($o) => $o->withInactive()->where('nama', 'like', "%{$q}%"));
                    });
                }
                if ($request->input('jatuh_tempo') === '1') {
                    $query->whereIn('status', ['diapprove', 'diretur_sebagian'])
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<=', now()->addDays(7));
                }
            };

            $counts = FakturBeli::query()->where($filtered)
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $data = FakturBeli::with([
                'pemasok:id,nama',
                'items' => fn ($q) => $q->select('id', 'fakturbeli_id', 'obat_id'),
                'items.obat' => fn ($q) => $q->withInactive()->select('id', 'nama'),
                'returs:id,fakturbeli_id,no_retur,status',
                'replacementInvoices:id,replaced_fakturbeli_id,no_faktur',
                'replacedFaktur:id,no_faktur',
                'sourceRetur:id,no_retur',
                'approver:id,name',
            ])
                ->select('erm_fakturbeli.*')
                ->where($filtered)
                ->when(array_key_exists((string) $request->input('status'), self::STATUS_LABEL), fn ($q) => $q->where('status', $request->input('status')));

            return DataTables::of($data)
                ->addColumn('pemasok', fn ($row) => optional($row->pemasok)->nama ?? '-')
                ->addColumn('nama_obat', function ($row) {
                    $names = $row->items->map(fn ($item) => optional($item->obat)->nama)->filter()->values();
                    return $names->count() > 3
                        ? $names->take(3)->implode(', ') . ' +' . ($names->count() - 3) . ' lainnya'
                        : ($names->implode(', ') ?: '-');
                })
                ->addColumn('jumlah_item', fn ($row) => $row->items->count())
                ->addColumn('status_label', fn ($row) => self::STATUS_LABEL[$row->status] ?? $row->status)
                ->addColumn('approved_by_user_name', fn ($row) => optional($row->approver)->name)
                ->addColumn('approved_at_label', fn ($row) => optional($row->approved_at)->format('d/m/Y H:i'))
                // Days until due (negative = overdue) for unpaid tracking
                ->addColumn('jatuh_tempo_hari', function ($row) {
                    if (!$row->due_date || !in_array($row->status, ['diapprove', 'diretur_sebagian'], true)) {
                        return null;
                    }
                    return (int) now()->startOfDay()->diffInDays(Carbon::parse($row->due_date)->startOfDay(), false);
                })
                ->addColumn('replaced_faktur_no', fn ($row) => optional($row->replacedFaktur)->no_faktur)
                ->addColumn('source_retur_no', fn ($row) => optional($row->sourceRetur)->no_retur)
                ->addColumn('replacement_invoice_nos', fn ($row) => $row->replacementInvoices->pluck('no_faktur')->filter()->values()->all())
                ->addColumn('latest_retur', function ($row) {
                    $retur = $row->returs->sortByDesc('id')->first();
                    return $retur ? ['id' => $retur->id, 'no_retur' => $retur->no_retur, 'status' => $retur->status] : null;
                })
                ->addColumn('actions', function ($row) use ($isAdmin) {
                    $latestRetur = $row->returs->sortByDesc('id')->first();
                    return [
                        'input' => $row->status === 'diminta',
                        'edit' => $row->status === 'diterima',
                        'approve' => $row->status === 'diterima',
                        'print' => in_array($row->status, ['diapprove', 'diretur_sebagian', 'diretur'], true),
                        // A new retur is possible while received stok remains on the faktur
                        'retur' => in_array($row->status, ['diapprove', 'diretur_sebagian'], true) && !($latestRetur && $latestRetur->status === 'diajukan'),
                        'pengganti' => $latestRetur && $latestRetur->status === 'diapprove' && $row->replacementInvoices->isEmpty()
                            && in_array($row->status, ['diretur', 'diretur_sebagian'], true)
                            ? $latestRetur->id : null,
                        'delete' => $isAdmin && in_array($row->status, ['diminta', 'diterima'], true),
                    ];
                })
                ->with('counts', [
                    'all' => (int) $counts->sum(),
                    'diminta' => (int) ($counts['diminta'] ?? 0),
                    'diterima' => (int) ($counts['diterima'] ?? 0),
                    'diapprove' => (int) ($counts['diapprove'] ?? 0),
                    'diretur' => (int) (($counts['diretur'] ?? 0) + ($counts['diretur_sebagian'] ?? 0)),
                ])
                ->orderColumn('requested_date', 'COALESCE(received_date, requested_date, created_at) $1')
                ->make(true);
        }

        $principals = Principal::orderBy('nama')->get();

        return view('erm.fakturbeli.index', compact('principals'));
    }

    /**
     * Cari faktur beli berdasarkan no_permintaan, return faktur id jika ditemukan
     */
    public function cariByNoPermintaan(Request $request)
    {
        $no = $request->input('no_permintaan');
        $fakturs = FakturBeli::with('pemasok')
            ->where('no_permintaan', $no)
            ->orderByRaw("CASE WHEN status = 'diminta' THEN 0 WHEN status = 'diterima' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->get();

        if ($fakturs->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Faktur tidak ditemukan']);
        }

        return response()->json([
            'success' => true,
            'fakturs' => $fakturs->map(function ($faktur) {
                return [
                    'id' => $faktur->id,
                    'no_faktur' => $faktur->no_faktur,
                    'pemasok' => optional($faktur->pemasok)->nama,
                    'status' => $faktur->status,
                    'requested_date' => $faktur->requested_date,
                    'received_date' => $faktur->received_date,
                ];
            })->values(),
        ]);
    }

    // The form lives in a modal on the Faktur Pembelian page; old links land there with the modal opened
    public function create(Request $request)
    {
        return redirect()->route('erm.fakturbeli.index', array_filter([
            'pengganti' => $request->input('replace_retur_id'),
            'baru' => $request->filled('replace_retur_id') ? null : 1,
        ]));
    }

    /**
     * Everything the faktur modal needs, as JSON: an existing faktur (id), a replacement for an approved retur
     * (replace_retur_id) or an empty new faktur. Includes the gudang list and the default gudang.
     */
    public function formData(Request $request)
    {
        $expiredId = app(\App\Services\ERM\GudangExpiredService::class)->gudangId();
        $gudangs = Gudang::orderBy('nama')->get(['id', 'nama'])
            ->reject(fn ($g) => $expiredId && (int) $g->id === (int) $expiredId)->values();
        // The gudang most fakturs went to; the user can change it per line
        $defaultGudang = (int) (FakturBeliItem::whereNotNull('gudang_id')
            ->when($expiredId, fn ($q) => $q->where('gudang_id', '!=', $expiredId))
            ->select('gudang_id', DB::raw('COUNT(*) as n'))->groupBy('gudang_id')->orderByDesc('n')->value('gudang_id')
            ?: optional($gudangs->first())->id);

        $mapItem = function ($item) {
            return [
                'permintaan_item_id' => $item->permintaan_item_id,
                'obat_id' => $item->obat_id,
                'obat_nama' => optional($item->obat)->nama ?? 'Obat #' . $item->obat_id,
                'satuan' => optional($item->obat)->satuan_stok_label,
                'diminta' => (int) $item->diminta,
                'qty' => (int) $item->qty,
                'harga' => (float) $item->harga,
                'diskon' => (float) $item->diskon,
                'diskon_type' => $item->diskon_type ?: 'nominal',
                'tax' => (float) $item->tax,
                'tax_type' => $item->tax_type ?: 'nominal',
                'gudang_id' => $item->gudang_id,
                'batch' => $item->batch,
                'expiration_date' => $item->expiration_date ? Carbon::parse($item->expiration_date)->format('Y-m-d') : null,
            ];
        };

        $base = [
            'gudangs' => $gudangs,
            'default_gudang_id' => $defaultGudang,
            'ppn_persen' => (float) config('pembelian.ppn_persen', 11),
        ];

        if ($request->filled('id')) {
            $faktur = FakturBeli::with(['items.obat' => fn ($q) => $q->withInactive(), 'pemasok:id,nama'])->findOrFail($request->input('id'));
            if ($lockedMessage = $this->fakturLockedMessage($faktur)) {
                return response()->json(['success' => false, 'message' => $lockedMessage], 422);
            }

            return response()->json($base + [
                'mode' => $faktur->status === 'diminta' ? 'input' : 'edit',
                'id' => $faktur->id,
                'status' => $faktur->status,
                'permintaan_id' => $faktur->permintaan_id,
                'no_permintaan' => $faktur->no_permintaan,
                'pemasok' => $faktur->pemasok ? ['id' => $faktur->pemasok->id, 'nama' => $faktur->pemasok->nama] : null,
                'pemasok_terkunci' => true,
                // The follow-up faktur for undelivered items carries a placeholder number until its own invoice arrives
                'no_faktur' => str_contains((string) $faktur->no_faktur, '-NEXT-') ? null : $faktur->no_faktur,
                'lanjutan_dari' => str_contains((string) $faktur->no_faktur, '-NEXT-') ? strstr($faktur->no_faktur, '-NEXT-', true) : null,
                'requested_date' => $faktur->requested_date,
                'received_date' => $faktur->received_date ?: now()->toDateString(),
                'due_date' => $faktur->due_date,
                'ship_date' => $faktur->ship_date,
                'notes' => $faktur->notes,
                'bukti_url' => $faktur->bukti ? asset('storage/' . $faktur->bukti) : null,
                // Stored global amounts are nominal; a faktur that never had them starts at the PPN rate
                'global_diskon' => (float) $faktur->global_diskon,
                'global_pajak' => $faktur->global_pajak !== null ? (float) $faktur->global_pajak : null,
                'items' => $faktur->items->map($mapItem)->values(),
            ]);
        }

        if ($request->filled('replace_retur_id')) {
            $retur = FakturRetur::with(['fakturbeli.pemasok', 'items.obat' => fn ($q) => $q->withInactive(), 'items.fakturbeliitem'])
                ->findOrFail($request->input('replace_retur_id'));
            if ($retur->status !== 'diapprove') {
                return response()->json(['success' => false, 'message' => 'Retur harus disetujui sebelum membuat faktur pengganti.'], 422);
            }
            $original = $retur->fakturbeli;

            return response()->json($base + [
                'mode' => 'pengganti',
                'id' => null,
                'replaced_fakturbeli_id' => $retur->fakturbeli_id,
                'source_retur_id' => $retur->id,
                'no_retur' => $retur->no_retur,
                'pemasok' => optional($original)->pemasok ? ['id' => $original->pemasok->id, 'nama' => $original->pemasok->nama] : null,
                'pemasok_terkunci' => true,
                'no_faktur' => null,
                'requested_date' => optional($original)->requested_date,
                'received_date' => now()->toDateString(),
                'due_date' => optional($original)->due_date,
                'notes' => 'Faktur pengganti dari retur ' . $retur->no_retur,
                'global_diskon' => 0,
                'global_pajak' => null,
                'items' => $retur->items->map(function ($returItem) use ($mapItem) {
                    $source = $returItem->fakturbeliitem;
                    $row = $source ? $mapItem($source) : [];
                    return array_merge($row, [
                        'obat_id' => $returItem->obat_id,
                        'obat_nama' => optional($returItem->obat)->nama ?? 'Obat #' . $returItem->obat_id,
                        'satuan' => optional($returItem->obat)->satuan_stok_label,
                        'diminta' => (int) $returItem->qty,
                        'qty' => (int) $returItem->qty,
                        // A replacement arrives as a new batch
                        'batch' => null,
                        'expiration_date' => null,
                    ]);
                })->values(),
            ]);
        }

        return response()->json($base + [
            'mode' => 'baru',
            'id' => null,
            'pemasok' => null,
            'pemasok_terkunci' => false,
            'received_date' => now()->toDateString(),
            'global_diskon' => 0,
            'global_pajak' => null,
            'items' => [],
        ]);
    }

    private function fakturRules(Request $request, ?FakturBeli $faktur = null): array
    {
        return [
            'permintaan_id' => 'nullable|exists:erm_permintaan,id',
            'replaced_fakturbeli_id' => 'nullable|exists:erm_fakturbeli,id',
            'source_retur_id' => 'nullable|exists:erm_fakturretur,id',
            'pemasok_id' => 'required|exists:erm_pemasok,id',
            // One supplier never issues the same invoice number twice
            'no_faktur' => [
                'required', 'string', 'max:191',
                \Illuminate\Validation\Rule::unique('erm_fakturbeli', 'no_faktur')
                    ->where(fn ($q) => $q->where('pemasok_id', $request->input('pemasok_id')))
                    ->ignore($faktur?->id),
            ],
            'received_date' => 'required|date',
            'requested_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'ship_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'bukti' => 'nullable|image|max:10240',
            'items' => 'required|array|min:1',
            'items.*.permintaan_item_id' => 'nullable|exists:erm_permintaan_items,id',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.qty' => 'required|integer|min:0',
            'items.*.diminta' => 'nullable|integer|min:0',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.diskon' => 'nullable|numeric|min:0',
            'items.*.diskon_type' => 'nullable|string|in:nominal,percent',
            'items.*.tax' => 'nullable|numeric|min:0',
            'items.*.tax_type' => 'nullable|string|in:nominal,percent',
            'items.*.gudang_id' => 'required|exists:erm_gudang,id',
            'items.*.batch' => 'nullable|string|max:255',
            'items.*.expiration_date' => 'nullable|date',
            'global_diskon' => 'nullable|numeric|min:0',
            'global_pajak' => 'nullable|numeric|min:0',
        ];
    }

    private const FAKTUR_MESSAGES = [
        'no_faktur.unique' => 'Nomor faktur ini sudah dipakai untuk pemasok yang sama.',
        'no_faktur.required' => 'Nomor faktur wajib diisi.',
        'items.required' => 'Tambahkan minimal satu obat.',
    ];

    /**
     * Item lines with their amounts: base = qty x harga, the item discount and item tax (nominal per
     * line or percent of the line) -> line total. Also checks qty vs diminta and that received lines
     * have a batch and ED (stok without them cannot be traced or rotated FEFO).
     */
    private function hitungItems(array $items): array
    {
        $errors = [];
        $lines = [];
        foreach ($items as $item) {
            $qty = (int) ($item['qty'] ?? 0);
            $diminta = (int) ($item['diminta'] ?? 0);
            $nama = optional(Obat::withInactive()->find($item['obat_id']))->nama ?? 'Obat #' . $item['obat_id'];
            if ($diminta > 0 && $qty > $diminta) {
                $errors[] = "{$nama}: qty diterima ({$qty}) melebihi qty diminta ({$diminta}).";
            }
            if ($qty > 0 && (trim((string) ($item['batch'] ?? '')) === '' || empty($item['expiration_date']))) {
                $errors[] = "{$nama}: nomor batch dan tanggal kadaluarsa wajib diisi untuk barang yang diterima.";
            }

            $harga = (float) ($item['harga'] ?? 0);
            $base = $qty * $harga;
            $diskonType = $item['diskon_type'] ?? 'nominal';
            $taxType = $item['tax_type'] ?? 'nominal';
            $diskonValue = $diskonType === 'percent' ? $base * (float) ($item['diskon'] ?? 0) / 100 : (float) ($item['diskon'] ?? 0);
            $taxValue = $taxType === 'percent' ? $base * (float) ($item['tax'] ?? 0) / 100 : (float) ($item['tax'] ?? 0);

            $lines[] = $item + [
                '_qty' => $qty,
                '_diskon_type' => $diskonType,
                '_tax_type' => $taxType,
                '_total' => round(max($base - $diskonValue + $taxValue, 0), 2),
            ];
        }

        return [$lines, $errors];
    }

    // Header amounts computed here, not taken from the browser: subtotal of the lines, minus global diskon, plus global pajak
    private function hitungTotal(array $lines, array $validated): array
    {
        $subtotal = round(array_sum(array_column($lines, '_total')), 2);
        $globalDiskon = round((float) ($validated['global_diskon'] ?? 0), 2);
        $globalPajak = round((float) ($validated['global_pajak'] ?? 0), 2);

        return [
            'subtotal' => $subtotal,
            'global_diskon' => $globalDiskon,
            'global_pajak' => $globalPajak,
            'total' => round(max($subtotal - $globalDiskon + $globalPajak, 0), 2),
        ];
    }

    private function simpanItems(FakturBeli $faktur, array $lines, array $oldPrincipal = []): void
    {
        foreach ($lines as $item) {
            $permintaanItemId = !empty($item['permintaan_item_id']) ? (int) $item['permintaan_item_id'] : null;
            $diminta = isset($item['diminta']) && $item['diminta'] !== '' ? (int) $item['diminta'] : $item['_qty'];
            $faktur->items()->create([
                'permintaan_item_id' => $permintaanItemId,
                'obat_id' => $item['obat_id'],
                'principal_id' => $this->masterPembelian->principalForItem(
                    (int) $item['obat_id'], $permintaanItemId,
                    $oldPrincipal['pi-' . $permintaanItemId] ?? $oldPrincipal['obat-' . $item['obat_id']] ?? null
                ),
                'qty' => $item['_qty'],
                'diminta' => $diminta,
                'sisa' => max($diminta - $item['_qty'], 0),
                'harga' => (float) ($item['harga'] ?? 0),
                'diskon' => (float) ($item['diskon'] ?? 0),
                'diskon_type' => $item['_diskon_type'],
                'tax' => (float) ($item['tax'] ?? 0),
                'tax_type' => $item['_tax_type'],
                'gudang_id' => $item['gudang_id'],
                'batch' => isset($item['batch']) ? trim($item['batch']) : null,
                'expiration_date' => $item['expiration_date'] ?? null,
                'total_amount' => $item['_total'],
            ]);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->fakturRules($request), self::FAKTUR_MESSAGES);

        if ($msg = Obat::satuanStokRequiredMessage(array_column($validated['items'], 'obat_id'))) {
            return response()->json(['success' => false, 'message' => $msg, 'errors' => ['items' => [$msg]]], 422);
        }
        [$lines, $errors] = $this->hitungItems($validated['items']);
        if (!array_filter(array_column($lines, '_qty'))) {
            $errors[] = 'Minimal satu obat harus punya qty diterima lebih dari 0.';
        }
        if ($errors) {
            return response()->json(['success' => false, 'message' => implode("\n", $errors), 'errors' => ['items' => $errors]], 422);
        }

        $buktiPath = $request->hasFile('bukti') ? $request->file('bukti')->store('fakturbeli_bukti', 'public') : null;

        $faktur = DB::transaction(function () use ($validated, $lines, $buktiPath) {
            $faktur = FakturBeli::create([
                'permintaan_id' => $validated['permintaan_id'] ?? null,
                'replaced_fakturbeli_id' => $validated['replaced_fakturbeli_id'] ?? null,
                'source_retur_id' => $validated['source_retur_id'] ?? null,
                'pemasok_id' => $validated['pemasok_id'],
                'no_faktur' => trim($validated['no_faktur']),
                'received_date' => $validated['received_date'],
                'requested_date' => ($validated['requested_date'] ?? null) ?: null,
                'due_date' => ($validated['due_date'] ?? null) ?: null,
                'ship_date' => ($validated['ship_date'] ?? null) ?: null,
                'notes' => ($validated['notes'] ?? null) ?: null,
                'bukti' => $buktiPath,
                'status' => 'diterima', // received; stok enters on approval
                'created_by' => Auth::id(),
            ] + $this->hitungTotal($lines, $validated));

            $this->simpanItems($faktur, $lines);

            return $faktur;
        });

        return response()->json(['success' => true, 'message' => "Faktur {$faktur->no_faktur} disimpan dan menunggu approval. Stok masuk setelah di-approve.", 'id' => $faktur->id]);
    }

    public function edit($id)
    {
        $faktur = FakturBeli::findOrFail($id);
        if ($lockedMessage = $this->fakturLockedMessage($faktur)) {
            return redirect()->route('erm.fakturbeli.index')->with('error', $lockedMessage);
        }

        return redirect()->route('erm.fakturbeli.index', ['input' => $faktur->id]);
    }

    public function update(Request $request, $id)
    {
        $faktur = FakturBeli::findOrFail($id);
        // Locked first, so an approved faktur says so instead of listing form errors
        if ($lockedMessage = $this->fakturLockedMessage($faktur)) {
            return response()->json(['success' => false, 'message' => $lockedMessage], 422);
        }
        $validated = $request->validate($this->fakturRules($request, $faktur), self::FAKTUR_MESSAGES);

        if ($msg = Obat::satuanStokRequiredMessage(array_column($validated['items'], 'obat_id'))) {
            return response()->json(['success' => false, 'message' => $msg, 'errors' => ['items' => [$msg]]], 422);
        }
        [$lines, $errors] = $this->hitungItems($validated['items']);
        if (!array_filter(array_column($lines, '_qty'))) {
            $errors[] = 'Minimal satu obat harus punya qty diterima lebih dari 0.';
        }
        if ($errors) {
            return response()->json(['success' => false, 'message' => implode("\n", $errors), 'errors' => ['items' => $errors]], 422);
        }

        try {
            DB::transaction(function () use ($request, $validated, $lines, $id) {
                $faktur = FakturBeli::lockForUpdate()->findOrFail($id);
                // Stok of an approved faktur is already in the gudang: editing would put it in again on the next approve
                if ($lockedMessage = $this->fakturLockedMessage($faktur)) {
                    throw new \DomainException($lockedMessage);
                }

                $buktiPath = $request->hasFile('bukti') ? $request->file('bukti')->store('fakturbeli_bukti', 'public') : $faktur->bukti;

                $faktur->update([
                    'permintaan_id' => $validated['permintaan_id'] ?? $faktur->permintaan_id,
                    'pemasok_id' => $validated['pemasok_id'],
                    'no_faktur' => trim($validated['no_faktur']),
                    'received_date' => $validated['received_date'],
                    'requested_date' => ($validated['requested_date'] ?? null) ?: $faktur->requested_date,
                    'due_date' => ($validated['due_date'] ?? null) ?: null,
                    'ship_date' => ($validated['ship_date'] ?? null) ?: null,
                    'notes' => ($validated['notes'] ?? null) ?: null,
                    'bukti' => $buktiPath,
                    'status' => 'diterima',
                ] + $this->hitungTotal($lines, $validated));

                // The form has no principal field, so keep the principal the old item had
                // (matched by permintaan item, else by obat); new lines take it from the permintaan / Master Pembelian.
                $oldPrincipal = [];
                foreach ($faktur->items()->whereNotNull('principal_id')->get(['permintaan_item_id', 'obat_id', 'principal_id']) as $old) {
                    if ($old->permintaan_item_id) {
                        $oldPrincipal['pi-' . $old->permintaan_item_id] = $old->principal_id;
                    }
                    $oldPrincipal['obat-' . $old->obat_id] = $oldPrincipal['obat-' . $old->obat_id] ?? $old->principal_id;
                }
                $faktur->items()->delete();
                $this->simpanItems($faktur, $lines, $oldPrincipal);
            });
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Faktur disimpan dan menunggu approval. Stok masuk setelah di-approve.', 'id' => (int) $id]);
    }

        public function destroy($id)
    {
        abort_unless(Auth::check() && Auth::user()->hasAnyRole(['Admin']), 403, 'Unauthorized action.');

        $faktur = FakturBeli::findOrFail($id);

        if ($lockedMessage = $this->fakturLockedMessage($faktur)) {
            return response()->json(['success' => false, 'message' => $lockedMessage], 422);
        }

        $faktur->items()->delete();
        $faktur->delete();
        return response()->json(['success' => true, 'message' => 'Faktur berhasil dihapus']);
    }

    /**
     * A faktur whose stok already entered the gudang (approved / diretur, or with kartu stok rows)
     * may not be edited or deleted; corrections go through Retur Pembelian.
     */
    private function fakturLockedMessage(FakturBeli $faktur): ?string
    {
        $stokSudahMasuk = in_array($faktur->status, ['diapprove', 'diretur_sebagian', 'diretur'], true)
            || \App\Models\ERM\KartuStok::where('ref_type', 'faktur_pembelian')->where('ref_id', $faktur->id)->exists();

        return $stokSudahMasuk
            ? 'Faktur ini sudah di-approve dan stoknya sudah masuk gudang, jadi tidak bisa diubah atau dihapus. Gunakan Retur ke Supplier untuk koreksi.'
            : null;
    }

        public function getPemasokSelect2(\Illuminate\Http\Request $request)
    {
        $q = $request->get('q');
        $data = Pemasok::where('nama', 'like', "%$q%")
            ->select('id', 'nama')
            ->limit(20)
            ->get();
        return response()->json($data);
    }

        public function getObatSelect2(\Illuminate\Http\Request $request)
    {
        $q = $request->get('q');
        $data = Obat::where('nama', 'like', "%$q%")
            ->select('id', 'nama')
            ->limit(20)
            ->get();
        return response()->json($data);
    }
        public function getGudangSelect2(\Illuminate\Http\Request $request)
    {
        $q = $request->get('q');
        $data = Gudang::where('nama', 'like', "%$q%")
            ->select('id', 'nama')
            ->limit(20)
            ->get();
        return response()->json($data);
    }
    
        /**
         * Select2 AJAX endpoint for FakturBeli search
         */
        public function select2(\Illuminate\Http\Request $request)
        {
            $q = $request->get('q');
            $query = FakturBeli::query();
            if ($q) {
                $query->where(function($qr) use ($q) {
                    $qr->where('no_faktur', 'like', "%$q%")
                       ->orWhere('no_permintaan', 'like', "%$q%");
                });
            }
            $list = $query->with('pemasok')->orderBy('received_date', 'desc')->limit(30)->get();
            $results = $list->map(function($f) {
                $text = $f->no_faktur ?: ('Faktur #' . $f->id);
                if ($f->pemasok) $text .= ' — ' . $f->pemasok->nama;
                $text .= ' (' . ($f->status ?? '-') . ')';
                return ['id' => $f->id, 'text' => $text];
            });
            return response()->json(['results' => $results]);
        }
    
    /**
     * What approving this faktur will do, shown in the approval dialog: per line the qty, batch, ED, the
     * price vs Master Pembelian, and the HPP the obat will get. Read-only.
     */
    public function approvePreview($id)
    {
        $faktur = FakturBeli::with(['items.obat' => fn ($q) => $q->withInactive(), 'items.gudang:id,nama', 'pemasok:id,nama'])->findOrFail($id);
        $tolerance = (float) config('pembelian.toleransi_harga_persen', 10);
        $masters = \App\Models\ERM\MasterFaktur::where('pemasok_id', $faktur->pemasok_id)
            ->whereIn('obat_id', $faktur->items->pluck('obat_id'))
            ->get()
            ->keyBy('obat_id');

        $problems = [];
        $items = $faktur->items->filter(fn ($item) => (int) $item->qty > 0)->map(function ($item) use ($masters, $tolerance, &$problems) {
            $nama = optional($item->obat)->nama ?? 'Obat #' . $item->obat_id;
            if (trim((string) $item->batch) === '' || !$item->expiration_date) {
                $problems[] = "{$nama}: batch / ED belum diisi.";
            }
            $master = $masters->get($item->obat_id);
            $selisih = ($master && (float) $master->harga > 0)
                ? round(((float) $item->harga - (float) $master->harga) / (float) $master->harga * 100, 1)
                : null;

            return [
                'obat' => $nama,
                'qty' => (int) $item->qty,
                'diminta' => (int) $item->diminta,
                'gudang' => optional($item->gudang)->nama,
                'batch' => $item->batch,
                'expiration_date' => $item->expiration_date ? Carbon::parse($item->expiration_date)->format('d/m/Y') : null,
                'harga' => (float) $item->harga,
                'harga_master' => $master ? (float) $master->harga : null,
                'selisih_persen' => $selisih,
                'selisih_besar' => $selisih !== null && abs($selisih) > $tolerance,
                'hpp_lama' => (float) optional($item->obat)->hpp,
                'hpp_baru' => (float) $item->harga, // master HPP = price before discount, excl. PPN
            ];
        })->values();

        return response()->json([
            'success' => true,
            'no_faktur' => $faktur->no_faktur,
            'pemasok' => optional($faktur->pemasok)->nama,
            'status' => $faktur->status,
            'total' => (float) $faktur->total,
            'items' => $items,
            'sisa' => $faktur->items->filter(fn ($item) => (int) $item->diminta > (int) $item->qty)->count(),
            'toleransi_persen' => $tolerance,
            'problems' => $problems,
        ]);
    }

    /**
     * Approve faktur and update stock
     */
    public function approveFaktur($id)
    {
        DB::beginTransaction();
        try {
            // Locked + checked inside the transaction: a double click or two users approving at once
            // must not put the stok in twice
            $faktur = FakturBeli::with(['items.obat', 'pemasok'])->lockForUpdate()->findOrFail($id);
            if ($faktur->status !== 'diterima') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Faktur harus berstatus diterima untuk bisa diapprove (status sekarang: ' . $faktur->status . ').'
                ], 400);
            }

            foreach ($faktur->items as $item) {
                $diminta = (int) ($item->diminta ?? 0);
                $qty = (int) ($item->qty ?? 0);

                if ($diminta > 0 && $qty > $diminta) {
                    throw new \DomainException('Qty diterima untuk ' . ($item->obat->nama ?? 'item') . ' melebihi qty diminta.');
                }
                // Stok without batch / ED cannot be traced or rotated FEFO
                if ($qty > 0 && (trim((string) $item->batch) === '' || !$item->expiration_date)) {
                    throw new \DomainException('Batch dan tanggal kadaluarsa ' . ($item->obat->nama ?? 'item') . ' belum diisi. Lengkapi lewat Edit faktur sebelum approve.');
                }
            }

            // Pisahkan item yang diterima dan item yang masih outstanding
            $itemsDiterima = [];
            $itemsOutstanding = [];
            foreach ($faktur->items as $item) {
                $qtyDiterima = (int) ($item->qty ?? 0);
                $qtyDiminta = (int) ($item->diminta ?? 0);
                $sisaQty = max($qtyDiminta - $qtyDiterima, 0);

                if ($qtyDiterima > 0) {
                    $itemsDiterima[] = $item;
                }

                if ($sisaQty > 0) {
                    $itemsOutstanding[] = [
                        'item' => $item,
                        'remaining_qty' => $sisaQty,
                    ];
                }

                $item->update(['sisa' => $sisaQty]);
            }

            // Proses item yang diterima (qty > 0)
            // HPP is defined WITHOUT PPN: item tax and global pajak are never part of HPP.
            foreach ($itemsDiterima as $item) {
                $qty = $item->qty ?? 0;
                $harga = $item->harga ?? 0;
                $diskon = $item->diskon ?? 0;
                $diskonType = $item->diskon_type ?? 'nominal';
                $base = $qty * $harga;
                $diskonValue = $diskonType === 'percent' ? ($base * $diskon / 100) : $diskon;
                $hppPerUnit = $qty > 0 ? ($base - $diskonValue) / $qty : 0; // after diskon, excl PPN (fallback only)
                $hppJualPerUnit = $qty > 0 ? $base / $qty : 0; // before diskon, excl PPN -> master HPP

                // Update stock using StokService
                $this->stokService->masukViaFaktur(
                    $item->obat_id,
                    $item->gudang_id,
                    $qty,
                    $faktur->id,
                    $faktur->no_faktur,
                    $item->batch,
                    $item->expiration_date,
                    $hppPerUnit, // hargaBeli (after diskon, excl PPN)
                    $hppJualPerUnit, // hargaBeliJual (before diskon, excl PPN)
                    $faktur->pemasok->nama ?? null
                );
            }

            // Update status faktur
            $faktur->update([
                'status' => 'diapprove',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // The price paid becomes the Master Pembelian price for the next permintaan
            $this->masterPembelian->syncFromFaktur($faktur);

            // Jika ada sisa item, buat faktur baru untuk delivery berikutnya
            if (count($itemsOutstanding) > 0) {
                $newNoFaktur = $faktur->no_faktur . '-NEXT-' . date('YmdHis');
                $newFaktur = FakturBeli::create([
                    'permintaan_id' => $faktur->permintaan_id,
                    'pemasok_id' => $faktur->pemasok_id,
                    'no_faktur' => $newNoFaktur,
                    'no_permintaan' => $faktur->no_permintaan,
                    'received_date' => $faktur->received_date,
                    'requested_date' => $faktur->requested_date,
                    'due_date' => $faktur->due_date,
                    'ship_date' => $faktur->ship_date,
                    'notes' => $faktur->notes,
                    'bukti' => $faktur->bukti,
                    'subtotal' => null,
                    'global_diskon' => null,
                    'global_pajak' => null,
                    'total' => null,
                    'status' => 'diminta',
                    'created_by' => Auth::id(),
                ]);
                foreach ($itemsOutstanding as $outstanding) {
                    $item = $outstanding['item'];
                    $remainingQty = $outstanding['remaining_qty'];

                    $newFaktur->items()->create([
                        'permintaan_item_id' => $item->permintaan_item_id,
                        'obat_id' => $item->obat_id,
                        'principal_id' => $item->principal_id,
                        'qty' => 0,
                        'diminta' => $remainingQty,
                        'sisa' => $remainingQty,
                        'harga' => $item->harga,
                        'diskon' => $item->diskon,
                        'diskon_type' => $item->diskon_type,
                        'tax' => $item->tax,
                        'tax_type' => $item->tax_type,
                        'gudang_id' => $item->gudang_id,
                        'batch' => $item->batch,
                        'expiration_date' => $item->expiration_date,
                        'total_amount' => 0,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => count($itemsOutstanding) > 0
                    ? 'Faktur berhasil diapprove dan stok telah diupdate. Faktur lanjutan dibuat untuk sisa item yang belum terpenuhi.'
                    : 'Faktur berhasil diapprove dan stok telah diupdate.'
            ]);
        } catch (\DomainException $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::rollback();
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }    // New methods for purchase request functionality
        /**
     * Print Faktur Pembelian as PDF using mPDF
     */
    public function printFaktur($id)
    {
        $faktur = \App\Models\ERM\FakturBeli::with(['items.obat', 'items.gudang', 'pemasok'])->findOrFail($id);
        $mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
        $html = view('erm.fakturbeli.print', compact('faktur'))->render();
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="FakturPembelian-' . $faktur->no_faktur . '.pdf"'
        ]);
    }

    /**
     * Export all FakturBeli items to CSV (Excel-compatible)
     * Columns: no faktur, tanggal jatuh tempo, nama obat, qty, satuan, nama vendor, harga per satuan, diskon, pajak, total harga
     */
    public function exportItemsExcel(Request $request)
    {
        $items = $this->buildApprovedItemExportQuery($request)
            ->with(['fakturbeli.pemasok', 'obat.principal', 'principal'])
            ->get();

        if ($request->input('export_type') === 'principal_summary') {
            $summaryRows = [];

            foreach ($items as $item) {
                $principalName = $this->resolvePrincipalNameForItem($item);
                $obatName = optional($item->obat)->nama ?: '-';
                $satuan = optional($item->obat)->satuan_stok_label ?: '';
                $key = $principalName . '|' . ($item->obat_id ?? 0);

                if (!isset($summaryRows[$key])) {
                    $summaryRows[$key] = [
                        'principal' => $principalName,
                        'nama_obat' => $obatName,
                        'satuan' => $satuan,
                        'qty' => 0,
                        'total_harga' => 0,
                    ];
                }

                $summaryRows[$key]['qty'] += (float) ($item->qty ?? 0);
                $summaryRows[$key]['total_harga'] += $this->calculateExportTotalHarga($item);
            }

            uasort($summaryRows, function ($left, $right) {
                return [$left['principal'], $left['nama_obat']] <=> [$right['principal'], $right['nama_obat']];
            });

            $exportArray = array_map(function ($row) {
                return [
                    $row['principal'],
                    $row['nama_obat'],
                    $row['satuan'],
                    $row['qty'],
                    $row['total_harga'],
                ];
            }, array_values($summaryRows));

            $headings = ['Principal', 'Nama Obat', 'Satuan', 'Total Qty', 'Total Harga'];

            $export = new class($exportArray, $headings) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
                private $array;
                private $headings;
                public function __construct(array $array, array $headings) { $this->array = $array; $this->headings = $headings; }
                public function array(): array { return $this->array; }
                public function headings(): array { return $this->headings; }
            };

            $filename = 'faktur_items_principal_summary_' . date('Ymd_His') . '.xlsx';

            return Excel::download($export, $filename);
        }

        $rows = [];

        // Group items by fakturbeli so we can place global pajak only on the first row
        $grouped = $items->groupBy(function($it) {
            return $it->fakturbeli->id ?? 0;
        });

        foreach ($grouped as $fakturId => $groupItems) {
            // Determine if this faktur uses only global pajak (no per-item tax values)
            $faktur = $groupItems->first()->fakturbeli;
            $hasPerItemTax = $groupItems->contains(function($it) {
                return !empty($it->tax) && floatval($it->tax) != 0;
            });

            $first = true;
            foreach ($groupItems as $item) {
                // For faktur that uses only global pajak, put the numeric global_pajak
                // only on the first row. Otherwise keep existing behavior (show value+type).
                if (!empty($faktur->global_pajak) && !$hasPerItemTax) {
                    if ($first) {
                        $globalPajakValue = $faktur->global_pajak;
                        $globalPajakType = '';
                    } else {
                        // hide global_pajak on subsequent rows for faktur that use only global pajak
                        $globalPajakValue = '';
                        $globalPajakType = '';
                    }
                } else {
                    $globalPajakValue = $faktur->global_pajak ?? '';
                    $globalPajakType = $faktur->global_pajak_type ?? '';
                }

                $rows[] = [
                    'no_faktur' => $faktur->no_faktur ?? '',
                    'due_date' => $faktur->due_date ?? '',
                    'requested_date' => $faktur->requested_date ?? '',
                    'received_date' => $faktur->received_date ?? '',
                    'nama_obat' => $item->obat->nama ?? '',
                    'qty' => $item->qty ?? 0,
                    'satuan' => $item->obat->satuan ?? '',
                    'nama_vendor' => $faktur->pemasok->nama ?? '',
                    'nama_principal' => $this->resolvePrincipalNameForItem($item),
                    'harga_per_satuan' => $item->harga ?? 0,
                    'diskon' => isset($item->diskon) ? $item->diskon : '',
                    'diskon_type' => isset($item->diskon_type) ? $item->diskon_type : '',
                    'pajak' => isset($item->tax) ? $item->tax : '',
                    'pajak_type' => isset($item->tax_type) ? $item->tax_type : '',
                    'global_diskon' => $faktur->global_diskon ?? '',
                    'global_diskon_type' => $faktur->global_diskon_type ?? '',
                    'global_pajak' => $globalPajakValue,
                    'global_pajak_type' => $globalPajakType,
                    'total_harga' => $this->calculateExportTotalHarga($item),
                ];

                $first = false;
            }
        }

        $exportArray = array_map(function($r) {
            return [
                $r['no_faktur'],
                $r['due_date'],
                $r['requested_date'],
                $r['received_date'],
                $r['nama_obat'],
                $r['qty'],
                $r['satuan'],
                $r['nama_vendor'],
                $r['nama_principal'],
                $r['harga_per_satuan'],
                ($r['diskon'] === '' ? '' : ($r['diskon'] . ($r['diskon_type'] ? (' ' . $r['diskon_type']) : ''))),
                ($r['pajak'] === '' ? '' : ($r['pajak'] . ($r['pajak_type'] ? (' ' . $r['pajak_type']) : ''))),
                ($r['global_diskon'] === '' ? '' : ($r['global_diskon'] . ($r['global_diskon_type'] ? (' ' . $r['global_diskon_type']) : ''))),
                ($r['global_pajak'] === '' ? '' : ($r['global_pajak'] . ($r['global_pajak_type'] ? (' ' . $r['global_pajak_type']) : ''))),
                $r['total_harga'],
            ];
        }, $rows);

        $headings = ['No Faktur', 'Tanggal Jatuh Tempo', 'Tanggal Permintaan', 'Tanggal Terima', 'Nama Obat', 'Qty', 'Satuan', 'Nama Distributor', 'Nama Principal', 'Harga Per Satuan', 'Diskon', 'Pajak', 'Global Diskon', 'Global Pajak', 'Total Harga'];

        $export = new class($exportArray, $headings) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            private $array;
            private $headings;
            public function __construct(array $array, array $headings) { $this->array = $array; $this->headings = $headings; }
            public function array(): array { return $this->array; }
            public function headings(): array { return $this->headings; }
        };

        $filename = 'faktur_items_' . date('Ymd_His') . '.xlsx';
        return Excel::download($export, $filename);
    }

    private function buildApprovedItemExportQuery(Request $request)
    {
        $query = FakturBeliItem::query();

        if ($request->filled('principal_id')) {
            $principalId = (int) $request->input('principal_id');

            $query->where(function ($principalQuery) use ($principalId) {
                // Principal saved on the item, or for items without one the obat's principal (Master Obat)
                $principalQuery->where('principal_id', $principalId)
                    ->orWhere(function ($fallbackQuery) use ($principalId) {
                        $fallbackQuery->whereNull('principal_id')
                            ->whereHas('obat', fn ($obatQuery) => $obatQuery->withInactive()->where('principal_id', $principalId));
                    });
            });
        }

        if ($request->filled('tanggal_terima_range')) {
            $range = explode(' - ', $request->input('tanggal_terima_range'));

            if (count($range) === 2) {
                $start = $range[0];
                $end = $range[1];

                return $query->whereHas('fakturbeli', function ($subQuery) use ($start, $end) {
                    $subQuery->where('status', 'diapprove')
                        ->whereDate('received_date', '>=', $start)
                        ->whereDate('received_date', '<=', $end);
                });
            }
        }

        return $query->whereHas('fakturbeli', function ($subQuery) {
            $subQuery->where('status', 'diapprove');
        });
    }

    private function resolvePrincipalNameForItem(FakturBeliItem $item): string
    {
        if ($item->principal) {
            return $item->principal->nama ?: 'Tanpa Principal';
        }

        // Item without its own principal: the obat's principal (Master Obat)
        return optional(optional($item->obat)->principal)->nama ?: 'Tanpa Principal';
    }

    /**
     * Value of one line as on the faktur: the line total (incl. its own discount and tax) plus its share of
     * the faktur's global pajak minus its share of the global diskon (by line value).
     */
    private function calculateExportTotalHarga(FakturBeliItem $item): float
    {
        $lineTotal = (float) ($item->total_amount ?? 0);
        $faktur = $item->fakturbeli;
        if (!$faktur) {
            return $lineTotal;
        }

        static $fakturSubtotal = [];
        if (!isset($fakturSubtotal[$faktur->id])) {
            $fakturSubtotal[$faktur->id] = (float) FakturBeliItem::where('fakturbeli_id', $faktur->id)->sum('total_amount');
        }
        $share = $fakturSubtotal[$faktur->id] > 0 ? $lineTotal / $fakturSubtotal[$faktur->id] : 0;

        return round($lineTotal + $share * ((float) $faktur->global_pajak - (float) $faktur->global_diskon), 2);
    }

    /**
     * Return JSON detail for a Faktur Beli (used by other modules to show faktur snapshot)
     */
    public function showJson($id)
    {
        $faktur = FakturBeli::with(['pemasok', 'items.obat'])->findOrFail($id);
        $items = collect($faktur->items)->map(function ($item) {
            return [
                'id' => $item->id,
                'obat_id' => $item->obat_id,
                'obat_nama' => $item->obat ? $item->obat->nama : null,
                'qty' => $item->qty,
                'harga' => $item->harga,
                'total_amount' => $item->total_amount,
            ];
        })->values()->all();

        // Return only necessary fields for UI
        return response()->json([
            'id' => $faktur->id,
            'no_faktur' => $faktur->no_faktur,
            'pemasok' => $faktur->pemasok ? [ 'id' => $faktur->pemasok->id, 'nama' => $faktur->pemasok->nama ] : null,
            'subtotal' => $faktur->subtotal,
            'global_diskon' => $faktur->global_diskon,
            'global_pajak' => $faktur->global_pajak,
            'total' => $faktur->total,
            'status' => $faktur->status,
            'items' => $items
        ]);
    }
}
