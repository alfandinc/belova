<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\StokOpname;
use App\Models\ERM\StokOpnameItem;
use App\Models\ERM\Gudang;
use App\Models\ERM\Obat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StokOpnameTemplateExport;
use App\Imports\StokOpnameImport;

class StokOpnameController extends Controller
{
    /**
     * Get latest total nilai stok sistem and stok fisik for AJAX sync
     */
    public function getStokTotals($id)
    {
        $items = StokOpnameItem::with('obat')
            ->where('stok_opname_id', $id)
            ->get();
        $totalStokSistem = 0;
        $totalStokFisik = 0;
        foreach ($items as $item) {
            $hppJual = $item->obat ? ($item->obat->hpp_jual ?? 0) : 0;
            $totalStokSistem += $hppJual * ($item->stok_sistem ?? 0);
            $totalStokFisik += $hppJual * ($item->stok_fisik ?? 0);
        }
        return response()->json([
            'totalStokSistem' => $totalStokSistem,
            'totalStokFisik' => $totalStokFisik,
        ]);
    }
    /**
     * Inline update stok fisik and recalculate selisih
     */
    public function updateStokFisik(Request $request, $itemId)
    {
        $request->validate([
            'stok_fisik' => 'required|numeric',
        ]);
        $item = StokOpnameItem::findOrFail($itemId);
        $item->stok_fisik = $request->stok_fisik;
        $item->selisih = $item->stok_fisik - $item->stok_sistem;
        $item->save();
        return response()->json([
            'success' => true,
            'stok_fisik' => $item->stok_fisik,
            'selisih' => $item->selisih,
        ]);
    }

