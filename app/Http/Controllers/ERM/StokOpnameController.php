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
use App\Exports\StokOpnameResultsExport;

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
            // include record-only temuan (net of jenis: 'lebih' as +, 'kurang' as -)
            $recordNet = DB::table('erm_stok_opname_temuan')
                ->where('stok_opname_item_id', $item->id)
                ->select(DB::raw("COALESCE(SUM(CASE WHEN jenis = 'lebih' THEN qty WHEN jenis = 'kurang' THEN -qty ELSE 0 END),0) as net"))
                ->first();
            $netTemuan = $recordNet ? (float) $recordNet->net : 0;

            $totalStokSistem += $hppJual * ($item->stok_sistem ?? 0);
            // nilai stok fisik includes stok_fisik plus any recorded temuan (record-only)
            $totalStokFisik += $hppJual * ((float)($item->stok_fisik ?? 0) + $netTemuan);
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
        $stokFisik = (float) $request->stok_fisik;
        $item->stok_fisik = $stokFisik;
        // Ensure stok_sistem is treated as float (casted in model)
        $stokSistem = (float) $item->stok_sistem;
        $item->selisih = $stokFisik - $stokSistem;
        $item->save();
        return response()->json([
            'success' => true,
            'stok_fisik' => (float) $item->stok_fisik,
            'selisih' => (float) $item->selisih,
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
            'operation' => 'nullable|in:add,remove,record', // add = tambah stok (default), remove = kurangi stok, record = only record temuan
        ]);

        try {
            DB::beginTransaction();
            
            $item = StokOpnameItem::with(['stokOpname', 'obat', 'obatStokGudang'])->findOrFail($itemId);
            $temuan = (float) $request->temuan;
            $catatan = $request->catatan;
            $operation = $request->operation ?? 'add';
            
            if ($temuan <= 0) {
                return response()->json(['success' => false, 'message' => 'Jumlah temuan harus lebih dari 0'], 400);
            }

            // Update stok fisik in opname item depending on operation
            // - 'add' or 'record' : increase stok_fisik by temuan
            // - 'remove' : decrease stok_fisik by temuan
            if ($operation === 'remove') {
                $item->stok_fisik = max(0, $item->stok_fisik - $temuan);
            } else {
                $item->stok_fisik = $item->stok_fisik + $temuan;
            }
            // Include net record-only temuan (lebih as +, kurang as -) when calculating selisih
            $recordNet = DB::table('erm_stok_opname_temuan')
                ->where('stok_opname_item_id', $item->id)
                ->select(DB::raw("COALESCE(SUM(CASE WHEN jenis = 'lebih' THEN qty WHEN jenis = 'kurang' THEN -qty ELSE 0 END),0) as net"))
                ->first();
            $netTemuan = $recordNet ? (float) $recordNet->net : 0;
            $item->selisih = ($item->stok_fisik - $item->stok_sistem) + $netTemuan;

            // Also update notes if provided
            if ($catatan) {
                $item->notes = $catatan;
            }

            $item->save();

            // Prepare keterangan for kartu stok / logging
            $keterangan = $catatan ?: "Temuan stok saat opname";

            // Determine batch reference and expiration when needed
            $targetBatch = $item->batch_name;
            $targetExp = $item->expiration_date;
            if ($item->batch_id === null) {
                $nearest = \App\Models\ERM\ObatStokGudang::where('obat_id', $item->obat_id)
                    ->where('gudang_id', $item->stokOpname->gudang_id)
                    ->orderByRaw("CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END, expiration_date ASC")
                    ->first();
                if ($nearest) {
                    $targetBatch = $nearest->batch;
                    $targetExp = $nearest->expiration_date;
                }
            }

            $stokService = app(\App\Services\ERM\StokService::class);

            if ($operation === 'record') {
                // Only record the temuan as a KartuStok entry (no stock mutation)
                $currentStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $item->obat_id)
                    ->where('gudang_id', $item->stokOpname->gudang_id)
                    ->sum('stok');

                \App\Models\ERM\KartuStok::create([
                    'obat_id' => $item->obat_id,
                    'gudang_id' => $item->stokOpname->gudang_id,
                    'tanggal' => now(),
                    'tipe' => 'temuan',
                    'qty' => $temuan,
                    'stok_setelah' => $currentStock,
                    'ref_type' => 'temuan_stok_opname',
                    'ref_id' => $item->stok_opname_id,
                    'batch' => $targetBatch,
                    'keterangan' => $keterangan,
                ]);

            } elseif ($operation === 'add') {
                // Add found stock to actual gudang stock (existing behavior)
                $stokService->tambahStok(
                    $item->obat_id,
                    $item->stokOpname->gudang_id,
                    $temuan,
                    $targetBatch,
                    $targetExp,
                    null,
                    null,
                    null,
                    null,
                    'temuan_stok_opname',
                    $item->stok_opname_id,
                    $keterangan
                );

            } else {
                // operation === 'remove' : reduce stock from gudang
                $toRemove = $temuan;

                if ($item->batch_id) {
                    // remove from specific batch
                    $stokService->kurangiStok(
                        $item->obat_id,
                        $item->stokOpname->gudang_id,
                        $toRemove,
                        $item->batch_name,
                        'temuan_stok_opname',
                        $item->stok_opname_id,
                        $keterangan
                    );
                } else {
                    // aggregated item: remove from nearest-expiry batches first
                    $batches = \App\Models\ERM\ObatStokGudang::where('obat_id', $item->obat_id)
                        ->where('gudang_id', $item->stokOpname->gudang_id)
                        ->orderByRaw("CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END, expiration_date ASC")
                        ->get();

                    foreach ($batches as $b) {
                        if ($toRemove <= 0) break;
                        $available = (float) $b->stok;
                        if ($available <= 0) continue;
                        $remQty = min($available, $toRemove);
                        $stokService->kurangiStok(
                            $item->obat_id,
                            $item->stokOpname->gudang_id,
                            $remQty,
                            $b->batch,
                            'temuan_stok_opname',
                            $item->stok_opname_id,
                            $keterangan
                        );
                        $toRemove -= $remQty;
                    }
                }
            }
            
            DB::commit();

            $opLabel = $operation === 'remove' ? 'dikurangi' : ($operation === 'record' ? 'dicatat' : 'ditambahkan');

            return response()->json([
                'success' => true,
                'message' => "Temuan {$temuan} berhasil {$opLabel}",
                'operation' => $operation,
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

            // Also get simple temuan records stored in the new temuan table (record-only)
            $temuanRecords = \App\Models\ERM\StokOpnameTemuan::where('stok_opname_item_id', $item->id)
                ->orderBy('created_at', 'desc')
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
                }),
                // Simple record-only temuan entries
                'temuan_records' => $temuanRecords->map(function($r) {
                    $userName = null;
                    if (!empty($r->created_by)) {
                        $u = \App\Models\User::find($r->created_by);
                        $userName = $u ? $u->name : null;
                    }
                    return [
                        'id' => $r->id,
                        'tanggal' => $r->created_at ? $r->created_at->format('d/m/Y H:i') : $r->created_at,
                        'qty' => $r->qty,
                        'jenis' => $r->jenis ?? null,
                        'process_status' => isset($r->process_status) ? (int)$r->process_status : 0,
                        'keterangan' => $r->keterangan,
                        'created_by' => $r->created_by,
                        'created_by_name' => $userName
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

    /**
     * Add a record-only temuan entry (no stock mutation)
     */
    public function addTemuanRecord(Request $request, $itemId)
    {
        $request->validate([
            'qty' => 'required|numeric|min:0.0001',
            'jenis' => 'required|in:kurang,lebih',
            'keterangan' => 'nullable|string|max:255',
        ]);

        try {
            $item = StokOpnameItem::with('stokOpname')->findOrFail($itemId);

            $r = \App\Models\ERM\StokOpnameTemuan::create([
                'stok_opname_id' => $item->stok_opname_id,
                'stok_opname_item_id' => $item->id,
                'qty' => $request->qty,
                'jenis' => $request->jenis,
                'keterangan' => $request->keterangan,
                'created_by' => auth()->id()
            ]);

            $createdByName = auth()->user() ? auth()->user()->name : null;

            // Recalculate and persist selisih on the opname item
            $recordNet = DB::table('erm_stok_opname_temuan')
                ->where('stok_opname_item_id', $item->id)
                ->select(DB::raw("COALESCE(SUM(CASE WHEN jenis = 'lebih' THEN qty WHEN jenis = 'kurang' THEN -qty ELSE 0 END),0) as net"))
                ->first();
            $netTemuan = $recordNet ? (float) $recordNet->net : 0;
            $item->selisih = ($item->stok_fisik - $item->stok_sistem) + $netTemuan;
            $item->save();

            return response()->json([
                'success' => true,
                'message' => 'Temuan berhasil dicatat',
                'record' => [
                    'id' => $r->id,
                    'tanggal' => $r->created_at ? $r->created_at->format('d/m/Y H:i') : null,
                    'qty' => $r->qty,
                    'jenis' => $r->jenis,
                    'process_status' => isset($r->process_status) ? (int)$r->process_status : 0,
                    'keterangan' => $r->keterangan,
                    'created_by' => $r->created_by,
                    'created_by_name' => $createdByName
                ],
                'selisih' => $item->selisih
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan temuan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process a record-only temuan: apply stock mutation based on jenis (lebih/kurang)
     */
    public function processTemuanRecord(Request $request, $temuanId)
    {
        try {
            $r = \App\Models\ERM\StokOpnameTemuan::findOrFail($temuanId);

            $item = StokOpnameItem::with('stokOpname')->findOrFail($r->stok_opname_item_id);
            $stokOpname = $item->stokOpname;

            $qty = (float) $r->qty;
            $jenis = $r->jenis ?? 'kurang';
            $keterangan = 'temuan stok opname';
            if ($r->keterangan) $keterangan .= ' - ' . $r->keterangan;

            $stokService = app(\App\Services\ERM\StokService::class);

            if ($jenis === 'lebih') {
                // add stock
                $targetBatch = $item->batch_name;
                $targetExp = $item->expiration_date;
                // If no specific batch on the opname item, add to the nearest-expiry existing batch in gudang
                if (empty($targetBatch)) {
                    $nearest = \App\Models\ERM\ObatStokGudang::where('obat_id', $item->obat_id)
                        ->where('gudang_id', $stokOpname->gudang_id)
                        ->orderByRaw("CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END, expiration_date ASC")
                        ->first();
                    if ($nearest) {
                        $targetBatch = $nearest->batch;
                        $targetExp = $nearest->expiration_date;
                    }
                }

                $stokService->tambahStok(
                    $item->obat_id,
                    $stokOpname->gudang_id,
                    $qty,
                    $targetBatch,
                    $targetExp,
                    null,
                    null,
                    null,
                    null,
                    'temuan_stok_opname',
                    $stokOpname->id,
                    $keterangan
                );
            } else {
                // kurang -> reduce stock
                // if batch available, remove from that batch, otherwise remove across batches
                if ($item->batch_id) {
                    $stokService->kurangiStok(
                        $item->obat_id,
                        $stokOpname->gudang_id,
                        $qty,
                        $item->batch_name,
                        'temuan_stok_opname',
                        $stokOpname->id,
                        $keterangan
                    );
                } else {
                    // remove from nearest expiry first
                    $toRemove = $qty;
                    $batches = \App\Models\ERM\ObatStokGudang::where('obat_id', $item->obat_id)
                        ->where('gudang_id', $stokOpname->gudang_id)
                        ->orderByRaw("CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END, expiration_date ASC")
                        ->get();
                    foreach ($batches as $b) {
                        if ($toRemove <= 0) break;
                        $available = (float) $b->stok;
                        if ($available <= 0) continue;
                        $remQty = min($available, $toRemove);
                        $stokService->kurangiStok(
                            $item->obat_id,
                            $stokOpname->gudang_id,
                            $remQty,
                            $b->batch,
                            'temuan_stok_opname',
                            $stokOpname->id,
                            $keterangan
                        );
                        $toRemove -= $remQty;
                    }
                }
            }

            // mark temuan as processed
            $r->process_status = 1;
            try {
                $r->save();
            } catch (\Exception $e) {
                // non-fatal: log and continue
                Log::warning('Failed to mark temuan processed: ' . $e->getMessage());
            }

            // After processing, update the opname item's selisih to include current net record-only temuan
            $newSelisih = null;
            try {
                $itemForSelisih = StokOpnameItem::find($r->stok_opname_item_id);
                if ($itemForSelisih) {
                    $recordNet = DB::table('erm_stok_opname_temuan')
                        ->where('stok_opname_item_id', $itemForSelisih->id)
                        ->select(DB::raw("COALESCE(SUM(CASE WHEN jenis = 'lebih' THEN qty WHEN jenis = 'kurang' THEN -qty ELSE 0 END),0) as net"))
                        ->first();
                    $netTemuan = $recordNet ? (float) $recordNet->net : 0;
                    $itemForSelisih->selisih = ($itemForSelisih->stok_fisik - $itemForSelisih->stok_sistem) + $netTemuan;
                    $itemForSelisih->save();
                    $newSelisih = $itemForSelisih->selisih;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to recalc selisih after processing temuan: ' . $e->getMessage());
            }

            return response()->json(['success' => true, 'message' => 'Temuan berhasil diproses ke stok', 'selisih' => $newSelisih]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memproses temuan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a record-only temuan entry
     */
    public function deleteTemuanRecord(Request $request, $temuanId)
    {
        try {
            $r = \App\Models\ERM\StokOpnameTemuan::findOrFail($temuanId);
            $item = StokOpnameItem::find($r->stok_opname_item_id);
            $r->delete();

            // Recalculate and persist selisih on the opname item
            if ($item) {
                $recordNet = DB::table('erm_stok_opname_temuan')
                    ->where('stok_opname_item_id', $item->id)
                    ->select(DB::raw("COALESCE(SUM(CASE WHEN jenis = 'lebih' THEN qty WHEN jenis = 'kurang' THEN -qty ELSE 0 END),0) as net"))
                    ->first();
                $netTemuan = $recordNet ? (float) $recordNet->net : 0;
                $item->selisih = ($item->stok_fisik - $item->stok_sistem) + $netTemuan;
                $item->save();
            }

            return response()->json(['success' => true, 'message' => 'Temuan berhasil dihapus', 'selisih' => $item ? $item->selisih : null]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus temuan: ' . $e->getMessage()], 500);
        }
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = StokOpname::with('gudang', 'user')->latest();
            // Apply periode filters if provided from DataTable AJAX
            if ($request->filled('periode_bulan')) {
                $data->where('periode_bulan', $request->periode_bulan);
            }
            if ($request->filled('periode_tahun')) {
                $data->where('periode_tahun', $request->periode_tahun);
            }
            return datatables()->of($data)
                ->addColumn('selisih_count', function($row) {
                    return $row->items()->whereRaw('ABS(selisih) > 0')->count();
                })
                ->addColumn('aksi', function($row) {
                    $lihatBtn = '<a href="'.route('erm.stokopname.create', $row->id).'" class="btn btn-primary btn-sm">Lihat Stok Opname</a>';
                    if ($row->status === 'selesai') {
                        $exportBtn = ' <a href="'.route('erm.stokopname.exportResults', $row->id).'" class="btn btn-success btn-sm">Export Hasil Excel</a>';
                        return $lihatBtn . $exportBtn;
                    }
                    return $lihatBtn;
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
            // include record-only temuan (net of jenis: 'lebih' as +, 'kurang' as -)
            $recordNet = DB::table('erm_stok_opname_temuan')
                ->where('stok_opname_item_id', $item->id)
                ->select(DB::raw("COALESCE(SUM(CASE WHEN jenis = 'lebih' THEN qty WHEN jenis = 'kurang' THEN -qty ELSE 0 END),0) as net"))
                ->first();
            $netTemuan = $recordNet ? (float) $recordNet->net : 0;

            $totalStokSistem += $hppJual * ($item->stok_sistem ?? 0);
            // nilai stok fisik includes stok_fisik plus any recorded temuan (record-only)
            $totalStokFisik += $hppJual * ((float)($item->stok_fisik ?? 0) + $netTemuan);
        }

        // Prepare filter lists for the view
        $kategoriList = Obat::select('kategori')->distinct()->whereNotNull('kategori')->pluck('kategori');
        $metodeList = DB::table('erm_metode_bayar')->select('id','nama')->get();

        // Expiration years available for this gudang
        $gudangId = $stokOpname->gudang_id;
        $expYears = DB::table('erm_obat_stok_gudang')
            ->where('gudang_id', $gudangId)
            ->whereNotNull('expiration_date')
            ->select(DB::raw('DISTINCT YEAR(expiration_date) as year'))
            ->orderByDesc('year')
            ->pluck('year');

        // Available obats (have stock entries in this gudang) for manual add
        // Exclude obat that already exist as items in this stok opname
        // Use a DB-level NOT EXISTS to ensure we only return obats that have stock in this gudang
        // and are NOT already present in the stok_opname_items for this opname.
        $availableObats = DB::table('erm_obat as o')
            ->join('erm_obat_stok_gudang as sg', 'o.id', '=', 'sg.obat_id')
            ->where('sg.gudang_id', $gudangId)
            ->whereNotExists(function($q) use ($stokOpname) {
                $q->select(DB::raw(1))
                  ->from('erm_stok_opname_items as i')
                  ->whereRaw('i.obat_id = o.id')
                  ->where('i.stok_opname_id', $stokOpname->id);
            })
            ->select('o.id', 'o.nama')
            ->distinct()
            ->orderBy('o.nama')
            ->get();

        return view('erm.stokopname.create', compact('stokOpname', 'items', 'totalStokSistem', 'totalStokFisik', 'kategoriList', 'metodeList', 'expYears', 'availableObats'));
    }

    /**
     * Add a single obat as a StokOpnameItem (aggregated per-obat) to an existing opname
     */
    public function addItem(Request $request, $stokOpnameId)
    {
        $request->validate([
            'obat_id' => 'required|exists:erm_obat,id'
        ]);

        try {
            $stokOpname = StokOpname::findOrFail($stokOpnameId);

            $obatId = (int)$request->obat_id;

            // Prevent duplicate item for same obat in this opname
            $exists = StokOpnameItem::where('stok_opname_id', $stokOpnameId)->where('obat_id', $obatId)->exists();
            if ($exists) {
                return response()->json(['success' => false, 'message' => 'Item untuk obat ini sudah ada di opname'], 400);
            }

            // Compute total sistem stock for this obat in the opname gudang
            $totalStok = (float) DB::table('erm_obat_stok_gudang')
                ->where('obat_id', $obatId)
                ->where('gudang_id', $stokOpname->gudang_id)
                ->sum('stok');

            // Capture snapshot per-batch for later allocation
            $batches = DB::table('erm_obat_stok_gudang')
                ->where('obat_id', $obatId)
                ->where('gudang_id', $stokOpname->gudang_id)
                ->orderBy('batch')
                ->get();

            $snapshot = $batches->map(function($b) {
                return [
                    'id' => $b->id,
                    'batch' => $b->batch,
                    'stok' => (float)$b->stok,
                    'expiration_date' => $b->expiration_date,
                ];
            })->toArray();

            $item = StokOpnameItem::create([
                'stok_opname_id' => $stokOpnameId,
                'obat_id' => $obatId,
                'batch_id' => null,
                'batch_name' => null,
                'expiration_date' => null,
                'stok_sistem' => $totalStok,
                'stok_fisik' => 0,
                'selisih' => -$totalStok,
                'notes' => null,
                'batch_snapshot' => $snapshot,
            ]);

            return response()->json(['success' => true, 'message' => 'Item berhasil ditambahkan', 'item_id' => $item->id]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan item: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Return available obats in the opname gudang that are not yet part of this opname (AJAX)
     */
    public function getAvailableObats($stokOpnameId)
    {
        try {
            $stokOpname = StokOpname::findOrFail($stokOpnameId);
            $gudangId = $stokOpname->gudang_id;

            $available = DB::table('erm_obat as o')
                ->join('erm_obat_stok_gudang as sg', 'o.id', '=', 'sg.obat_id')
                ->where('sg.gudang_id', $gudangId)
                ->whereNotExists(function($q) use ($stokOpname) {
                    $q->select(DB::raw(1))
                      ->from('erm_stok_opname_items as i')
                      ->whereRaw('i.obat_id = o.id')
                      ->where('i.stok_opname_id', $stokOpname->id);
                })
                ->select('o.id', 'o.nama')
                ->distinct()
                ->orderBy('o.nama')
                ->get();

            return response()->json(['success' => true, 'data' => $available]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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

    /**
     * Export stok opname results (items) to Excel
     */
    public function exportResultsExcel($id)
    {
        $stokOpname = StokOpname::findOrFail($id);

        // Only allow export if stok opname exists. Caller (view) may choose to show button only when status is 'selesai'.
        return Excel::download(new StokOpnameResultsExport($id), 'stok_opname_results_' . $id . '.xlsx');
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
            // Use a query builder so Yajra DataTables can perform server-side paging/filtering.
            $stokOpname = StokOpname::findOrFail($id);
            $gudangId = (int) $stokOpname->gudang_id;

                // Subquery to aggregate batches into a single string per obat (batch::stok::exp separated by ||)
                // Use a single-line SQL string to avoid accidental backslash/newline injection issues.
                $batchSub = "(SELECT GROUP_CONCAT(CONCAT(g.batch, '::', g.stok, '::', IFNULL(DATE_FORMAT(g.expiration_date, '%d/%m/%Y'), '-')) SEPARATOR '||') FROM erm_obat_stok_gudang g WHERE g.obat_id = erm_stok_opname_items.obat_id AND g.gudang_id = {$gudangId} AND g.stok > 0) as batch_list";

                // Sum temuan quantities from kartu_stok (ref_type temuan_stok_opname) for this opname and obat/gudang
                $kartuQueryNoAlias = "(SELECT COALESCE(SUM(ks.qty),0) FROM erm_kartu_stok ks WHERE ks.ref_type = 'temuan_stok_opname' AND ks.ref_id = erm_stok_opname_items.stok_opname_id AND ks.obat_id = erm_stok_opname_items.obat_id AND ks.gudang_id = {$gudangId})";

                // Sum temuan quantities from the new record-only temuan table per item (absolute sum)
                $recordQueryNoAlias = "(SELECT COALESCE(SUM(t.qty),0) FROM erm_stok_opname_temuan t WHERE t.stok_opname_item_id = erm_stok_opname_items.id)";

                // Net record-only temuan: treat 'lebih' as +qty and 'kurang' as -qty
                $recordNetQuery = "(SELECT COALESCE(SUM(CASE WHEN t.jenis = 'lebih' THEN t.qty WHEN t.jenis = 'kurang' THEN -t.qty ELSE 0 END),0) FROM erm_stok_opname_temuan t WHERE t.stok_opname_item_id = erm_stok_opname_items.id)";

                // Total temuan: use only record-only temuan (net of jenis: 'lebih' as +, 'kurang' as -)
                $totalTemuanSub = "COALESCE((" . $recordNetQuery . "),0) as total_temuan";

                // Adjusted selisih = (stok_fisik - stok_sistem) + net_record_temuan
                $adjustedSelisihSub = "((erm_stok_opname_items.stok_fisik - erm_stok_opname_items.stok_sistem) + COALESCE((" . $recordNetQuery . "),0)) as adjusted_selisih";

            $query = DB::table('erm_stok_opname_items')
                ->leftJoin('erm_obat', 'erm_stok_opname_items.obat_id', '=', 'erm_obat.id')
                ->select(
                    'erm_stok_opname_items.*',
                    'erm_obat.nama as nama_obat',
                    'erm_obat.hpp_jual as hpp_jual',
                    'erm_obat.satuan as satuan',
                    'erm_obat.kategori as kategori',
                    DB::raw($batchSub),
                    DB::raw($kartuQueryNoAlias . ' as kartu_temuan_sum'),
                    DB::raw($recordQueryNoAlias . ' as record_temuan_sum'),
                    DB::raw($recordNetQuery . ' as record_temuan_net'),
                    DB::raw($totalTemuanSub),
                        DB::raw($adjustedSelisihSub),
                        // nearest expiration date (formatted) for this obat in the gudang (earliest non-null expiration)
                        DB::raw("(SELECT IFNULL(DATE_FORMAT(MIN(g.expiration_date),'%d/%m/%Y'), '-') FROM erm_obat_stok_gudang g WHERE g.obat_id = erm_stok_opname_items.obat_id AND g.gudang_id = {$gudangId} AND g.stok > 0 AND g.expiration_date IS NOT NULL) as nearest_exp"),
                    // metode_bayar name from related table (nullable)
                    DB::raw("(SELECT mb.nama FROM erm_metode_bayar mb WHERE mb.id = erm_obat.metode_bayar_id LIMIT 1) as metode_bayar")
                )
                ->where('stok_opname_id', $id);

            // Apply server-side filters if requested
            $filterSelisih = $request->get('filter_selisih');
            $filterKategori = $request->get('filter_kategori');
            $filterMetode = $request->get('filter_metode');
            // Expression matching adjusted selisih used above
            $adjustExpr = "((erm_stok_opname_items.stok_fisik - erm_stok_opname_items.stok_sistem) + COALESCE((" . $recordNetQuery . "),0))";
            if ($filterSelisih === 'with') {
                $query->whereRaw($adjustExpr . ' <> 0');
            } elseif ($filterSelisih === 'without') {
                $query->whereRaw($adjustExpr . ' = 0');
            }
            if (!empty($filterKategori)) {
                $query->where('erm_obat.kategori', $filterKategori);
            }
            if (!empty($filterMetode)) {
                // filter by metode_bayar id on obat table
                $query->where('erm_obat.metode_bayar_id', $filterMetode);
            }

            // filter by expiration year if provided (numeric)
            $filterExpYear = $request->get('filter_exp_year');
            if (!empty($filterExpYear) && is_numeric($filterExpYear)) {
                $year = (int) $filterExpYear;
                $query->whereExists(function($sub) use ($gudangId, $year) {
                    $sub->select(DB::raw('1'))
                        ->from('erm_obat_stok_gudang as g2')
                        ->whereRaw('g2.obat_id = erm_stok_opname_items.obat_id')
                        ->where('g2.gudang_id', $gudangId)
                        ->whereRaw('g2.stok > 0')
                        ->whereRaw('YEAR(g2.expiration_date) = ' . (int)$year);
                });
            }

            // filter by stok_fisik zero/non-zero
            $filterStokFisik = $request->get('filter_stok_fisik');
            if ($filterStokFisik === 'zero') {
                $query->where('erm_stok_opname_items.stok_fisik', 0);
            } elseif ($filterStokFisik === 'nonzero') {
                $query->whereRaw('COALESCE(erm_stok_opname_items.stok_fisik,0) <> 0');
            }

            return datatables()->of($query)
                // Override selisih column to show adjusted selisih that includes unprocessed record-only temuan
                ->addColumn('selisih', function($row) {
                    // $row is stdClass; use adjusted_selisih if present, else fallback to stored selisih
                    if (isset($row->adjusted_selisih)) return $row->adjusted_selisih;
                    return $row->selisih;
                })
                // Ensure ordering by displayed selisih (adjusted selisih) works server-side
                ->orderColumn('selisih', function($query, $order) use ($recordNetQuery) {
                    $expr = "((erm_stok_opname_items.stok_fisik - erm_stok_opname_items.stok_sistem) + COALESCE((" . $recordNetQuery . "),0))";
                    $query->orderByRaw($expr . ' ' . $order);
                })
                ->addColumn('batch_name', function($row) {
                    // $row is stdClass from query
                    if (!empty($row->batch_id)) {
                        return e($row->batch_name ?: '-');
                    }

                    if (empty($row->batch_list)) return '-';

                    $parts = [];
                    $chunks = explode('||', $row->batch_list);
                    foreach ($chunks as $c) {
                        if ($c === '') continue;
                        [$batch, $stok, $exp] = array_pad(explode('::', $c), 3, '-');
                        $parts[] = '<strong>'.e($batch).'</strong> (' . number_format((float)$stok, 0, ',', '.') . ') <small>' . e($exp) . '</small>';
                    }

                    return implode('<br>', $parts);
                })
                ->filterColumn('nama_obat', function($query, $keyword) {
                    $query->where('erm_obat.nama', 'like', "%{$keyword}%");
                })
                // Ensure searching by 'satuan' targets the joined `erm_obat.satuan` column
                ->filterColumn('satuan', function($query, $keyword) {
                    $query->where('erm_obat.satuan', 'like', "%{$keyword}%");
                })
                // Handle search on nearest_exp which is an alias from a subquery
                ->filterColumn('nearest_exp', function($query, $keyword) use ($gudangId) {
                    $expr = "(SELECT IFNULL(DATE_FORMAT(MIN(g.expiration_date),'%d/%m/%Y'), '-') FROM erm_obat_stok_gudang g WHERE g.obat_id = erm_stok_opname_items.obat_id AND g.gudang_id = {$gudangId} AND g.stok > 0 AND g.expiration_date IS NOT NULL)";
                    $query->whereRaw($expr . " LIKE ?", ["%{$keyword}%"]);
                })
                ->rawColumns(['batch_name'])
                ->make(true);
    }

    /**
     * Return batch list for a given stok_opname_item (for modal display)
     */
    public function getItemBatches($itemId)
    {
        try {
            $item = StokOpnameItem::with(['stokOpname', 'obat'])->findOrFail($itemId);
            $gudangId = $item->stokOpname ? $item->stokOpname->gudang_id : null;

            $query = \App\Models\ERM\ObatStokGudang::where('obat_id', $item->obat_id);
            if ($gudangId) {
                $query->where('gudang_id', $gudangId);
            }
            $batches = $query->select('id','batch','stok','expiration_date')->orderByRaw('COALESCE(expiration_date, "9999-12-31") asc')->get();

            // Normalize expiration date fields for the frontend: provide raw (Y-m-d) and formatted display (d/m/Y)
            $batchesNormalized = $batches->map(function($b) {
                $raw = $b->expiration_date ? \Carbon\Carbon::parse($b->expiration_date)->format('Y-m-d') : '';
                $display = $b->expiration_date ? \Carbon\Carbon::parse($b->expiration_date)->format('d/m/Y') : '-';
                return [
                    'id' => $b->id,
                    'batch' => $b->batch,
                    'stok' => (float) $b->stok,
                    'expiration_date' => $display,
                    'expiration_date_raw' => $raw
                ];
            })->values();

            return response()->json([
                'success' => true,
                'obat' => $item->obat ? $item->obat->nama : null,
                'batches' => $batchesNormalized,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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
            // New behavior: generate one aggregated item per obat (single row per obat)
            // but keep batch details available for allocation when applying opname.
            // Include stok entries for the gudang regardless of stok value (allow stok = 0)
            // and ensure we only include active `Obat` by using the `obatAktif` relation
            // (the default `obat` relation on ObatStokGudang uses withInactive()).
            $stokList = \App\Models\ERM\ObatStokGudang::where('gudang_id', $gudangId)
                ->with('obatAktif')
                ->whereHas('obatAktif')
                ->orderBy('obat_id')
                ->orderBy('batch')
                ->get();

            // Group by obat_id and create one StokOpnameItem per obat
            $grouped = $stokList->groupBy('obat_id');
            foreach ($grouped as $obatId => $collection) {
                $totalStok = $collection->sum('stok');
                // Capture per-batch snapshot so we can allocate against original batch stocks later
                $snapshot = $collection->map(function($b) {
                    return [
                        'id' => $b->id,
                        'batch' => $b->batch,
                        'stok' => (float) $b->stok,
                        'expiration_date' => $b->expiration_date,
                    ];
                })->values()->toArray();

                StokOpnameItem::create([
                    'stok_opname_id' => $stokOpnameId,
                    'obat_id' => $obatId,
                    'batch_id' => null,
                    'batch_name' => null,
                    'expiration_date' => null,
                    'stok_sistem' => $totalStok,
                    'stok_fisik' => 0,
                    'selisih' => -$totalStok,
                    'notes' => null,
                    'batch_snapshot' => $snapshot,
                ]);
            }
            
            // Persist status change: mark opname as 'proses' after items generated
            $stokOpname->status = 'proses';
            $stokOpname->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => $stokOpname->status,
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
            
            // Process all items so we can record opname entries even when selisih == 0
            $items = StokOpnameItem::where('stok_opname_id', $stokOpnameId)
                ->with(['obatStokGudang', 'obat'])
                ->get();
            
            foreach ($items as $item) {
                // If the item was generated per-batch we have obatStokGudang relation.
                // If the item is aggregated per-obat (batch_id == null), distribute the change across batches.
                if ($item->batch_id === null) {
                    // Aggregated flow: allocate target total across batches using expiry rules
                    $batches = \App\Models\ERM\ObatStokGudang::where('obat_id', $item->obat_id)
                        ->where('gudang_id', $stokOpname->gudang_id)
                        ->orderBy('expiration_date', 'desc')
                        ->get();

                    if ($batches->isEmpty()) continue;

                    $currentTotal = (float) $batches->sum('stok');
                    $targetTotal = (float) $item->stok_fisik;

                    // If there is no change, still record a kartu stok entry for this opname (qty 0)
                    if (abs($targetTotal - $currentTotal) < 0.000001) {
                        \App\Models\ERM\KartuStok::create([
                            'obat_id' => $item->obat_id,
                            'gudang_id' => $stokOpname->gudang_id,
                            'tanggal' => now(),
                            'tipe' => 'stok_opname',
                            'qty' => 0,
                            'stok_setelah' => $currentTotal,
                            'ref_type' => 'stok_opname',
                            'ref_id' => $stokOpname->id,
                            'batch' => null,
                            'keterangan' => "Stok opname tercatat (tidak ada perubahan) - stok saat ini: {$currentTotal}",
                            'user_id' => auth()->id()
                        ]);
                        $updatedCount++;
                        continue;
                    }

                    // Generate stok opname reference number
                    $opnameRef = "OPNAME-{$stokOpname->periode_bulan}-{$stokOpname->periode_tahun}";

                    if ($targetTotal > $currentTotal) {
                        // Add stock: explicitly preserve the farthest-expiry batch stock,
                        // then allocate the remaining difference to the nearest-expiry batch(es).
                        // Use timestamp-based sorts to be robust with date formats
                        $farthest = $batches->sortByDesc(function($b) {
                            return $b->expiration_date ? \Carbon\Carbon::parse($b->expiration_date)->timestamp : 0;
                        })->first();
                        $nearest = $batches->sortBy(function($b) {
                            return $b->expiration_date ? \Carbon\Carbon::parse($b->expiration_date)->timestamp : PHP_INT_MAX;
                        })->first();

                        // Build allocations map starting from zero, then set preserved farthest stock
                        $allocations = [];
                        foreach ($batches as $b) {
                            $allocations[$b->id] = 0;
                        }

                        // Prefer deterministic snapshot-based allocation when snapshot exists.
                        $snapshot = !empty($item->batch_snapshot) && is_array($item->batch_snapshot) ? $item->batch_snapshot : null;
                        $batchesById = $batches->keyBy('id');

                        if ($snapshot) {
                            // Sort snapshot by expiration to find farthest and nearest as captured during generation
                            $snapCollection = collect($snapshot);
                            $farthestSnap = $snapCollection->sortByDesc(function($s) {
                                return isset($s['expiration_date']) ? \Carbon\Carbon::parse($s['expiration_date'])->timestamp : 0;
                            })->first();
                            $nearestSnap = $snapCollection->sortBy(function($s) {
                                return isset($s['expiration_date']) ? \Carbon\Carbon::parse($s['expiration_date'])->timestamp : PHP_INT_MAX;
                            })->first();

                            $farthestId = $farthestSnap['id'] ?? null;
                            $nearestId = $nearestSnap['id'] ?? null;

                            $preservedStock = 0;
                            if ($farthestId && isset($farthestSnap['stok'])) {
                                $preservedStock = (float) $farthestSnap['stok'];
                            }
                            $preservedStock = min($preservedStock, $targetTotal);

                            if ($farthestId && isset($allocations[$farthestId])) {
                                $allocations[$farthestId] = $preservedStock;
                            }

                            $remaining = $targetTotal - $preservedStock;

                            if ($remaining > 0) {
                                // allocate remaining to the nearest snapshot batch if available and different
                                if ($nearestId && $nearestId != $farthestId) {
                                    $allocations[$nearestId] = ($allocations[$nearestId] ?? 0) + $remaining;
                                    $remaining = 0;
                                } else {
                                    // fallback to applying remaining to any other existing batch (nearest DB)
                                    $dbNearest = $batches->sortBy(function($b) {
                                        return $b->expiration_date ? \Carbon\Carbon::parse($b->expiration_date)->timestamp : PHP_INT_MAX;
                                    })->first();
                                    if ($dbNearest) {
                                        $allocations[$dbNearest->id] = ($allocations[$dbNearest->id] ?? 0) + $remaining;
                                        $remaining = 0;
                                    }
                                }
                            }
                        } else {
                            // No snapshot: fallback to DB-based logic (preserve farthest DB batch)
                            $preservedStock = $farthest ? (float) $farthest->stok : 0;
                            $preservedStock = min($preservedStock, $targetTotal);
                            if ($farthest) {
                                $allocations[$farthest->id] = $preservedStock;
                            }
                            $remaining = $targetTotal - $preservedStock;
                            if ($remaining > 0) {
                                if ($nearest && $nearest->id != ($farthest->id ?? null)) {
                                    $allocations[$nearest->id] = ($allocations[$nearest->id] ?? 0) + $remaining;
                                    $remaining = 0;
                                } elseif ($farthest) {
                                    $allocations[$farthest->id] = ($allocations[$farthest->id] ?? 0) + $remaining;
                                    $remaining = 0;
                                }
                            }
                        }

                        // Apply per-batch adjustments based on allocations
                        foreach ($batches as $b) {
                            $allocated = $allocations[$b->id];
                            $current = (float) $b->stok;
                            $deltaBatch = $allocated - $current;
                            if ($deltaBatch > 0) {
                                $stokService->tambahStok(
                                    $item->obat_id,
                                    $stokOpname->gudang_id,
                                    $deltaBatch,
                                    $b->batch,
                                    $b->expiration_date,
                                    null,
                                    null,
                                    null,
                                    null,
                                    'stok_opname',
                                    $stokOpname->id,
                                    "Adjustment Stok Opname {$opnameRef} - Surplus {$deltaBatch} (aggregated)"
                                );
                            } elseif ($deltaBatch < 0) {
                                $qtyToRemove = abs($deltaBatch);
                                $stokService->kurangiStok(
                                    $item->obat_id,
                                    $stokOpname->gudang_id,
                                    $qtyToRemove,
                                    $b->batch,
                                    'stok_opname',
                                    $stokOpname->id,
                                    "Adjustment Stok Opname {$opnameRef} - Shortage {$qtyToRemove} (aggregated)"
                                );
                            }
                        }

                    } elseif ($targetTotal < $currentTotal) {
                        // Remove stock: remove from nearest-expiry batches first
                        $toRemove = $currentTotal - $targetTotal;
                        $batchesAsc = $batches->sortBy('expiration_date');
                        foreach ($batchesAsc as $b) {
                            if ($toRemove <= 0) break;
                            $available = (float) $b->stok;
                            $remQty = min($toRemove, $available);
                            if ($remQty <= 0) continue;
                            $stokService->kurangiStok(
                                $item->obat_id,
                                $stokOpname->gudang_id,
                                $remQty,
                                $b->batch,
                                'stok_opname',
                                $stokOpname->id,
                                "Adjustment Stok Opname {$opnameRef} - Shortage {$remQty} (aggregated)"
                            );
                            $toRemove -= $remQty;
                        }
                    }

                    $updatedCount++;
                    continue;
                }

                if (!$item->obatStokGudang) continue;

                $stokGudang = $item->obatStokGudang;

                // Use current gudang stock as the source of truth instead of the saved snapshot
                $currentStock = (float) $stokGudang->stok;
                $targetStock = (float) $item->stok_fisik; // desired physical stock
                $delta = $targetStock - $currentStock; // positive -> need to add, negative -> need to remove

                // If there is no change, still record a kartu stok entry (qty 0)
                if (abs($delta) < 0.000001) {
                    \App\Models\ERM\KartuStok::create([
                        'obat_id' => $item->obat_id,
                        'gudang_id' => $stokOpname->gudang_id,
                        'tanggal' => now(),
                        'tipe' => 'stok_opname',
                        'qty' => 0,
                        'stok_setelah' => $currentStock,
                        'ref_type' => 'stok_opname',
                        'ref_id' => $stokOpname->id,
                        'batch' => $stokGudang->batch,
                        'keterangan' => "Stok opname tercatat (tidak ada perubahan) - stok saat ini: {$currentStock}",
                        'user_id' => auth()->id()
                    ]);
                    $updatedCount++;
                    continue;
                }

                // Generate stok opname reference number
                $opnameRef = "OPNAME-{$stokOpname->periode_bulan}-{$stokOpname->periode_tahun}";

                if ($delta > 0) {
                    // Need to add stock (found more than current system)
                    $stokService->tambahStok(
                        $item->obat_id,
                        $stokOpname->gudang_id,
                        $delta,
                        $stokGudang->batch,
                        $stokGudang->expiration_date,
                        null,
                        null,
                        null,
                        null,
                        'stok_opname',
                        $stokOpname->id,
                        "Adjustment Stok Opname {$opnameRef} - Surplus {$delta}"
                    );
                } elseif ($delta < 0) {
                    // Need to remove stock (found less than current system)
                    $qtyToRemove = abs($delta);

                    // kurangiStok expects there to be sufficient stock in the selected batch; using currentStock ensures this is valid
                    $stokService->kurangiStok(
                        $item->obat_id,
                        $stokOpname->gudang_id,
                        $qtyToRemove,
                        $stokGudang->batch,
                        'stok_opname',
                        $stokOpname->id,
                        "Adjustment Stok Opname {$opnameRef} - Shortage {$qtyToRemove}"
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
