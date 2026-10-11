<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\FakturRetur;
use App\Models\ERM\FakturReturItem;
use App\Models\ERM\FakturBeli;
use App\Models\ERM\ObatStokGudang;
use App\Services\ERM\StokService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Retur ke Supplier: diajukan -> diapprove (stok of the batch leaves the gudang) or ditolak.
 * Only stok received on an approved faktur can be returned, at most what was received minus earlier returns.
 */
class FakturReturController extends Controller
{
    private const STATUS_LABEL = [
        'diajukan' => 'Diajukan',
        'diapprove' => 'Disetujui',
        'ditolak' => 'Ditolak',
        'menunggu_retur' => 'Diajukan',
    ];

    // Statuses of a faktur whose stok entered the gudang
    private const FAKTUR_BISA_RETUR = ['diapprove', 'diretur_sebagian'];

    protected $stokService;

    public function __construct(StokService $stokService)
    {
        $this->stokService = $stokService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $counts = FakturRetur::select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');

            $query = FakturRetur::with(['fakturbeli:id,no_faktur', 'pemasok:id,nama'])
                ->withCount('items')
                ->withSum('items as total_qty', 'qty')
                ->select('erm_fakturretur.*')
                ->when(array_key_exists((string) $request->input('status'), self::STATUS_LABEL), fn ($q) => $q->where('status', $request->input('status')));

            return \Yajra\DataTables\DataTables::of($query)
                ->addColumn('no_faktur', fn ($row) => optional($row->fakturbeli)->no_faktur ?? '-')
                ->addColumn('pemasok_nama', fn ($row) => optional($row->pemasok)->nama ?? '-')
                ->addColumn('status_label', fn ($row) => self::STATUS_LABEL[$row->status] ?? $row->status)
                ->filterColumn('no_faktur', function ($q, $keyword) {
                    $q->whereHas('fakturbeli', fn ($f) => $f->where('no_faktur', 'like', "%{$keyword}%"));
                })
                ->filterColumn('pemasok_nama', function ($q, $keyword) {
                    $q->whereHas('pemasok', fn ($p) => $p->where('nama', 'like', "%{$keyword}%"));
                })
                ->with('counts', [
                    'all' => (int) $counts->sum(),
                    'diajukan' => (int) (($counts['diajukan'] ?? 0) + ($counts['menunggu_retur'] ?? 0)),
                    'diapprove' => (int) ($counts['diapprove'] ?? 0),
                    'ditolak' => (int) ($counts['ditolak'] ?? 0),
                ])
                ->make(true);
        }

