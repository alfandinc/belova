<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\FakturRetur;
use App\Models\ERM\FakturReturItem;
use App\Models\ERM\FakturBeli;
use App\Models\ERM\FakturBeliItem;
use App\Models\ERM\Obat;
use App\Models\ERM\Gudang;
use App\Services\ERM\StokService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FakturReturController extends Controller
{
    protected $stokService;

    public function __construct(StokService $stokService)
    {
        $this->stokService = $stokService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = FakturRetur::with(['fakturbeli', 'pemasok']);
            return \Yajra\DataTables\DataTables::of($query)
                ->addColumn('fakturbeli.no_faktur', function($row) {
                    return $row->fakturbeli ? $row->fakturbeli->no_faktur : '-';
                })
                ->addColumn('pemasok.nama', function($row) {
                    return $row->pemasok ? $row->pemasok->nama : '-';
                })
                    ->addColumn('action', function($row) {
                        return '<button type="button" class="btn btn-info btn-sm btn-detail-retur" data-id="'.$row->id.'">Detail</button>';
                    })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('erm.fakturretur.index');
    }

    public function create(Request $request)
    {
        $fakturbeli_id = $request->input('fakturbeli_id');
        $fakturbeli = $fakturbeli_id ? FakturBeli::with('items.obat')->find($fakturbeli_id) : null;
        $fakturbelis = FakturBeli::orderByDesc('id')->get();
        $gudangs = Gudang::all();
        return view('erm.fakturretur.create', compact('fakturbelis', 'fakturbeli', 'gudangs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fakturbeli_id' => 'required|exists:erm_fakturbeli,id',
            'tanggal_retur' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.fakturbeli_item_id' => 'required|exists:erm_fakturbeli_items,id',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.gudang_id' => 'required|exists:erm_gudang,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.batch' => 'nullable|string',
            'items.*.expiration_date' => 'nullable|date',
            'items.*.alasan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $retur = FakturRetur::create([
                'fakturbeli_id' => $validated['fakturbeli_id'],
                'pemasok_id' => FakturBeli::find($validated['fakturbeli_id'])->pemasok_id ?? null,
                'no_retur' => 'RET-' . time(),
                'tanggal_retur' => $validated['tanggal_retur'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'diajukan',
            ]);

            foreach ($validated['items'] as $item) {
                $returItem = $retur->items()->create([
                    'fakturbeli_item_id' => $item['fakturbeli_item_id'],
                    'obat_id' => $item['obat_id'],
                    'gudang_id' => $item['gudang_id'],
                    'qty' => $item['qty'],
                    'batch' => $item['batch'] ?? null,
                    'expiration_date' => $item['expiration_date'] ?? null,
                    'alasan' => $item['alasan'] ?? null,
                    'status' => 'diajukan',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Retur berhasil diajukan']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $retur = FakturRetur::with(['fakturbeli', 'items.obat', 'items.gudang'])->findOrFail($id);
        return view('erm.fakturretur.show', compact('retur'));
    }

    public function approve($id)
    {
        $retur = FakturRetur::with(['items'])->findOrFail($id);
        if ($retur->status !== 'diajukan') {
            return response()->json(['success' => false, 'message' => 'Retur harus berstatus diajukan']);
        }
        DB::beginTransaction();
        try {
            foreach ($retur->items as $item) {
                // Kurangi stok sesuai item retur
                $this->stokService->kurangiStok(
                    $item->obat_id,
                    $item->gudang_id,
                    $item->qty,
                    $item->batch,
                    'retur',
                    $retur->id,
                    $item->alasan ?? 'Retur faktur pembelian'
                );
                $item->update(['status' => 'diapprove']);
            }
            $retur->update([
                'status' => 'diapprove',
                'approved_by' => Auth::id()
            ]);

            // Update FakturBeli status to 'diretur'
            if ($retur->fakturbeli_id) {
                \App\Models\ERM\FakturBeli::where('id', $retur->fakturbeli_id)
                    ->update(['status' => 'diretur']);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Retur berhasil diapprove, stok dikurangi, dan status faktur beli diubah menjadi diretur']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
