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
use Illuminate\Support\Facades\DB;

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
                ->addColumn('approved_by_user_name', function($row) {
                    if (isset($row->approved_by) && $row->approved_by) {
                        $user = \App\Models\User::find($row->approved_by);
                        return $user ? $user->name : null;
                    }
                    return null;
                })
                ->addColumn('action', function($row) {
                    $actionBtn = '';
                    // Edit button with contextual label based on status
                    if ($row->status === 'diminta') {
                        $actionBtn .= '<a href="/erm/fakturpembelian/' . $row->id . '/edit" class="btn btn-sm btn-primary">Input Faktur</a> ';
                    } else {
                        $actionBtn .= '<a href="/erm/fakturpembelian/' . $row->id . '/edit" class="btn btn-sm btn-primary">Edit</a> ';
                    }
                    // Approve button - only show for diterima status (not diminta or diapprove yet)
                    if ($row->status === 'diterima') {
                        $actionBtn .= '<button class="btn btn-sm btn-success btn-approve-faktur" data-id="' . $row->id . '">Approve</button> ';
                        $actionBtn .= '<button class="btn btn-sm btn-info btn-debug-hpp" data-id="' . $row->id . '">Cek HPP</button> ';
                    }
                    // Delete button
                    $actionBtn .= '<button class="btn btn-sm btn-danger btn-delete-faktur" data-id="' . $row->id . '">Delete</button>';
                    return $actionBtn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('erm.fakturbeli.index');
    }

    /**
     * Cari faktur beli berdasarkan no_permintaan, return faktur id jika ditemukan
     */
    public function cariByNoPermintaan(Request $request)
    {
        $no = $request->input('no_permintaan');
        $faktur = \App\Models\ERM\FakturBeli::where('no_permintaan', $no)->first();
        if ($faktur) {
            return response()->json(['success' => true, 'faktur_id' => $faktur->id]);
        } else {
            return response()->json(['success' => false, 'message' => 'Faktur tidak ditemukan']);
        }
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
            'items.*.diminta' => 'nullable|integer|min:1',
            'items.*.harga' => 'required|numeric',
            'items.*.diskon' => 'nullable|numeric',
            'items.*.diskon_type' => 'nullable|string|in:nominal,persen',
            'items.*.tax' => 'nullable|numeric',
            'items.*.tax_type' => 'nullable|string|in:nominal,persen',
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
            'status' => 'diterima', // When creating a full faktur, mark as received
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
                'diminta' => $item['diminta'] ?? $qty, // Default to qty if diminta not provided
                'sisa' => $qty,
                'harga' => $harga,
                'diskon' => $diskon,
                'diskon_type' => $diskonType,
                'tax' => $tax,
                'tax_type' => $taxType,
                'gudang_id' => $item['gudang_id'],
                'batch' => $item['batch'] ?? null,
                'expiration_date' => $item['expiration_date'] ?? null,
            ]);
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
            'items.*.diminta' => 'nullable|integer|min:1',
            'items.*.harga' => 'required|numeric',
            'items.*.diskon' => 'nullable|numeric',
            'items.*.diskon_type' => 'nullable|string|in:nominal,persen',
            'items.*.tax' => 'nullable|numeric',
            'items.*.tax_type' => 'nullable|string|in:nominal,persen',
            'items.*.gudang_id' => 'required|exists:erm_gudang,id',
            'items.*.batch' => 'nullable|string',
            'items.*.expiration_date' => 'nullable|date',
            'subtotal' => 'nullable|numeric',
            'global_diskon' => 'nullable|numeric',
            'global_pajak' => 'nullable|numeric',
            'total' => 'nullable|numeric',
        ]);        $faktur = FakturBeli::findOrFail($id);

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
            'status' => 'diterima', // Update status when editing
        ]);

        // Remove old items and re-add
        $faktur->items()->delete();
        foreach ($validated['items'] as $item) {
            $qty = $item['qty'] ?? 0;
            $diminta = $item['diminta'] ?? $qty;
            $diskonType = $item['diskon_type'] ?? 'nominal';
            $taxType = $item['tax_type'] ?? 'nominal';
            
            $faktur->items()->create([
                'obat_id' => $item['obat_id'],
                'qty' => $qty,
                'diminta' => $diminta,
                'sisa' => $qty,
                'harga' => $item['harga'],
                'diskon' => $item['diskon'] ?? 0,
                'diskon_type' => $diskonType,
                'tax' => $item['tax'] ?? 0,
                'tax_type' => $taxType,
                'gudang_id' => $item['gudang_id'],
                'batch' => $item['batch'] ?? null,
                'expiration_date' => $item['expiration_date'] ?? null,
            ]);
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
    
    /**
     * Debug HPP calculation without actually updating anything
     */
    public function debugHpp($id)
    {
        $faktur = FakturBeli::with('items.obat')->findOrFail($id);
        
        if ($faktur->status !== 'diterima') {
            return response()->json([
                'success' => false,
                'message' => 'Faktur tidak dalam status diterima'
            ]);
        }
        
        // Calculate subtotal for proper global tax distribution
        $invoiceSubtotal = 0;
        foreach ($faktur->items as $invoiceItem) {
            $invoiceSubtotal += ($invoiceItem->harga ?? 0) * ($invoiceItem->qty ?? 0);
        }
        
        $debugInfo = [];
        
        foreach ($faktur->items as $item) {
            if ($item->obat) {
                $qty = $item->qty ?? 0;
                $harga = $item->harga ?? 0;
                $itemTax = $item->tax ?? 0;
                $diskon = $item->diskon ?? 0;
                // Calculate proportional global tax for this item
                $itemValue = $harga * $qty;
                $globalTaxPortion = $invoiceSubtotal > 0 ? 
                    (($itemValue / $invoiceSubtotal) * ($faktur->global_pajak ?? 0)) : 0;
                // Calculate total purchase cost for this item (harga already includes per-item tax, add global tax portion)
                $purchaseCost = $itemValue + $globalTaxPortion;
                $obat = $item->obat;
                $oldHpp = $obat->hpp ?? 0;
                $oldStok = $obat->stok ?? 0;
                $newStok = $oldStok + $qty;
                // Moving Average Method
                $newHpp = ($oldHpp * $oldStok + $purchaseCost) / ($oldStok + $qty > 0 ? $oldStok + $qty : 1);
                $debugInfo[] = [
                    'obat_id' => $item->obat_id,
                    'obat_nama' => $obat->nama ?? 'Unknown',
                    'qty' => $qty,
                    'harga' => $harga,
                    'itemValue' => $itemValue,
                    'itemTax' => $itemTax,
                    'globalTaxPortion' => $globalTaxPortion,
                    'oldHpp' => $oldHpp,
                    'oldStok' => $oldStok,
                    'purchaseCost' => $purchaseCost,
                    'newStok' => $newStok,
                    'newHpp' => $newHpp,
                    'hasTax' => ($itemTax > 0 || $globalTaxPortion > 0),
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'faktur' => [
                'id' => $faktur->id,
                'no_faktur' => $faktur->no_faktur,
                'subtotal' => $faktur->subtotal,
                'global_diskon' => $faktur->global_diskon,
                'global_pajak' => $faktur->global_pajak,
                'total' => $faktur->total,
                'invoiceSubtotalCalculated' => $invoiceSubtotal,
            ],
            'items' => $debugInfo
        ]);
    }

    /**
     * Approve faktur and update stock
     */
    public function approveFaktur($id)
    {
        $faktur = FakturBeli::with('items.obat')->findOrFail($id);
        
        if ($faktur->status !== 'diterima') {
            return response()->json([
                'success' => false,
                'message' => 'Faktur tidak dalam status diterima'
            ]);
        }
        
        // Begin transaction
        DB::beginTransaction();
        try {
            // Update faktur status and approved_by
            $faktur->update([
                'status' => 'diapprove',
                'approved_by' => \Illuminate\Support\Facades\Auth::id()
            ]);

            // Calculate subtotal for proper global tax distribution
            $invoiceSubtotal = 0;
            foreach ($faktur->items as $invoiceItem) {
                $invoiceSubtotal += ($invoiceItem->harga ?? 0) * ($invoiceItem->qty ?? 0);
            }

            // Update stock for each obat
            foreach ($faktur->items as $item) {
                if ($item->obat) {
                    // Moving Average HPP Calculation (match debug logic)
                    $qty = $item->qty ?? 0;
                    $harga = $item->harga ?? 0;
                    $itemTax = $item->tax ?? 0;
                    $diskon = $item->diskon ?? 0;
                    // Calculate proportional global tax for this item
                    $itemValue = $harga * $qty;
                    $globalTaxPortion = $invoiceSubtotal > 0 ? 
                        (($itemValue / $invoiceSubtotal) * ($faktur->global_pajak ?? 0)) : 0;
                    // Purchase cost = harga*qty + global pajak portion
                    $purchaseCost = $itemValue + $globalTaxPortion;
                    $obat = $item->obat;
                    $oldHpp = $obat->hpp ?? 0;
                    $oldStok = $obat->stok ?? 0;
                    $newStok = $oldStok + $qty;
                    // Moving Average Method
                    $newHpp = ($oldHpp * $oldStok + $purchaseCost) / (($oldStok + $qty) > 0 ? ($oldStok + $qty) : 1);
                    // Update the obat record
                    $obat->update([
                        'stok' => $newStok,
                        'hpp' => $newHpp
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Faktur berhasil diapprove dan stok obat diperbarui'
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Gagal approve faktur: ' . $e->getMessage()
            ]);
        }
    }
    
    // New methods for purchase request functionality
    public function createPermintaan()
    {
        $pemasoks = Pemasok::all();
        return view('erm.fakturbeli.permintaan', compact('pemasoks'));
    }

    public function storePermintaan(Request $request)
    {
        $validated = $request->validate([
            'pemasok_id' => 'required|exists:erm_pemasok,id',
            'requested_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.diminta' => 'required|integer|min:1',
        ]);

        $faktur = FakturBeli::create([
            'pemasok_id' => $validated['pemasok_id'],
            'requested_date' => $validated['requested_date'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'diminta',
        ]);

        foreach ($validated['items'] as $item) {
            $faktur->items()->create([
                'obat_id' => $item['obat_id'],
                'diminta' => $item['diminta'],
                'qty' => 0, // Initially, no items received yet
                'sisa' => 0,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Permintaan pembelian berhasil disimpan']);
    }

    public function editPermintaan($id)
    {
        $faktur = FakturBeli::with('items')->findOrFail($id);
        $pemasoks = Pemasok::all();
        return view('erm.fakturbeli.permintaan', compact('faktur', 'pemasoks'));
    }

    public function updatePermintaan(Request $request, $id)
    {
        $validated = $request->validate([
            'pemasok_id' => 'required|exists:erm_pemasok,id',
            'requested_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.diminta' => 'required|integer|min:1',
        ]);

        $faktur = FakturBeli::findOrFail($id);

        $faktur->update([
            'pemasok_id' => $validated['pemasok_id'],
            'requested_date' => $validated['requested_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Remove old items and re-add
        $faktur->items()->delete();
        
        foreach ($validated['items'] as $item) {
            $faktur->items()->create([
                'obat_id' => $item['obat_id'],
                'diminta' => $item['diminta'],
                'qty' => 0, // Initially, no items received yet
                'sisa' => 0,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Permintaan pembelian berhasil diupdate']);
    }

    public function completeFaktur($id)
    {
        $faktur = FakturBeli::with(['items.obat', 'pemasok'])->findOrFail($id);
        if ($faktur->status !== 'diminta') {
            return redirect()->route('erm.fakturbeli.index')->with('error', 'Faktur ini tidak dalam status permintaan');
        }
        
        $pemasoks = Pemasok::all();
        $gudangs = Gudang::all();
        
        // Pass the faktur to the create view with a flag indicating it's for completion
        return view('erm.fakturbeli.create', compact('faktur', 'pemasoks', 'gudangs'));
    }
}
