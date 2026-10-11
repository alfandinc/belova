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
    // Forecast status labels shared with the view (same as the Forecast Stok page)
    private const STATUS_LABELS = [
        'kritis' => ['Kritis', 'badge-danger'],
        'pesan' => ['Perlu Pesan', 'badge-warning'],
        'menunggu' => ['Menunggu Barang', 'badge-primary'],
        'aman' => ['Aman', 'badge-success'],
        'overstock' => ['Overstock', 'badge-info'],
        'tidak_bergerak' => ['Tidak Bergerak', 'badge-secondary'],
        'tidak_ada_pemakaian' => ['Tidak Ada Pemakaian', 'badge-light'],
        'stop_pengadaan' => ['Stop Pengadaan', 'badge-dark'],
    ];

    /**
     * Forecast (default settings, live stock) for every active obat, keyed by obat_id.
     */
    private function forecastPerObat(): array
    {
        $forecastService = app(\App\Services\ERM\ForecastService::class);

        return $forecastService->computeAll($forecastService->defaultParams());
    }

    private function renderStatusBadge(?array $f): string
    {
        if (!$f) {
            return '<span class="badge badge-light" title="Obat tidak aktif, tidak diforecast">Tidak Aktif</span>';
        }

        [$label, $class] = self::STATUS_LABELS[$f['status']] ?? self::STATUS_LABELS['tidak_ada_pemakaian'];
        $details = [];
        if ($f['reorder_point'] > 0) {
            $details[] = 'Titik pesan ' . $this->fmtQty($f['reorder_point']);
            $details[] = 'Stok maks ' . $this->fmtQty($f['max_stock']);
        }
        if ($f['days_of_cover'] !== null) {
            $details[] = 'cukup ' . number_format(floor($f['days_of_cover']), 0, ',', '.') . ' hari';
        }
        if ($f['qty_pesan'] > 0) {
            $details[] = 'saran pesan ' . $this->fmtQty($f['qty_pesan']) . ($f['jumlah_pesan_box'] ? ' (' . $f['jumlah_pesan_box'] . ' box)' : '');
        }
        $title = implode(' · ', array_merge(['Stok semua gudang (tanpa gudang expired): ' . $this->fmtQty($f['total_stock'])], $details));

        $html = '<span class="badge ' . $class . '" title="' . e($title) . '" data-toggle="tooltip">' . e($label) . '</span>';
        if (!empty($f['wajib_stok'])) {
            $html .= ' <i class="fas fa-shield-alt text-danger" title="Wajib Ada"></i>';
        }

        return $html;
    }

    private function fmtQty($value): string
    {
        $value = (float) $value;
        return number_format($value, floor($value) == $value ? 0 : 2, ',', '.');
    }

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
        $stokOpnames = StokOpname::with('gudang')->orderBy('tanggal_opname', 'desc')->get();

        $metodePemusnahan = \App\Services\ERM\GudangExpiredService::METODE_PEMUSNAHAN;
        // Quantities change only through Penyesuaian Stok documents (same roles as that menu); masuk needs Admin/Farmasi
        $canMutasi = (bool) optional(Auth::user())->hasAnyRole(['Admin', 'Farmasi', 'Beautician', 'Lab']);
        $canMasuk = (bool) optional(Auth::user())->hasAnyRole(['Admin', 'Farmasi']);
        $expiredGudangId = app(\App\Services\ERM\GudangExpiredService::class)->gudangId();

        return view('erm.stok-gudang.index', compact('gudangs', 'defaultGudang', 'kategoris', 'stokOpnames', 'metodePemusnahan', 'canMutasi', 'canMasuk', 'expiredGudangId'));
    }

    public function getData(Request $request)
    {
        $expiredGudangId = GudangMapping::getDefaultGudangId('expired');

        // Stock status per obat comes from the forecast (titik pesan / stok maks), computed on live stock
        $forecast = $this->forecastPerObat();

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

        $query = ObatStokGudang::leftJoin('erm_obat as o', 'o.id', $table . '.obat_id')
            ->leftJoin('erm_gudang as g', 'g.id', $table . '.gudang_id')
            ->leftJoinSub($latestExpiredFollowUps, 'lfu', function ($join) use ($table) {
                $join->on('lfu.obat_id', '=', $table . '.obat_id')
                    ->on('lfu.gudang_id', '=', $table . '.gudang_id');
            })
            ->leftJoin('erm_obat_expired_tindak_lanjut as latest_follow_up', 'latest_follow_up.id', '=', 'lfu.latest_follow_up_id')
            ->select(
                $table . '.obat_id',
                $table . '.gudang_id',
                DB::raw('SUM(' . $table . '.stok) as total_stok'),
                'o.nama as obat_nama',
                'o.kode_obat as obat_kode',
                'o.status_aktif',
                DB::raw("COALESCE(NULLIF(o.satuan_stok, ''), o.satuan) as obat_satuan"),
                // Use a numeric alias for HPP for reliable calculations
                DB::raw('COALESCE(o.hpp, 0) as hpp_val'),
                'g.nama as gudang_nama',
                'latest_follow_up.tindak_lanjut as latest_tindak_lanjut',
                'latest_follow_up.status as latest_follow_up_status',
                'latest_follow_up.nomor_dokumen as latest_nomor_dokumen'
            )
            ->groupBy($table . '.obat_id', $table . '.gudang_id', 'o.nama', 'o.kode_obat', 'o.status_aktif', 'g.nama', 'latest_follow_up.tindak_lanjut', 'latest_follow_up.status', 'latest_follow_up.nomor_dokumen');

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

        // Filter by forecast status (perlu_dipesan = kritis + pesan)
        if ($request->status_stok) {
            $wanted = $request->status_stok === 'perlu_dipesan' ? ['kritis', 'pesan'] : [$request->status_stok];
            $obatIds = array_keys(array_filter($forecast, function ($f) use ($wanted) {
                return in_array($f['status'], $wanted, true);
            }));
            $query->whereIn($table . '.obat_id', $obatIds ?: [0]);
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
                    if ($row->latest_tindak_lanjut === 'dimusnahkan') {
                        $followUpHtml = '<span class="badge badge-danger">Dimusnahkan</span>';
                        if (!empty($row->latest_nomor_dokumen)) {
                            $followUpHtml .= ' <a href="' . route('erm.stok-gudang.berita-acara', $row->latest_nomor_dokumen) . '" target="_blank" class="small">BA ' . e($row->latest_nomor_dokumen) . '</a>';
                        }
                    } elseif ($row->latest_follow_up_status === ObatExpiredTindakLanjut::STATUS_MENUNGGU_RETUR) {
                        $followUpHtml = '<span class="badge badge-warning" title="Stok keluar setelah retur di-approve di menu Retur ke Supplier">Menunggu Approval Retur ' . e($row->latest_nomor_dokumen) . '</span>';
                    } elseif ($row->latest_follow_up_status === ObatExpiredTindakLanjut::STATUS_BATAL) {
                        $followUpHtml = '<span class="badge badge-secondary">Retur ditolak</span>';
                    } else {
                        $followUpHtml = '<span class="badge badge-warning">Diretur</span>';
                    }
                    $link .= '<div class="mt-1">' . $followUpHtml . '</div>';
                }

                return $link;
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

                $canFollowUpExpired = $expiredGudangId
                    && (int) $gudangId === (int) $expiredGudangId
                    && (float) $row->total_stok > 0;

                $btn = '<div class="action-buttons"><div class="btn-group btn-group-sm" role="group" aria-label="Aksi stok gudang">';
                $btn .= '<button class="btn btn-primary btn-kartu-stok" data-obat-id="'.$obatId.'" data-gudang-id="'.$gudangId.'" title="Kartu Stok"><i class="fas fa-book-open"></i></button>';
                if ($canFollowUpExpired) {
                    $btn .= '<button class="btn btn-warning btn-expired-follow-up" data-obat-id="'.$obatId.'" data-gudang-id="'.$gudangId.'" data-obat-nama="'.e($row->obat_nama ?: 'Obat').'" title="Tindak Lanjut"><i class="fas fa-clipboard-check"></i></button>';
                }
                // Read-only batch list (batch, ED, status); quantities change only through Penyesuaian Stok
                $btn .= '<button class="btn btn-outline-secondary show-batch-details" data-obat-id="'.$obatId.'" data-gudang-id="'.$gudangId.'" title="Lihat batch & ED"><i class="fas fa-layer-group"></i></button>';
                $btn .= '</div></div>';
                return $btn;
            })
            ->editColumn('total_stok', function ($row) use ($forecast) {
                $unit = isset($row->obat_satuan) && $row->obat_satuan ? trim($row->obat_satuan) : '';
                if ($unit !== '') {
                    $unit = function_exists('mb_strtolower') ? mb_strtolower($unit) : strtolower($unit);
                }
                $unitDisplay = $unit !== '' ? ' ' . e($unit) : '';
                $formatted = number_format((float) $row->total_stok, 2, ',', '.');

                return '<div><strong>' . $formatted . $unitDisplay . '</strong></div>'
                    . $this->renderStatusBadge($forecast[$row->obat_id] ?? null);
            })
            // Allow ordering by obat name and kode via the joined columns
            ->orderColumn('nama_obat', 'o.nama $1')
            ->orderColumn('kode_obat', 'o.kode_obat $1')
            ->orderColumn('total_stok', 'total_stok $1')
            // Allow ordering by HPP (use COALESCE to mirror displayed hpp_val)
            ->orderColumn('hpp', function($query, $direction) use ($table) {
                $query->orderBy(DB::raw('COALESCE(o.hpp, 0)'), $direction);
            })
            // Allow ordering by Nilai Stok (total_stok * hpp_val)
            ->orderColumn('nilai_stok', function($query, $direction) use ($table) {
                $expr = DB::raw('SUM(' . $table . '.stok) * COALESCE(o.hpp, 0)');
                $query->orderBy($expr, $direction);
            })
            ->rawColumns(['nama_obat', 'actions', 'hpp', 'total_stok'])
            ->make(true);
    }

    public function getBatchDetails(Request $request)
    {
        $obatId = $request->obat_id;
        $gudangId = $request->gudang_id;

        // Without gudang_id (opened from the low-stock / expiring modals) show batches in every gudang
        $allGudang = empty($gudangId);

        $stokGudang = ObatStokGudang::with(['obat', 'gudang'])
            ->where('obat_id', $obatId)
            ->when(!$allGudang, function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId);
            })
            ->orderBy('gudang_id')
            // FEFO: nearest expiry first, batches without ED last
            ->orderByRaw('expiration_date IS NULL, expiration_date ASC')
            ->get();

        $details = $stokGudang->map(function ($item) {
            return [
                'id' => $item->id,
                'batch' => $item->batch,
                'gudang_id' => $item->gudang_id,
                'gudang' => $item->gudang ? $item->gudang->nama : ('Gudang #' . $item->gudang_id),
                'stok' => $item->stok, // Raw value for editing
                // Use Indonesian formatting: dot thousands separator and comma decimal
                'stok_display' => (function() use ($item) {
                    $formatted = number_format($item->stok, 2, ',', '.');
                    $unit = '';
                    try {
                        if ($item->obat && $item->obat->satuan_stok_label) {
                            $unitRaw = trim($item->obat->satuan_stok_label);
                            $unit = function_exists('mb_strtolower') ? mb_strtolower($unitRaw) : strtolower($unitRaw);
                        }
                    } catch (\Exception $e) {
                        $unit = '';
                    }
                    return $unit !== '' ? ($formatted . ' ' . e($unit)) : $formatted;
                })(), // Formatted for display (2 decimals) with lowercase unit
                'expiration_date' => $item->expiration_date ? Carbon::parse($item->expiration_date)->format('d-m-Y') : '-',
                'expiration_date_raw' => $item->expiration_date ? Carbon::parse($item->expiration_date)->format('Y-m-d') : '',
                // Expired batches cannot be dispensed; the billing stock check skips them
                'is_expired' => $item->isExpired(),
                'status' => $this->getExpirationStatus($item)
            ];
        });

        $first = $stokGudang->first();
        $obat = $first && $first->obat ? $first->obat : Obat::withInactive()->find($obatId);
        $gudangNama = $allGudang
            ? 'Semua Gudang'
            : ($first && $first->gudang ? $first->gudang->nama : (Gudang::find($gudangId)->nama ?? '-'));

        return response()->json([
            'data' => $details,
            'obat' => $obat ? $obat->nama : '',
            'gudang' => $gudangNama,
            'all_gudang' => $allGudang
        ]);
    }

    private function getExpirationStatus(ObatStokGudang $item)
    {
        if ($item->isMigrasi()) {
            return '<span class="badge badge-secondary" title="Stok migrasi dari sistem lama; ED sementara, lengkapi di layar Stok MIGRATE">ED belum diketahui (migrasi)</span>';
        }

        $date = $item->expiration_date;
        if (!$date) {
            return '<span class="badge badge-secondary">Tidak Ada Tanggal</span>';
        }

        $expDate = Carbon::parse($date);
        $now = Carbon::now();

        // Same rule as dispensing: usable through the ED day itself
        if ($item->isExpired()) {
            return '<span class="badge badge-danger">Expired</span>';
        }
        
        // Carbon 3 returns a signed diff, so measure from now to the (future) expiry date.
        // 6 months matches the "Stok Expired" modal threshold.
        if ($now->diffInMonths($expDate) <= 6) {
            return '<span class="badge badge-warning">Hampir Expired</span>';
        }
        
        return '<span class="badge badge-success">Aman</span>';
    }


    /**
     * Data Perlu Dicek: batches with suspicious data (default/missing ED, unofficial batch, no incoming
     * document, stok not matching the kartu stok, ...). Read-only.
     */
    public function auditData(Request $request)
    {
        $result = app(\App\Services\ERM\StokAuditService::class)->temuan([
            'gudang_id' => $request->gudang_id,
            'issue' => $request->issue,
        ]);

        return response()->json($result + [
            'issues' => collect(\App\Services\ERM\StokAuditService::ISSUES)->map(function ($v) {
                return ['label' => $v[0], 'severity' => $v[1], 'info' => $v[2]];
            }),
        ]);
    }

    /**
     * Ganti nama batch (mis. batch MIGRATE-* menjadi nomor batch asli).
     * Riwayat kartu stok batch lama di gudang yang sama ikut dipindah ke nama baru.
     */
    public function updateBatchName(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:erm_obat_stok_gudang,id',
            'batch' => 'required|string|max:255'
        ]);

        $newBatch = trim($request->batch);
        if ($newBatch === '') {
            return response()->json(['success' => false, 'message' => 'Nama batch wajib diisi.'], 422);
        }

        try {
            return DB::transaction(function () use ($request, $newBatch) {
                $stokGudang = ObatStokGudang::lockForUpdate()->findOrFail($request->id);
                $oldBatch = $stokGudang->batch;

                if ($oldBatch === $newBatch) {
                    return response()->json(['success' => true, 'message' => 'Nama batch tidak berubah.']);
                }

                $duplicate = ObatStokGudang::where('obat_id', $stokGudang->obat_id)
                    ->where('gudang_id', $stokGudang->gudang_id)
                    ->where('batch', $newBatch)
                    ->where('id', '!=', $stokGudang->id)
                    ->exists();
                if ($duplicate) {
                    return response()->json([
                        'success' => false,
                        'message' => "Batch {$newBatch} sudah ada untuk obat ini di gudang yang sama."
                    ], 422);
                }

                $stokGudang->batch = $newBatch;
                $stokGudang->save();

                $kartuUpdated = KartuStok::where('obat_id', $stokGudang->obat_id)
                    ->where('gudang_id', $stokGudang->gudang_id)
                    ->where('batch', $oldBatch)
                    ->update(['batch' => $newBatch]);

                \Illuminate\Support\Facades\Log::info('Batch renamed', [
                    'stok_gudang_id' => $stokGudang->id,
                    'obat_id' => $stokGudang->obat_id,
                    'gudang_id' => $stokGudang->gudang_id,
                    'old_batch' => $oldBatch,
                    'new_batch' => $newBatch,
                    'kartu_stok_updated' => $kartuUpdated,
                    'user_id' => Auth::id(),
                ]);

                app(\App\Services\ERM\StokAuditService::class)->lupakanCache();

                return response()->json([
                    'success' => true,
                    'message' => 'Nama batch berhasil diubah',
                    'data' => ['batch' => $newBatch, 'kartu_stok_updated' => $kartuUpdated]
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengubah batch: ' . $e->getMessage()], 422);
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

            if ($old !== $new) {
                \Illuminate\Support\Facades\Log::info('Batch expiration changed', [
                    'stok_gudang_id' => $stokGudang->id,
                    'obat_id' => $stokGudang->obat_id,
                    'gudang_id' => $stokGudang->gudang_id,
                    'batch' => $stokGudang->batch,
                    'old_expiration_date' => $old,
                    'new_expiration_date' => $new,
                    'user_id' => Auth::id(),
                ]);
            }

            app(\App\Services\ERM\StokAuditService::class)->lupakanCache();

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
     * Sum kartu stok masuk/keluar per obat in one query, keyed by obat_id.
     */
    private function kartuStokTotals(array $obatIds, $gudangId, \Closure $dateFilter)
    {
        $totals = [];
        foreach (array_chunk($obatIds, 1000) as $chunk) {
            $query = KartuStok::whereIn('obat_id', $chunk)
                ->whereIn('tipe', ['masuk', 'keluar'])
                ->when($gudangId, function ($q) use ($gudangId) { $q->where('gudang_id', $gudangId); })
                ->select('obat_id', 'tipe', DB::raw('SUM(qty) as total'))
                ->groupBy('obat_id', 'tipe');
            $dateFilter($query);

            foreach ($query->get() as $r) {
                $totals[$r->obat_id][$r->tipe] = (float) $r->total;
            }
        }
        return $totals;
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
        $requestGudangNama = $gudangId ? (Gudang::find($gudangId)->nama ?? '-') : '-';

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

            // Kartu stok totals: if opname selected, restrict to opname date; otherwise use provided date range
            $op = $stokOpnameId ? StokOpname::with('gudang')->find($stokOpnameId) : null;
            if ($op) {
                $namaGudang = $op->gudang ? $op->gudang->nama : $requestGudangNama;
                $dateFilter = function ($q) use ($op) { $q->whereDate('tanggal', $op->tanggal_opname); };
            } else {
                $namaGudang = $requestGudangNama;
                $dateFilter = function ($q) use ($start, $end) { $q->whereBetween('tanggal', [$start, $end]); };
            }

            $obatIds = $items->pluck('obat_id')->all();
            $obats = Obat::withInactive()->whereIn('id', $obatIds)->get()->keyBy('id');
            $totals = $this->kartuStokTotals($obatIds, $gudangId, $dateFilter);

            foreach ($items as $item) {
                $obat = $obats->get($item->obat_id);
                $nama = $obat ? $obat->nama : ('[ID '.$item->obat_id.']');
                $totalStok = (float) $item->stok_fisik;
                $hpp = $obat ? ($obat->hpp ?? 0) : 0;
                $masuk = $totals[$item->obat_id]['masuk'] ?? 0;
                $keluar = $totals[$item->obat_id]['keluar'] ?? 0;

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
            // Live totals per obat (and optionally per gudang)
            $query = ObatStokGudang::select('obat_id', DB::raw('SUM(stok) as total_stok'))
                ->groupBy('obat_id');
            if ($gudangId) $query->where('gudang_id', $gudangId);
            $rows = $query->get();

            $obatIds = $rows->pluck('obat_id')->all();
            $obats = Obat::withInactive()->whereIn('id', $obatIds)->get()->keyBy('id');

            if ($type == '3') {
                // Data per tanggal tertentu: compute stok as of pivot date by reversing movements after that date
                try {
                    $pivot = $pivotDate ? Carbon::parse($pivotDate)->endOfDay() : Carbon::today()->endOfDay();
                } catch (\Exception $e) {
                    $pivot = Carbon::today()->endOfDay();
                }
                $totals = $this->kartuStokTotals($obatIds, $gudangId, function ($q) use ($pivot) {
                    $q->where('tanggal', '>', $pivot);
                });
            } else {
                $totals = $this->kartuStokTotals($obatIds, $gudangId, function ($q) use ($start, $end) {
                    $q->whereBetween('tanggal', [$start, $end]);
                });
            }

            foreach ($rows as $row) {
                $obat = $obats->get($row->obat_id);
                $nama = $obat ? $obat->nama : ('[ID '.$row->obat_id.']');
                $liveStok = (float) $row->total_stok;
                $hpp = $obat ? ($obat->hpp ?? 0) : 0;
                $masuk = (float) ($totals[$row->obat_id]['masuk'] ?? 0);
                $keluar = (float) ($totals[$row->obat_id]['keluar'] ?? 0);

                // Type 3 reconstructs stok at pivot date: live - masukAfter + keluarAfter
                $stok = $type == '3' ? ($liveStok - $masuk + $keluar) : $liveStok;
                $nilaiStok = round($stok * $hpp, 4);

                $exportRows[] = [
                    $nama,
                    (float) $stok,
                    $hpp,
                    $nilaiStok,
                    $masuk,
                    $keluar,
                    $requestGudangNama
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
     * AJAX: obat yang perlu dipesan menurut forecast (Kritis / Perlu Pesan), plus yang Menunggu Barang.
     * Returned in full for a client-side table (the list is short), sorted by urgency.
     */
    public function getLowStockData(Request $request)
    {
        $forecast = array_filter($this->forecastPerObat(), function ($f) {
            return in_array($f['status'], ['kritis', 'pesan', 'menunggu'], true);
        });

        $obatIds = array_keys($forecast);
        $obats = Obat::whereIn('id', $obatIds ?: [0])
            ->get(['id', 'nama', 'kode_obat', 'satuan', 'satuan_stok'])
            ->keyBy('id');

        $perGudang = ObatStokGudang::with('gudang')
            ->whereIn('obat_id', $obatIds ?: [0])
            ->select('obat_id', 'gudang_id', DB::raw('SUM(stok) as total_stok'))
            ->groupBy('obat_id', 'gudang_id')
            ->orderBy('gudang_id')
            ->get()
            ->groupBy('obat_id');

        $rank = ['kritis' => 0, 'pesan' => 1, 'menunggu' => 2];
        $rows = [];
        foreach ($forecast as $obatId => $f) {
            $obat = $obats->get($obatId);
            if (!$obat) {
                continue;
            }
            $unit = $obat->satuan_stok ?: $obat->satuan;

            $detail = ($perGudang->get($obatId) ?? collect())->map(function ($d) use ($unit) {
                return [
                    'gudang' => $d->gudang ? $d->gudang->nama : ('Gudang #' . $d->gudang_id),
                    'stok' => round((float) $d->total_stok, 2),
                    'satuan' => $unit,
                ];
            })->values();

            $rows[] = array_merge($f, [
                'obat_id' => $obatId,
                'obat_nama' => $obat->nama,
                'obat_kode' => $obat->kode_obat,
                'satuan' => $unit,
                'status_label' => self::STATUS_LABELS[$f['status']][0],
                'status_badge' => self::STATUS_LABELS[$f['status']][1],
                'status_rank' => $rank[$f['status']],
                'detail_gudang' => $detail,
            ]);
        }

        usort($rows, function ($a, $b) {
            if ($a['status_rank'] !== $b['status_rank']) {
                return $a['status_rank'] <=> $b['status_rank'];
            }
            return ($a['days_of_cover'] ?? 0) <=> ($b['days_of_cover'] ?? 0);
        });

        return response()->json(['data' => $rows]);
    }

    /**
     * AJAX: jumlah obat yang perlu dipesan (Kritis + Perlu Pesan) untuk badge tombol
     */
    public function getLowStockCount(Request $request)
    {
        $count = count(array_filter($this->forecastPerObat(), function ($f) {
            return in_array($f['status'], ['kritis', 'pesan'], true);
        }));

        return response()->json(['count' => $count]);
    }

    /**
     * AJAX: ringkasan untuk kartu di atas halaman Manajemen Stok
     */
    public function getSummary(Request $request)
    {
        $forecast = $this->forecastPerObat();
        $byStatus = array_count_values(array_column($forecast, 'status'));

        // Value of stock that did not move in the forecast period (dead stock)
        $deadIds = array_keys(array_filter($forecast, function ($f) {
            return $f['status'] === 'tidak_bergerak';
        }));
        $hpp = Obat::whereIn('id', $deadIds ?: [0])->pluck('hpp', 'id');
        $deadValue = 0;
        foreach ($deadIds as $id) {
            $deadValue += (float) $forecast[$id]['total_stock'] * (float) ($hpp[$id] ?? 0);
        }

        $expiredGudangId = GudangMapping::getDefaultGudangId('expired');
        $expiryQuery = function () use ($expiredGudangId) {
            return ObatStokGudang::where('stok', '>', 0)
                ->edTerverifikasi()
                ->when($expiredGudangId, function ($q) use ($expiredGudangId) {
                    $q->where('gudang_id', '!=', $expiredGudangId);
                });
        };

        // Everything the page header needs in one request (cards + button badges)
        $stokService = app(\App\Services\ERM\StokService::class);
        $expiringCount = $this->getExpiringCount($request)->getData(true)['count'] ?? 0;

        return response()->json([
            'nilai_gudang' => $request->gudang_id ? round((float) $stokService->getNilaiStokGudang($request->gudang_id), 2) : 0,
            'nilai_keseluruhan' => round((float) $stokService->getNilaiStokKeseluruhan(), 2),
            'akan_expired' => $expiringCount,
            'kritis' => $byStatus['kritis'] ?? 0,
            'perlu_pesan' => ($byStatus['kritis'] ?? 0) + ($byStatus['pesan'] ?? 0),
            'menunggu' => $byStatus['menunggu'] ?? 0,
            'overstock' => $byStatus['overstock'] ?? 0,
            'tidak_bergerak' => $byStatus['tidak_bergerak'] ?? 0,
            'nilai_tidak_bergerak' => round($deadValue, 2),
            // Batches still in a usable gudang
            'sudah_expired' => $expiryQuery()->whereDate('expiration_date', '<', now())->count(),
            'expired_30_hari' => $expiryQuery()->whereDate('expiration_date', '>=', now())
                ->whereDate('expiration_date', '<=', now()->addDays(30))->count(),
            // Batches with suspicious data (cached for 10 minutes)
            'data_perlu_dicek' => app(\App\Services\ERM\StokAuditService::class)->jumlah(),
        ]);
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

        // Default GROUP_CONCAT limit (1024 chars) truncates obat with many batches
        DB::statement('SET SESSION group_concat_max_len = 1000000');

        $query = ObatStokGudang::leftJoin('erm_obat as o', 'o.id', $table . '.obat_id')
            ->leftJoin('erm_gudang as g', 'g.id', $table . '.gudang_id')
            ->edTerverifikasi()
            ->where($table . '.stok', '>', 0)
            ->select(
                $table . '.obat_id',
                DB::raw('SUM(' . $table . '.stok) as total_stok'),
                DB::raw('MIN(' . $table . '.expiration_date) as nearest_exp'),
                'o.nama as obat_nama',
                'o.kode_obat as obat_kode',
                DB::raw("COALESCE(NULLIF(o.satuan_stok, ''), o.satuan) as obat_satuan"),
                DB::raw("GROUP_CONCAT(DISTINCT CASE WHEN {$table}.expiration_date IS NOT NULL AND {$table}.expiration_date <= '{$expThreshold}' THEN CONCAT({$table}.id, '||', COALESCE({$table}.batch, '-'), '||', REPLACE(CAST({$table}.stok AS CHAR), '.', ','), '||', DATE_FORMAT({$table}.expiration_date, '%Y-%m-%d'), '||', COALESCE(g.nama, '-'), '||', {$table}.gudang_id) END SEPARATOR ';;') as exp_batches")
            )
            ->groupBy($table . '.obat_id', 'o.nama', 'o.kode_obat', 'o.satuan', 'o.satuan_stok')
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
                $link .= '<div class="text-muted small">' . e($row->obat_kode ?: '-') . '</div>';
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
                        $html .= '<button type="button" class="btn btn-sm btn-danger btn-move-expired" data-stok-id="' . e($stokId) . '" data-batch="' . e($cols[1] ?? '-') . '" data-gudang="' . e($cols[4] ?? '-') . '" data-obat="' . e($row->obat_nama) . '" data-target-gudang-name="' . e($expiredGudangName ?: 'Gudang Expired') . '" title="' . $title . '"><i class="fas fa-exchange-alt"></i> Pindah</button>';
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
            ->edTerverifikasi()
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

        $service = app(\App\Services\ERM\GudangExpiredService::class);

        try {
            $targetName = DB::transaction(function () use ($request, $service) {
                $source = ObatStokGudang::with(['gudang'])->lockForUpdate()->findOrFail($request->id);
                $service->pindahkanBatch($source, 'Karantina manual');
                return optional(optional($service->mapping())->gudang)->nama ?? 'gudang expired';
            });

            return response()->json([
                'success' => true,
                'message' => 'Batch berhasil dipindahkan ke ' . $targetName,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memindahkan batch ke gudang expired: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Karantina: move every batch past its ED from the active gudang into the gudang expired at once.
     */
    public function moveAllExpiredToGudangExpired(Request $request)
    {
        try {
            $result = app(\App\Services\ERM\GudangExpiredService::class)
                ->pindahkanSemuaYangExpired(Auth::id(), 'Karantina manual (semua batch expired)');
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $message = $result['batch'] > 0
            ? $result['batch'] . ' batch (' . $this->fmtQty($result['qty']) . ' unit) dipindahkan ke gudang expired.'
            : 'Tidak ada batch expired di gudang aktif.';
        if ($result['gagal']) {
            $message .= ' ' . count($result['gagal']) . ' batch gagal: ' . collect($result['gagal'])->pluck('message')->unique()->implode('; ');
        }

        return response()->json(['success' => empty($result['gagal']), 'message' => $message, 'result' => $result]);
    }

    public function storeExpiredFollowUp(Request $request)
    {
        $metode = \App\Services\ERM\GudangExpiredService::METODE_PEMUSNAHAN;
        $request->validate([
            'obat_id' => 'required|integer|exists:erm_obat,id',
            'gudang_id' => 'required|integer|exists:erm_gudang,id',
            'tindak_lanjut' => 'required|in:diretur,dimusnahkan',
            'tanggal' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'metode' => ['required_if:tindak_lanjut,dimusnahkan', 'nullable', \Illuminate\Validation\Rule::in($metode)],
            'saksi_1' => 'required_if:tindak_lanjut,dimusnahkan|nullable|string|max:150',
            'saksi_2' => 'required_if:tindak_lanjut,dimusnahkan|nullable|string|max:150|different:saksi_1',
        ], [
            'metode.required_if' => 'Metode pemusnahan wajib dipilih.',
            'saksi_1.required_if' => 'Saksi 1 wajib diisi untuk berita acara pemusnahan.',
            'saksi_2.required_if' => 'Saksi 2 wajib diisi untuk berita acara pemusnahan.',
            'saksi_2.different' => 'Saksi 1 dan Saksi 2 harus orang yang berbeda.',
        ]);

        $service = app(\App\Services\ERM\GudangExpiredService::class);
        if (!$service->isGudangExpired($request->gudang_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Tindak lanjut hanya bisa dilakukan pada gudang expired.'
            ], 422);
        }

        $data = $request->only(['tanggal', 'notes', 'metode', 'saksi_1', 'saksi_2']);

        try {
            if ($request->tindak_lanjut === 'dimusnahkan') {
                $result = $service->musnahkan((int) $request->obat_id, $data);

                return response()->json([
                    'success' => true,
                    'message' => 'Pemusnahan ' . $result['batch'] . ' batch dicatat dengan Berita Acara ' . $result['nomor_dokumen'] . '.',
                    'nomor_dokumen' => $result['nomor_dokumen'],
                    'print_url' => route('erm.stok-gudang.berita-acara', $result['nomor_dokumen']),
                ]);
            }

            $result = $service->ajukanRetur((int) $request->obat_id, $data);
            $nomor = collect($result['returs'])->pluck('no_retur')->implode(', ');

            return response()->json([
                'success' => true,
                'message' => 'Retur ke Supplier ' . $nomor . ' diajukan. Stok keluar dari gudang expired setelah retur di-approve di menu Retur ke Supplier.',
                'retur_url' => route('erm.fakturretur.index'),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan tindak lanjut expired: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Printable Berita Acara Pemusnahan Obat Kadaluarsa.
     */
    public function beritaAcara($nomor)
    {
        $items = ObatExpiredTindakLanjut::with(['obat', 'stokGudang.gudang', 'creator'])
            ->where('nomor_dokumen', $nomor)
            ->where('tindak_lanjut', 'dimusnahkan')
            ->orderBy('id')
            ->get();

        abort_if($items->isEmpty(), 404);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('erm.stok-gudang.berita-acara', [
            'nomor' => $nomor,
            'items' => $items,
            'header' => $items->first(),
        ])->setPaper('a4', 'portrait')->stream('berita-acara-pemusnahan-' . $nomor . '.pdf');
    }

    /**
     * Riwayat tindak lanjut expired, one row per document (berita acara / retur).
     */
    public function riwayatTindakLanjut(Request $request)
    {
        $rows = ObatExpiredTindakLanjut::with(['obat:id,nama', 'creator:id,name', 'fakturRetur:id,no_retur,status'])
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->groupBy(function ($r) {
                return $r->nomor_dokumen ?: 'LAMA-' . $r->id;
            })
            ->map(function ($group) {
                $first = $group->first();
                $statusLabel = [
                    ObatExpiredTindakLanjut::STATUS_MENUNGGU_RETUR => 'Menunggu Approval Retur',
                    ObatExpiredTindakLanjut::STATUS_SELESAI => 'Selesai',
                    ObatExpiredTindakLanjut::STATUS_BATAL => 'Batal (retur ditolak)',
                ][$first->status] ?? $first->status;

                return [
                    'nomor_dokumen' => $first->nomor_dokumen,
                    'jenis' => $first->tindak_lanjut === 'dimusnahkan' ? 'Pemusnahan' : 'Retur',
                    'tanggal' => optional($first->tanggal ?? $first->created_at)->format('d-m-Y'),
                    'obat' => $group->pluck('obat.nama')->filter()->unique()->implode(', '),
                    'jumlah_batch' => $group->count(),
                    'jumlah' => round((float) $group->sum('jumlah'), 2),
                    'status' => $first->status,
                    'status_label' => $statusLabel,
                    'petugas' => optional($first->creator)->name,
                    'print_url' => ($first->tindak_lanjut === 'dimusnahkan' && $first->nomor_dokumen)
                        ? route('erm.stok-gudang.berita-acara', $first->nomor_dokumen) : null,
                ];
            })
            ->values();

        return response()->json(['data' => $rows]);
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
            ->edTerverifikasi()
            ->whereDate($table . '.expiration_date', '<=', $threshold->format('Y-m-d'))
            ->select(
                $table . '.id',
                $table . '.obat_id',
                $table . '.batch',
                $table . '.stok',
                $table . '.expiration_date',
                'o.nama as obat_nama',
                'o.kode_obat as obat_kode',
                DB::raw("COALESCE(NULLIF(o.satuan_stok, ''), o.satuan) as obat_satuan")
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
                $link .= '<div class="text-muted small">' . e($row->obat_kode ?: '-') . '</div>';
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
