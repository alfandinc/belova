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
        $query = MutasiGudang::with(['obat', 'gudangAsal', 'gudangTujuan', 'requestedBy', 'approvedBy']);

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
                return $mutasi->obat->nama;
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
                $buttons = '<button type="button" class="btn btn-sm btn-info btn-detail" data-id="'.$mutasi->id.'"><i class="fas fa-eye"></i></button>';
                
                if ($mutasi->status === 'pending') {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-success btn-approve" data-id="'.$mutasi->id.'"><i class="fas fa-check"></i></button>';
                    $buttons .= ' <button type="button" class="btn btn-sm btn-danger btn-reject" data-id="'.$mutasi->id.'"><i class="fas fa-times"></i></button>';
                }
                
                return $buttons;
            })
            ->rawColumns(['status_label', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'gudang_asal_id' => 'required|exists:erm_gudang,id',
            'gudang_tujuan_id' => 'required|exists:erm_gudang,id|different:gudang_asal_id',
            'obat_id' => 'required|exists:erm_obat,id',
            'jumlah' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            // Check total stok di gudang asal
            $totalStok = \App\Models\ERM\ObatStokGudang::where('obat_id', $request->obat_id)
                ->where('gudang_id', $request->gudang_asal_id)
                ->sum('stok');
            if ($totalStok < $request->jumlah) {
                throw new \Exception('Stok tidak mencukupi');
            }

            // Create mutasi, stok belum diproses
            $mutasi = MutasiGudang::create([
                'nomor_mutasi' => 'MUT-' . date('YmdHis'),
                'gudang_asal_id' => $request->gudang_asal_id,
                'gudang_tujuan_id' => $request->gudang_tujuan_id,
                'obat_id' => $request->obat_id,
                'jumlah' => $request->jumlah,
                'keterangan' => $request->keterangan,
                'status' => 'pending',
                'requested_by' => Auth::id()
            ]);

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
        $mutasi = MutasiGudang::with(['obat', 'gudangAsal', 'gudangTujuan', 'requestedBy', 'approvedBy'])
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
            $stokGudangList = \App\Models\ERM\ObatStokGudang::where('obat_id', $mutasi->obat_id)
                ->where('gudang_id', $mutasi->gudang_asal_id)
                ->where('stok', '>', 0)
                ->orderBy('expiration_date', 'asc')
                ->orderBy('batch', 'asc')
                ->get();

            $jumlahMutasi = $mutasi->jumlah;
            $stokTersedia = $stokGudangList->sum('stok');
            if ($stokTersedia < $jumlahMutasi) {
                throw new \Exception('Stok tidak mencukupi');
            }

            foreach ($stokGudangList as $stokGudang) {
                if ($jumlahMutasi <= 0) break;
                $ambil = min($stokGudang->stok, $jumlahMutasi);
                
                // Get gudang names for better keterangan
                $gudangAsal = $mutasi->gudangAsal->nama;
                $gudangTujuan = $mutasi->gudangTujuan->nama;
                
                // Mutasi stok per batch, batch dan exp date ikut dipindahkan dengan referensi lengkap
                $stokService->kurangiStok(
                    $mutasi->obat_id, 
                    $mutasi->gudang_asal_id, 
                    $ambil, 
                    $stokGudang->batch,
                    'mutasi_gudang',
                    $mutasi->id,
                    "Mutasi {$mutasi->nomor_mutasi} ke {$gudangTujuan}"
                );
                
                $stokService->tambahStok(
                    $mutasi->obat_id, 
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
}
