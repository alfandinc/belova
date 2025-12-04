<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\ObatStokGudang;
use App\Models\ERM\Obat;
use App\Models\ERM\Gudang;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Maatwebsite\Excel\Excel;
use App\Exports\StokGudangExport;

class StokGudangController extends Controller {
    // AJAX: Get nilai stok gudang dan keseluruhan
    public function getNilaiStok(Request $request)
    {
        $gudangId = $request->gudang_id;
        $stokService = app(\App\Services\ERM\StokService::class);
        $nilaiGudang = $gudangId ? $stokService->getNilaiStokGudang($gudangId) : 0;
        $nilaiKeseluruhan = $stokService->getNilaiStokKeseluruhan();
        return response()->json([
            'nilai_gudang' => $nilaiGudang,
            'nilai_keseluruhan' => $nilaiKeseluruhan
        ]);
    }
    public function index()
    {
        $gudangs = Gudang::all();
        $defaultGudang = $gudangs->first(); // Get first warehouse as default
        return view('erm.stok-gudang.index', compact('gudangs', 'defaultGudang'));
    }

    public function getData(Request $request)
    {
        // Determine which relation to use based on hide_inactive filter
        $obatRelation = ($request->hide_inactive == 1) ? 'obatAktif' : 'obat';
        
        // Compute aggregated totals and status in SQL so status immediately reflects min/max changes
        $query = ObatStokGudang::with([$obatRelation, 'gudang'])
            ->select(
                'obat_id',
                'gudang_id',
                DB::raw('SUM(stok) as total_stok'),
                DB::raw('MIN(min_stok) as min_stok'),
                DB::raw('MAX(max_stok) as max_stok'),
                // status_stok: 'minimum' if total <= min, 'maksimum' if total >= max, otherwise 'normal'
                DB::raw("CASE WHEN SUM(stok) <= COALESCE(MIN(min_stok),0) THEN 'minimum' WHEN SUM(stok) >= COALESCE(MAX(max_stok),0) THEN 'maksimum' ELSE 'normal' END as status_stok")
            )
            ->groupBy('obat_id', 'gudang_id');

        // Filter by gudang
        if ($request->gudang_id) {
            $query->where('gudang_id', $request->gudang_id);
        } else {
            // If no gudang selected, use the first one
            $defaultGudang = Gudang::first();
            if ($defaultGudang) {
                $query->where('gudang_id', $defaultGudang->id);
            }
        }

        // Search obat by name or code
        if ($request->search_obat) {
            $searchTerm = $request->search_obat;
            $query->whereHas('obat', function($q) use ($searchTerm, $request) {
                if ($request->hide_inactive == 1) {
                    // Default behavior - only show active obat
                    $q->where('status_aktif', 1);
                } else {
                    // Include inactive obat
                    $q->withInactive();
                }
                $q->where('nama', 'like', "%{$searchTerm}%")
                  ->orWhere('kode_obat', 'like', "%{$searchTerm}%");
            });
        } else {
            // Apply hide_inactive filter even when no search
            if ($request->hide_inactive == 1) {
                $query->whereHas('obat', function($q) {
                    $q->where('status_aktif', 1);
                });
            }
        }

        return DataTables::of($query)
            ->addColumn('nama_obat', function ($row) use ($request) {
                // Use the appropriate relation based on filter
                $obat = ($request->hide_inactive == 1) ? $row->obatAktif : $row->obat;
                
                if (!$obat) {
                    if ($request->hide_inactive == 1) {
                        return '<span class="text-muted">[Obat tidak aktif - disembunyikan]</span>';
                    } else {
                        return '<span class="text-danger">[Obat tidak ditemukan - ID: '.$row->obat_id.']</span>';
                    }
                }
                
                $nama = $obat->nama ?? '-';
                
                // Tambahkan badge untuk status obat (hanya jika menampilkan semua obat)
                if ($request->hide_inactive != 1) {
                    if ($obat->status_aktif == 0) {
                        $nama .= ' <span class="badge badge-warning">Tidak Aktif</span>';
                    } else {
                        $nama .= ' <span class="badge badge-success">Aktif</span>';
                    }
                }
                
                return $nama;
            })
            ->addColumn('kode_obat', function ($row) use ($request) {
                // Use the appropriate relation based on filter
                $obat = ($request->hide_inactive == 1) ? $row->obatAktif : $row->obat;
                
                if (!$obat) {
                    return '<span class="text-muted">-</span>';
                }
                
                return $obat->kode_obat ?? '-';
            })
            ->addColumn('nama_gudang', function ($row) {
                return $row->gudang->nama ?? '-';
            })
            ->addColumn('nilai_stok', function ($row) use ($request) {
                // Calculate nilai stok per row using master cost (hpp)
                $obat = ($request->hide_inactive == 1) ? $row->obatAktif : $row->obat;
                $hpp = $obat ? ($obat->hpp ?? 0) : 0;
                $nilai = ($row->total_stok ?? 0) * $hpp;
                return 'Rp ' . number_format($nilai, 0, ',', '.');
            })
            ->addColumn('actions', function ($row) use ($request) {
                // Use the appropriate relation based on filter
                $obat = ($request->hide_inactive == 1) ? $row->obatAktif : $row->obat;
                
                if (!$obat) {
                    return '<button class="btn btn-sm btn-secondary" disabled>
                        <i class="fas fa-ban"></i> Obat tidak tersedia
                    </button>';
                }
                
                // Detail batch button + Edit Min/Max button
                $html  = '<button class="btn btn-sm btn-info show-batch-details mr-1" data-obat-id="'.$row->obat_id.'" data-gudang-id="'.$row->gudang_id.'">';
                $html .= '<i class="fas fa-list"></i> Detail Batch</button>';
                $html .= '<button class="btn btn-sm btn-warning btn-edit-minmax" data-obat-id="'.$row->obat_id.'" data-gudang-id="'.$row->gudang_id.'" data-min="'.($row->min_stok ?? 0).'" data-max="'.($row->max_stok ?? 0).'">';
                $html .= '<i class="fas fa-edit"></i> Edit Min/Max</button>';

                return $html;
            })
            ->addColumn('status_stok', function ($row) {
                // status_stok is computed in the SQL select
                $status = $row->status_stok ?? 'normal';
                if ($status === 'minimum') return '<span class="badge badge-danger">Stok Minimum</span>';
                if ($status === 'maksimum') return '<span class="badge badge-warning">Stok Maksimum</span>';
                return '<span class="badge badge-success">Normal</span>';
            })
            ->editColumn('total_stok', function ($row) {
                return number_format($row->total_stok, 2);
            })
            ->filterColumn('status_stok', function($query, $keyword) {
                // Custom filter untuk status stok akan dihandle di client side
            })
            ->rawColumns(['nama_obat', 'kode_obat', 'status_stok', 'actions'])
            ->make(true);
    }

