<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\FakturBeli;
use App\Models\ERM\FakturBeliItem;
use App\Models\ERM\Pemasok;
use App\Models\ERM\Gudang;
use App\Models\ERM\Obat;
use App\Services\ERM\StokService;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class FakturBeliController extends Controller
{
    protected $stokService;

    public function __construct(StokService $stokService)
    {
        $this->stokService = $stokService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = FakturBeli::with(['pemasok', 'items.obat'])->select('erm_fakturbeli.*');
            // Filter by received_date range if provided
            if ($request->filled('tanggal_terima_range')) {
                $range = explode(' - ', $request->input('tanggal_terima_range'));
                if (count($range) === 2) {
                    $start = $range[0];
                    $end = $range[1];
                    $data = $data->whereDate('received_date', '>=', $start)
                                 ->whereDate('received_date', '<=', $end);
                }
            }
            // Filter by status if provided
            if ($request->filled('status')) {
                $data = $data->where('status', $request->input('status'));
            }
            // Hide diretur if requested
            if ($request->input('hide_diretur') == 1) {
                $data = $data->where('status', '!=', 'diretur');
            }
            // Filter by nama obat
            if ($request->filled('search_nama_obat')) {
                $searchNamaObat = $request->input('search_nama_obat');
                $data = $data->whereHas('items.obat', function($q) use ($searchNamaObat) {
                    $q->where('nama', 'like', "%$searchNamaObat%");
                });
            }
            // Filter by no_faktur
            if ($request->filled('search_no_faktur')) {
                $searchNo = $request->input('search_no_faktur');
                $data = $data->where('no_faktur', 'like', "%$searchNo%");
            }
            // Filter by pemasok
            if ($request->filled('search_pemasok')) {
                $searchPemasok = $request->input('search_pemasok');
                $data = $data->whereHas('pemasok', function($q) use ($searchPemasok) {
                    $q->where('nama', 'like', "%$searchPemasok%");
                });
            }
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
                    if ($row->status === 'diapprove') {
                        return '<a href="/erm/fakturpembelian/' . $row->id . '/print" target="_blank" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</a>';
                    }
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
            'bukti' => 'nullable|image|max:10240',
            'items' => 'required|array',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.diminta' => 'nullable|integer|min:0',
            'items.*.harga' => 'required|numeric',
            'items.*.diskon' => 'nullable|numeric',
            'items.*.diskon_type' => 'nullable|string|in:nominal,percent',
            'items.*.tax' => 'nullable|numeric',
            'items.*.tax_type' => 'nullable|string|in:nominal,percent',
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
            $diskonValue = $diskonType === 'percent' ? ($base * $diskon / 100) : $diskon;
            $taxValue = $taxType === 'percent' ? ($base * $tax / 100) : $tax;
            $itemSubtotal = $base - $diskonValue + $taxValue;
            $itemSubtotals[] = $itemSubtotal;
        }
        $totalItemSubtotal = array_sum($itemSubtotals);
        $globalDiskon = $validated['global_diskon'] ?? 0;
        $globalDiskonType = $validated['global_diskon_type'] ?? 'nominal';
        $globalDiskonValue = $globalDiskonType === 'percent' ? ($totalItemSubtotal * $globalDiskon / 100) : $globalDiskon;
        $globalPajak = $validated['global_pajak'] ?? 0;
        $globalPajakType = $validated['global_pajak_type'] ?? 'nominal';
        $globalPajakValue = $globalPajakType === 'percent' ? ($totalItemSubtotal * $globalPajak / 100) : $globalPajak;

        foreach ($validated['items'] as $idx => $item) {
            $qty = $item['qty'] ?? 0;
            $harga = $item['harga'] ?? 0;
            $diskon = $item['diskon'] ?? 0;
            $diskonType = $item['diskon_type'] ?? 'nominal';
            $tax = $item['tax'] ?? 0;
            $taxType = $item['tax_type'] ?? 'nominal';
            $base = $qty * $harga;
            $diskonValue = $diskonType === 'percent' ? ($base * $diskon / 100) : $diskon;
            $taxValue = $taxType === 'percent' ? ($base * $tax / 100) : $tax;
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
                'total_amount' => $itemSubtotal,
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
            'bukti' => 'nullable|image|max:10240',
            'items' => 'required|array',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.qty' => 'required|integer|min:0',
            'items.*.diminta' => 'nullable|integer|min:0',
            'items.*.harga' => 'required|numeric',
            'items.*.diskon' => 'nullable|numeric',
            'items.*.diskon_type' => 'nullable|string|in:nominal,percent',
            'items.*.tax' => 'nullable|numeric',
            'items.*.tax_type' => 'nullable|string|in:nominal,percent',
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

        DB::beginTransaction();
        try {
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
            $harga = $item['harga'] ?? 0;
            $diskon = $item['diskon'] ?? 0;
            $diskonType = $item['diskon_type'] ?? 'nominal';
            $tax = $item['tax'] ?? 0;
            $taxType = $item['tax_type'] ?? 'nominal';
            $base = $qty * $harga;
            $diskonValue = $diskonType === 'percent' ? ($base * $diskon / 100) : $diskon;
            $taxValue = $taxType === 'percent' ? ($base * $tax / 100) : $tax;
            $itemSubtotal = $base - $diskonValue + $taxValue;

            $faktur->items()->create([
                'obat_id' => $item['obat_id'],
                'qty' => $qty,
                'diminta' => $item['diminta'] ?? $qty,
                'sisa' => $qty,
                'harga' => $harga,
                'diskon' => $diskon,
                'diskon_type' => $diskonType,
                'tax' => $tax,
                'tax_type' => $taxType,
                'gudang_id' => $item['gudang_id'],
                'batch' => $item['batch'] ?? null,
                'expiration_date' => $item['expiration_date'] ?? null,
                'total_amount' => $itemSubtotal,
            ]);
        }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Faktur berhasil diupdate']);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log error for debugging
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan faktur', 'error' => $e->getMessage()]);
        }
    // ...existing code...
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
                $diskon = $item->diskon ?? 0;
                $diskonType = $item->diskon_type ?? 'nominal';
                $itemTax = $item->tax ?? 0;
                $taxType = $item->tax_type ?? 'nominal';
                $base = $qty * $harga;
                $diskonValue = $diskonType === 'percent' ? ($base * $diskon / 100) : $diskon;
                $taxValue = $taxType === 'percent' ? ($base * $itemTax / 100) : $itemTax;
                $itemSubtotal = $base - $diskonValue + $taxValue;
                // Jika hanya satu item, global pajak langsung diberikan penuh
                if (count($faktur->items) === 1) {
                    $globalTaxPortion = $faktur->global_pajak ?? 0;
                } else {
                    $prop = $invoiceSubtotal > 0 ? $itemSubtotal / $invoiceSubtotal : 0;
                    $globalTaxPortion = $invoiceSubtotal > 0 ? ($itemSubtotal / $invoiceSubtotal) * ($faktur->global_pajak ?? 0) : 0;
                }
                
                // Calculate HPP (include diskon) dan HPP Jual (exclude diskon)
                $purchaseCost = $itemSubtotal + $globalTaxPortion; // HPP (dengan diskon)
                $purchaseCostJual = $base + $taxValue + $globalTaxPortion; // HPP Jual (tanpa diskon)
                
                $obat = $item->obat;
                $oldHpp = $obat->hpp ?? 0;
                $oldHppJual = $obat->hpp_jual ?? 0;
                $oldStok = $obat->total_stok ?? 0; // Use total_stok attribute

                // Per-unit costs used by the system when updating master HPP
                $hppPerUnit = $qty > 0 ? ($purchaseCost / $qty) : 0; // include diskon
                $hppJualPerUnit = $qty > 0 ? ($purchaseCostJual / $qty) : 0; // exclude diskon

                // New behavior: master HPP and HPP_JUAL are set directly to the discount-excluded per-unit price
                // (i.e., no averaging). Represent that as 'direct' values for debug display.
                $newHpp_direct = $hppJualPerUnit; // price excluding discount per unit
                $newHppJual_direct = $hppJualPerUnit; // same as above

                // Simple average (legacy/system behavior) for reference
                $newHpp_simple = ($oldHpp + $hppPerUnit) / 2;
                $newHppJual_simple = ($oldHppJual + $hppJualPerUnit) / 2;

                // Also compute weighted average for reference (previous debug behavior)
                $newStok = $oldStok + $qty;
                $newHpp_weighted = $newStok > 0 ? (($oldHpp * $oldStok) + $purchaseCost) / $newStok : 0;
                $newHppJual_weighted = $newStok > 0 ? (($oldHppJual * $oldStok) + $purchaseCostJual) / $newStok : 0;

                $debugInfo[] = [
                    'obat_id' => $item->obat_id,
                    'obat_nama' => $obat->nama ?? 'Unknown',
                    'qty' => $qty,
                    'harga' => $harga,
                    'diskon_value' => $diskonValue,
                    'tax_value' => $taxValue,
                    'itemSubtotal' => $itemSubtotal, // Sudah include diskon dan pajak item
                    'globalTaxPortion' => $globalTaxPortion,
                    'purchaseCost' => $purchaseCost, // HPP dengan diskon
                    'purchaseCostJual' => $purchaseCostJual, // HPP tanpa diskon  
                    'oldHpp' => $oldHpp,
                    'oldHppJual' => $oldHppJual,
                    'oldStok' => $oldStok,
                    'newStok' => $newStok,
                    // Show new direct-set behavior as primary (no averaging)
                    'newHpp' => $newHpp_direct,
                    'newHppJual' => $newHppJual_direct,
                    // Also include weighted results for comparison
                    'newHpp_weighted' => $newHpp_weighted,
                    'newHppJual_weighted' => $newHppJual_weighted,
                    'hppPerUnit' => $qty > 0 ? $purchaseCost / $qty : 0,
                    'hppJualPerUnit' => $qty > 0 ? $purchaseCostJual / $qty : 0,
                    // Deltas for direct, simple and weighted methods
                    'selisihHpp_direct' => $newHpp_direct - $oldHpp,
                    'selisihHppJual_direct' => $newHppJual_direct - $oldHppJual,
                    'selisihHpp_simple' => $newHpp_simple - $oldHpp,
                    'selisihHppJual_simple' => $newHppJual_simple - $oldHppJual,
                    'selisihHpp_weighted' => $newHpp_weighted - $oldHpp,
                    'selisihHppJual_weighted' => $newHppJual_weighted - $oldHppJual,
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
        $faktur = FakturBeli::with(['items.obat', 'pemasok'])->findOrFail($id);
        if ($faktur->status !== 'diterima') {
            return response()->json([
                'success' => false,
                'message' => 'Faktur harus berstatus diterima untuk bisa diapprove'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Pisahkan item yang diterima dan yang tidak
            $itemsDiterima = [];
            $itemsBelumDikirim = [];
            foreach ($faktur->items as $item) {
                if (($item->qty ?? 0) > 0) {
                    $itemsDiterima[] = $item;
                } else {
                    $itemsBelumDikirim[] = $item;
                }
            }

            // Proses item yang diterima (qty > 0)
            $invoiceSubtotal = 0;
            foreach ($itemsDiterima as $item) {
                $qty = $item->qty ?? 0;
                $harga = $item->harga ?? 0;
                $diskon = $item->diskon ?? 0;
                $diskonType = $item->diskon_type ?? 'nominal';
                $tax = $item->tax ?? 0;
                $taxType = $item->tax_type ?? 'nominal';
                $base = $qty * $harga;
                $diskonValue = $diskonType === 'percent' ? ($base * $diskon / 100) : $diskon;
                $taxValue = $taxType === 'percent' ? ($base * $tax / 100) : $tax;
                $itemSubtotal = $base - $diskonValue + $taxValue;
                $invoiceSubtotal += $itemSubtotal;
            }

            foreach ($itemsDiterima as $item) {
                $qty = $item->qty ?? 0;
                $harga = $item->harga ?? 0;
                $diskon = $item->diskon ?? 0;
                $diskonType = $item->diskon_type ?? 'nominal';
                $itemTax = $item->tax ?? 0;
                $taxType = $item->tax_type ?? 'nominal';
                $base = $qty * $harga;
                $diskonValue = $diskonType === 'percent' ? ($base * $diskon / 100) : $diskon;
                $taxValue = $taxType === 'percent' ? ($base * $itemTax / 100) : $itemTax;
                $itemSubtotal = $base - $diskonValue + $taxValue;
                $globalPajakValue = $faktur->global_pajak ?? 0;
                $prop = $invoiceSubtotal > 0 ? $itemSubtotal / $invoiceSubtotal : 0;
                $globalPajakItem = $globalPajakValue * $prop;
                $hppPerUnit = $qty > 0 ? ($itemSubtotal + $globalPajakItem) / $qty : 0;
                $hppJualPerUnit = $qty > 0 ? ($base + $taxValue + $globalPajakItem) / $qty : 0;

                // Update stock using StokService
                $this->stokService->masukViaFaktur(
                    $item->obat_id,
                    $item->gudang_id,
                    $qty,
                    $faktur->id,
                    $faktur->no_faktur,
                    $item->batch,
                    $item->expiration_date,
                    $hppPerUnit, // hargaBeli (include diskon/tax)
                    $hppJualPerUnit, // hargaBeliJual (exclude diskon/tax)
                    $faktur->pemasok->nama ?? null
                );
            }

            // Update status faktur
            $faktur->update([
                'status' => 'diapprove',
                'approved_by' => Auth::id()
            ]);

            // Jika ada item yang belum dikirim (qty == 0), buat faktur baru dengan status 'diminta'
            if (count($itemsBelumDikirim) > 0) {
                // Generate no_faktur baru (bisa disesuaikan dengan format yang diinginkan)
                $newNoFaktur = $faktur->no_faktur . '-NEXT-' . date('YmdHis');
                $newFaktur = FakturBeli::create([
                    'pemasok_id' => $faktur->pemasok_id,
                    'no_faktur' => $newNoFaktur,
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
                ]);
                foreach ($itemsBelumDikirim as $item) {
                    $newFaktur->items()->create([
                        'obat_id' => $item->obat_id,
                        'qty' => 0,
                        'diminta' => $item->diminta,
                        'sisa' => 0,
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
                'message' => 'Faktur berhasil diapprove dan stok telah diupdate. Faktur baru dibuat untuk item yang belum dikirim.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }    // New methods for purchase request functionality
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
        $query = FakturBeliItem::with(['fakturbeli.pemasok', 'obat']);
        // Only include items from faktur with status 'diapprove'
        if ($request->filled('tanggal_terima_range')) {
            // If date range provided, apply both status and date filters
            $range = explode(' - ', $request->input('tanggal_terima_range'));
            if (count($range) === 2) {
                $start = $range[0];
                $end = $range[1];
                $query->whereHas('fakturbeli', function($q) use ($start, $end) {
                    $q->where('status', 'diapprove')
                      ->whereDate('received_date', '>=', $start)
                      ->whereDate('received_date', '<=', $end);
                });
            } else {
                // Fallback: just filter by status
                $query->whereHas('fakturbeli', function($q) {
                    $q->where('status', 'diapprove');
                });
            }
        } else {
            // No date range — filter by status only
            $query->whereHas('fakturbeli', function($q) {
                $q->where('status', 'diapprove');
            });
        }

        $items = $query->get();
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
                    'harga_per_satuan' => $item->harga ?? 0,
                    'diskon' => isset($item->diskon) ? $item->diskon : '',
                    'diskon_type' => isset($item->diskon_type) ? $item->diskon_type : '',
                    'pajak' => isset($item->tax) ? $item->tax : '',
                    'pajak_type' => isset($item->tax_type) ? $item->tax_type : '',
                    'global_diskon' => $faktur->global_diskon ?? '',
                    'global_diskon_type' => $faktur->global_diskon_type ?? '',
                    'global_pajak' => $globalPajakValue,
                    'global_pajak_type' => $globalPajakType,
                    // Add 11% to the total harga as requested
                    'total_harga' => (float)($item->total_amount ?? 0) * 1.11,
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
                $r['harga_per_satuan'],
                ($r['diskon'] === '' ? '' : ($r['diskon'] . ($r['diskon_type'] ? (' ' . $r['diskon_type']) : ''))),
                ($r['pajak'] === '' ? '' : ($r['pajak'] . ($r['pajak_type'] ? (' ' . $r['pajak_type']) : ''))),
                ($r['global_diskon'] === '' ? '' : ($r['global_diskon'] . ($r['global_diskon_type'] ? (' ' . $r['global_diskon_type']) : ''))),
                ($r['global_pajak'] === '' ? '' : ($r['global_pajak'] . ($r['global_pajak_type'] ? (' ' . $r['global_pajak_type']) : ''))),
                $r['total_harga'],
            ];
        }, $rows);

        $headings = ['No Faktur', 'Tanggal Jatuh Tempo', 'Tanggal Permintaan', 'Tanggal Terima', 'Nama Obat', 'Qty', 'Satuan', 'Nama Vendor', 'Harga Per Satuan', 'Diskon', 'Pajak', 'Global Diskon', 'Global Pajak', 'Total Harga'];

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

    /**
     * Return JSON detail for a Faktur Beli (used by other modules to show faktur snapshot)
     */
    public function showJson($id)
    {
        $faktur = FakturBeli::with(['pemasok', 'items.obat'])->findOrFail($id);
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
            'items' => $faktur->items->map(function($it){
                return [
                    'id' => $it->id,
                    'obat_id' => $it->obat_id,
                    'obat_nama' => $it->obat ? $it->obat->nama : null,
                    'qty' => $it->qty,
                    'harga' => $it->harga,
                    'total_amount' => $it->total_amount,
                ];
            })
        ]);
    }
}
