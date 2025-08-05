<?php


namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\FakturBeli;
use App\Models\ERM\FakturBeliItem;
use App\Models\ERM\Pemasok;
use App\Models\ERM\Gudang;
use App\Models\ERM\Obat;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Storage;

class FakturBeliController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = FakturBeli::with('pemasok')->select('erm_fakturbeli.*');
            return DataTables::of($data)
                ->addColumn('pemasok', function($row) {
                    return $row->pemasok ? $row->pemasok->nama : '-';
                })
                ->addColumn('action', function($row) {
                    return '<a href="/erm/fakturpembelian/' . $row->id . '/edit" class="btn btn-sm btn-primary">Edit</a> '
                        . '<button class="btn btn-sm btn-danger btn-delete-faktur" data-id="' . $row->id . '">Delete</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('erm.fakturbeli.index');
    }

    public function create()
    {
        $pemasoks = Pemasok::all();
        $gudangs = Gudang::all();
        $obats = Obat::all();
        return view('erm.fakturbeli.create', compact('pemasoks', 'gudangs', 'obats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pemasok_id' => 'required|exists:erm_pemasok,id',
            'no_faktur' => 'required|string',
            'received_date' => 'required|date',
            'ship_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'bukti' => 'nullable|image|max:2048',
            'items' => 'required|array',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga' => 'required|numeric',
            'items.*.diskon' => 'nullable|numeric',
            'items.*.tax' => 'nullable|numeric',
            'items.*.gudang_id' => 'required|exists:erm_gudang,id',
            'items.*.batch' => 'nullable|string',
            'items.*.expiration_date' => 'nullable|date',
            'subtotal' => 'nullable|numeric',
            'global_diskon' => 'nullable|numeric',
            'global_pajak' => 'nullable|numeric',
            'total' => 'nullable|numeric',
        ]);

        $buktiPath = null;
        if ($request->hasFile('bukti')) {
            $buktiPath = $request->file('bukti')->store('fakturbeli_bukti', 'public');
        }

        $faktur = FakturBeli::create([
            'pemasok_id' => $validated['pemasok_id'],
            'no_faktur' => $validated['no_faktur'],
            'received_date' => $validated['received_date'],
            'ship_date' => $validated['ship_date'],
            'notes' => $validated['notes'],
            'bukti' => $buktiPath,
            'subtotal' => $validated['subtotal'] ?? null,
            'global_diskon' => $validated['global_diskon'] ?? null,
            'global_pajak' => $validated['global_pajak'] ?? null,
            'total' => $validated['total'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $faktur->items()->create([
                'obat_id' => $item['obat_id'],
                'qty' => $item['qty'],
                'sisa' => $item['qty'],
                'harga' => $item['harga'],
                'diskon' => $item['diskon'] ?? 0,
                'tax' => $item['tax'] ?? 0,
                'gudang_id' => $item['gudang_id'],
                'batch' => $item['batch'] ?? null,
                'expiration_date' => $item['expiration_date'] ?? null,
            ]);
            // Update HPP and stok for Obat
            $obat = \App\Models\ERM\Obat::withInactive()->find($item['obat_id']);
            if ($obat) {
                $oldHpp = $obat->hpp ?? 0;
                $qty = $item['qty'] ?? 1;
                $tax = $item['tax'] ?? 0;
                $newHpp = $qty > 0 ? ($item['harga'] + ($tax / $qty)) : $item['harga'];
                if ($oldHpp > 0) {
                    $finalHpp = ($oldHpp + $newHpp) / 2;
                } else {
                    $finalHpp = $newHpp;
                }
                $obat->hpp = $finalHpp;
                // Add qty to stok
                $obat->stok = ($obat->stok ?? 0) + $qty;
                $obat->save();
            }
        }

        return response()->json(['success' => true, 'message' => 'Faktur berhasil disimpan']);
    }

        public function edit($id)
    {
        $faktur = FakturBeli::with('items')->findOrFail($id);
        $pemasoks = Pemasok::all();
        $gudangs = Gudang::all();
        $obats = Obat::all();
        return view('erm.fakturbeli.create', compact('faktur', 'pemasoks', 'gudangs', 'obats'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'pemasok_id' => 'required|exists:erm_pemasok,id',
            'no_faktur' => 'required|string',
            'received_date' => 'required|date',
            'ship_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'bukti' => 'nullable|image|max:2048',
            'items' => 'required|array',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga' => 'required|numeric',
            'items.*.diskon' => 'nullable|numeric',
            'items.*.tax' => 'nullable|numeric',
            'items.*.gudang_id' => 'required|exists:erm_gudang,id',
            'items.*.batch' => 'nullable|string',
            'items.*.expiration_date' => 'nullable|date',
            'subtotal' => 'nullable|numeric',
            'global_diskon' => 'nullable|numeric',
            'global_pajak' => 'nullable|numeric',
            'total' => 'nullable|numeric',
        ]);

        $faktur = FakturBeli::findOrFail($id);

        $buktiPath = $faktur->bukti;
        if ($request->hasFile('bukti')) {
            $buktiPath = $request->file('bukti')->store('fakturbeli_bukti', 'public');
        }

        $faktur->update([
            'pemasok_id' => $validated['pemasok_id'],
            'no_faktur' => $validated['no_faktur'],
            'received_date' => $validated['received_date'],
            'ship_date' => $validated['ship_date'],
            'notes' => $validated['notes'],
            'bukti' => $buktiPath,
            'subtotal' => $validated['subtotal'] ?? null,
            'global_diskon' => $validated['global_diskon'] ?? null,
            'global_pajak' => $validated['global_pajak'] ?? null,
            'total' => $validated['total'] ?? null,
        ]);

        // Remove old items and re-add
        $faktur->items()->delete();
        foreach ($validated['items'] as $item) {
            $faktur->items()->create([
                'obat_id' => $item['obat_id'],
                'qty' => $item['qty'],
                'sisa' => $item['qty'],
                'harga' => $item['harga'],
                'diskon' => $item['diskon'] ?? 0,
                'tax' => $item['tax'] ?? 0,
                'gudang_id' => $item['gudang_id'],
                'batch' => $item['batch'] ?? null,
                'expiration_date' => $item['expiration_date'] ?? null,
            ]);
            // Update HPP and stok for Obat
            $obat = \App\Models\ERM\Obat::withInactive()->find($item['obat_id']);
            if ($obat) {
                $oldHpp = $obat->hpp ?? 0;
                $qty = $item['qty'] ?? 1;
                $tax = $item['tax'] ?? 0;
                $newHpp = $qty > 0 ? ($item['harga'] + ($tax / $qty)) : $item['harga'];
                if ($oldHpp > 0) {
                    $finalHpp = ($oldHpp + $newHpp) / 2;
                } else {
                    $finalHpp = $newHpp;
                }
                $obat->hpp = $finalHpp;
                // Add qty to stok
                $obat->stok = ($obat->stok ?? 0) + $qty;
                $obat->save();
            }
        }

        return response()->json(['success' => true, 'message' => 'Faktur berhasil diupdate']);
    }

        public function destroy($id)
    {
        $faktur = FakturBeli::findOrFail($id);
        $faktur->items()->delete();
        $faktur->delete();
        return response()->json(['success' => true, 'message' => 'Faktur berhasil dihapus']);
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
}