    public function getBatchDetails(Request $request)
    {
        $obatId = $request->obat_id;
        $gudangId = $request->gudang_id;

        $stokGudang = ObatStokGudang::with(['obat', 'gudang'])
            ->where('obat_id', $obatId)
            ->where('gudang_id', $gudangId)
            ->get();

        $details = $stokGudang->map(function ($item) {
            return [
                'id' => $item->id,
                'batch' => $item->batch,
                'stok' => $item->stok, // Raw value for editing
                // Use Indonesian formatting: dot thousands separator and comma decimal
                'stok_display' => number_format($item->stok, 2, ',', '.'), // Formatted for display (2 decimals)
                'expiration_date' => $item->expiration_date ? Carbon::parse($item->expiration_date)->format('d-m-Y') : '-',
                'expiration_date_raw' => $item->expiration_date ? Carbon::parse($item->expiration_date)->format('Y-m-d') : '',
                'status' => $this->getExpirationStatus($item->expiration_date)
            ];
        });

        $first = $stokGudang->first();
        
        return response()->json([
            'data' => $details,
            'obat' => $first ? $first->obat->nama : '',
            'gudang' => $first ? $first->gudang->nama : ''
        ]);
    }

    private function getExpirationStatus($date)
    {
        if (!$date) {
            return '<span class="badge badge-secondary">Tidak Ada Tanggal</span>';
        }
        
        $expDate = Carbon::parse($date);
        $now = Carbon::now();
        
        if ($expDate->isPast()) {
            return '<span class="badge badge-danger">Expired</span>';
        }
        
        if ($expDate->diffInMonths($now) <= 3) {
            return '<span class="badge badge-warning">Hampir Expired</span>';
        }
        
        return '<span class="badge badge-success">Aman</span>';
    }

