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
            $data = FakturBeli::with(['pemasok', 'items.obat'])->select('erm_fakturbeli.*');
            return DataTables::of($data)
                ->addColumn('pemasok', function($row) {
                    return $row->pemasok ? $row->pemasok->nama : '-';
                })
                ->addColumn('nama_obat', function($row) {
                    if (!$row->relationLoaded('items')) return '-';
                    $list = $row->items->map(function($item) {
                        return $item->obat ? $item->obat->nama : '';
                    })->filter()->toArray();
                    return implode(', ', $list) ?: '-';
                })
                ->addColumn('due_date', function($row) {
                    return $row->due_date ?? '-';
                })
                ->addColumn('total', function($row) {
                    return $row->total ?? 0;
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
            'no_faktur' => 'required|string|unique:erm_fakturbeli,no_faktur',
            'received_date' => 'required|date',
            'requested_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'ship_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'bukti' => 'nullable|image|max:2048',
            'items' => 'required|array',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga' => 'required|numeric',
            'items.*.diskon' => 'nullable|numeric',
            'items.*.diskon_type' => 'nullable|string',
            'items.*.tax' => 'nullable|numeric',
            'items.*.tax_type' => 'nullable|string',
            'items.*.gudang_id' => 'required|exists:erm_gudang,id',
            'items.*.batch' => 'nullable|string',
            'items.*.expiration_date' => 'nullable|date',
            'subtotal' => 'nullable|numeric',
            'global_diskon' => 'nullable|numeric',
            'global_diskon_type' => 'nullable|string',
            'global_pajak' => 'nullable|numeric',
            'global_pajak_type' => 'nullable|string',
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
            'requested_date' => $validated['requested_date'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'ship_date' => $validated['ship_date'],
            'notes' => $validated['notes'],
            'bukti' => $buktiPath,
            'subtotal' => $validated['subtotal'] ?? null,
            'global_diskon' => $validated['global_diskon'] ?? null,
            'global_pajak' => $validated['global_pajak'] ?? null,
            'total' => $validated['total'] ?? null,
        ]);

        // Calculate subtotal for all items (for global diskon/pajak percentage)
        $itemSubtotals = [];
        foreach ($validated['items'] as $item) {
            $qty = $item['qty'] ?? 0;
            $harga = $item['harga'] ?? 0;
            $diskon = $item['diskon'] ?? 0;
            $diskonType = $item['diskon_type'] ?? 'nominal';
            $tax = $item['tax'] ?? 0;
            $taxType = $item['tax_type'] ?? 'nominal';
            $base = $qty * $harga;
            $diskonValue = $diskonType === 'persen' ? ($base * $diskon / 100) : $diskon;
            $taxValue = $taxType === 'persen' ? ($base * $tax / 100) : $tax;
            $itemSubtotal = $base - $diskonValue + $taxValue;
            $itemSubtotals[] = $itemSubtotal;
        }
        $totalItemSubtotal = array_sum($itemSubtotals);
        $globalDiskon = $validated['global_diskon'] ?? 0;
        $globalDiskonType = $validated['global_diskon_type'] ?? 'nominal';
        $globalDiskonValue = $globalDiskonType === 'persen' ? ($totalItemSubtotal * $globalDiskon / 100) : $globalDiskon;
        $globalPajak = $validated['global_pajak'] ?? 0;
        $globalPajakType = $validated['global_pajak_type'] ?? 'nominal';
        $globalPajakValue = $globalPajakType === 'persen' ? ($totalItemSubtotal * $globalPajak / 100) : $globalPajak;

        foreach ($validated['items'] as $idx => $item) {
            $qty = $item['qty'] ?? 0;
            $harga = $item['harga'] ?? 0;
            $diskon = $item['diskon'] ?? 0;
            $diskonType = $item['diskon_type'] ?? 'nominal';
            $tax = $item['tax'] ?? 0;
            $taxType = $item['tax_type'] ?? 'nominal';
            $base = $qty * $harga;
            $diskonValue = $diskonType === 'persen' ? ($base * $diskon / 100) : $diskon;
            $taxValue = $taxType === 'persen' ? ($base * $tax / 100) : $tax;
            $itemSubtotal = $base - $diskonValue + $taxValue;
            // Distribute global pajak proportionally
            $prop = $totalItemSubtotal > 0 ? $itemSubtotal / $totalItemSubtotal : 0;
            $globalPajakItem = $globalPajakValue * $prop;
            // HPP calculation: (itemSubtotal + globalPajakItem) / qty
            $hpp = $qty > 0 ? ($itemSubtotal + $globalPajakItem) / $qty : 0;

            $faktur->items()->create([
                'obat_id' => $item['obat_id'],
                'qty' => $qty,
                'sisa' => $qty,
                'harga' => $harga,
                'diskon' => $diskon,
                'tax' => $tax,
                'gudang_id' => $item['gudang_id'],
                'batch' => $item['batch'] ?? null,
                'expiration_date' => $item['expiration_date'] ?? null,
                // Optionally store diskon_type/tax_type if you add columns
            ]);
            // Update HPP and stok for Obat
            $obat = \App\Models\ERM\Obat::withInactive()->find($item['obat_id']);
            if ($obat) {
                $oldHpp = $obat->hpp ?? 0;
                $finalHpp = $oldHpp > 0 ? ($oldHpp + $hpp) / 2 : $hpp;
                $obat->hpp = $finalHpp;
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
            'requested_date' => 'nullable|date',
            'due_date' => 'nullable|date',
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
            'requested_date' => $validated['requested_date'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
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
