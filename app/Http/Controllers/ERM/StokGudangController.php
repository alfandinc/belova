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

        // Get distinct obat kategori values to populate the kategori filter
        $kategoris = Obat::select('kategori')
            ->distinct()
            ->whereNotNull('kategori')
            ->where('kategori', '<>', '')
            ->pluck('kategori')
            ->sort()
            ->values();

        return view('erm.stok-gudang.index', compact('gudangs', 'defaultGudang', 'kategoris'));
    }

    public function getData(Request $request)
    {
        // Compute aggregated totals and status in SQL so status immediately reflects min/max changes
        // Join obat and gudang tables so we can support server-side ordering by obat name / kode
        $table = (new ObatStokGudang())->getTable();
        $query = ObatStokGudang::leftJoin('erm_obat as o', 'o.id', $table . '.obat_id')
            ->leftJoin('erm_gudang as g', 'g.id', $table . '.gudang_id')
            ->select(
                $table . '.obat_id',
                $table . '.gudang_id',
                DB::raw('SUM(' . $table . '.stok) as total_stok'),
                DB::raw('MIN(' . $table . '.min_stok) as min_stok'),
                DB::raw('MAX(' . $table . '.max_stok) as max_stok'),
                // status_stok: 'minimum' if total <= min, 'maksimum' if total >= max, otherwise 'normal'
                DB::raw("CASE WHEN SUM(" . $table . ".stok) <= COALESCE(MIN(" . $table . ".min_stok),0) THEN 'minimum' WHEN SUM(" . $table . ".stok) >= COALESCE(MAX(" . $table . ".max_stok),0) THEN 'maksimum' ELSE 'normal' END as status_stok"),
                'o.nama as obat_nama',
                'o.kode_obat as obat_kode',
                'g.nama as gudang_nama'
            )
            ->groupBy($table . '.obat_id', $table . '.gudang_id', 'o.nama', 'o.kode_obat', 'g.nama');

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

        // Search obat by name or code (use joined columns)
        if ($request->search_obat) {
            $searchTerm = $request->search_obat;
            $query->where(function($q) use ($searchTerm) {
                $q->where('o.nama', 'like', "%{$searchTerm}%")
                  ->orWhere('o.kode_obat', 'like', "%{$searchTerm}%");
            });
        }

        // Apply hide_inactive filter if requested (filter on joined obat table)
        if ($request->hide_inactive == 1) {
            $query->where('o.status_aktif', 1);
        }

        // Apply kategori filter if provided (filter on joined obat table)
        if ($request->kategori) {
            $query->where('o.kategori', $request->kategori);
        }

        return DataTables::of($query)
            ->addColumn('nama_obat', function ($row) use ($request) {
                $nama = $row->obat_nama ?? null;
                if (!$nama) {
                    return '<span class="text-danger">[Obat tidak ditemukan - ID: '.$row->obat_id.']</span>';
                }

                // Append badge for inactive if not hiding inactive
                if ($request->hide_inactive != 1 && isset($row->status_aktif) && $row->status_aktif == 0) {
                    $nama .= ' <span class="badge badge-warning">Tidak Aktif</span>';
                }

                $link = '<a href="#" class="show-batch-details" data-obat-id="'.$row->obat_id.'" data-gudang-id="'.$row->gudang_id.'">' . $nama . '</a>';
                return $link;
            })
            ->addColumn('kode_obat', function ($row) use ($request) {
                return $row->obat_kode ?? '-';
            })
            ->addColumn('nama_gudang', function ($row) {
                return $row->gudang->nama ?? '-';
            })
            ->addColumn('nilai_stok', function ($row) use ($request) {
                // If HPP is available via join this would be used; otherwise fallback to zero
                $hpp = isset($row->hpp) ? $row->hpp : 0;
                if (empty($hpp)) {
                    $nilai = 0;
                } else {
                    $nilai = ($row->total_stok ?? 0) * $hpp;
                }
                return 'Rp ' . number_format($nilai, 0, ',', '.');
            })
            ->addColumn('actions', function ($row) use ($request) {
                // Render the Kartu Stok button in the actions column
                // Use obat id and gudang id present in the aggregated row
                $obatId = $row->obat_id;
                $gudangId = $row->gudang_id;
                if (!$obatId) return '';

                $btn = '<div class="text-center"><button class="btn btn-sm btn-primary btn-kartu-stok" data-obat-id="'.$obatId.'" data-gudang-id="'.$gudangId.'">Kartu Stok</button></div>';
                return $btn;
            })
            ->addColumn('status_stok', function ($row) {
                // status_stok is computed in the SQL select
                $status = $row->status_stok ?? 'normal';
                $min = isset($row->min_stok) ? $row->min_stok : 0;
                $max = isset($row->max_stok) ? $row->max_stok : 0;
                // Render badge as clickable element that opens the Edit Min/Max modal via existing JS handler
                if ($status === 'minimum') return '<span style="cursor:pointer" class="badge badge-danger btn-edit-minmax" data-obat-id="'.$row->obat_id.'" data-gudang-id="'.$row->gudang_id.'" data-min="'.($min).'" data-max="'.($max).'">Stok Minimum</span>';
                if ($status === 'maksimum') return '<span style="cursor:pointer" class="badge badge-warning btn-edit-minmax" data-obat-id="'.$row->obat_id.'" data-gudang-id="'.$row->gudang_id.'" data-min="'.($min).'" data-max="'.($max).'">Stok Maksimum</span>';
                return '<span style="cursor:pointer" class="badge badge-success btn-edit-minmax" data-obat-id="'.$row->obat_id.'" data-gudang-id="'.$row->gudang_id.'" data-min="'.($min).'" data-max="'.($max).'">Normal</span>';
            })
            ->editColumn('total_stok', function ($row) {
                return number_format($row->total_stok, 2);
            })
            ->filterColumn('status_stok', function($query, $keyword) {
                // Custom filter untuk status stok akan dihandle di client side
            })
            // Allow ordering by obat name and kode via the joined columns
            ->orderColumn('nama_obat', 'o.nama $1')
            ->orderColumn('kode_obat', 'o.kode_obat $1')
            ->orderColumn('total_stok', 'total_stok $1')
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
            'stok' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string'
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
            // Use keterangan provided by user when present; otherwise fallback to generated message
            $keteranganUser = $request->input('keterangan');
            $generatedKeterangan = "Edit stok batch {$stokGudang->batch}: {$stokLama} → {$stokBaru}";

            if ($selisih != 0) {
                // Require keterangan when there is a change
                if (empty($keteranganUser) || trim($keteranganUser) === '') {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Keterangan wajib diisi saat mengubah stok.'
                    ], 422);
                }

                \App\Models\ERM\KartuStok::create([
                    'obat_id' => $stokGudang->obat_id,
                    'gudang_id' => $stokGudang->gudang_id,
                    'tanggal' => now(),
                    'tipe' => $selisih > 0 ? 'masuk' : 'keluar', // Sesuaikan enum
                    'qty' => abs($selisih),
                    'stok_setelah' => $stokBaru,
                    'keterangan' => trim($keteranganUser) !== '' ? $keteranganUser : $generatedKeterangan,
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