    public function updateBatchStok(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:erm_obat_stok_gudang,id',
            'stok' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            $stokGudang = ObatStokGudang::findOrFail($request->id);
            $stokLama = $stokGudang->stok;
            $stokBaru = $request->stok;
            
            // Update stok
            $stokGudang->update(['stok' => $stokBaru]);
            
            // Log perubahan ke kartu stok
            $selisih = $stokBaru - $stokLama;
            $keterangan = "Edit stok batch {$stokGudang->batch}: {$stokLama} → {$stokBaru}";
            
            if ($selisih != 0) {
                \App\Models\ERM\KartuStok::create([
                    'obat_id' => $stokGudang->obat_id,
                    'gudang_id' => $stokGudang->gudang_id,
                    'tanggal' => now(),
                    'tipe' => $selisih > 0 ? 'masuk' : 'keluar', // Sesuaikan enum
                    'qty' => abs($selisih),
                    'stok_setelah' => $stokBaru,
                    'keterangan' => $keterangan,
                    'batch' => $stokGudang->batch,
                    'ref_type' => 'manual_edit',
                    'ref_id' => $stokGudang->id
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stok batch berhasil diupdate',
                'data' => [
                    'stok_lama' => $stokLama,
                    'stok_baru' => $stokBaru,
                    'selisih' => $selisih
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal update stok batch: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update min_stok and max_stok for obat in a gudang (applies to all batches)
     */
    public function updateMinMax(Request $request)
    {
        $request->validate([
            // Model tables are named erm_obat and erm_gudang
            'obat_id' => 'required|integer|exists:erm_obat,id',
            'gudang_id' => 'required|integer|exists:erm_gudang,id',
            'min_stok' => 'nullable|numeric|min:0',
            'max_stok' => 'nullable|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            $obatId = $request->obat_id;
            $gudangId = $request->gudang_id;
            $min = $request->min_stok !== null ? $request->min_stok : 0;
            $max = $request->max_stok !== null ? $request->max_stok : 0;

            // Update all batch records for this obat/gudang so aggregated view reflects new min/max
            ObatStokGudang::where('obat_id', $obatId)
                ->where('gudang_id', $gudangId)
                ->update([
                    'min_stok' => $min,
                    'max_stok' => $max
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Min/Max stok berhasil disimpan',
                'data' => [
                    'obat_id' => $obatId,
                    'gudang_id' => $gudangId,
                    'min_stok' => $min,
                    'max_stok' => $max
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan Min/Max stok: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Export stok gudang to Excel
     */
    public function exportToExcel(Request $request)
    {
        // Determine which relation to use based on hide_inactive filter
        $obatRelation = ($request->hide_inactive == 1) ? 'obatAktif' : 'obat';

        $query = ObatStokGudang::with([$obatRelation, 'gudang'])
            ->select(
                'obat_id',
                'gudang_id',
                DB::raw('SUM(stok) as total_stok'),
                DB::raw('MIN(min_stok) as min_stok'),
                DB::raw('MAX(max_stok) as max_stok')
            )
            ->groupBy('obat_id', 'gudang_id');

        if ($request->gudang_id) {
            $query->where('gudang_id', $request->gudang_id);
        }

        // Apply hide inactive filter
        if ($request->hide_inactive == 1) {
            $query->whereHas('obat', function($q) {
                $q->where('status_aktif', 1);
            });
        }

        // Apply search
        if ($request->search_obat) {
            $searchTerm = $request->search_obat;
            $query->whereHas('obat', function($q) use ($searchTerm, $request) {
                if ($request->hide_inactive == 1) {
                    $q->where('status_aktif', 1);
                } else {
                    $q->withInactive();
                }
                $q->where('nama', 'like', "%{$searchTerm}%")
                  ->orWhere('kode_obat', 'like', "%{$searchTerm}%");
            });
        }

        $rows = $query->get();

        // Map rows into exportable collection
        $exportRows = collect();

        foreach ($rows as $row) {
            $obat = ($request->hide_inactive == 1) ? $row->obatAktif : $row->obat;
            $nama = $obat->nama ?? '-';
            $totalStok = $row->total_stok ?? 0;
            $hpp = $obat ? ($obat->hpp ?? 0) : 0;
            $hppJual = $obat ? ($obat->hpp_jual ?? 0) : 0;
            $kategori = $obat ? ($obat->kategori ?? '-') : '-';
            $nilaiStok = $totalStok * $hpp;
            $namaGudang = $row->gudang->nama ?? '-';

            $exportRows->push([
                $nama,
                (float) $totalStok,
                $hpp,
                $hppJual,
                $kategori,
                $nilaiStok,
                $namaGudang
            ]);
        }

        $export = new StokGudangExport($exportRows);

        $fileName = 'stok_gudang_' . now()->format('Ymd_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
    }
}
