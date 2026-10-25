<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\HRD\Employee;
use App\Models\HRD\EmployeeSchedule;
use App\Models\HRD\PengajuanLembur;
use Illuminate\Http\Request;
use App\Models\ERM\StokOpname;
use App\Models\ERM\StokOpnameItem;
use App\Models\ERM\Gudang;
use App\Models\ERM\Obat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StokOpnameTemplateExport;
use App\Imports\StokOpnameImport;
use App\Exports\StokOpnameResultsExport;
use App\Exports\StokOpnameTemuanExport;

class StokOpnameController extends Controller
{
    /**
     * Get latest total nilai stok sistem and stok fisik for AJAX sync
     */
    public function getStokTotals($id)
    {
        return response()->json($this->hitungTotalOpname($id));
    }

    /**
     * Header totals (nilai = hpp x qty). Stok fisik is the stok after Submit, so nilai fisik uses it alone.
     * Temuan only explain the selisih: sisa = (fisik - sistem) + net temuan is the unexplained part.
     * Signs follow the item selisih: + = fisik lebih, - = fisik kurang.
     */
    private function hitungTotalOpname($stokOpnameId): array
    {
        $items = StokOpnameItem::with('obat')
            ->where('stok_opname_id', $stokOpnameId)
            ->get();
        $netPerItem = DB::table('erm_stok_opname_temuan')
            ->whereIn('stok_opname_item_id', $items->pluck('id'))
            ->groupBy('stok_opname_item_id')
            ->select('stok_opname_item_id', DB::raw("COALESCE(SUM(CASE WHEN jenis IN ('lebih','minus') THEN qty WHEN jenis IN ('kurang','plus') THEN -qty ELSE 0 END),0) as net"))
            ->pluck('net', 'stok_opname_item_id');

        $totalStokSistem = 0;
        $totalStokFisik = 0;
        $sisaNilai = 0;
        $itemsWithSelisih = 0;
        foreach ($items as $item) {
            $hpp = $item->obat ? (float) ($item->obat->hpp ?? 0) : 0;
            $sisa = ((float) $item->stok_fisik - (float) $item->stok_sistem) + (float) ($netPerItem[$item->id] ?? 0);

            $totalStokSistem += $hpp * (float) $item->stok_sistem;
            $totalStokFisik += $hpp * (float) $item->stok_fisik;
            $sisaNilai += $hpp * $sisa;
            if (abs($sisa) > 0.000001) {
                $itemsWithSelisih++;
            }
        }

        return [
            'totalStokSistem' => $totalStokSistem,
            'totalStokFisik' => $totalStokFisik,
            'selisihNilai' => $totalStokFisik - $totalStokSistem,
            'sisaNilai' => $sisaNilai,
            'itemsWithSelisih' => $itemsWithSelisih,
            'totalItems' => $items->count(),
        ];
    }
    /**
     * Inline update stok fisik and recalculate selisih
     */
    public function updateStokFisik(Request $request, $itemId)
    {
        $request->validate([
            'stok_fisik' => 'required|numeric|min:0',
        ], [
            'stok_fisik.min' => 'Stok fisik tidak boleh negatif.',
        ]);
        $item = StokOpnameItem::with('stokOpname')->findOrFail($itemId);
        if ($blocked = $this->tolakJikaSudahDiterapkan($item->stokOpname)) {
            return $blocked;
        }
        $item->stok_fisik = (float) $request->stok_fisik;
        $item->save();
        // selisih includes the item's temuan, same as everywhere else
        $selisih = $this->recalcSelisihTemuan($item);
        return response()->json([
            'success' => true,
            'stok_fisik' => (float) $item->stok_fisik,
            'selisih' => (float) $selisih,
        ]);
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
                        $u = \App\Models\User::query()->find($r->created_by);
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
            // gudang stok is stored with 2 decimals
            'qty' => 'required|numeric|min:0.01|decimal:0,2',
            // Backward compatible: old values (kurang/lebih) and new values (plus/minus)
            'jenis' => 'required|in:kurang,lebih,plus,minus',
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
                'created_by' => Auth::id()
            ]);

            $createdByName = Auth::user() ? Auth::user()->name : null;

            // Recalculate and persist selisih on the opname item
            $recordNet = DB::table('erm_stok_opname_temuan')
                ->where('stok_opname_item_id', $item->id)
                ->select(DB::raw("COALESCE(SUM(CASE WHEN jenis IN ('lebih','minus') THEN qty WHEN jenis IN ('kurang','plus') THEN -qty ELSE 0 END),0) as net"))
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

    public function processTemuanRecord(Request $request, $temuanId)
    {
        $result = $this->applyTemuanToStok($temuanId);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => 'Gagal memproses temuan: ' . $result['message']], 422);
        }

        return response()->json(['success' => true, 'message' => 'Temuan berhasil diproses ke stok', 'selisih' => $result['selisih']]);
    }

    /**
     * Delete a record-only temuan entry. A temuan already applied to stok stays as the audit trail.
     */
    public function deleteTemuanRecord(Request $request, $temuanId)
    {
        try {
            $r = \App\Models\ERM\StokOpnameTemuan::findOrFail($temuanId);
            if ((int) $r->process_status === 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Temuan ini sudah diproses ke stok, jadi tidak bisa dihapus. Buat temuan kebalikannya bila perlu koreksi.'
                ], 422);
            }

            $item = StokOpnameItem::find($r->stok_opname_item_id);
            $r->delete();

            $selisih = $item ? $this->recalcSelisihTemuan($item) : null;

            return response()->json(['success' => true, 'message' => 'Temuan berhasil dihapus', 'selisih' => $selisih]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus temuan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Apply one temuan to stok (single and bulk processing). Runs in one transaction with the temuan
     * locked, so a temuan is applied at most once and never half.
     * Returns ['success' => bool, 'message' => ?string, 'selisih' => ?float].
     */
    protected function applyTemuanToStok($temuanId)
    {
        try {
            $selisih = DB::transaction(function () use ($temuanId) {
                $r = \App\Models\ERM\StokOpnameTemuan::lockForUpdate()->findOrFail($temuanId);
                if ((int) $r->process_status === 1) {
                    throw new \RuntimeException('Temuan ini sudah diproses sebelumnya.');
                }

                $item = StokOpnameItem::with('stokOpname')->findOrFail($r->stok_opname_item_id);
                $stokOpname = $item->stokOpname;

                // Before Submit Stok the opname would later reset stok to stok_fisik and erase this temuan
                if (!$this->opnameSudahDiterapkan($stokOpname)) {
                    throw new \RuntimeException('Opname belum di-Submit Stok. Temuan baru bisa diproses setelah penyesuaian opname diterapkan.');
                }

                $qty = (float) $r->qty;
                if ($qty <= 0) {
                    throw new \RuntimeException('Qty temuan harus lebih dari 0.');
                }
                $jenis = $r->jenis ?? 'plus';
                $keterangan = 'temuan stok opname';
                if ($r->keterangan) $keterangan .= ' - ' . $r->keterangan;

                $stokService = app(\App\Services\ERM\StokService::class);

                // A temuan explains part of the selisih; processing undoes the opname adjustment for that qty
                // (the real transaction gets input later). plus/kurang = explains a selisih plus (fisik lebih)
                // -> remove; minus/lebih = explains a selisih minus (fisik kurang) -> add back
                if (in_array($jenis, ['lebih', 'minus'], true)) {
                    $targetBatch = $item->batch_name;
                    $targetExp = $item->expiration_date;
                    if (empty($targetBatch)) {
                        // No batch on the opname item: add to the nearest-expiry existing batch
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
                } elseif ($item->batch_id) {
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
                    // Remove from nearest expiry first; the whole qty must be available
                    $batches = \App\Models\ERM\ObatStokGudang::where('obat_id', $item->obat_id)
                        ->where('gudang_id', $stokOpname->gudang_id)
                        ->where('stok', '>', 0)
                        ->orderByRaw("CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END, expiration_date ASC")
                        ->lockForUpdate()
                        ->get();
                    $available = (float) $batches->sum('stok');
                    if (round($available, 4) < round($qty, 4)) {
                        throw new \RuntimeException("Stok di gudang ({$available}) lebih kecil dari qty temuan ({$qty}).");
                    }

                    $toRemove = $qty;
                    foreach ($batches as $b) {
                        if ($toRemove <= 0) break;
                        $remQty = min((float) $b->stok, $toRemove);
                        $stokService->kurangiStok(
                            $item->obat_id,
                            $stokOpname->gudang_id,
                            $remQty,
                            $b->batch,
                            'temuan_stok_opname',
                            $stokOpname->id,
                            $keterangan
                        );
                        $toRemove = round($toRemove - $remQty, 4);
                    }
                }

                $r->process_status = 1;
                $r->save();

                return $this->recalcSelisihTemuan($item);
            });

            return ['success' => true, 'selisih' => $selisih];
        } catch (\Throwable $e) {
            Log::warning('applyTemuanToStok failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * selisih = (stok fisik - stok sistem) + net of all temuan records of the item.
     */
    private function recalcSelisihTemuan(StokOpnameItem $item)
    {
        $recordNet = DB::table('erm_stok_opname_temuan')
            ->where('stok_opname_item_id', $item->id)
            ->select(DB::raw("COALESCE(SUM(CASE WHEN jenis IN ('lebih','minus') THEN qty WHEN jenis IN ('kurang','plus') THEN -qty ELSE 0 END),0) as net"))
            ->first();
        $item = $item->fresh();
        $item->selisih = ($item->stok_fisik - $item->stok_sistem) + ($recordNet ? (float) $recordNet->net : 0);
        $item->save();

        return $item->selisih;
    }

    /**
     * Bulk process temuan records (AJAX)
     */
    public function processTemuanBulk(Request $request)
    {
        $request->validate([ 'ids' => 'required|array' ]);
        $results = ['processed' => [], 'failed' => []];
        foreach ($request->ids as $id) {
            $res = $this->applyTemuanToStok($id);
            if (!empty($res['success'])) {
                $results['processed'][] = $id;
            } else {
                $results['failed'][$id] = $res['message'] ?? 'Unknown error';
            }
        }
        return response()->json(['success' => true, 'results' => $results]);
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
                    $recordNetQuery = "SELECT COALESCE(SUM(CASE WHEN t.jenis IN ('lebih','minus') THEN t.qty WHEN t.jenis IN ('kurang','plus') THEN -t.qty ELSE 0 END),0) FROM erm_stok_opname_temuan t WHERE t.stok_opname_item_id = erm_stok_opname_items.id";

                    return DB::table('erm_stok_opname_items')
                        ->where('stok_opname_id', $row->id)
                        ->whereRaw("ABS(((COALESCE(erm_stok_opname_items.stok_fisik,0) - COALESCE(erm_stok_opname_items.stok_sistem,0)) + COALESCE(({$recordNetQuery}),0))) > 0.000001")
                        ->count();
                })
                ->addColumn('aksi', function($row) {
                    $lihatBtn = '<a href="'.route('erm.stokopname.create', $row->id).'" class="btn btn-primary btn-sm">Stok Opname</a>';
                    $showTemuanBtn = '<button type="button" class="btn btn-secondary btn-sm btn-show-temuan" data-id="'.$row->id.'">Temuan</button>';
                    if ($row->status === 'selesai') {
                        $exportBtn = '<a href="'.route('erm.stokopname.exportResults', $row->id).'" class="btn btn-success btn-sm">Export Hasil</a>';
                        $group = '<div class="btn-group" role="group" aria-label="Aksi Stok Opname">' . $lihatBtn . $showTemuanBtn . $exportBtn . '</div>';
                        return $group;
                    }
                    return '<div class="btn-group" role="group" aria-label="Aksi Stok Opname">' . $lihatBtn . $showTemuanBtn . '</div>';
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
            'created_by' => Auth::id(),
        ]);
        return response()->json(['success' => true, 'id' => $stokOpname->id]);
    }

    public function generateLemburSo(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:hrd_employee,id',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i',
        ]);

        $baseStart = Carbon::parse($validated['tanggal'] . ' ' . $validated['jam_mulai']);
        $baseEnd = Carbon::parse($validated['tanggal'] . ' ' . $validated['jam_selesai']);
        if ($baseEnd->lessThanOrEqualTo($baseStart)) {
            $baseEnd->addDay();
        }

        $employees = Employee::whereIn('id', $validated['employee_ids'])
            ->get()
            ->keyBy('id');

        $scheduleRangeStart = $baseStart->copy()->subDay()->toDateString();
        $scheduleRangeEnd = $baseEnd->copy()->toDateString();

        $schedules = EmployeeSchedule::with('shift')
            ->whereIn('employee_id', $validated['employee_ids'])
            ->whereBetween('date', [$scheduleRangeStart, $scheduleRangeEnd])
            ->get()
            ->groupBy('employee_id');

        $results = [
            'generated' => [],
            'skipped' => [],
        ];

        DB::transaction(function () use ($validated, $employees, $schedules, $baseStart, $baseEnd, &$results) {
            foreach ($validated['employee_ids'] as $employeeId) {
                $employee = $employees->get($employeeId);

                if (!$employee) {
                    $results['skipped'][] = [
                        'employee_id' => $employeeId,
                        'employee_name' => 'Unknown',
                        'reason' => 'Karyawan tidak ditemukan.',
                    ];
                    continue;
                }

                $segments = [
                    [
                        'start' => $baseStart->copy(),
                        'end' => $baseEnd->copy(),
                    ],
                ];

                $employeeSchedules = collect($schedules->get($employeeId, []))
                    ->filter(function ($schedule) {
                        return $schedule->shift;
                    })
                    ->sortBy(function ($schedule) {
                        return $schedule->date . ' ' . $schedule->shift->start_time;
                    })
                    ->values();

                foreach ($employeeSchedules as $schedule) {
                    $shiftStart = Carbon::parse($schedule->date . ' ' . $schedule->shift->start_time);
                    $shiftEnd = Carbon::parse($schedule->date . ' ' . $schedule->shift->end_time);
                    if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
                        $shiftEnd->addDay();
                    }

                    $trimmedSegments = [];
                    foreach ($segments as $segment) {
                        $trimmedSegments = array_merge(
                            $trimmedSegments,
                            $this->subtractShiftInterval($segment['start'], $segment['end'], $shiftStart, $shiftEnd)
                        );
                    }
                    $segments = $trimmedSegments;
                }

                $segments = array_values(array_filter($segments, function ($segment) {
                    return $segment['start']->lt($segment['end']);
                }));

                if (empty($segments)) {
                    $results['skipped'][] = [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->nama,
                        'reason' => 'Jam SO seluruhnya masuk ke dalam shift.',
                    ];
                    continue;
                }

                foreach ($segments as $segment) {
                    $tanggal = $segment['start']->toDateString();
                    $jamMulai = $segment['start']->format('H:i');
                    $jamSelesai = $segment['end']->format('H:i');

                    $record = PengajuanLembur::firstOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'tanggal' => $tanggal,
                            'jam_mulai' => $jamMulai,
                            'jam_selesai' => $jamSelesai,
                        ],
                        [
                            'alasan' => 'Lembur SO',
                        ]
                    );

                    $results['generated'][] = [
                        'record_id' => $record->id,
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->nama,
                        'tanggal' => $tanggal,
                        'jam_mulai' => $jamMulai,
                        'jam_selesai' => $jamSelesai,
                    ];
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Lembur SO berhasil diproses.',
            'results' => $results,
        ]);
    }

    protected function subtractShiftInterval(Carbon $soStart, Carbon $soEnd, Carbon $shiftStart, Carbon $shiftEnd)
    {
        $overlapStart = $soStart->copy()->max($shiftStart);
        $overlapEnd = $soEnd->copy()->min($shiftEnd);

        if ($overlapStart->greaterThanOrEqualTo($overlapEnd)) {
            return [
                [
                    'start' => $soStart->copy(),
                    'end' => $soEnd->copy(),
                ],
            ];
        }

        $segments = [];
        if ($soStart->lt($overlapStart)) {
            $segments[] = [
                'start' => $soStart->copy(),
                'end' => $overlapStart->copy(),
            ];
        }
        if ($overlapEnd->lt($soEnd)) {
            $segments[] = [
                'start' => $overlapEnd->copy(),
                'end' => $soEnd->copy(),
            ];
        }

        return $segments;
    }

    public function create($id)
    {
        $stokOpname = StokOpname::with('gudang')->findOrFail($id);
        $items = StokOpnameItem::where('stok_opname_id', $stokOpname->id)->get();

        // Header totals (nilai sistem / fisik / selisih / belum terjelaskan)
        $totals = $this->hitungTotalOpname($stokOpname->id);

        // Prepare filter lists for the view
        $kategoriList = Obat::KATEGORI_LIST;
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

        return view('erm.stokopname.create', compact('stokOpname', 'items', 'totals', 'kategoriList', 'metodeList', 'expYears', 'availableObats'));
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
            if ($blocked = $this->tolakJikaSudahDiterapkan($stokOpname)) {
                return $blocked;
            }

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

    /**
     * Export temuan (record-only temuan entries) for a stok opname to Excel
     */
    public function exportTemuanExcel($id)
    {
        $stokOpname = StokOpname::findOrFail($id);
        return Excel::download(new StokOpnameTemuanExport($id), 'stok_opname_temuan_' . $id . '.xlsx');
    }

    /**
     * Return temuan records for a stok opname (AJAX DataTable)
     */
    public function temuanData(Request $request, $id)
    {
        $stokOpname = StokOpname::find($id);
        $opnameApplied = $stokOpname ? $this->opnameSudahDiterapkan($stokOpname) : false;

        $data = \App\Models\ERM\StokOpnameTemuan::with(['item.obat'])
            ->where('stok_opname_id', $id)
            ->orderBy('created_at', 'desc');

        return datatables()->of($data)
            ->addColumn('obat', function($r) {
                return ($r->item && $r->item->obat) ? $r->item->obat->nama : '-';
            })
            // allow searching by obat name
            ->filterColumn('obat', function($query, $keyword) {
                $query->whereHas('item.obat', function($q) use ($keyword) {
                    $q->where('nama', 'like', "%{$keyword}%");
                });
            })
            ->addColumn('process_status', function($r) {
                if (!empty($r->process_status) && $r->process_status == 1) {
                    return '<span class="badge badge-success">Diproses</span>';
                }
                return '<span class="badge badge-warning">Belum Diproses</span>';
            })
            ->addColumn('aksi', function($r) use ($opnameApplied) {
                $btn = '';
                // show "Proses" button only when the opname was applied (Submit Stok) and temuan not yet processed
                if ($opnameApplied && (empty($r->process_status) || $r->process_status == 0)) {
                    $btn .= '<button class="btn btn-success btn-sm process-temuan" data-id="'.$r->id.'">Proses</button> ';
                }
                $btn .= '<button class="btn btn-danger btn-sm delete-temuan" data-id="'.$r->id.'">Hapus</button>';
                return $btn;
            })
            ->with([
                'opname_status' => $stokOpname ? $stokOpname->status : null,
                'opname_applied' => $opnameApplied,
            ])
            ->rawColumns(['aksi','process_status'])
            ->make(true);
    }

    public function uploadExcel(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
        $stokOpname = StokOpname::findOrFail($id);
        if ($this->opnameSudahDiterapkan($stokOpname)) {
            return back()->with('error', 'Opname ini sudah di-Submit Stok, data tidak bisa diubah lagi.');
        }
        DB::beginTransaction();
        try {
            $import = new StokOpnameImport($stokOpname->id);
            Excel::import($import, $request->file('file'));
            DB::commit();
            if ($import->imported <= 0) {
                return back()->with('error', 'Tidak ada data yang diimport. Pastikan header kolom: obat_id, stok_sistem, stok_fisik. Baris dilewati: ' . json_encode($import->skippedRows));
            }
            $msg = 'Data stok opname berhasil diupload: ' . $import->imported . ' baris.';
            if ($import->skipped > 0) {
                $msg .= ' Dilewati ' . $import->skipped . ' baris: ' . json_encode($import->skippedRows);
            }
            return back()->with('success', $msg);
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

                // Net record-only temuan: treat 'minus/lebih' as +qty and 'plus/kurang' as -qty
                $recordNetQuery = "(SELECT COALESCE(SUM(CASE WHEN t.jenis IN ('lebih','minus') THEN t.qty WHEN t.jenis IN ('kurang','plus') THEN -t.qty ELSE 0 END),0) FROM erm_stok_opname_temuan t WHERE t.stok_opname_item_id = erm_stok_opname_items.id)";

                // Total temuan: use only record-only temuan (net of jenis: 'minus/lebih' as +, 'plus/kurang' as -)
                $totalTemuanSub = "COALESCE((" . $recordNetQuery . "),0) as total_temuan";

                // Adjusted selisih = (stok_fisik - stok_sistem) + net_record_temuan
                $adjustedSelisihSub = "((erm_stok_opname_items.stok_fisik - erm_stok_opname_items.stok_sistem) + COALESCE((" . $recordNetQuery . "),0)) as adjusted_selisih";

            $query = DB::table('erm_stok_opname_items')
                ->leftJoin('erm_obat', 'erm_stok_opname_items.obat_id', '=', 'erm_obat.id')
                ->select(
                    'erm_stok_opname_items.*',
                    'erm_obat.nama as nama_obat',
                    'erm_obat.hpp as hpp_obat',
                    DB::raw("COALESCE(NULLIF(erm_obat.satuan_stok, ''), erm_obat.satuan) as satuan"),
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

            // Header "Items Selisih": whole opname, not just the current page / filters
            $itemsWithSelisih = DB::table('erm_stok_opname_items')
                ->where('stok_opname_id', $id)
                ->whereRaw("ABS(" . $adjustExpr . ") > 0.000001")
                ->count();

            return datatables()->of($query)
                ->with(['items_with_selisih' => $itemsWithSelisih])
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
                    $query->where(DB::raw("COALESCE(NULLIF(erm_obat.satuan_stok, ''), erm_obat.satuan)"), 'like', "%{$keyword}%");
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
        $sudahDiterapkan = $this->opnameSudahDiterapkan($stokOpname);

        // Once the adjustment hit the stok the opname is final; reopening it would allow applying it again
        if ($sudahDiterapkan && $request->status !== 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Opname ini sudah diterapkan ke stok, jadi statusnya tidak bisa diubah lagi.'
            ], 422);
        }

        // 'selesai' only comes from Submit Stok; set by hand it hides Submit and opens temuan processing
        // while the stok was never adjusted
        if (!$sudahDiterapkan && $request->status === 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Status Selesai hanya bisa didapat lewat tombol Submit Stok.'
            ], 422);
        }

        $stokOpname->status = $request->status;
        $stokOpname->save();
        return response()->json(['success' => true, 'status' => $stokOpname->status]);
    }

    /**
     * True once updateStokFromOpname has written the adjustment (kartu stok rows reference the opname).
     */
    private function opnameSudahDiterapkan(StokOpname $stokOpname): bool
    {
        return \App\Models\ERM\KartuStok::where('ref_type', 'stok_opname')
            ->where('ref_id', $stokOpname->id)
            ->exists();
    }

    /**
     * 422 response when the opname was already applied (items are then a final record), else null.
     */
    private function tolakJikaSudahDiterapkan(StokOpname $stokOpname)
    {
        if (!$this->opnameSudahDiterapkan($stokOpname)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Opname ini sudah di-Submit Stok, data item tidak bisa diubah lagi.'
        ], 422);
    }

    /**
     * Generate stok opname items from current stock in selected gudang
     */
    public function generateStokOpnameItems($stokOpnameId)
    {
        try {
            DB::beginTransaction();
            
            $stokOpname = StokOpname::lockForUpdate()->findOrFail($stokOpnameId);
            $gudangId = $stokOpname->gudang_id;

            if ($blocked = $this->tolakJikaSudahDiterapkan($stokOpname)) {
                DB::rollBack();
                return $blocked;
            }
            // Regenerating deletes the items; their temuan would point to nothing
            $jumlahTemuan = \App\Models\ERM\StokOpnameTemuan::where('stok_opname_id', $stokOpnameId)->count();
            if ($jumlahTemuan > 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Opname ini sudah punya {$jumlahTemuan} temuan. Hapus temuannya dulu sebelum Generate Items ulang."
                ], 422);
            }

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
                'message' => 'Generated ' . $grouped->count() . ' items for stock opname'
            ]);

        } catch (\Throwable $e) {
            DB::rollback();
            return response()->json([
                'success' => false, 
                'message' => 'Failed to generate items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Items whose current gudang stok no longer equals the stok sistem captured at Generate,
     * i.e. a transaction happened during the opname. Shown as a warning before Submit Stok.
     */
    public function submitCheck($stokOpnameId)
    {
        $stokOpname = StokOpname::findOrFail($stokOpnameId);

        $items = StokOpnameItem::with('obat')
            ->where('stok_opname_id', $stokOpname->id)
            ->get();

        $currentPerObat = DB::table('erm_obat_stok_gudang')
            ->where('gudang_id', $stokOpname->gudang_id)
            ->whereIn('obat_id', $items->pluck('obat_id')->unique())
            ->whereNull('deleted_at')
            ->groupBy('obat_id')
            ->select('obat_id', DB::raw('SUM(stok) as total'))
            ->pluck('total', 'obat_id');
        $currentPerBatch = DB::table('erm_obat_stok_gudang')
            ->whereIn('id', $items->pluck('batch_id')->filter())
            ->pluck('stok', 'id');

        $berubah = [];
        foreach ($items as $item) {
            $current = $item->batch_id
                ? (float) ($currentPerBatch[$item->batch_id] ?? 0)
                : (float) ($currentPerObat[$item->obat_id] ?? 0);
            $sistem = (float) $item->stok_sistem;
            if (abs($current - $sistem) >= 0.01) {
                $berubah[] = [
                    'nama' => $item->obat ? $item->obat->nama : ('Obat #' . $item->obat_id),
                    'batch' => $item->batch_name,
                    'stok_sistem' => $sistem,
                    'stok_sekarang' => $current,
                ];
            }
        }

        return response()->json(['success' => true, 'berubah' => $berubah]);
    }

    /**
     * Update stock fisik based on stock opname result using StokService
     */
    public function updateStokFromOpname($stokOpnameId)
    {
        try {
            DB::beginTransaction();

            // Locked + applied at most once: re-applying would reset stok to the old physical count
            // and erase every sale since the opname
            $stokOpname = StokOpname::lockForUpdate()->findOrFail($stokOpnameId);
            if ($this->opnameSudahDiterapkan($stokOpname)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Penyesuaian stok dari opname ini sudah pernah diterapkan dan tidak bisa diterapkan ulang.'
                ], 422);
            }

            $stokService = app(\App\Services\ERM\StokService::class);
            $updatedCount = 0;
            
            // Process all items so we can record opname entries even when selisih == 0
            $items = StokOpnameItem::where('stok_opname_id', $stokOpnameId)
                ->with(['obatStokGudang', 'obat'])
                ->get();
            if ($items->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Opname ini belum punya item. Generate Items dulu.'
                ], 422);
            }

            foreach ($items as $item) {
                // If the item was generated per-batch we have obatStokGudang relation.
                // If the item is aggregated per-obat (batch_id == null), distribute the change across batches.
                if ($item->batch_id === null) {
                    // Aggregated flow: allocate target total across batches using expiry rules
                    $batches = \App\Models\ERM\ObatStokGudang::where('obat_id', $item->obat_id)
                        ->where('gudang_id', $stokOpname->gudang_id)
                        ->orderBy('expiration_date', 'desc')
                        ->get();

                    $currentTotal = (float) $batches->sum('stok');
                    $targetTotal = (float) $item->stok_fisik;

                    // The obat's stok rows were removed since Generate: counted stok goes to a row
                    // without batch; nothing counted still gets its kartu stok row (marks the opname applied)
                    if ($batches->isEmpty()) {
                        if ($targetTotal > 0.000001) {
                            $stokService->tambahStok(
                                $item->obat_id,
                                $stokOpname->gudang_id,
                                $targetTotal,
                                null,
                                null,
                                null,
                                null,
                                null,
                                null,
                                'stok_opname',
                                $stokOpname->id,
                                $this->buildStokOpnameKeterangan("OPNAME-{$stokOpname->periode_bulan}-{$stokOpname->periode_tahun}", 0, $targetTotal, 'surplus ' . $this->formatStockNumber($targetTotal) . ' (tanpa batch)')
                            );
                        } else {
                            \App\Models\ERM\KartuStok::create([
                                'obat_id' => $item->obat_id,
                                'gudang_id' => $stokOpname->gudang_id,
                                'tanggal' => now(),
                                'tipe' => 'stok_opname',
                                'qty' => 0,
                                'stok_setelah' => 0,
                                'ref_type' => 'stok_opname',
                                'ref_id' => $stokOpname->id,
                                'batch' => null,
                                'keterangan' => $this->buildStokOpnameKeterangan(null, 0, 0, 'tidak ada perubahan'),
                                'user_id' => Auth::id()
                            ]);
                        }
                        $updatedCount++;
                        continue;
                    }

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
                            'keterangan' => $this->buildStokOpnameKeterangan(null, $currentTotal, $targetTotal, 'tidak ada perubahan'),
                            'user_id' => Auth::id()
                        ]);
                        $updatedCount++;
                        continue;
                    }

                    // Generate stok opname reference number
                    $opnameRef = "OPNAME-{$stokOpname->periode_bulan}-{$stokOpname->periode_tahun}";

                    if ($targetTotal > $currentTotal) {
                        // Surplus goes to ONE batch; the other batches keep their stok. The batch with the
                        // farthest ED gets it (conservative for expiry); batches without ED come last.
                        $surplus = $targetTotal - $currentTotal;
                        $target = $batches->sortByDesc(function ($b) {
                            return $b->expiration_date ? \Carbon\Carbon::parse($b->expiration_date)->timestamp : -1;
                        })->first();
                        $current = (float) $target->stok;

                        $stokService->tambahStok(
                            $item->obat_id,
                            $stokOpname->gudang_id,
                            $surplus,
                            $target->batch,
                            $target->expiration_date,
                            null,
                            null,
                            null,
                            null,
                            'stok_opname',
                            $stokOpname->id,
                            $this->buildStokOpnameKeterangan($opnameRef, $current, $current + $surplus, 'surplus ' . $this->formatStockNumber($surplus) . ' (aggregated)')
                        );

                    } elseif ($targetTotal < $currentTotal) {
                        // Remove stock: remove from nearest-expiry batches first
                        $toRemove = $currentTotal - $targetTotal;
                        // Nearest ED first; batches without ED last (sortBy would put NULL first)
                        $batchesAsc = $batches->sortBy(function ($b) {
                            return $b->expiration_date ? \Carbon\Carbon::parse($b->expiration_date)->timestamp : PHP_INT_MAX;
                        });
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
                                $this->buildStokOpnameKeterangan($opnameRef, $available, $available - $remQty, 'shortage ' . $this->formatStockNumber($remQty) . ' (aggregated)')
                            );
                            // Rounded: float leftovers (e.g. 1e-13) must not become extra tiny kartu stok rows
                            $toRemove = round($toRemove - $remQty, 4);
                        }
                    }

                    $updatedCount++;
                    continue;
                }

                if (!$item->obatStokGudang) {
                    // Batch row removed since the import: re-create it with the counted stok
                    $targetStock = (float) $item->stok_fisik;
                    if ($targetStock > 0.000001) {
                        $stokService->tambahStok(
                            $item->obat_id,
                            $stokOpname->gudang_id,
                            $targetStock,
                            $item->batch_name,
                            $item->expiration_date,
                            null,
                            null,
                            null,
                            null,
                            'stok_opname',
                            $stokOpname->id,
                            $this->buildStokOpnameKeterangan("OPNAME-{$stokOpname->periode_bulan}-{$stokOpname->periode_tahun}", 0, $targetStock, 'surplus ' . $this->formatStockNumber($targetStock))
                        );
                        $updatedCount++;
                    }
                    continue;
                }

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
                        'keterangan' => $this->buildStokOpnameKeterangan(null, $currentStock, $targetStock, 'tidak ada perubahan'),
                        'user_id' => Auth::id()
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
                        $this->buildStokOpnameKeterangan($opnameRef, $currentStock, $targetStock, 'surplus ' . $this->formatStockNumber($delta))
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
                        $this->buildStokOpnameKeterangan($opnameRef, $currentStock, $targetStock, 'shortage ' . $this->formatStockNumber($qtyToRemove))
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

        } catch (\Throwable $e) {
            // Throwable, not only Exception: a PHP Error must also roll back and answer with a message
            DB::rollback();
            Log::error('updateStokFromOpname failed', ['stok_opname_id' => $stokOpnameId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false, 
                'message' => 'Failed to update stock: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function buildStokOpnameKeterangan($opnameRef, $stokSebelum, $stokSetelah, $status = null)
    {
        $parts = [];

        if ($opnameRef) {
            $parts[] = 'Adjustment Stok Opname ' . $opnameRef;
        } else {
            $parts[] = 'Stok opname tercatat';
        }

        if ($status) {
            $parts[] = $status;
        }

        $parts[] = 'stok sebelum: ' . $this->formatStockNumber($stokSebelum);
        $parts[] = 'stok setelah opname: ' . $this->formatStockNumber($stokSetelah);

        return implode(' - ', $parts);
    }

    protected function formatStockNumber($value)
    {
        $formatted = number_format((float) $value, 4, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
