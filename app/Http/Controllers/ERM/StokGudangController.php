<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\ObatStokGudang;
use App\Models\ERM\Obat;
use App\Models\ERM\Gudang;
use App\Models\ERM\StokOpname;
use App\Models\ERM\KartuStok;
use App\Models\ERM\StokOpnameItem;
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

        // Provide recent stok opname records for download modal
        $stokOpnames = StokOpname::orderBy('tanggal_opname', 'desc')->get();

        return view('erm.stok-gudang.index', compact('gudangs', 'defaultGudang', 'kategoris', 'stokOpnames'));
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
                'o.satuan as obat_satuan',
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

                $btn = '<div class="text-center">';
                $btn .= '<button class="btn btn-sm btn-primary btn-kartu-stok mr-1" data-obat-id="'.$obatId.'" data-gudang-id="'.$gudangId.'" title="Kartu Stok"><i class="fas fa-book-open"></i></button>';
                // Icon-only trash button with tooltip/title
                $btn .= '<button class="btn btn-sm btn-danger btn-delete-stok" data-obat-id="'.$obatId.'" data-gudang-id="'.$gudangId.'" title="Hapus Stok"><i class="fas fa-trash"></i></button>';
                $btn .= '</div>';
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
                $unit = isset($row->obat_satuan) && $row->obat_satuan ? trim($row->obat_satuan) : '';
                if ($unit !== '') {
                    $unit = function_exists('mb_strtolower') ? mb_strtolower($unit) : strtolower($unit);
                }
                $formatted = number_format($row->total_stok, 2);
                return $unit !== '' ? ($formatted . ' ' . e($unit)) : $formatted;
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
                'stok_display' => (function() use ($item) {
                    $formatted = number_format($item->stok, 2, ',', '.');
                    $unit = '';
                    try {
                        if ($item->obat && $item->obat->satuan) {
                            $unitRaw = trim($item->obat->satuan);
                            $unit = function_exists('mb_strtolower') ? mb_strtolower($unitRaw) : strtolower($unitRaw);
                        }
                    } catch (\Exception $e) {
                        $unit = '';
                    }
                    return $unit !== '' ? ($formatted . ' ' . e($unit)) : $formatted;
                })(), // Formatted for display (2 decimals) with lowercase unit
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
                          'ref_id' => $stokGudang->id,
                          'user_id' => Auth::id()
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
     * Update expiration date for a batch (obat_stok_gudang)
     */
    public function updateBatchExpiration(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:erm_obat_stok_gudang,id',
            'expiration_date' => 'nullable|date_format:Y-m-d'
        ]);

        try {
            $stokGudang = ObatStokGudang::findOrFail($request->id);
            $old = $stokGudang->expiration_date ? $stokGudang->expiration_date->format('Y-m-d') : null;
            $new = $request->expiration_date ?: null;
            $stokGudang->expiration_date = $new;
            $stokGudang->save();

            return response()->json([
                'success' => true,
                'message' => 'Tanggal kadaluarsa berhasil disimpan',
                'data' => [
                    'expiration_date_raw' => $stokGudang->expiration_date ? $stokGudang->expiration_date->format('Y-m-d') : '',
                    'expiration_date' => $stokGudang->expiration_date ? $stokGudang->expiration_date->format('d/m/Y') : '-'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan tanggal kadaluarsa: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Delete (zero-out) all stok for an obat in a gudang and log kartu stok entries.
     */
    public function deleteObatFromGudang(Request $request)
    {
        $request->validate([
            'obat_id' => 'required|integer|exists:erm_obat,id',
            'gudang_id' => 'required|integer|exists:erm_gudang,id'
        ]);

        try {
            DB::beginTransaction();

            $obatId = $request->obat_id;
            $gudangId = $request->gudang_id;

            $stokRows = ObatStokGudang::where('obat_id', $obatId)
                ->where('gudang_id', $gudangId)
                ->get();

            if ($stokRows->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Tidak ada stok untuk obat/gudang tersebut'], 404);
            }

            $userId = Auth::id();

            foreach ($stokRows as $row) {
                $old = (float) $row->stok;

                // If there was stock, create a kartu stok record documenting the removal
                if ($old > 0) {
                    \App\Models\ERM\KartuStok::create([
                        'obat_id' => $row->obat_id,
                        'gudang_id' => $row->gudang_id,
                        'tanggal' => now(),
                        'tipe' => 'keluar',
                        'qty' => $old,
                        'stok_setelah' => 0,
                        'keterangan' => 'Penghapusan stok/record obat dari gudang oleh pengguna',
                        'batch' => $row->batch,
                        'ref_type' => 'delete_stok_gudang',
                        'ref_id' => $row->id,
                        'user_id' => $userId
                    ]);

                    // Delete the stok row (uses SoftDeletes trait) so the obat-gudang record is removed
                    try {
                        $row->delete();
                    } catch (\Exception $e) {
                        // If delete fails, fallback to zeroing stok as a best-effort
                        $row->stok = 0;
                        $row->save();
                    }
                }

            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Stok berhasil dikosongkan untuk obat di gudang ini']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menghapus stok: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export stok gudang to Excel
     */
    public function exportToExcel(Request $request)
    {
        $type = $request->input('type', '1'); // 1 = live, 2 = stok opname
        $gudangId = $request->input('gudang_id');
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');

        // Normalize date range; default for live is today
        try {
            if ($dateStart) $start = Carbon::parse($dateStart)->startOfDay();
            else $start = Carbon::today()->startOfDay();
            if ($dateEnd) $end = Carbon::parse($dateEnd)->endOfDay();
            else $end = Carbon::today()->endOfDay();
        } catch (\Exception $e) {
            $start = Carbon::today()->startOfDay();
            $end = Carbon::today()->endOfDay();
        }

        $exportRows = [];

        if ($type == '2') {
            // Export based on stok opname selection or date range
            $stokOpnameId = $request->input('stok_opname_id');

            $opnameQuery = StokOpnameItem::query()
                ->select('obat_id', DB::raw('SUM(stok_fisik) as stok_fisik'));

            if ($stokOpnameId) {
                $opnameQuery->where('stok_opname_id', $stokOpnameId);
            } else {
                $opnameQuery->whereHas('stokOpname', function($q) use ($gudangId, $start, $end) {
                    if ($gudangId) $q->where('gudang_id', $gudangId);
                    $q->whereBetween('tanggal_opname', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
                });
            }

            $opnameQuery->groupBy('obat_id');
            $items = $opnameQuery->get();

            foreach ($items as $item) {
                $obat = Obat::withInactive()->find($item->obat_id);
                $nama = $obat ? $obat->nama : ('[ID '.$item->obat_id.']');
                $totalStok = (float) $item->stok_fisik;
                $hpp = $obat ? ($obat->hpp ?? 0) : 0;

                // Kartu stok totals: if opname selected, restrict to opname date; otherwise use provided date range
                $masukQuery = KartuStok::where('obat_id', $item->obat_id)->where('tipe', 'masuk');
                $keluarQuery = KartuStok::where('obat_id', $item->obat_id)->where('tipe', 'keluar');
                if ($gudangId) {
                    $masukQuery->where('gudang_id', $gudangId);
                    $keluarQuery->where('gudang_id', $gudangId);
                }

                if ($stokOpnameId) {
                    $op = StokOpname::find($stokOpnameId);
                    if ($op) {
                        $masukQuery->whereDate('tanggal', $op->tanggal_opname);
                        $keluarQuery->whereDate('tanggal', $op->tanggal_opname);
                        $namaGudang = $op->gudang ? $op->gudang->nama : ($gudangId ? (Gudang::find($gudangId)->nama ?? '-') : '-');
                    } else {
                        $masukQuery->whereBetween('tanggal', [$start, $end]);
                        $keluarQuery->whereBetween('tanggal', [$start, $end]);
                        $namaGudang = $gudangId ? (Gudang::find($gudangId)->nama ?? '-') : '-';
                    }
                } else {
                    $masukQuery->whereBetween('tanggal', [$start, $end]);
                    $keluarQuery->whereBetween('tanggal', [$start, $end]);
                    $namaGudang = $gudangId ? (Gudang::find($gudangId)->nama ?? '-') : '-';
                }

                $masuk = $masukQuery->sum('qty');
                $keluar = $keluarQuery->sum('qty');

                $nilaiStok = round($totalStok * $hpp, 4);
                $exportRows[] = [
                    $nama,
                    $totalStok,
                    $hpp,
                    $nilaiStok,
                    (float) $masuk,
                    (float) $keluar,
                    $namaGudang
                ];
            }

        } else {
            // Live data: use current totals from ObatStokGudang
            $query = ObatStokGudang::select('obat_id', DB::raw('SUM(stok) as total_stok'))
                ->groupBy('obat_id');

            if ($gudangId) $query->where('gudang_id', $gudangId);

            $rows = $query->get();

            foreach ($rows as $row) {
                $obat = Obat::withInactive()->find($row->obat_id);
                $nama = $obat ? $obat->nama : ('[ID '.$row->obat_id.']');
                $totalStok = (float) $row->total_stok;
                $hpp = $obat ? ($obat->hpp ?? 0) : 0;

                $masuk = KartuStok::where('obat_id', $row->obat_id)
                    ->when($gudangId, function($q) use ($gudangId){ return $q->where('gudang_id', $gudangId); })
                    ->whereBetween('tanggal', [$start, $end])
                    ->where('tipe', 'masuk')
                    ->sum('qty');

                $keluar = KartuStok::where('obat_id', $row->obat_id)
                    ->when($gudangId, function($q) use ($gudangId){ return $q->where('gudang_id', $gudangId); })
                    ->whereBetween('tanggal', [$start, $end])
                    ->where('tipe', 'keluar')
                    ->sum('qty');

                $namaGudang = $gudangId ? (Gudang::find($gudangId)->nama ?? '-') : '-';

                $nilaiStok = round($totalStok * $hpp, 4);
                $exportRows[] = [
                    $nama,
                    $totalStok,
                    $hpp,
                    $nilaiStok,
                    (float) $masuk,
                    (float) $keluar,
                    $namaGudang
                ];
            }
        }

        $export = new StokGudangExport($exportRows);

        // Build descriptive filename: gudang, type, date
        $gudangName = $gudangId ? (Gudang::find($gudangId)->nama ?? 'all_gudang') : 'all_gudang';
        $typeLabel = ($type == '2') ? 'stok-opname' : 'live';
        $stokOpnameId = $request->input('stok_opname_id');

        if ($type == '2' && $stokOpnameId) {
            $op = StokOpname::find($stokOpnameId);
            if ($op && $op->tanggal_opname) {
                $dateLabel = Carbon::parse($op->tanggal_opname)->format('Ymd');
            } else {
                $dateLabel = $start->format('Ymd') . '-' . $end->format('Ymd');
            }
        } else {
            // Use provided date range or today's date for live
            $dateLabel = ($dateStart || $dateEnd) ? ($start->format('Ymd') . '-' . $end->format('Ymd')) : Carbon::now()->format('Ymd');
        }

        // Sanitize gudang name for filename
        $gudangSlug = preg_replace('/[^A-Za-z0-9_\-]/', '', str_replace(' ', '_', strtolower($gudangName)));

        $fileName = sprintf('stok_%s_%s_%s.xlsx', $gudangSlug ?: 'gudang', $typeLabel, $dateLabel);

        return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
    }
}
