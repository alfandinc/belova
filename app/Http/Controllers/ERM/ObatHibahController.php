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

    public function index()
    {
        $hibahs = ObatHibah::with(['items.obat', 'items.gudang', 'creator', 'approver'])
            ->latest('received_date')
            ->latest('id')
            ->paginate(20);

        return view('erm.obat-hibah.index', compact('hibahs'));
    }

    public function create()
    {
        $obats = Obat::where('status_aktif', 1)->orderBy('nama')->get();
        $gudangs = Gudang::orderBy('nama')->get();

        return view('erm.obat-hibah.create', compact('obats', 'gudangs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'received_date' => 'required|date',
            'sumber' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
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
            'items.*.batch' => 'nullable|string|max:255',
            'items.*.expiration_date' => 'nullable|date',
        ]);

        $buktiPath = null;
        if ($request->hasFile('bukti')) {
            $buktiPath = $request->file('bukti')->store('obat_hibah_bukti', 'public');
        }

        DB::transaction(function () use ($validated, $buktiPath) {
            ObatHibah::create([
                'nomor_hibah' => ObatHibah::generateNomorHibah(),
                'received_date' => $validated['received_date'],
                'sumber' => $validated['sumber'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'bukti' => $buktiPath,
                'status' => 'diterima',
                'created_by' => Auth::id(),
            ])->items()->createMany(collect($validated['items'])->map(function ($item) {
                return [
                    'obat_id' => $item['obat_id'],
                    'gudang_id' => $item['gudang_id'],
                    'qty' => $item['qty'],
                    'batch' => $item['batch'] ?? null,
                    'expiration_date' => $item['expiration_date'] ?? null,
                ];
            })->all());
        });

        return redirect()
            ->route('erm.obat-hibah.index')
            ->with('success', 'Obat hibah berhasil disimpan dan menunggu approval sebelum stok masuk ke gudang.');
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

        DB::transaction(function () use ($hibah) {
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
        });

        $hibah->load('approver');

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

    protected function isAjaxRequest(Request $request): bool
    {
        return $request->ajax() || $request->wantsJson();
    }
}