    /**
     * Submit temuan - add found stock to both stok fisik and stok gudang
     */
    public function submitTemuan(Request $request, $itemId)
    {
        $request->validate([
            'temuan' => 'required|numeric|min:0',
            'catatan' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();
            
            $item = StokOpnameItem::with(['stokOpname', 'obat', 'obatStokGudang'])->findOrFail($itemId);
            $temuan = (float) $request->temuan;
            $catatan = $request->catatan;
            
            if ($temuan <= 0) {
                return response()->json(['success' => false, 'message' => 'Jumlah temuan harus lebih dari 0'], 400);
            }
            
            // Update stok fisik in opname item
            $item->stok_fisik += $temuan;
            $item->selisih = $item->stok_fisik - $item->stok_sistem;
            
            // Also update notes if provided
            if ($catatan) {
                $item->notes = $catatan;
            }
            
            $item->save();
            
            // Prepare keterangan for kartu stok
            $keterangan = "Temuan stok saat opname";
            if ($catatan) {
                $keterangan = $catatan;
            } else {
                $keterangan .= " - batch: {$item->batch_name}";
            }
            
            // Add to actual stock using StokService
            $stokService = app(\App\Services\ERM\StokService::class);
            $stokService->tambahStok(
                $item->obat_id,
                $item->stokOpname->gudang_id,
                $temuan,
                $item->batch_name, // batch
                $item->expiration_date, // expDate
                null, // rak
                null, // lokasi
                null, // hargaBeli - don't change HPP
                null, // hargaBeliJual - don't change HPP
                'temuan_stok_opname', // refType
                $item->stok_opname_id, // refId
                $keterangan // use catatan as keterangan
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Temuan {$temuan} berhasil ditambahkan",
                'stok_fisik' => $item->stok_fisik,
                'selisih' => $item->selisih,
                'temuan' => $temuan,
                'notes' => $item->notes
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false, 
                'message' => 'Gagal menyimpan temuan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get temuan history for a specific stok opname item
     */
    public function getTemuanHistory($itemId)
    {
        try {
            $item = StokOpnameItem::with(['obat', 'stokOpname.gudang'])->findOrFail($itemId);
            
            // Get temuan records from kartu stok
            $temuanHistory = \App\Models\ERM\KartuStok::where('obat_id', $item->obat_id)
                ->where('gudang_id', $item->stokOpname->gudang_id)
                ->where('ref_type', 'temuan_stok_opname')
                ->where('ref_id', $item->stok_opname_id)
                ->where('batch', $item->batch_name)
                ->orderBy('tanggal', 'desc')
                ->get();
            
            $itemInfo = [
                'obat_nama' => $item->obat ? $item->obat->nama : 'Unknown',
                'batch' => $item->batch_name,
                'gudang' => $item->stokOpname->gudang ? $item->stokOpname->gudang->nama : 'Unknown'
            ];
            
            return response()->json([
                'success' => true,
                'item' => $itemInfo,
                'history' => $temuanHistory->map(function($record) {
                    // Handle tanggal field safely - it might be string or Carbon instance
                    $tanggal = $record->tanggal;
                    if (is_string($tanggal)) {
                        try {
                            $tanggal = \Carbon\Carbon::parse($tanggal)->format('d/m/Y H:i');
                        } catch (\Exception $e) {
                            $tanggal = $record->tanggal; // fallback to original string
                        }
                    } else {
                        $tanggal = $record->tanggal->format('d/m/Y H:i');
                    }
                    
                    return [
                        'tanggal' => $tanggal,
                        'qty' => $record->qty,
                        'keterangan' => $record->keterangan,
                        'stok_setelah' => $record->stok_setelah
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Gagal mengambil data history temuan: ' . $e->getMessage()
            ], 500);
        }
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = StokOpname::with('gudang', 'user')->latest();
            return datatables()->of($data)
                ->addColumn('selisih_count', function($row) {
                    return $row->items()->whereRaw('ABS(selisih) > 0')->count();
                })
                ->addColumn('aksi', function($row) {
                    return '<a href="'.route('erm.stokopname.create', $row->id).'" class="btn btn-primary btn-sm">Lakukan Stok Opname</a>';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }
        $gudangs = Gudang::all();
        return view('erm.stokopname.index', compact('gudangs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal_opname' => 'required|date',
            'gudang_id' => 'required|exists:erm_gudang,id',
            'periode_bulan' => 'required|integer',
            'periode_tahun' => 'required|integer',
        ]);
        $stokOpname = StokOpname::create([
            'tanggal_opname' => $request->tanggal_opname,
            'gudang_id' => $request->gudang_id,
            'periode_bulan' => $request->periode_bulan,
            'periode_tahun' => $request->periode_tahun,
            'notes' => $request->notes,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);
        return response()->json(['success' => true, 'id' => $stokOpname->id]);
    }

    public function create($id)
    {
        $stokOpname = StokOpname::with('gudang')->findOrFail($id);
        $items = StokOpnameItem::with('obat')
            ->where('stok_opname_id', $stokOpname->id)
            ->get();

        // Calculate total nilai stok sistem and stok fisik (based on hpp_jual)
        $totalStokSistem = 0;
        $totalStokFisik = 0;
        foreach ($items as $item) {
            $hppJual = $item->obat ? ($item->obat->hpp_jual ?? 0) : 0;
            $totalStokSistem += $hppJual * ($item->stok_sistem ?? 0);
            $totalStokFisik += $hppJual * ($item->stok_fisik ?? 0);
        }

        return view('erm.stokopname.create', compact('stokOpname', 'items', 'totalStokSistem', 'totalStokFisik'));
    }

    public function downloadExcel($id)
    {
        $stokOpname = StokOpname::findOrFail($id);

        // Only include obat that have stock entries for the selected gudang
        $gudangId = $stokOpname->gudang_id;

        $obats = Obat::whereHas('stokGudang', function($q) use ($gudangId) {
            $q->where('gudang_id', $gudangId);
        })
        // eager load the stokGudang rows only for this gudang to reduce queries in export
        ->with(['stokGudang' => function($q) use ($gudangId) {
            $q->where('gudang_id', $gudangId);
        }])
        ->get();

        return Excel::download(new StokOpnameTemplateExport($obats, $gudangId), 'stok_opname_template.xlsx');
    }

    public function uploadExcel(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
        $stokOpname = StokOpname::findOrFail($id);
        DB::beginTransaction();
        try {
            $import = new StokOpnameImport($stokOpname->id);
            Excel::import($import, $request->file('file'));
            DB::commit();
            if ($import->imported > 0) {
                return back()->with('success', 'Data stok opname berhasil diupload: ' . $import->imported . ' baris.');
            } else {
                $msg = 'Tidak ada data yang diimport. Pastikan header kolom: obat_id, stok_sistem, stok_fisik. Baris dilewati: ' . json_encode($import->skippedRows);
                return back()->with('error', $msg);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal upload: ' . $e->getMessage());
        }
    }

        public function itemsData(Request $request, $id)
    {
        $items = StokOpnameItem::query()
            ->leftJoin('erm_obat', 'erm_stok_opname_items.obat_id', '=', 'erm_obat.id')
            ->select(
                'erm_stok_opname_items.*', 
                'erm_obat.nama as nama_obat',
                'erm_obat.hpp_jual as hpp_jual'
            )
            ->where('stok_opname_id', $id)
            ->orderByRaw('ABS(selisih) DESC');
        
        return datatables()->of($items)
            ->filterColumn('nama_obat', function($query, $keyword) {
                $query->where('erm_obat.nama', 'like', "%$keyword%");
            })
            ->make(true);
    }

        public function updateItemNotes(Request $request, $itemId)
    {
        $request->validate([
            'notes' => 'nullable|string|max:255',
        ]);
        $item = StokOpnameItem::findOrFail($itemId);
        $item->notes = $request->notes;
        $item->save();
        return response()->json(['success' => true, 'notes' => $item->notes]);
    }

        public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,proses,selesai',
        ]);
        $stokOpname = StokOpname::findOrFail($id);
        $stokOpname->status = $request->status;
        $stokOpname->save();
        return response()->json(['success' => true, 'status' => $stokOpname->status]);
    }
    /**
     * @deprecated Use updateStokFromOpname instead for proper audit trail
     * Legacy method - directly updates obat.stok field without StokService
     */
    public function saveStokFisik($id)
    {
        Log::warning('Using deprecated saveStokFisik method. Use updateStokFromOpname instead for proper kartu stok recording.');
        
        $items = StokOpnameItem::where('stok_opname_id', $id)->get();
        $updated = 0;
        foreach ($items as $item) {
            $obat = \App\Models\ERM\Obat::withInactive()->find($item->obat_id);
            if ($obat) {
                $obat->stok = $item->stok_fisik;
                $obat->save();
                $updated++;
            }
        }
        return response()->json([
            'success' => true, 
            'message' => "$updated stok obat berhasil diperbarui.",
            'warning' => 'Method ini deprecated. Gunakan updateStokFromOpname untuk pencatatan kartu stok yang benar.'
        ]);
    }

    /**
     * Generate stok opname items from current stock in selected gudang
     */
    public function generateStokOpnameItems($stokOpnameId)
    {
        try {
            DB::beginTransaction();
            
            $stokOpname = StokOpname::findOrFail($stokOpnameId);
            $gudangId = $stokOpname->gudang_id;
            
            // Delete existing items for regeneration
            StokOpnameItem::where('stok_opname_id', $stokOpnameId)->delete();
            
            // Get all ObatStokGudang for this gudang with stock > 0
            $stokList = \App\Models\ERM\ObatStokGudang::where('gudang_id', $gudangId)
                ->where('stok', '>', 0)
                ->with('obat')
                ->orderBy('obat_id')
                ->orderBy('batch')
                ->get();
            
            foreach ($stokList as $stokGudang) {
                StokOpnameItem::create([
                    'stok_opname_id' => $stokOpnameId,
                    'obat_id' => $stokGudang->obat_id,
                    'batch_id' => $stokGudang->id,
                    'batch_name' => $stokGudang->batch,
                    'expiration_date' => $stokGudang->expiration_date,
                    'stok_sistem' => $stokGudang->stok,
                    'stok_fisik' => 0, // Will be filled during opname process
                    'selisih' => -$stokGudang->stok, // Initially negative (system - physical)
                    'notes' => null,
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true, 
                'message' => 'Generated ' . $stokList->count() . ' items for stock opname'
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false, 
                'message' => 'Failed to generate items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update stock fisik based on stock opname result using StokService
     */
    public function updateStokFromOpname($stokOpnameId)
    {
        try {
            DB::beginTransaction();
            
            $stokOpname = StokOpname::findOrFail($stokOpnameId);
            $stokService = app(\App\Services\ERM\StokService::class);
            $updatedCount = 0;
            
            $items = StokOpnameItem::where('stok_opname_id', $stokOpnameId)
                ->where('selisih', '!=', 0) // Only process items with difference
                ->with(['obatStokGudang', 'obat'])
                ->get();
            
            foreach ($items as $item) {
                if (!$item->obatStokGudang) continue;
                
                $stokGudang = $item->obatStokGudang;
                $selisih = $item->selisih; // positive = surplus, negative = shortage
                
                // Generate stok opname reference number
                $opnameRef = "OPNAME-{$stokOpname->periode_bulan}-{$stokOpname->periode_tahun}";
                
                if ($selisih > 0) {
                    // Add stock (found more than system) - TANPA mengubah HPP
                    $stokService->tambahStok(
                        $item->obat_id,
                        $stokOpname->gudang_id,
                        $selisih,
                        $stokGudang->batch,
                        $stokGudang->expiration_date,
                        null, // rak
                        null, // lokasi
                        null, // hargaBeli - TIDAK DIISI agar HPP tidak berubah
                        null, // hargaBeliJual - TIDAK DIISI agar HPP tidak berubah
                        'stok_opname', // refType
                        $stokOpname->id, // refId
                        "Adjustment Stok Opname {$opnameRef} - Surplus {$selisih}" // keterangan
                    );
                } else {
                    // Reduce stock (found less than system)
                    $stokService->kurangiStok(
                        $item->obat_id,
                        $stokOpname->gudang_id,
                        abs($selisih),
                        $stokGudang->batch,
                        'stok_opname', // refType
                        $stokOpname->id, // refId
                        "Adjustment Stok Opname {$opnameRef} - Shortage " . abs($selisih) // keterangan
                    );
                }
                
                $updatedCount++;
            }
            
            // Mark stock opname as completed
            $stokOpname->update(['status' => 'selesai']);
            
            DB::commit();
            
            return response()->json([
                'success' => true, 
                'message' => "Successfully updated {$updatedCount} stock adjustments from stock opname"
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false, 
                'message' => 'Failed to update stock: ' . $e->getMessage()
            ], 500);
        }
    }
}
