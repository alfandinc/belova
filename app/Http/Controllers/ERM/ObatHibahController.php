<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Gudang;
use App\Models\ERM\Obat;
use App\Models\ERM\ObatHibah;
use App\Services\ERM\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ObatHibahController extends Controller
{
    public function __construct(protected StokService $stokService)
    {
    }

    public function index(Request $request)
    {
        $status = in_array($request->input('status'), ['diterima', 'diapprove', 'ditolak'], true) ? $request->input('status') : null;
        $q = trim((string) $request->input('q'));

        $base = ObatHibah::query()->when($q !== '', function ($query) use ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('nomor_hibah', 'like', "%{$q}%")
                    ->orWhere('sumber', 'like', "%{$q}%")
                    ->orWhereHas('items.obat', fn ($o) => $o->withInactive()->where('nama', 'like', "%{$q}%"));
            });
        });
        $counts = (clone $base)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');

        $hibahs = (clone $base)
            ->with(['items.obat' => fn ($o) => $o->withInactive(), 'items.gudang', 'creator', 'approver', 'rejecter'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'diterima' THEN 0 ELSE 1 END")
            ->latest('received_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $canApprove = (bool) optional(Auth::user())->hasAnyRole(['Admin', 'Farmasi']);
        // Gudang choices of the input modal (not the gudang expired)
        $gudangs = $this->gudangHibah();

        return view('erm.obat-hibah.index', compact('hibahs', 'counts', 'status', 'q', 'canApprove', 'gudangs'));
    }

    private function gudangHibah()
    {
        $expiredId = app(\App\Services\ERM\GudangExpiredService::class)->gudangId();

        return Gudang::orderBy('nama')->get(['id', 'nama'])
            ->reject(fn ($g) => $expiredId && (int) $g->id === (int) $expiredId)
            ->values();
    }

    // The form lives in a modal on the Obat Hibah page; the old link lands there with the modal opened
    public function create()
    {
        return redirect()->route('erm.obat-hibah.index', ['baru' => 1]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'received_date' => 'required|date',
            'sumber' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'bukti' => 'nullable|image|max:10240',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => [
                'required',
                Rule::exists('erm_obat', 'id')->where(function ($query) {
                    $query->where('status_aktif', 1);
                }),
            ],
            'items.*.gudang_id' => 'required|exists:erm_gudang,id',
            'items.*.qty' => 'required|numeric|gt:0',
            // Stok without batch / ED cannot be traced or rotated FEFO
            'items.*.batch' => 'required|string|max:255',
            'items.*.expiration_date' => 'required|date',
        ], [
            'items.*.batch.required' => 'Nomor batch wajib diisi untuk setiap obat.',
            'items.*.expiration_date.required' => 'Tanggal kadaluarsa wajib diisi untuk setiap obat.',
            'items.*.obat_id.exists' => 'Obat harus obat aktif.',
        ]);

        // Stok in the gudang expired only comes from expired batches
        $expiredService = app(\App\Services\ERM\GudangExpiredService::class);
        foreach ($validated['items'] as $item) {
            if ($expiredService->isGudangExpired($item['gudang_id'])) {
                return response()->json(['success' => false, 'message' => 'Obat hibah tidak bisa dimasukkan ke gudang ED.'], 422);
            }
        }

        $buktiPath = $request->hasFile('bukti') ? $request->file('bukti')->store('obat_hibah_bukti', 'public') : null;

        $nomor = DB::transaction(function () use ($validated, $buktiPath) {
            $hibah = ObatHibah::create([
                'nomor_hibah' => ObatHibah::generateNomorHibah(),
                'received_date' => $validated['received_date'],
                'sumber' => $validated['sumber'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'bukti' => $buktiPath,
                'status' => 'diterima',
                'created_by' => Auth::id(),
            ]);
            $hibah->items()->createMany(collect($validated['items'])->map(fn ($item) => [
                'obat_id' => $item['obat_id'],
                'gudang_id' => $item['gudang_id'],
                'qty' => $item['qty'],
                'batch' => trim($item['batch']),
                'expiration_date' => $item['expiration_date'],
            ])->all());

            return $hibah->nomor_hibah;
        });

        return response()->json([
            'success' => true,
            'message' => "Obat hibah {$nomor} disimpan dan menunggu approval sebelum stok masuk ke gudang.",
        ]);
    }

    public function approve($id)
    {
        $hibah = ObatHibah::with('items')->findOrFail($id);

        if ($hibah->status !== 'diterima') {
            $message = 'Obat hibah harus berstatus diterima untuk bisa diapprove.';

            if ($this->isAjaxRequest($request = request())) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 400);
            }

            return redirect()
                ->route('erm.obat-hibah.index')
                ->with('error', $message);
        }

        $approved = DB::transaction(function () use ($hibah) {
            // Re-read locked: a double click / second approver must not put the stok in twice
            $hibah = ObatHibah::with('items')->lockForUpdate()->findOrFail($hibah->id);
            if ($hibah->status !== 'diterima') {
                return false;
            }

            foreach ($hibah->items as $hibahItem) {
                $this->stokService->masukViaHibah(
                    $hibahItem->obat_id,
                    $hibahItem->gudang_id,
                    $hibahItem->qty,
                    $hibah->id,
                    $hibah->nomor_hibah,
                    $hibahItem->batch,
                    $hibahItem->expiration_date,
                    $hibah->sumber,
                    $hibah->notes
                );
            }

            $hibah->update([
                'status' => 'diapprove',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            return true;
        });

        if (!$approved) {
            $message = 'Obat hibah ini sudah diproses oleh user lain.';
            if ($this->isAjaxRequest(request())) {
                return response()->json(['success' => false, 'message' => $message], 409);
            }
            return redirect()->route('erm.obat-hibah.index')->with('error', $message);
        }

        $hibah->refresh()->load('approver');

        if ($this->isAjaxRequest(request())) {
            return response()->json([
                'success' => true,
                'message' => 'Obat hibah berhasil diapprove dan stok gudang telah ditambahkan.',
                'data' => [
                    'id' => $hibah->id,
                    'status' => $hibah->status,
                    'status_label' => ucfirst($hibah->status),
                    'approver_name' => $hibah->approver?->name,
                ],
            ]);
        }

        return redirect()
            ->route('erm.obat-hibah.index')
            ->with('success', 'Obat hibah berhasil diapprove dan stok gudang telah ditambahkan.');
    }

    /**
     * Reject a hibah that is still waiting; stok is untouched.
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:500'], ['reason.required' => 'Alasan penolakan wajib diisi.']);

        $nomor = DB::transaction(function () use ($request, $id) {
            $hibah = ObatHibah::lockForUpdate()->findOrFail($id);
            if ($hibah->status !== 'diterima') {
                return null;
            }
            $hibah->update([
                'status' => 'ditolak',
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejected_reason' => trim($request->reason),
            ]);
            return $hibah->nomor_hibah;
        });

        if (!$nomor) {
            return response()->json(['success' => false, 'message' => 'Hanya obat hibah yang menunggu approval yang bisa ditolak.'], 422);
        }

        return response()->json(['success' => true, 'message' => "Obat hibah {$nomor} ditolak. Stok tidak berubah."]);
    }

    protected function isAjaxRequest(Request $request): bool
    {
        return $request->ajax() || $request->wantsJson();
    }

}