<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\ObatStokGudang;
use App\Models\ERM\Obat;
use App\Models\ERM\GudangMapping;
use App\Models\ERM\Gudang;
use App\Models\ERM\StokOpname;
use App\Models\ERM\KartuStok;
use App\Models\ERM\StokOpnameItem;
use App\Models\ERM\ObatExpiredTindakLanjut;
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
        $expiredGudangId = GudangMapping::getDefaultGudangId('expired');

        // Compute aggregated totals and status in SQL so status immediately reflects min/max changes
        // Join obat and gudang tables so we can support server-side ordering by obat name / kode
        $table = (new ObatStokGudang())->getTable();
        $latestExpiredFollowUps = DB::table('erm_obat_expired_tindak_lanjut as eotl')
            ->join($table . ' as followup_stock', 'followup_stock.id', '=', 'eotl.obat_stok_gudang_id')
            ->select(
                'followup_stock.obat_id',
                'followup_stock.gudang_id',
                DB::raw('MAX(eotl.id) as latest_follow_up_id')
            )
            ->groupBy('followup_stock.obat_id', 'followup_stock.gudang_id');

        $globalTotals = DB::table($table . ' as global_stock')
            ->select(
                'global_stock.obat_id',
                DB::raw('SUM(global_stock.stok) as global_total_stok'),
                DB::raw('MIN(global_stock.min_stok) as global_min_stok'),
                DB::raw('MAX(global_stock.max_stok) as global_max_stok')
            )
            ->groupBy('global_stock.obat_id');

        $query = ObatStokGudang::leftJoin('erm_obat as o', 'o.id', $table . '.obat_id')
            ->leftJoin('erm_gudang as g', 'g.id', $table . '.gudang_id')
            ->leftJoinSub($globalTotals, 'gt', function ($join) use ($table) {
                $join->on('gt.obat_id', '=', $table . '.obat_id');
            })
            ->leftJoinSub($latestExpiredFollowUps, 'lfu', function ($join) use ($table) {
                $join->on('lfu.obat_id', '=', $table . '.obat_id')
                    ->on('lfu.gudang_id', '=', $table . '.gudang_id');
            })
            ->leftJoin('erm_obat_expired_tindak_lanjut as latest_follow_up', 'latest_follow_up.id', '=', 'lfu.latest_follow_up_id')
            ->select(
                $table . '.obat_id',
                $table . '.gudang_id',
                DB::raw('SUM(' . $table . '.stok) as total_stok'),
                DB::raw('COALESCE(gt.global_min_stok, 0) as min_stok'),
                DB::raw('COALESCE(gt.global_max_stok, 0) as max_stok'),
                DB::raw('COALESCE(gt.global_total_stok, 0) as global_total_stok'),
                // status_stok now compares against total stock across all gudang for the obat
                DB::raw("CASE WHEN COALESCE(gt.global_total_stok, 0) <= COALESCE(gt.global_min_stok,0) THEN 'minimum' WHEN COALESCE(gt.global_total_stok, 0) >= COALESCE(gt.global_max_stok,0) AND COALESCE(gt.global_max_stok,0) > 0 THEN 'maksimum' ELSE 'normal' END as status_stok"),
                'o.nama as obat_nama',
                'o.kode_obat as obat_kode',
                'o.satuan as obat_satuan',
                // Use a numeric alias for HPP (fallback to hpp_jual) for reliable calculations
                DB::raw('COALESCE(o.hpp, o.hpp_jual, 0) as hpp_val'),
                'g.nama as gudang_nama',
                'latest_follow_up.tindak_lanjut as latest_tindak_lanjut'
            )
            ->groupBy($table . '.obat_id', $table . '.gudang_id', 'o.nama', 'o.kode_obat', 'g.nama', 'latest_follow_up.tindak_lanjut');

        // Filter by gudang
        if ($request->gudang_id) {
            $query->where($table . '.gudang_id', $request->gudang_id);
        } else {
            // If no gudang selected, use the first one
            $defaultGudang = Gudang::first();
            if ($defaultGudang) {
                $query->where($table . '.gudang_id', $defaultGudang->id);
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
            ->filter(function ($q) use ($request, $table) {
                $searchValue = null;
                if ($request->has('search')) {
                    $s = $request->input('search');
                    if (is_array($s) && isset($s['value'])) $searchValue = trim($s['value']);
                    elseif (is_string($s)) $searchValue = trim($s);
                }

                if ($searchValue) {
                    $q->where(function ($sub) use ($searchValue, $table) {
                        $sub->where('o.nama', 'like', "%{$searchValue}%")
                            ->orWhere('o.kode_obat', 'like', "%{$searchValue}%")
                            ->orWhere($table . '.batch', 'like', "%{$searchValue}%");
                    });
                }
            })
            ->addColumn('nama_obat', function ($row) use ($request) {
                $nama = $row->obat_nama ?? null;
                if (!$nama) {
                    return '<span class="text-danger">[Obat tidak ditemukan - ID: '.$row->obat_id.']</span>';
                }

                // Append badge for inactive if not hiding inactive
                $badgeHtml = '';
                if ($request->hide_inactive != 1 && isset($row->status_aktif) && $row->status_aktif == 0) {
                    $badgeHtml = ' <span class="badge badge-warning">Tidak Aktif</span>';
                }

                $kode = isset($row->obat_kode) && $row->obat_kode ? e($row->obat_kode) : '-';

                // Render name as link
                $link = '<a href="#" class="show-batch-details" data-obat-id="'.$row->obat_id.'" data-gudang-id="'.$row->gudang_id.'">' . e($nama) . '</a>' . $badgeHtml;

                // Show kode under the name (status icon moved to stok column)
                $link .= '<div class="mt-1 text-muted small">' . $kode . '</div>';

                if (!empty($row->latest_tindak_lanjut)) {
                    $followUpLabel = $row->latest_tindak_lanjut === 'diretur' ? 'Diretur' : 'Dimusnahkan';
                    $followUpBadgeClass = $row->latest_tindak_lanjut === 'diretur' ? 'badge-warning' : 'badge-danger';
                    $link .= '<div class="mt-1"><span class="badge ' . $followUpBadgeClass . '">' . e($followUpLabel) . '</span></div>';
                }

                return $link;
            })
            ->addColumn('nama_gudang', function ($row) {
                return $row->gudang->nama ?? '-';
            })
            ->addColumn('hpp', function ($row) {
                $val = isset($row->hpp_val) ? $row->hpp_val : 0;
                return number_format((float)$val, 2, ',', '.');
            })
            ->addColumn('nilai_stok', function ($row) use ($request) {
                // Use the numeric hpp_val (already COALESCE'd) to compute nilai reliably
                $hpp = isset($row->hpp_val) ? (float)$row->hpp_val : 0.0;
                $total = isset($row->total_stok) ? (float)$row->total_stok : 0.0;
                $nilai = $total * $hpp;
                return 'Rp ' . number_format($nilai, 0, ',', '.');
            })
            ->addColumn('actions', function ($row) use ($request, $expiredGudangId) {
                // Render the Kartu Stok button in the actions column
                // Use obat id and gudang id present in the aggregated row
                $obatId = $row->obat_id;
                $gudangId = $row->gudang_id;
                if (!$obatId) return '';

                $user = Auth::user();
                $canDeleteStok = $user && $user->hasAnyRole(['Admin', 'admin']);
                $canFollowUpExpired = $expiredGudangId
                    && (int) $gudangId === (int) $expiredGudangId
                    && (float) $row->total_stok > 0;

                $btn = '<div class="d-flex justify-content-center flex-wrap action-buttons">';
                $btn .= '<button class="btn btn-sm btn-primary btn-kartu-stok mr-1" data-obat-id="'.$obatId.'" data-gudang-id="'.$gudangId.'" title="Kartu Stok"><i class="fas fa-book-open"></i></button>';
                if ($canFollowUpExpired) {
                    $btn .= '<button class="btn btn-sm btn-warning btn-expired-follow-up mr-1" data-obat-id="'.$obatId.'" data-gudang-id="'.$gudangId.'" data-obat-nama="'.e($row->obat_nama ?: 'Obat').'" title="Tindak Lanjut Expired"><i class="fas fa-clipboard-check"></i> Tindak Lanjut</button>';
                }
                if ($canDeleteStok) {
                    $btn .= '<button class="btn btn-sm btn-danger btn-delete-stok" data-obat-id="'.$obatId.'" data-gudang-id="'.$gudangId.'" title="Hapus Stok"><i class="fas fa-trash"></i></button>';
                }
                $btn .= '</div>';
                return $btn;
            })
            
            ->editColumn('total_stok', function ($row) {
                $unit = isset($row->obat_satuan) && $row->obat_satuan ? trim($row->obat_satuan) : '';
                if ($unit !== '') {
                    $unit = function_exists('mb_strtolower') ? mb_strtolower($unit) : strtolower($unit);
                }
                $formatted = number_format($row->total_stok, 2);

                // Build status icon (clickable) based on status_stok
                $status = isset($row->status_stok) ? $row->status_stok : 'normal';
                $min = isset($row->min_stok) ? $row->min_stok : 0;
                $max = isset($row->max_stok) ? $row->max_stok : 0;
                $commonAttrs = 'style="cursor:pointer;display:inline-block;" class="btn-edit-minmax" data-obat-id="'.$row->obat_id.'" data-gudang-id="'.$row->gudang_id.'" data-min="'.($min).'" data-max="'.($max).'"';

                if ($status === 'minimum') {
                    $statusSpan = '<span '.$commonAttrs.' title="Stok Minimum" style="line-height:0.6;">'
                        .'<i class="fas fa-caret-down text-danger" style="display:block;font-size:12px;line-height:0.6"></i>'
                        .'<i class="fas fa-caret-down text-danger" style="display:block;font-size:12px;margin-top:-4px;line-height:0.6"></i>'
                        .'</span>';
                } else if ($status === 'maksimum') {
                    $statusSpan = '<span '.$commonAttrs.' title="Stok Maksimum" style="line-height:0.6;">'
                        .'<i class="fas fa-caret-up text-warning" style="display:block;font-size:12px;line-height:0.6"></i>'
                        .'<i class="fas fa-caret-up text-warning" style="display:block;font-size:12px;margin-top:-4px;line-height:0.6"></i>'
                        .'</span>';
                } else {
                    $statusSpan = '<span '.$commonAttrs.' title="Stok Normal" style="line-height:0.6;">'
                        .'<i class="fas fa-caret-up text-success" style="display:block;font-size:10px;line-height:0.6"></i>'
                        .'<i class="fas fa-caret-down text-success" style="display:block;font-size:10px;margin-top:-4px;line-height:0.6"></i>'
                        .'</span>';
                }

                $unitDisplay = $unit !== '' ? ' ' . e($unit) : '';
                // Make the stok text itself a clickable trigger for editing min/max
                $html = '<div style="display:flex;align-items:center;justify-content:flex-end">'
                    .'<span '.$commonAttrs.'><strong>' . $formatted . $unitDisplay . '</strong></span>'
                    .'<span style="margin-left:8px">' . $statusSpan . '</span>'
                    .'</div>';

                return $html;
            })
            ->filterColumn('status_stok', function($query, $keyword) {
                // Custom filter untuk status stok akan dihandle di client side
            })
            // Allow ordering by obat name and kode via the joined columns
            ->orderColumn('nama_obat', 'o.nama $1')
            ->orderColumn('kode_obat', 'o.kode_obat $1')
            ->orderColumn('total_stok', 'total_stok $1')
            // Allow ordering by HPP (use COALESCE to mirror displayed hpp_val)
            ->orderColumn('hpp', function($query, $direction) use ($table) {
                $query->orderBy(DB::raw('COALESCE(o.hpp, o.hpp_jual, 0)'), $direction);
            })
            // Allow ordering by Nilai Stok (total_stok * hpp_val)
            ->orderColumn('nilai_stok', function($query, $direction) use ($table) {
                $expr = DB::raw('SUM(' . $table . '.stok) * COALESCE(o.hpp, o.hpp_jual, 0)');
                $query->orderBy($expr, $direction);
            })
            ->rawColumns(['nama_obat', 'actions', 'hpp', 'total_stok'])
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
     * Update min/max stok for an obat in a gudang (called from modal)
     */
    public function updateMinMax(Request $request)
    {
        $request->validate([
            'obat_id' => 'required|integer|exists:erm_obat,id',
            'gudang_id' => 'nullable|integer|exists:erm_gudang,id',
            'min_stok' => 'nullable|numeric|min:0',
            'max_stok' => 'nullable|numeric|min:0'
        ]);

        $obatId = $request->obat_id;
        $min = $request->min_stok !== null ? $request->min_stok : 0;
        $max = $request->max_stok !== null ? $request->max_stok : 0;

        try {
            $rows = ObatStokGudang::where('obat_id', $obatId)->get();
            if ($rows->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Tidak ada baris stok untuk obat tersebut'], 404);
            }

            foreach ($rows as $r) {
                $r->min_stok = $min;
                $r->max_stok = $max;
                $r->save();
            }

            return response()->json(['success' => true, 'message' => 'Min/Max stok total semua gudang berhasil disimpan']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan min/max: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sync min/max values to obat stock rows in other gudang that still have no values.
     * Missing values are treated as both min_stok and max_stok equal to 0.
     */
    public function syncMissingMinMax()
    {
        try {
            DB::beginTransaction();

            $syncedObatCount = 0;
            $syncedRowCount = 0;
            $skippedObatCount = 0;

            $obatIds = ObatStokGudang::query()
                ->select('obat_id')
                ->distinct()
                ->pluck('obat_id');

            foreach ($obatIds as $obatId) {
                $source = ObatStokGudang::where('obat_id', $obatId)
                    ->where(function ($query) {
                        $query->where('min_stok', '>', 0)
                            ->orWhere('max_stok', '>', 0);
                    })
                    ->orderByRaw('CASE WHEN min_stok > 0 AND max_stok > 0 THEN 0 ELSE 1 END')
                    ->first();

                if (!$source) {
                    $skippedObatCount++;
                    continue;
                }

                $targetRows = ObatStokGudang::where('obat_id', $obatId)
                    ->where(function ($query) {
                        $query->where(function ($sub) {
                            $sub->where('min_stok', '=', 0)
                                ->where('max_stok', '=', 0);
                        })
                        ->orWhereNull('min_stok')
                        ->orWhereNull('max_stok');
                    })
                    ->get();

                if ($targetRows->isEmpty()) {
                    continue;
                }

                $updatedForObat = 0;
                foreach ($targetRows as $row) {
                    $row->min_stok = $source->min_stok;
                    $row->max_stok = $source->max_stok;
                    $row->save();
                    $updatedForObat++;
                }

                if ($updatedForObat > 0) {
                    $syncedObatCount++;
                    $syncedRowCount += $updatedForObat;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sync min/max selesai.',
                'synced_obat_count' => $syncedObatCount,
                'synced_row_count' => $syncedRowCount,
                'skipped_obat_count' => $skippedObatCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal sync min/max: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete (zero-out) all stok for an obat in a gudang and log kartu stok entries.
     */
    public function deleteObatFromGudang(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['Admin', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk menghapus stok.'
            ], 403);
        }

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
        $type = $request->input('type', '1'); // 1 = live, 2 = stok opname, 3 = tanggal tertentu
        $gudangId = $request->input('gudang_id');
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');
        $pivotDate = $request->input('pivot_date'); // for type 3

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
                $obat = Obat::find($item->obat_id);
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

        } else if ($type == '3') {
            // Data per tanggal tertentu: compute stok as of pivot date by reversing movements after that date
            // Determine pivot date; default to today if not provided
            try {
                $pivot = $pivotDate ? Carbon::parse($pivotDate)->endOfDay() : Carbon::today()->endOfDay();
            } catch (\Exception $e) {
                $pivot = Carbon::today()->endOfDay();
            }

            // Current live totals per obat (and optionally per gudang)
            $query = ObatStokGudang::select('obat_id', DB::raw('SUM(stok) as total_stok'))
                ->groupBy('obat_id');
            if ($gudangId) $query->where('gudang_id', $gudangId);
            $rows = $query->get();

            foreach ($rows as $row) {
                $obat = Obat::find($row->obat_id);
                $nama = $obat ? $obat->nama : ('[ID '.$row->obat_id.']');
                $liveStok = (float) $row->total_stok;
                $hpp = $obat ? ($obat->hpp ?? 0) : 0;

                // Sum kartu stok AFTER the pivot date to reverse them
                $masukAfter = KartuStok::where('obat_id', $row->obat_id)
                    ->when($gudangId, function($q) use ($gudangId){ return $q->where('gudang_id', $gudangId); })
                    ->where('tanggal', '>', $pivot)
                    ->where('tipe', 'masuk')
                    ->sum('qty');

                $keluarAfter = KartuStok::where('obat_id', $row->obat_id)
                    ->when($gudangId, function($q) use ($gudangId){ return $q->where('gudang_id', $gudangId); })
                    ->where('tanggal', '>', $pivot)
                    ->where('tipe', 'keluar')
                    ->sum('qty');

                // Reconstruct stok at pivot date: live - masukAfter + keluarAfter
                $stokAtPivot = $liveStok - (float)$masukAfter + (float)$keluarAfter;

                $namaGudang = $gudangId ? (Gudang::find($gudangId)->nama ?? '-') : '-';
                $nilaiStok = round($stokAtPivot * $hpp, 4);

                $exportRows[] = [
                    $nama,
                    (float) $stokAtPivot,
                    $hpp,
                    $nilaiStok,
                    (float) $masukAfter,
                    (float) $keluarAfter,
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
                $obat = Obat::find($row->obat_id);
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
        $typeLabel = ($type == '2') ? 'stok-opname' : (($type == '3') ? 'tanggal-tertentu' : 'live');
        $stokOpnameId = $request->input('stok_opname_id');

        if ($type == '2' && $stokOpnameId) {
            $op = StokOpname::find($stokOpnameId);
            if ($op && $op->tanggal_opname) {
                $dateLabel = Carbon::parse($op->tanggal_opname)->format('Ymd');
            } else {
                $dateLabel = $start->format('Ymd') . '-' . $end->format('Ymd');
            }
        } else if ($type == '3') {
            // Filename uses pivot date
            try {
                $dateLabel = ($pivotDate ? Carbon::parse($pivotDate)->format('Ymd') : Carbon::today()->format('Ymd'));
            } catch (\Exception $e) {
                $dateLabel = Carbon::today()->format('Ymd');
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

    /**
     * AJAX: Return list of obat where total stok per gudang is below min_stok
     */
    public function getLowStockData(Request $request)
    {
        // Aggregate across all gudang: group by obat only
        $table = (new ObatStokGudang())->getTable();

        $query = ObatStokGudang::leftJoin('erm_obat as o', 'o.id', $table . '.obat_id')
            ->select(
                $table . '.obat_id',
                DB::raw('SUM(' . $table . '.stok) as total_stok'),
                DB::raw('MIN(' . $table . '.min_stok) as min_stok'),
                'o.nama as obat_nama',
                'o.kode_obat as obat_kode',
                'o.satuan as obat_satuan'
            )
            ->groupBy($table . '.obat_id', 'o.nama', 'o.kode_obat', 'o.satuan')
            ->havingRaw('SUM(' . $table . '.stok) < COALESCE(MIN(' . $table . '.min_stok), 0)');

        if ($request->hide_inactive == 1) {
            $query->where('o.status_aktif', 1);
        }

        return DataTables::of($query)
            ->filter(function ($q) use ($request, $table) {
                $searchValue = null;
                if ($request->has('search')) {
                    $s = $request->input('search');
                    if (is_array($s) && isset($s['value'])) $searchValue = trim($s['value']);
                    elseif (is_string($s)) $searchValue = trim($s);
                }

                if ($searchValue) {
                    $q->where(function ($sub) use ($searchValue, $table) {
                        $sub->where('o.nama', 'like', "%{$searchValue}%")
                            ->orWhere('o.kode_obat', 'like', "%{$searchValue}%")
                            ->orWhere($table . '.batch', 'like', "%{$searchValue}%");
                    });
                }
            })
            ->addColumn('nama_obat', function ($row) {
                $link = '<a href="#" class="show-batch-details" data-obat-id="'.$row->obat_id.'" data-gudang-id="">' . e($row->obat_nama) . '</a>';
                $link .= '<div class="text-muted small">' . ($row->obat_kode ?: '-') . '</div>';
                return $link;
            })
            ->addColumn('total_stok', function ($row) {
                $unit = isset($row->obat_satuan) && $row->obat_satuan ? (function_exists('mb_strtolower') ? mb_strtolower($row->obat_satuan) : strtolower($row->obat_satuan)) : '';
                $formatted = number_format((float)$row->total_stok, 2, ',', '.');
                return $formatted . ($unit ? ' ' . e($unit) : '');
            })
            ->addColumn('min_stok', function ($row) {
                return number_format((float)$row->min_stok, 2, ',', '.');
            })
            ->addColumn('detail_stok_gudang', function ($row) use ($request) {
                $detailRows = ObatStokGudang::with('gudang')
                    ->select('gudang_id', DB::raw('SUM(stok) as total_stok'))
                    ->where('obat_id', $row->obat_id)
                    ->groupBy('gudang_id')
                    ->orderBy('gudang_id')
                    ->get();

                if ($detailRows->isEmpty()) {
                    return '<span class="text-muted">Tidak ada detail gudang</span>';
                }

                $unit = isset($row->obat_satuan) && $row->obat_satuan
                    ? (function_exists('mb_strtolower') ? mb_strtolower($row->obat_satuan) : strtolower($row->obat_satuan))
                    : '';

                $html = '<div class="small">';
                foreach ($detailRows as $detail) {
                    $gudangName = $detail->gudang ? e($detail->gudang->nama) : ('Gudang #' . $detail->gudang_id);
                    $qty = number_format((float) $detail->total_stok, 2, ',', '.');
                    $html .= '<div><strong>' . $gudangName . ':</strong> ' . $qty . ($unit ? ' ' . e($unit) : '') . '</div>';
                }
                $html .= '</div>';

                return $html;
            })
            ->addColumn('gudang', function ($row) {
                return 'Semua Gudang';
            })
            ->rawColumns(['nama_obat', 'detail_stok_gudang'])
            ->make(true);
    }

    /**
     * AJAX: Return count of obat where total stok per gudang is below min_stok
     */
    public function getLowStockCount(Request $request)
    {
        $table = (new ObatStokGudang())->getTable();

        // Aggregate across all gudang (group by obat)
        $query = ObatStokGudang::leftJoin('erm_obat as o', 'o.id', $table . '.obat_id')
            ->select(
                $table . '.obat_id',
                DB::raw('SUM(' . $table . '.stok) as total_stok'),
                DB::raw('MIN(' . $table . '.min_stok) as min_stok')
            )
            ->groupBy($table . '.obat_id')
            ->havingRaw('SUM(' . $table . '.stok) < COALESCE(MIN(' . $table . '.min_stok), 0)');

        if ($request->hide_inactive == 1) {
            $query->where('o.status_aktif', 1);
        }

        // Apply global search (DataTables sends search[value]) to common fields
        $searchValue = null;
        if ($request->has('search')) {
            $s = $request->input('search');
            if (is_array($s) && isset($s['value'])) $searchValue = trim($s['value']);
            elseif (is_string($s)) $searchValue = trim($s);
        }

        if ($searchValue) {
            $query->where(function($q) use ($searchValue, $table) {
                $q->where('o.nama', 'like', "%{$searchValue}%")
                  ->orWhere('o.kode_obat', 'like', "%{$searchValue}%")
                  ->orWhere($table . '.batch', 'like', "%{$searchValue}%");
            });
        }

        try {
            $count = $query->get()->count();
        } catch (\Exception $e) {
            $count = 0;
        }

        return response()->json(['count' => $count]);
    }

    /**
     * AJAX: Return list of obat where nearest expiration date is within next X months (default 6)
     */
    public function getExpiringData(Request $request)
    {
        $months = intval($request->input('months', 6));
        $threshold = Carbon::now()->addMonths($months)->endOfDay();
        $expiredGudangMapping = GudangMapping::getActiveMapping('expired');
        $expiredGudangId = $expiredGudangMapping ? $expiredGudangMapping->gudang_id : null;
        $expiredGudangName = $expiredGudangMapping && $expiredGudangMapping->gudang ? $expiredGudangMapping->gudang->nama : null;

        $table = (new ObatStokGudang())->getTable();
        $expThreshold = $threshold->format('Y-m-d H:i:s');

        $query = ObatStokGudang::leftJoin('erm_obat as o', 'o.id', $table . '.obat_id')
            ->leftJoin('erm_gudang as g', 'g.id', $table . '.gudang_id')
            ->whereNotNull($table . '.expiration_date')
            ->where($table . '.stok', '>', 0)
            ->select(
                $table . '.obat_id',
                DB::raw('SUM(' . $table . '.stok) as total_stok'),
                DB::raw('MIN(' . $table . '.expiration_date) as nearest_exp'),
                'o.nama as obat_nama',
                'o.kode_obat as obat_kode',
                'o.satuan as obat_satuan',
                DB::raw("GROUP_CONCAT(DISTINCT CASE WHEN {$table}.expiration_date IS NOT NULL AND {$table}.expiration_date <= '{$expThreshold}' THEN CONCAT({$table}.id, '||', {$table}.batch, '||', REPLACE(CAST({$table}.stok AS CHAR), '.', ','), '||', DATE_FORMAT({$table}.expiration_date, '%Y-%m-%d'), '||', COALESCE(g.nama, '-'), '||', {$table}.gudang_id) END SEPARATOR ';;') as exp_batches")
            )
            ->groupBy($table . '.obat_id', 'o.nama', 'o.kode_obat', 'o.satuan')
            ->havingRaw('MIN(' . $table . '.expiration_date) <= ?', [$expThreshold]);

        if ($expiredGudangId) {
            $query->where($table . '.gudang_id', '!=', $expiredGudangId);
        }

        if ($request->hide_inactive == 1) {
            $query->where('o.status_aktif', 1);
        }

        return DataTables::of($query)
            ->filter(function ($q) use ($request, $table) {
                $searchValue = null;
                if ($request->has('search')) {
                    $search = $request->input('search');
                    if (is_array($search) && isset($search['value'])) {
                        $searchValue = trim($search['value']);
                    } elseif (is_string($search)) {
                        $searchValue = trim($search);
                    }
                }

                if ($searchValue !== null && $searchValue !== '') {
                    $q->where(function ($sub) use ($searchValue, $table) {
                        $sub->where('o.nama', 'like', "%{$searchValue}%")
                            ->orWhere('o.kode_obat', 'like', "%{$searchValue}%")
                            ->orWhere($table . '.batch', 'like', "%{$searchValue}%")
                            ->orWhere('g.nama', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->addColumn('nama_obat', function ($row) {
                $link = '<a href="#" class="show-batch-details" data-obat-id="'.$row->obat_id.'" data-gudang-id="">' . e($row->obat_nama) . '</a>';
                $link .= '<div class="text-muted small">' . ($row->obat_kode ?: '-') . '</div>';
                return $link;
            })
            ->addColumn('exp_batches', function ($row) {
                if (empty($row->exp_batches)) return '-';
                $parts = explode(';;', $row->exp_batches);
                $items = array_filter(array_map('trim', $parts));
                if (empty($items)) return '-';

                $html = '<table class="table table-sm mb-0" style="margin-bottom:0;border-collapse:collapse;width:100%;">';
                $html .= '<tbody>';
                foreach ($items as $it) {
                    $cols = explode('||', $it);
                    $batch = isset($cols[1]) ? $cols[1] : '-';
                    $html .= '<tr style="border-bottom:1px solid #eee;"><td style="padding:6px 8px; vertical-align:middle;">' . e($batch) . '</td></tr>';
                }
                $html .= '</tbody></table>';
                return $html;
            })
            ->addColumn('exp_stoks', function ($row) {
                if (empty($row->exp_batches)) return '-';
                $parts = explode(';;', $row->exp_batches);
                $items = array_filter(array_map('trim', $parts));
                if (empty($items)) return '-';

                $unit = isset($row->obat_satuan) && $row->obat_satuan ? ' ' . e($row->obat_satuan) : '';
                $html = '<table class="table table-sm mb-0" style="margin-bottom:0;border-collapse:collapse;width:100%;">';
                $html .= '<tbody>';
                foreach ($items as $it) {
                    $cols = explode('||', $it);
                    $stok = isset($cols[2]) ? $cols[2] : '0';
                    $html .= '<tr style="border-bottom:1px solid #eee;"><td style="padding:6px 8px; vertical-align:middle;">' . e($stok) . $unit . '</td></tr>';
                }
                $html .= '</tbody></table>';
                return $html;
            })
            ->addColumn('exp_gudangs', function ($row) {
                if (empty($row->exp_batches)) return '-';
                $parts = explode(';;', $row->exp_batches);
                $items = array_filter(array_map('trim', $parts));
                if (empty($items)) return '-';

                $html = '<table class="table table-sm mb-0" style="margin-bottom:0;border-collapse:collapse;width:100%;">';
                $html .= '<tbody>';
                foreach ($items as $it) {
                    $cols = explode('||', $it);
                    $gudang = isset($cols[4]) ? $cols[4] : '-';
                    $html .= '<tr style="border-bottom:1px solid #eee;"><td style="padding:6px 8px; vertical-align:middle;">' . e($gudang) . '</td></tr>';
                }
                $html .= '</tbody></table>';
                return $html;
            })
            ->addColumn('exp_dates', function ($row) {
                if (empty($row->exp_batches)) return '-';
                $parts = explode(';;', $row->exp_batches);
                $items = array_filter(array_map('trim', $parts));
                if (empty($items)) return '-';

                $html = '<table class="table table-sm mb-0" style="margin-bottom:0;border-collapse:collapse;width:100%;">';
                $html .= '<tbody>';
                foreach ($items as $it) {
                    $cols = explode('||', $it);
                    $exp = isset($cols[3]) ? $cols[3] : '';
                    $expFormatted = '-';
                    try {
                        if ($exp) $expFormatted = Carbon::parse($exp)->format('d-m-Y');
                    } catch (\Exception $e) {
                        $expFormatted = $exp ?: '-';
                    }
                    $html .= '<tr style="border-bottom:1px solid #eee;"><td style="padding:6px 8px; vertical-align:middle;">' . e($expFormatted) . '</td></tr>';
                }
                $html .= '</tbody></table>';
                return $html;
            })
            ->addColumn('exp_actions', function ($row) use ($expiredGudangId, $expiredGudangName) {
                if (empty($row->exp_batches)) return '-';
                $parts = explode(';;', $row->exp_batches);
                $items = array_filter(array_map('trim', $parts));
                if (empty($items)) return '-';

                $html = '<table class="table table-sm mb-0" style="margin-bottom:0;border-collapse:collapse;width:100%;">';
                $html .= '<tbody>';
                foreach ($items as $it) {
                    $cols = explode('||', $it);
                    $stokId = isset($cols[0]) ? $cols[0] : null;
                    $html .= '<tr style="border-bottom:1px solid #eee;"><td style="padding:6px 8px; vertical-align:middle;">';
                    if (!$expiredGudangId) {
                        $html .= '<button type="button" class="btn btn-sm btn-secondary" disabled title="Mapping gudang expired belum diset">Belum di-map</button>';
                    } else {
                        $title = $expiredGudangName ? 'Pindah ke ' . e($expiredGudangName) : 'Pindah ke gudang expired';
                        $html .= '<button type="button" class="btn btn-sm btn-danger btn-move-expired" data-stok-id="' . e($stokId) . '" data-target-gudang-name="' . e($expiredGudangName ?: 'Gudang Expired') . '" title="' . $title . '"><i class="fas fa-exchange-alt"></i> Pindah</button>';
                    }
                    $html .= '</td></tr>';
                }
                $html .= '</tbody></table>';
                return $html;
            })
            ->rawColumns(['nama_obat', 'exp_batches', 'exp_stoks', 'exp_gudangs', 'exp_dates', 'exp_actions'])
            ->make(true);
    }

    /**
     * AJAX: Return count of obat that have expiration within next X months
     */
    public function getExpiringCount(Request $request)
    {
        $months = intval($request->input('months', 6));
        $threshold = Carbon::now()->addMonths($months)->endOfDay();
        $expiredGudangId = GudangMapping::getDefaultGudangId('expired');

        $table = (new ObatStokGudang())->getTable();

        $query = ObatStokGudang::leftJoin('erm_obat as o', 'o.id', $table . '.obat_id')
            ->whereNotNull($table . '.expiration_date')
            ->where($table . '.stok', '>', 0)
            ->select($table . '.obat_id', DB::raw('MIN(' . $table . '.expiration_date) as nearest_exp'))
            ->groupBy($table . '.obat_id')
            ->havingRaw('MIN(' . $table . '.expiration_date) <= ?', [$threshold->format('Y-m-d H:i:s')]);

        if ($expiredGudangId) {
            $query->where($table . '.gudang_id', '!=', $expiredGudangId);
        }

        if ($request->hide_inactive == 1) {
            $query->where('o.status_aktif', 1);
        }

        try {
            $count = $query->get()->count();
        } catch (\Exception $e) {
            $count = 0;
        }

        return response()->json(['count' => $count]);
    }

    public function moveBatchToExpiredGudang(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:erm_obat_stok_gudang,id',
        ]);

        $expiredMapping = GudangMapping::getActiveMapping('expired');
        if (!$expiredMapping || !$expiredMapping->gudang_id) {
            return response()->json([
                'success' => false,
                'message' => 'Mapping gudang expired belum diset.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $source = ObatStokGudang::with(['gudang'])
                ->lockForUpdate()
                ->findOrFail($request->id);

            $qty = (float) $source->stok;
            if ($qty <= 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Batch ini tidak memiliki stok untuk dipindahkan.'
                ], 422);
            }

            $targetGudangId = $expiredMapping->gudang_id;
            $targetGudang = $expiredMapping->gudang ?: Gudang::find($targetGudangId);

            if ((int) $source->gudang_id === (int) $targetGudangId) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Batch ini sudah berada di gudang expired.'
                ], 422);
            }

            $targetQuery = ObatStokGudang::withTrashed()
                ->where('obat_id', $source->obat_id)
                ->where('gudang_id', $targetGudangId)
                ->where('batch', $source->batch);

            if ($source->expiration_date) {
                $targetQuery->whereDate('expiration_date', $source->expiration_date->format('Y-m-d'));
            } else {
                $targetQuery->whereNull('expiration_date');
            }

            $target = $targetQuery->lockForUpdate()->first();

            if ($target && method_exists($target, 'trashed') && $target->trashed()) {
                $target->restore();
            }

            if (!$target) {
                $target = ObatStokGudang::create([
                    'obat_id' => $source->obat_id,
                    'gudang_id' => $targetGudangId,
                    'stok' => 0,
                    'min_stok' => $source->min_stok,
                    'max_stok' => $source->max_stok,
                    'batch' => $source->batch,
                    'expiration_date' => $source->expiration_date,
                    'rak' => $source->rak,
                    'lokasi' => $source->lokasi,
                ]);
            }

            $target->stok = (float) $target->stok + $qty;
            if (!$target->expiration_date && $source->expiration_date) {
                $target->expiration_date = $source->expiration_date;
            }
            $target->save();

            $sourceGudangName = $source->gudang ? $source->gudang->nama : ('Gudang #' . $source->gudang_id);
            $targetGudangName = $targetGudang ? $targetGudang->nama : ('Gudang #' . $targetGudangId);

            $source->stok = 0;
            $source->save();

            KartuStok::create([
                'obat_id' => $source->obat_id,
                'gudang_id' => $source->gudang_id,
                'tanggal' => now(),
                'tipe' => 'keluar',
                'qty' => $qty,
                'stok_setelah' => 0,
                'batch' => $source->batch,
                'keterangan' => 'Pindah ke gudang expired ' . $targetGudangName,
                'ref_type' => 'mutasi_gudang_expired',
                'ref_id' => $source->id,
                'user_id' => Auth::id(),
            ]);

            KartuStok::create([
                'obat_id' => $target->obat_id,
                'gudang_id' => $targetGudangId,
                'tanggal' => now(),
                'tipe' => 'masuk',
                'qty' => $qty,
                'stok_setelah' => (float) $target->stok,
                'batch' => $target->batch,
                'keterangan' => 'Mutasi expired dari ' . $sourceGudangName,
                'ref_type' => 'mutasi_gudang_expired',
                'ref_id' => $source->id,
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Batch berhasil dipindahkan ke ' . $targetGudangName,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memindahkan batch ke gudang expired: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeExpiredFollowUp(Request $request)
    {
        $request->validate([
            'obat_id' => 'required|integer|exists:erm_obat,id',
            'gudang_id' => 'required|integer|exists:erm_gudang,id',
            'tindak_lanjut' => 'required|in:diretur,dimusnahkan',
            'notes' => 'required|string|max:5000',
        ]);

        $expiredGudangId = GudangMapping::getDefaultGudangId('expired');
        if (!$expiredGudangId || (int) $request->gudang_id !== (int) $expiredGudangId) {
            return response()->json([
                'success' => false,
                'message' => 'Tindak lanjut hanya bisa dilakukan pada gudang expired.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $stokRows = ObatStokGudang::where('obat_id', $request->obat_id)
                ->where('gudang_id', $request->gudang_id)
                ->where('stok', '>', 0)
                ->lockForUpdate()
                ->get();

            if ($stokRows->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada stok aktif di gudang expired untuk ditindaklanjuti.'
                ], 422);
            }

            $userId = Auth::id();
            $actionLabel = $request->tindak_lanjut === 'diretur' ? 'Diretur' : 'Dimusnahkan';
            $processedBatches = 0;
            $processedQty = 0;

            foreach ($stokRows as $stokRow) {
                $qty = (float) $stokRow->stok;
                if ($qty <= 0) {
                    continue;
                }

                ObatExpiredTindakLanjut::create([
                    'obat_id' => $stokRow->obat_id,
                    'obat_stok_gudang_id' => $stokRow->id,
                    'jumlah' => $qty,
                    'expiration_date' => $stokRow->expiration_date,
                    'tindak_lanjut' => $request->tindak_lanjut,
                    'notes' => $request->notes,
                    'created_by' => $userId,
                ]);

                $stokRow->stok = 0;
                $stokRow->save();

                KartuStok::create([
                    'obat_id' => $stokRow->obat_id,
                    'gudang_id' => $stokRow->gudang_id,
                    'tanggal' => now(),
                    'tipe' => 'keluar',
                    'qty' => $qty,
                    'stok_setelah' => 0,
                    'batch' => $stokRow->batch,
                    'keterangan' => 'Tindak lanjut expired: ' . $actionLabel . '. ' . $request->notes,
                    'ref_type' => 'expired_tindak_lanjut',
                    'ref_id' => $stokRow->id,
                    'user_id' => $userId,
                ]);         

                $processedBatches++;
                $processedQty += $qty;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $actionLabel . ' berhasil dicatat untuk ' . $processedBatches . ' batch.',
                'processed_batches' => $processedBatches,
                'processed_qty' => $processedQty,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan tindak lanjut expired: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AJAX: Return per-batch list of obat batches that expire within next X months
     */
    public function getExpiringBatches(Request $request)
    {
        $months = intval($request->input('months', 6));
        $threshold = Carbon::now()->addMonths($months)->endOfDay();

        $table = (new ObatStokGudang())->getTable();

        $query = ObatStokGudang::leftJoin('erm_obat as o', 'o.id', $table . '.obat_id')
            ->whereNotNull($table . '.expiration_date')
            ->whereDate($table . '.expiration_date', '<=', $threshold->format('Y-m-d'))
            ->select(
                $table . '.id',
                $table . '.obat_id',
                $table . '.batch',
                $table . '.stok',
                $table . '.expiration_date',
                'o.nama as obat_nama',
                'o.kode_obat as obat_kode',
                'o.satuan as obat_satuan'
            );

        if ($request->gudang_id) {
            $query->where($table . '.gudang_id', $request->gudang_id);
        }

        if ($request->hide_inactive == 1) {
            $query->where('o.status_aktif', 1);
        }

        return DataTables::of($query)
            ->filter(function ($q) use ($request, $table) {
                $searchValue = null;
                if ($request->has('search')) {
                    $s = $request->input('search');
                    if (is_array($s) && isset($s['value'])) $searchValue = trim($s['value']);
                    elseif (is_string($s)) $searchValue = trim($s);
                }

                if ($searchValue) {
                    $q->where(function ($sub) use ($searchValue, $table) {
                        $sub->where('o.nama', 'like', "%{$searchValue}%")
                            ->orWhere('o.kode_obat', 'like', "%{$searchValue}%")
                            ->orWhere($table . '.batch', 'like', "%{$searchValue}%");
                    });
                }
            })
            ->addColumn('nama_obat', function ($row) {
                $link = '<a href="#" class="show-batch-details" data-obat-id="'.$row->obat_id.'" data-gudang-id="">' . e($row->obat_nama) . '</a>';
                $link .= '<div class="text-muted small">' . ($row->obat_kode ?: '-') . '</div>';
                return $link;
            })
            ->addColumn('batch', function ($row) {
                return e($row->batch ?: '-');
            })
            ->addColumn('stok', function ($row) {
                $unit = isset($row->obat_satuan) && $row->obat_satuan ? (function_exists('mb_strtolower') ? mb_strtolower($row->obat_satuan) : strtolower($row->obat_satuan)) : '';
                $formatted = number_format((float)$row->stok, 2, ',', '.');
                return $formatted . ($unit ? ' ' . e($unit) : '');
            })
            ->addColumn('expiration_date', function ($row) {
                try { return Carbon::parse($row->expiration_date)->format('d-m-Y'); } catch (\Exception $e) { return '-'; }
            })
            ->rawColumns(['nama_obat'])
            ->make(true);
    }
}