        return view('erm.fakturretur.index');
    }

    /** Qty of each faktur item already returned or waiting to be returned. */
    private function qtySudahDiretur(array $fakturItemIds, ?int $exceptReturId = null): array
    {
        return FakturReturItem::query()
            ->whereIn('fakturbeli_item_id', $fakturItemIds ?: [0])
            ->whereHas('fakturretur', function ($q) use ($exceptReturId) {
                $q->whereIn('status', ['diajukan', 'menunggu_retur', 'diapprove'])
                    ->when($exceptReturId, fn ($r) => $r->where('id', '!=', $exceptReturId));
            })
            ->select('fakturbeli_item_id', DB::raw('SUM(qty) as qty'))
            ->groupBy('fakturbeli_item_id')
            ->pluck('qty', 'fakturbeli_item_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    public function create(Request $request)
    {
        $fakturbeli = null;
        $sisaRetur = [];
        $stokBatch = [];
        if ($request->filled('fakturbeli_id')) {
            $fakturbeli = FakturBeli::with(['items.obat' => fn ($q) => $q->withInactive(), 'items.gudang', 'pemasok'])->find($request->input('fakturbeli_id'));
            if ($fakturbeli && in_array($fakturbeli->status, self::FAKTUR_BISA_RETUR, true)) {
                $sudah = $this->qtySudahDiretur($fakturbeli->items->pluck('id')->all());
                foreach ($fakturbeli->items as $item) {
                    $sisaRetur[$item->id] = max(0, (float) $item->qty - ($sudah[$item->id] ?? 0));
                    $stokBatch[$item->id] = (float) ObatStokGudang::where('obat_id', $item->obat_id)
                        ->where('gudang_id', $item->gudang_id)
                        ->where('batch', $item->batch)
                        ->value('stok');
                }
            }
        }

        // The faktur is searched (select2) instead of listing every faktur
        return view('erm.fakturretur.create', compact('fakturbeli', 'sisaRetur', 'stokBatch'));
    }

    /**
     * Select2 search for the retur form: fakturs whose stok entered the gudang, by faktur / permintaan
     * number, pemasok or obat name. Newest first.
     */
    public function fakturOptions(Request $request)
    {
        $q = trim((string) $request->input('q'));

        $fakturs = FakturBeli::with('pemasok:id,nama')
            ->withCount('items')
            ->whereIn('status', self::FAKTUR_BISA_RETUR)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('no_faktur', 'like', "%{$q}%")
                        ->orWhere('no_permintaan', 'like', "%{$q}%")
                        ->orWhereHas('pemasok', fn ($p) => $p->where('nama', 'like', "%{$q}%"))
                        ->orWhereHas('items.obat', fn ($o) => $o->withInactive()->where('nama', 'like', "%{$q}%"));
                });
            })
            ->orderByDesc('received_date')
            ->orderByDesc('id')
            ->limit(30)
            ->get(['id', 'no_faktur', 'no_permintaan', 'pemasok_id', 'received_date', 'status', 'total']);

        return response()->json(['results' => $fakturs->map(fn ($f) => [
            'id' => $f->id,
            'text' => ($f->no_faktur ?: 'Faktur #' . $f->id) . ' — ' . (optional($f->pemasok)->nama ?? '-'),
            'no_faktur' => $f->no_faktur ?: 'Faktur #' . $f->id,
            'pemasok' => optional($f->pemasok)->nama,
            'tanggal' => $f->received_date ? Carbon::parse($f->received_date)->format('d/m/Y') : null,
            'no_permintaan' => $f->no_permintaan,
            'jumlah_item' => $f->items_count,
            'total' => (float) $f->total,
            'sebagian' => $f->status === 'diretur_sebagian',
        ])->values()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fakturbeli_id' => 'required|exists:erm_fakturbeli,id',
            'tanggal_retur' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array',
            'items.*.fakturbeli_item_id' => 'required|exists:erm_fakturbeli_items,id',
            'items.*.qty' => 'required|integer|min:0',
            'items.*.alasan' => 'nullable|string|max:255',
        ], [
            'items.required' => 'Pilih obat yang diretur.',
        ]);

        $itemsToProcess = collect($validated['items'])->filter(fn ($it) => (int) $it['qty'] > 0)->values();
        if ($itemsToProcess->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Isi qty retur minimal untuk satu obat.'], 422);
        }

        try {
            $retur = DB::transaction(function () use ($validated, $itemsToProcess) {
                $faktur = FakturBeli::with('items.obat')->lockForUpdate()->findOrFail($validated['fakturbeli_id']);
                if (!in_array($faktur->status, self::FAKTUR_BISA_RETUR, true)) {
                    throw new \DomainException('Hanya faktur yang sudah disetujui (stoknya sudah masuk gudang) yang bisa diretur.');
                }

                $fakturItems = $faktur->items->keyBy('id');
                $sudah = $this->qtySudahDiretur($fakturItems->keys()->all());

                $errors = [];
                foreach ($itemsToProcess as $it) {
                    $fakturItem = $fakturItems->get((int) $it['fakturbeli_item_id']);
                    if (!$fakturItem) {
                        throw new \DomainException('Item retur tidak berasal dari faktur ini.');
                    }
                    $nama = optional($fakturItem->obat)->nama ?? 'Obat #' . $fakturItem->obat_id;
                    $sisa = (float) $fakturItem->qty - ($sudah[$fakturItem->id] ?? 0);
                    if ((float) $it['qty'] > $sisa) {
                        $errors[] = "{$nama}: maksimal bisa diretur " . rtrim(rtrim(number_format($sisa, 2, ',', '.'), '0'), ',') . " (diterima {$fakturItem->qty}, sudah/sedang diretur " . ($sudah[$fakturItem->id] ?? 0) . ').';
                    }
                }
                if ($errors) {
                    throw new \DomainException(implode("\n", $errors));
                }

                $retur = FakturRetur::create([
                    'fakturbeli_id' => $faktur->id,
                    'pemasok_id' => $faktur->pemasok_id,
                    'no_retur' => $this->nomorRetur(),
                    'tanggal_retur' => $validated['tanggal_retur'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'diajukan',
                ]);

                // Obat, gudang, batch and ED come from the faktur line, not from the browser
                foreach ($itemsToProcess as $it) {
                    $fakturItem = $fakturItems->get((int) $it['fakturbeli_item_id']);
                    $retur->items()->create([
                        'fakturbeli_item_id' => $fakturItem->id,
                        'obat_id' => $fakturItem->obat_id,
                        'gudang_id' => $fakturItem->gudang_id,
                        'qty' => (int) $it['qty'],
                        'batch' => $fakturItem->batch,
                        'expiration_date' => $fakturItem->expiration_date,
                        'alasan' => $it['alasan'] ?? null,
                        'status' => 'diajukan',
                    ]);
                }

                return $retur;
            });
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => "Retur {$retur->no_retur} diajukan. Stok berkurang setelah retur disetujui."]);
    }

    // RET-YYYYMMDD-NNN; read locked inside the transaction, the unique index stops any remaining collision
    private function nomorRetur(): string
    {
        $prefix = 'RET-' . date('Ymd') . '-';
        $last = FakturRetur::where('no_retur', 'like', $prefix . '%')->lockForUpdate()->orderByDesc('no_retur')->value('no_retur');
        $num = ($last && preg_match('/-(\d+)$/', $last, $m)) ? ((int) $m[1] + 1) : 1;

        return $prefix . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
    }

    public function show($id)
    {
        $retur = FakturRetur::with(['fakturbeli', 'pemasok', 'items.obat' => fn ($q) => $q->withInactive(), 'items.gudang'])->findOrFail($id);
        $statusLabel = self::STATUS_LABEL[$retur->status] ?? $retur->status;
        $approver = $retur->approved_by ? \App\Models\User::find($retur->approved_by) : null;
        // Read-only when opened as a preview (kartu stok)
        $canDecide = !request()->boolean('preview') && (bool) optional(Auth::user())->hasAnyRole(['Admin', 'Farmasi']);

        return view('erm.fakturretur.show', compact('retur', 'statusLabel', 'approver', 'canDecide'));
    }

    public function approve($id)
    {
        DB::beginTransaction();
        try {
            // Locked + checked inside the transaction so the stok can only leave once
            $retur = FakturRetur::with(['items'])->lockForUpdate()->findOrFail($id);
            if (!in_array($retur->status, ['diajukan', 'menunggu_retur'], true)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Retur ini sudah diproses (status: ' . (self::STATUS_LABEL[$retur->status] ?? $retur->status) . ').'], 422);
            }

            foreach ($retur->items as $item) {
                $this->stokService->kurangiStok(
                    $item->obat_id,
                    $item->gudang_id,
                    $item->qty,
                    $item->batch,
                    'retur',
                    $retur->id,
                    'Retur ' . $retur->no_retur . ($item->alasan ? ' - ' . $item->alasan : '')
                );
                $item->update(['status' => 'diapprove']);
            }
            $retur->update([
                'status' => 'diapprove',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Whole faktur returned -> diretur, otherwise diretur_sebagian
            $statusFaktur = null;
            if ($retur->fakturbeli_id) {
                $faktur = FakturBeli::with('items')->lockForUpdate()->find($retur->fakturbeli_id);
                if ($faktur) {
                    // Count approved returns only
                    $diapprove = FakturReturItem::whereIn('fakturbeli_item_id', $faktur->items->pluck('id'))
                        ->whereHas('fakturretur', fn ($q) => $q->where('status', 'diapprove'))
                        ->select('fakturbeli_item_id', DB::raw('SUM(qty) as qty'))
                        ->groupBy('fakturbeli_item_id')
                        ->pluck('qty', 'fakturbeli_item_id');
                    $semua = $faktur->items->filter(fn ($i) => (float) $i->qty > 0)->every(fn ($i) => (float) ($diapprove[$i->id] ?? 0) >= (float) $i->qty);
                    $statusFaktur = $semua ? 'diretur' : 'diretur_sebagian';
                    $faktur->update(['status' => $statusFaktur]);
                }
            }

            // Retur created from the gudang expired: mark its tindak lanjut as done
            app(\App\Services\ERM\GudangExpiredService::class)->sinkronStatusRetur($retur, 'diapprove');

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => "Retur {$retur->no_retur} disetujui dan stok dikurangi."
                    . ($statusFaktur ? ' Faktur ditandai ' . ($statusFaktur === 'diretur' ? 'diretur.' : 'diretur sebagian.') : ''),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Reject a retur that is still diajukan; stok is untouched.
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:500'], ['reason.required' => 'Alasan penolakan wajib diisi.']);

        $rejected = DB::transaction(function () use ($id, $request) {
            $retur = FakturRetur::lockForUpdate()->findOrFail($id);
            if (!in_array($retur->status, ['diajukan', 'menunggu_retur'], true)) {
                return false;
            }

            $retur->items()->update(['status' => 'ditolak']);
            $retur->update(['status' => 'ditolak', 'approved_by' => Auth::id(), 'approved_at' => now(), 'rejected_reason' => trim($request->reason)]);

            // A tindak lanjut expired waiting on this retur becomes batal, so the stok can be followed up again
            app(\App\Services\ERM\GudangExpiredService::class)->sinkronStatusRetur($retur, 'ditolak');
            return true;
        });

        if (!$rejected) {
            return response()->json(['success' => false, 'message' => 'Hanya retur yang masih diajukan yang bisa ditolak.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Retur ditolak. Stok tidak berubah.']);
    }
}
