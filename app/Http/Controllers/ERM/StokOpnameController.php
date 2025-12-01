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
            }

            // Determine which batch to add the temuan to.
            // For aggregated items (batch_id == null) prefer the nearest-expiry existing batch in the gudang.
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

            // If no nearest found and we still don't have a batch name, keep behavior to pass null (stokService may create new batch)

            // Add to actual stock using StokService
            $stokService = app(\App\Services\ERM\StokService::class);
            $stokService->tambahStok(
                $item->obat_id,
                $item->stokOpname->gudang_id,
                $temuan,
                $targetBatch, // batch to receive temuan
                $targetExp, // expDate
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
            // Use a query builder so Yajra DataTables can perform server-side paging/filtering.
            $stokOpname = StokOpname::findOrFail($id);
            $gudangId = (int) $stokOpname->gudang_id;

                // Subquery to aggregate batches into a single string per obat (batch::stok::exp separated by ||)
                // Use a single-line SQL string to avoid accidental backslash/newline injection issues.
                $batchSub = "(SELECT GROUP_CONCAT(CONCAT(g.batch, '::', g.stok, '::', IFNULL(DATE_FORMAT(g.expiration_date, '%d/%m/%Y'), '-')) SEPARATOR '||') FROM erm_obat_stok_gudang g WHERE g.obat_id = erm_stok_opname_items.obat_id AND g.gudang_id = {$gudangId} AND g.stok > 0) as batch_list";

            $query = DB::table('erm_stok_opname_items')
                ->leftJoin('erm_obat', 'erm_stok_opname_items.obat_id', '=', 'erm_obat.id')
                ->select(
                    'erm_stok_opname_items.*',
                    'erm_obat.nama as nama_obat',
                    'erm_obat.hpp_jual as hpp_jual',
                    'erm_obat.satuan as satuan',
                    DB::raw($batchSub)
                )
                ->where('stok_opname_id', $id)
                ->orderBy('selisih', 'asc');

            return datatables()->of($query)
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
                ->rawColumns(['batch_name'])
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
            // New behavior: generate one aggregated item per obat (single row per obat)
            // but keep batch details available for allocation when applying opname.
            $stokList = \App\Models\ERM\ObatStokGudang::where('gudang_id', $gudangId)
                ->where('stok', '>', 0)
                ->with('obat')
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
