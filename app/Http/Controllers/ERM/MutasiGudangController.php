<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\MutasiGudang;
use App\Models\ERM\Gudang;
use App\Models\ERM\Obat;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MutasiGudangController extends Controller
{
    /**
     * Get obat yang tersedia di gudang asal beserta stoknya
     */
    public function getObatGudang(Request $request)
    {
        $gudangId = $request->get('gudang_id');
        $search = $request->get('q');
        if (!$gudangId) {
            return response()->json(['results' => []]);
        }

        $query = \App\Models\ERM\ObatStokGudang::where('gudang_id', $gudangId)
            ->where('stok', '>', 0)
            ->with('obat');
        if ($search) {
            $query->whereHas('obat', function($q) use ($search) {
                $q->where('nama', 'like', "%$search%");
            });
        }
        $stokList = $query->get();

        // Group by obat_id, sum stok
        $grouped = [];
        foreach ($stokList as $stokGudang) {
            $obatId = $stokGudang->obat_id;
            if (!isset($grouped[$obatId])) {
                $grouped[$obatId] = [
                    'id' => $stokGudang->obat->id,
                    'nama' => $stokGudang->obat->nama,
                    'stok' => 0,
                    'satuan' => $stokGudang->obat->satuan,
                ];
            }
            $grouped[$obatId]['stok'] += $stokGudang->stok;
        }

        $results = array_values($grouped);
        return response()->json(['results' => $results]);
    }
    public function index()
    {
        $gudangs = Gudang::orderBy('nama')->get();
        return view('erm.mutasi-gudang.index', compact('gudangs'));
    }

    public function data(Request $request)
    {
        $query = MutasiGudang::with(['items.obat', 'obat', 'gudangAsal', 'gudangTujuan', 'requestedBy', 'approvedBy']);

        if ($request->gudang_id) {
            $gudangId = $request->gudang_id;
            $query->where(function($q) use ($gudangId) {
                $q->where('gudang_asal_id', $gudangId)
                  ->orWhere('gudang_tujuan_id', $gudangId);
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('tanggal', function ($mutasi) {
                return $mutasi->created_at->format('d/m/Y H:i');
            })
            ->addColumn('nama_obat', function ($mutasi) {
                // Build a comma-separated list of obat names (limit to first 3)
                if ($mutasi->items && $mutasi->items->count() > 0) {
                    $names = $mutasi->items->map(function($it) {
                        return $it->obat ? $it->obat->nama : ('Obat ID ' . $it->obat_id);
                    })->filter()->values()->all();

                    if (count($names) <= 3) {
                        return implode(', ', $names);
                    }
                    $first = array_slice($names, 0, 3);
                    $remaining = count($names) - 3;
                    return implode(', ', $first) . " ... +{$remaining} more";
                }

                // Fallback to single obat
                return $mutasi->obat ? $mutasi->obat->nama : '-';
            })
            ->addColumn('gudang_asal', function ($mutasi) {
                return $mutasi->gudangAsal->nama;
            })
            ->addColumn('gudang_tujuan', function ($mutasi) {
                return $mutasi->gudangTujuan->nama;
            })
            ->addColumn('requested_by', function ($mutasi) {
                return $mutasi->requestedBy->name;
            })
            ->addColumn('approved_by', function ($mutasi) {
                return $mutasi->approvedBy ? $mutasi->approvedBy->name : '-';
            })
            ->addColumn('status_label', function ($mutasi) {
                $labels = [
                    'pending' => '<span class="badge bg-warning">Pending</span>',
                    'approved' => '<span class="badge bg-success">Disetujui</span>',
                    'rejected' => '<span class="badge bg-danger">Ditolak</span>'
                ];
                return $labels[$mutasi->status];
            })
            ->addColumn('action', function ($mutasi) {
                $buttons = '<button type="button" class="btn btn-sm btn-info btn-detail" data-id="'.$mutasi->id.'" title="Detail"><i class="fas fa-eye"></i></button>';

                // Approve / Reject for pending
                if ($mutasi->status === 'pending') {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-success btn-approve" data-id="'.$mutasi->id.'" title="Setujui"><i class="fas fa-check"></i></button>';
                    $buttons .= ' <button type="button" class="btn btn-sm btn-danger btn-reject" data-id="'.$mutasi->id.'" title="Tolak"><i class="fas fa-times"></i></button>';
                }

                // Print button (open PDF in new tab)
                $printUrl = route('erm.mutasi-gudang.print', $mutasi->id);
                $buttons .= ' <a href="'.$printUrl.'" class="btn btn-sm btn-secondary" target="_blank" title="Cetak"><i class="fas fa-print"></i></a>';

                return $buttons;
            })
            ->rawColumns(['status_label', 'action'])
            ->make(true);
    }

    /**
     * Print mutasi as PDF
     */
    public function print($id)
    {
        $mutasi = MutasiGudang::with(['items.obat', 'obat', 'gudangAsal', 'gudangTujuan', 'requestedBy', 'approvedBy'])
            ->findOrFail($id);

        // Prepare stock before/after per item
        $items = $mutasi->items;
        foreach ($items as $item) {
            $stokAsalNow = \App\Models\ERM\ObatStokGudang::where('obat_id', $item->obat_id)
                ->where('gudang_id', $mutasi->gudang_asal_id)
                ->sum('stok');

            $stokTujuanNow = \App\Models\ERM\ObatStokGudang::where('obat_id', $item->obat_id)
                ->where('gudang_id', $mutasi->gudang_tujuan_id)
                ->sum('stok');

            if ($mutasi->status === 'approved') {
                // If already approved, the current values reflect post-mutation state
                $item->stok_asal_setelah = $stokAsalNow;
                $item->stok_asal_sebelum = $stokAsalNow + $item->jumlah;

                $item->stok_tujuan_setelah = $stokTujuanNow;
                $item->stok_tujuan_sebelum = max(0, $stokTujuanNow - $item->jumlah);
            } else {
                // Pending/rejected: show current as 'sebelum' and predicted 'setelah'
                $item->stok_asal_sebelum = $stokAsalNow;
                $item->stok_asal_setelah = max(0, $stokAsalNow - $item->jumlah);

                $item->stok_tujuan_sebelum = $stokTujuanNow;
                $item->stok_tujuan_setelah = $stokTujuanNow + $item->jumlah;
            }
        }

        $pdf = \PDF::loadView('erm.mutasi-gudang.print', compact('mutasi'))
            ->setPaper('a4', 'landscape');

        $filename = 'mutasi-' . ($mutasi->nomor_mutasi ?: $mutasi->id) . '.pdf';
        return $pdf->stream($filename);
    }

    public function store(Request $request)
    {
        // Support multiple items: items = [{obat_id, jumlah, keterangan}, ...]
        $rules = [
            'gudang_asal_id' => 'required|exists:erm_gudang,id',
            'gudang_tujuan_id' => 'required|exists:erm_gudang,id|different:gudang_asal_id',
        ];

        if ($request->has('items')) {
            $rules['items'] = 'required|array|min:1';
            $rules['items.*.obat_id'] = 'required|exists:erm_obat,id';
            $rules['items.*.jumlah'] = 'required|integer|min:1';
            $rules['items.*.keterangan'] = 'nullable|string|max:255';
        } else {
            $rules['obat_id'] = 'required|exists:erm_obat,id';
            $rules['jumlah'] = 'required|integer|min:1';
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            // If multiple items, validate stok per item (only check total available in gudang)
            $items = [];
            if ($request->has('items')) {
                $items = $request->items;
                // Validate stock per obat in gudang asal
                foreach ($items as $it) {
                    $stok = \App\Models\ERM\ObatStokGudang::where('obat_id', $it['obat_id'])
                        ->where('gudang_id', $request->gudang_asal_id)
                        ->sum('stok');
                    if ($stok < $it['jumlah']) {
                        throw new \Exception("Stok tidak mencukupi untuk obat ID {$it['obat_id']}");
                    }
                }
            } else {
                // Single item fallback
                $items = [[
                    'obat_id' => $request->obat_id,
                    'jumlah' => $request->jumlah,
                    'keterangan' => $request->keterangan ?? null
                ]];
            }

            // Create mutasi
            // If multiple items provided, do NOT populate parent obat_id/jumlah — keep those fields null
            $mutasiData = [
                'nomor_mutasi' => 'MUT-' . date('YmdHis'),
                'gudang_asal_id' => $request->gudang_asal_id,
                'gudang_tujuan_id' => $request->gudang_tujuan_id,
                'keterangan' => $request->keterangan ?? null,
                'status' => 'pending',
                'requested_by' => Auth::id()
            ];

            // If only one item, you may optionally set parent obat_id/jumlah; but to respect user's request
            // we keep parent fields null when items array is supplied.
            $mutasi = MutasiGudang::create($mutasiData);

            // Create related items
            foreach ($items as $it) {
                \App\Models\ERM\MutasiGudangItem::create([
                    'mutasi_id' => $mutasi->id,
                    'obat_id' => $it['obat_id'],
                    'jumlah' => $it['jumlah'],
                    'keterangan' => $it['keterangan'] ?? null
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mutasi berhasil dibuat'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function show($id)
    {
        $mutasi = MutasiGudang::with(['items.obat', 'obat', 'gudangAsal', 'gudangTujuan', 'requestedBy', 'approvedBy'])
            ->findOrFail($id);
        
        $html = view('erm.mutasi-gudang._detail', compact('mutasi'))->render();
        
        return response()->json([
            'html' => $html,
            'status' => $mutasi->status,
            'can_approve' => true
        ]);
    }

    public function approve($id)
    {
        try {
            DB::beginTransaction();

            $mutasi = MutasiGudang::findOrFail($id);
            if ($mutasi->status !== 'pending') {
                throw new \Exception('Mutasi sudah diproses');
            }


            // Pakai StokService untuk mutasi stok per batch (FIFO)
            $stokService = app(\App\Services\ERM\StokService::class);

            // Jika ada items terkait, proses per item
            $items = $mutasi->items()->get();
            if ($items->isEmpty()) {
                // Fallback to legacy single-item behavior
                $items = collect([
                    (object) [
                        'obat_id' => $mutasi->obat_id,
                        'jumlah' => $mutasi->jumlah,
                        'keterangan' => $mutasi->keterangan
                    ]
                ]);
            }

            foreach ($items as $item) {
                $stokGudangList = \App\Models\ERM\ObatStokGudang::where('obat_id', $item->obat_id)
                    ->where('gudang_id', $mutasi->gudang_asal_id)
                    ->where('stok', '>', 0)
                    ->orderBy('expiration_date', 'asc')
                    ->orderBy('batch', 'asc')
                    ->get();

                $jumlahMutasi = $item->jumlah;
                $stokTersedia = $stokGudangList->sum('stok');
                if ($stokTersedia < $jumlahMutasi) {
                    throw new \Exception("Stok tidak mencukupi untuk obat ID {$item->obat_id}");
                }

                foreach ($stokGudangList as $stokGudang) {
                    if ($jumlahMutasi <= 0) break;
                    $ambil = min($stokGudang->stok, $jumlahMutasi);

                    // Get gudang names for better keterangan
                    $gudangAsal = $mutasi->gudangAsal->nama;
                    $gudangTujuan = $mutasi->gudangTujuan->nama;

                    // Mutasi stok per batch, batch dan exp date ikut dipindahkan dengan referensi lengkap
                    $stokService->kurangiStok(
                        $item->obat_id,
                        $mutasi->gudang_asal_id,
                        $ambil,
                        $stokGudang->batch,
                        'mutasi_gudang',
                        $mutasi->id,
                        "Mutasi {$mutasi->nomor_mutasi} ke {$gudangTujuan}"
                    );

                    $stokService->tambahStok(
                        $item->obat_id,
                        $mutasi->gudang_tujuan_id,
                        $ambil,
                        $stokGudang->batch,
                        $stokGudang->expiration_date,
                        null, // rak
                        null, // lokasi
                        null, // hargaBeli
                        null, // hargaBeliJual
                        'mutasi_gudang',
                        $mutasi->id,
                        "Mutasi {$mutasi->nomor_mutasi} dari {$gudangAsal}"
                    );

                    $jumlahMutasi -= $ambil;
                }
            }

            // Update status mutasi
            $mutasi->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now()
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Mutasi berhasil disetujui'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function reject($id)
    {
        try {
            $mutasi = MutasiGudang::findOrFail($id);
            
            if ($mutasi->status !== 'pending') {
                throw new \Exception('Mutasi sudah diproses');
            }

            $mutasi->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mutasi berhasil ditolak'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Migrate stok from obat.stok field to specific gudang
     */
    public function migrateStokToGudang(Request $request)
    {
        $request->validate([
            'gudang_id' => 'required|exists:erm_gudang,id'
        ]);

        try {
            DB::beginTransaction();
            
            $gudangId = $request->gudang_id;
            $stokService = app(\App\Services\ERM\StokService::class);
            $migratedCount = 0;
            $totalStokMigrated = 0;
            
            // Get ALL obat (termasuk yang stok 0 atau null) untuk migrasi yang aman
            $obatList = Obat::withInactive()
                ->get();
            
            foreach ($obatList as $obat) {
                // Ambil stok dari field stok, default ke 0 jika null
                $stokToMigrate = $obat->stok ?? 0;
                
                // Generate batch name berdasarkan tanggal sekarang
                $batchName = 'MIGRATE-' . date('Ymd') . '-' . $obat->id;
                
                // Set expiration date 3 bulan dari sekarang
                $expirationDate = now()->addMonths(3)->format('Y-m-d');
                
                // Add stok ke gudang tujuan dengan batch dan ED (bahkan jika stok 0)
                // Ini memastikan semua obat memiliki record di sistem gudang
                $stokService->tambahStok(
                    $obat->id,
                    $gudangId,
                    $stokToMigrate,
                    $batchName,
                    $expirationDate,
                    $obat->hpp ?? 0,
                    $obat->hpp_jual ?? 0
                );
                
                // TIDAK reset field stok untuk safety - biarkan admin cleanup manual nanti
                // $obat->update(['stok' => 0]);
                
                $migratedCount++;
                $totalStokMigrated += $stokToMigrate;
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Berhasil migrasi SEMUA {$migratedCount} obat (dengan total stok aktual {$totalStokMigrated}) ke gudang yang dipilih. Termasuk obat dengan stok 0/null untuk keamanan data."
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal migrasi stok: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Manual cleanup field stok menjadi 0 setelah migrasi berhasil
     */
    public function cleanupFieldStok(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Get obat yang sudah ada stok di gudang tapi masih ada di field stok
            $obatToCleanup = Obat::withInactive()
                ->where('stok', '>', 0)
                ->whereHas('stokGudang', function($query) {
                    $query->where('stok', '>', 0);
                })
                ->get();
            
            $cleanupCount = 0;
            $totalStokCleaned = 0;
            
            foreach ($obatToCleanup as $obat) {
                $stokLama = $obat->stok;
                $obat->update(['stok' => 0]);
                
                $cleanupCount++;
                $totalStokCleaned += $stokLama;
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Berhasil cleanup {$cleanupCount} obat dengan total stok {$totalStokCleaned} direset ke 0"
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal cleanup field stok: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get summary of obat with stok > 0 in field stok for migration preview
     */
    public function getMigrationPreview()
    {
        try {
            // Debug: Log current user role
            Log::info('Migration Preview Request', [
                'user_id' => Auth::id(),
                'user_roles' => Auth::user() ? Auth::user()->getRoleNames() : 'Not authenticated'
            ]);
            
            $obatList = Obat::withInactive()
                ->select('id', 'nama', 'stok', 'satuan')
                ->get();
                
            $totalObat = $obatList->count();
            $totalStok = $obatList->sum('stok'); // Sum akan mengabaikan null values
            
            // Untuk preview, pisahkan obat yang ada stok dan yang tidak ada stok
            $obatWithStock = $obatList->where('stok', '>', 0);
            $obatWithoutStock = $obatList->filter(function($obat) {
                return $obat->stok <= 0 || is_null($obat->stok);
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_obat' => $totalObat,
                    'total_stok' => $totalStok,
                    'obat_with_stock_count' => $obatWithStock->count(),
                    'obat_without_stock_count' => $obatWithoutStock->count(),
                    'obat_list_preview' => $obatList->take(10), // Show first 10 for preview
                    'message' => "Akan migrasi SEMUA {$totalObat} obat ke gudang (termasuk yang stok 0/null untuk keamanan)"
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get obat aktif yang belum memiliki stok di gudang manapun
     */
    public function getObatWithoutStock(Request $request)
    {
        $search = $request->get('q');
        
        try {
            // Ambil obat aktif yang tidak ada di stok gudang manapun
            $query = Obat::whereDoesntHave('stokGudang')
                ->select('id', 'nama', 'satuan', 'kode_obat')
                ->orderBy('nama');
            
            if ($search) {
                $query->where('nama', 'like', "%$search%");
            }
            
            $obatList = $query->get();
            
            $results = $obatList->map(function($obat) {
                return [
                    'id' => $obat->id,
                    'nama' => $obat->nama,
                    'text' => $obat->nama . ($obat->kode_obat ? ' (' . $obat->kode_obat . ')' : ''),
                    'satuan' => $obat->satuan
                ];
            });
            
            return response()->json(['results' => $results]);
            
        } catch (\Exception $e) {
            return response()->json([
                'results' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get preview of all obat without stock for bulk operation
     */
    public function getBulkObatPreview(Request $request)
    {
        try {
            $obatList = Obat::whereDoesntHave('stokGudang')
                ->select('id', 'nama', 'satuan', 'kode_obat')
                ->orderBy('nama')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_obat' => $obatList->count(),
                    'obat_list_preview' => $obatList->take(10)->map(function($obat) {
                        return [
                            'nama' => $obat->nama,
                            'satuan' => $obat->satuan,
                            'kode_obat' => $obat->kode_obat
                        ];
                    }),
                    'message' => "Akan menambahkan {$obatList->count()} obat ke gudang yang dipilih"
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Store bulk obat baru - add all obat without stock to target gudang
     */
    public function storeBulkObatBaru(Request $request)
    {
        $request->validate([
            'gudang_id' => 'required|exists:erm_gudang,id',
            'jumlah' => 'required|numeric|min:0.01',
            'batch' => 'nullable|string|max:100',
            'expiration_date' => 'nullable|date|after:today',
            'rak' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            // Get all obat that don't have stock in any gudang
            $obatList = Obat::whereDoesntHave('stokGudang')
                ->select('id', 'nama', 'satuan')
                ->get();

            if ($obatList->isEmpty()) {
                throw new \Exception('Tidak ada obat yang perlu ditambahkan stoknya.');
            }

            $gudang = Gudang::findOrFail($request->gudang_id);
            $stokService = app(\App\Services\ERM\StokService::class);
            
            $batchPrefix = $request->batch ?: 'BULK-' . date('Ymd');
            $expirationDate = $request->expiration_date ? $request->expiration_date : null;
            $keterangan = 'Bulk stok awal' . ($request->keterangan ? ' - ' . $request->keterangan : '');
            
            $successCount = 0;
            $errors = [];

            foreach ($obatList as $obat) {
                try {
                    // Double-check this obat doesn't have stock (race condition safety)
                    $existingStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $obat->id)->exists();
                    if ($existingStock) {
                        $errors[] = "Obat {$obat->nama} sudah memiliki stok, dilewati";
                        continue;
                    }

                    $batch = $batchPrefix . '-' . $obat->id;
                    
                    $stokService->tambahStok(
                        $obat->id,
                        $request->gudang_id, 
                        (float) $request->jumlah,  // Cast to float to prevent string multiplication error
                        $batch,
                        $expirationDate,
                        $request->rak,
                        null, // harga beli - optional
                        $keterangan
                    );
                    
                    $successCount++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Error untuk obat {$obat->nama}: " . $e->getMessage();
                }
            }

            DB::commit();

            $message = "Berhasil menambahkan stok awal untuk {$successCount} obat ke gudang {$gudang->nama} dengan jumlah {$request->jumlah} per obat.";
            
            if (!empty($errors)) {
                $message .= "\n\nCatatan:\n- " . implode("\n- ", array_slice($errors, 0, 5)); // Show max 5 errors
                if (count($errors) > 5) {
                    $message .= "\n- Dan " . (count($errors) - 5) . " error lainnya...";
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'details' => [
                    'total_processed' => $obatList->count(),
                    'success_count' => $successCount,
                    'error_count' => count($errors)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Tambah stok baru untuk obat yang belum ada di gudang
     */
    public function storeObatBaru(Request $request)
    {
        $request->validate([
            'obat_id' => 'required|exists:erm_obat,id',
            'gudang_id' => 'required|exists:erm_gudang,id',
            'jumlah' => 'required|numeric|min:0.01',
            'batch' => 'nullable|string|max:100',
            'expiration_date' => 'nullable|date|after:today',
            'rak' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            // Cek apakah obat sudah ada stok di gudang manapun
            $existingStock = \App\Models\ERM\ObatStokGudang::where('obat_id', $request->obat_id)->exists();
            if ($existingStock) {
                throw new \Exception('Obat ini sudah memiliki stok di gudang. Gunakan fitur mutasi biasa.');
            }

            $obat = Obat::findOrFail($request->obat_id);
            $gudang = Gudang::findOrFail($request->gudang_id);

            // Gunakan StokService untuk menambah stok
            $stokService = app(\App\Services\ERM\StokService::class);
            
            $batch = $request->batch ?: 'INITIAL-' . date('Ymd') . '-' . $request->obat_id;
            $expirationDate = $request->expiration_date ? $request->expiration_date : null;

            $stokService->tambahStok(
                $request->obat_id,
                $request->gudang_id, 
                (float) $request->jumlah,  // Cast to float to prevent string multiplication error
                $batch,
                $expirationDate,
                $request->rak,
                null, // harga beli - optional
                'Stok awal obat baru' . ($request->keterangan ? ' - ' . $request->keterangan : '')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menambahkan stok awal {$request->jumlah} {$obat->satuan} untuk obat {$obat->nama} di gudang {$gudang->nama}."
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
