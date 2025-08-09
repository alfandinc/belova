<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Permintaan;
use App\Models\ERM\PermintaanItem;
use App\Models\ERM\Obat;
use App\Models\ERM\Pemasok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermintaanController extends Controller
{
    public function index()
    {
        $permintaans = Permintaan::with('items')->orderBy('created_at', 'desc')->paginate(20);
        return view('erm.permintaan.index', compact('permintaans'));
    }

    public function create()
    {
        $obats = Obat::all();
        $pemasoks = Pemasok::all();
        return view('erm.permintaan.create', compact('obats', 'pemasoks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'request_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.pemasok_id' => 'required|exists:erm_pemasok,id',
            'items.*.jumlah_box' => 'required|integer|min:1',
            'items.*.qty_total' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            $permintaan = Permintaan::create([
                'request_date' => $request->request_date,
                'status' => 'waiting_approval',
            ]);
            foreach ($request->items as $item) {
                PermintaanItem::create([
                    'permintaan_id' => $permintaan->id,
                    'obat_id' => $item['obat_id'],
                    'pemasok_id' => $item['pemasok_id'],
                    'jumlah_box' => $item['jumlah_box'],
                    'qty_total' => $item['qty_total'],
                ]);
            }
        });
        return redirect()->route('erm.permintaan.index')->with('success', 'Permintaan created!');
    }

        public function getMasterFaktur(Request $request)
    {
        $request->validate([
            'obat_id' => 'required|exists:erm_obat,id',
            'pemasok_id' => 'required|exists:erm_pemasok,id',
        ]);
        $master = \App\Models\ERM\MasterFaktur::where('obat_id', $request->obat_id)
            ->where('pemasok_id', $request->pemasok_id)
            ->first();
        if (!$master) {
            return response()->json(['found' => false]);
        }
        return response()->json([
            'found' => true,
            'harga' => $master->harga,
            'qty_per_box' => $master->qty_per_box,
            'diskon' => $master->diskon,
            'diskon_type' => $master->diskon_type,
        ]);
    }
        public function approve($id)
    {
        $permintaan = Permintaan::with('items')->findOrFail($id);
        if ($permintaan->status !== 'waiting_approval') {
            return redirect()->back()->with('error', 'Permintaan sudah diproses.');
        }
        $userId = auth()->id();
        $now = now();
        DB::transaction(function () use ($permintaan, $userId, $now) {
            // Group items by pemasok
            $grouped = $permintaan->items->groupBy('pemasok_id');
            foreach ($grouped as $pemasokId => $items) {
                $faktur = \App\Models\ERM\FakturBeli::create([
                    'pemasok_id' => $pemasokId,
                    'no_faktur' => null,
                    'requested_date' => $permintaan->request_date,
                    'status' => 'diminta',
                ]);
                foreach ($items as $item) {
                    // Get harga, diskon, diskon_type from master faktur
                    $master = \App\Models\ERM\MasterFaktur::where('obat_id', $item->obat_id)
                        ->where('pemasok_id', $item->pemasok_id)
                        ->first();
                    $harga = $master ? $master->harga : 0;
                    $diskon = $master ? $master->diskon : 0;
                    $diskon_type = $master ? $master->diskon_type : 'nominal';
                    \App\Models\ERM\FakturBeliItem::create([
                        'fakturbeli_id' => $faktur->id,
                        'obat_id' => $item->obat_id,
                        'qty' => $item->qty_total,
                        'sisa' => $item->qty_total,
                        'harga' => $harga,
                        'diskon' => $diskon,
                        'diskon_type' => $diskon_type,
                        'diminta' => $item->qty_total,
                    ]);
                }
            }
            $permintaan->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_date' => $now,
            ]);
        });
        return redirect()->route('erm.permintaan.index')->with('success', 'Permintaan disetujui dan faktur berhasil dibuat!');
    }
}
