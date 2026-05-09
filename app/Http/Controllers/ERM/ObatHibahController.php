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
use Illuminate\Validation\Rule;

class ObatHibahController extends Controller
{
    public function __construct(protected StokService $stokService)
    {
    }

    public function index()
    {
        $hibahs = ObatHibah::with(['items.obat', 'items.gudang', 'creator'])
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

        DB::transaction(function () use ($validated) {
            $hibah = ObatHibah::create([
                'nomor_hibah' => ObatHibah::generateNomorHibah(),
                'received_date' => $validated['received_date'],
                'sumber' => $validated['sumber'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $hibahItem = $hibah->items()->create([
                    'obat_id' => $item['obat_id'],
                    'gudang_id' => $item['gudang_id'],
                    'qty' => $item['qty'],
                    'batch' => $item['batch'] ?? null,
                    'expiration_date' => $item['expiration_date'] ?? null,
                ]);

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
        });

        return redirect()
            ->route('erm.obat-hibah.index')
            ->with('success', 'Obat hibah berhasil disimpan dan stok gudang telah ditambahkan.');
    }
}