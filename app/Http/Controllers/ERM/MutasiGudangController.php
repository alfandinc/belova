<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ERM\MutasiGudang;
use App\Models\ERM\MutasiGudangItem;
use App\Models\ERM\Gudang;
use App\Models\ERM\Obat;
use App\Models\ERM\ObatStokGudang;
use App\Services\ERM\GudangExpiredService;
use App\Services\ERM\StokService;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Transfer Antar Gudang: a request (pending) moves stok only when approved, batch by batch (usable
 * batches FEFO first, expired batches last; the user is warned about expired stok). Stok enters the system through Faktur Pembelian, Obat Hibah or
 * Penyesuaian Stok; never from here.
 */
class MutasiGudangController extends Controller
{
    // Roles that see the menu may also approve or reject; the requester may approve their own request
    private const APPROVER_ROLES = ['Admin', 'Farmasi', 'Beautician', 'Lab', 'Marketing'];

    // Inactive obat keep their name on old transfers (the Obat model hides them by default)
    private function relations(): array
    {
        $withInactive = function ($q) {
            $q->withoutGlobalScope('active');
        };

        return [
            'items.obat' => $withInactive,
            'obat' => $withInactive,
            'gudangAsal',
            'gudangTujuan',
            'requestedBy',
            'approvedBy',
            'rejectedBy',
        ];
    }

    private function canDecide($user = null): bool
    {
        $user = $user ?: Auth::user();
        return $user && $user->hasAnyRole(self::APPROVER_ROLES);
    }

    /**
     * Obat with stok in the gudang asal, stok summed over batches; stok_ed = the expired part (shown with a warning)
     */
    public function getObatGudang(Request $request)
    {
        $gudangId = $request->get('gudang_id');
        $search = $request->get('q');
        if (!$gudangId) {
            return response()->json(['results' => []]);
        }

        $query = ObatStokGudang::where('gudang_id', $gudangId)
            ->where('stok', '>', 0)
            ->select('erm_obat_stok_gudang.*')
            ->selectRaw(ObatStokGudang::sqlSudahEd() . ' as sudah_ed')
            ->whereHas('obat', function ($q) use ($search) {
                $q->where('status_aktif', 1);
                if ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('nama', 'like', "%$search%")
                            ->orWhere('kode_obat', 'like', "%$search%");
                    });
                }
            })
            ->with('obat');

        $grouped = [];
        foreach ($query->get() as $stokGudang) {
            if (!$stokGudang->obat) {
                continue;
            }
            $obatId = $stokGudang->obat_id;
            if (!isset($grouped[$obatId])) {
                $grouped[$obatId] = [
                    'id' => $stokGudang->obat->id,
                    'nama' => $stokGudang->obat->nama,
                    'stok' => 0,
                    'stok_ed' => 0,
                    'ed_terlama' => null,
                    'satuan' => $stokGudang->obat->satuan_stok_label,
                ];
            }
            $grouped[$obatId]['stok'] += (float) $stokGudang->stok;
            if ((int) $stokGudang->sudah_ed === 1) {
                $grouped[$obatId]['stok_ed'] += (float) $stokGudang->stok;
                $ed = \Carbon\Carbon::parse($stokGudang->expiration_date)->format('Y-m-d');
                if ($grouped[$obatId]['ed_terlama'] === null || $ed < $grouped[$obatId]['ed_terlama']) {
                    $grouped[$obatId]['ed_terlama'] = $ed;
                }
            }
        }

        $results = collect($grouped)->sortBy('nama')->values()->map(function ($row) {
            $row['stok'] = round($row['stok'], 2);
            $row['stok_ed'] = round($row['stok_ed'], 2);
            $row['ed_terlama'] = $row['ed_terlama'] ? \Carbon\Carbon::parse($row['ed_terlama'])->format('d/m/Y') : null;
            return $row;
        });

        return response()->json(['results' => $results]);
    }

    public function index()
    {
        $gudangs = Gudang::orderBy('nama')->get();
        // The gudang expired is neither a source nor a destination of a transfer
        $expiredGudangId = app(GudangExpiredService::class)->gudangId();
        $gudangTransfer = $gudangs->reject(function ($g) use ($expiredGudangId) {
            return $expiredGudangId && (int) $g->id === (int) $expiredGudangId;
        })->values();

        return view('erm.mutasi-gudang.index', compact('gudangs', 'gudangTransfer'));
    }

    public function data(Request $request)
    {
        // Gudang and period filters; the status tab is applied on top so the tab counts follow them
        $filtered = function ($q) use ($request) {
            if ($request->gudang_id) {
                $gudangId = $request->gudang_id;
                $q->where(function ($sub) use ($gudangId) {
                    $sub->where('gudang_asal_id', $gudangId)->orWhere('gudang_tujuan_id', $gudangId);
                });
            }
            if ($request->start) {
                $q->where('erm_mutasi_gudang.created_at', '>=', \Carbon\Carbon::parse($request->start)->startOfDay());
            }
            if ($request->end) {
                $q->where('erm_mutasi_gudang.created_at', '<=', \Carbon\Carbon::parse($request->end)->endOfDay());
            }
        };

        $counts = MutasiGudang::query()->where($filtered)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $withInactive = function ($q) {
            $q->withoutGlobalScope('active')->select('id', 'nama');
        };
        $userName = function ($q) {
            $q->select('id', 'name');
        };

        // Only what the table shows: no full models, items or obat records in the payload
        $query = MutasiGudang::query()
            ->select('erm_mutasi_gudang.id', 'nomor_mutasi', 'gudang_asal_id', 'gudang_tujuan_id', 'obat_id', 'status',
                'requested_by', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'erm_mutasi_gudang.created_at')
            ->with([
                'items' => function ($q) {
                    $q->select('id', 'mutasi_id', 'obat_id');
                },
                'items.obat' => $withInactive,
                'obat' => $withInactive,
                'gudangAsal:id,nama',
                'gudangTujuan:id,nama',
                'requestedBy' => $userName,
                'approvedBy' => $userName,
                'rejectedBy' => $userName,
            ])
            ->where($filtered)
            ->when(in_array($request->status, ['pending', 'approved', 'rejected'], true), function ($q) use ($request) {
                $q->where('status', $request->status);
            });

        return DataTables::of($query)
            ->addColumn('tanggal', function ($mutasi) {
                return $mutasi->created_at->format('d/m/Y H:i');
            })
            ->addColumn('obat', function ($mutasi) {
                $names = $mutasi->items->count()
                    ? $mutasi->items->map(function ($it) {
                        return $it->obat ? $it->obat->nama : ('Obat ID ' . $it->obat_id);
                    })->all()
                    : [$mutasi->obat ? $mutasi->obat->nama : '-'];

                return ['nama' => array_slice($names, 0, 3), 'lainnya' => max(0, count($names) - 3), 'jumlah_item' => count($names)];
            })
            // Search the obat names of the items (and of legacy single-obat rows)
            ->filterColumn('obat', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('items.obat', function ($o) use ($keyword) {
                        $o->withoutGlobalScopes()->where('nama', 'like', "%{$keyword}%");
                    })->orWhereHas('obat', function ($o) use ($keyword) {
                        $o->withoutGlobalScopes()->where('nama', 'like', "%{$keyword}%");
                    });
                });
            })
            ->filterColumn('nomor_mutasi', function ($query, $keyword) {
                $query->where('erm_mutasi_gudang.nomor_mutasi', 'like', "%{$keyword}%");
            })
            ->addColumn('gudang_asal', function ($mutasi) {
                return optional($mutasi->gudangAsal)->nama;
            })
            ->addColumn('gudang_tujuan', function ($mutasi) {
                return optional($mutasi->gudangTujuan)->nama;
            })
            ->addColumn('peminta', function ($mutasi) {
                return optional($mutasi->requestedBy)->name;
            })
            ->addColumn('diputuskan', function ($mutasi) {
                if ($mutasi->status === 'rejected') {
                    return ['nama' => optional($mutasi->rejectedBy)->name, 'waktu' => optional($mutasi->rejected_at)->format('d/m/Y H:i')];
                }
                if ($mutasi->status === 'approved') {
                    return ['nama' => optional($mutasi->approvedBy)->name, 'waktu' => optional($mutasi->approved_at)->format('d/m/Y H:i')];
                }
                return null;
            })
            ->orderColumn('tanggal', 'erm_mutasi_gudang.created_at $1')
            ->only(['id', 'nomor_mutasi', 'status', 'tanggal', 'obat', 'gudang_asal', 'gudang_tujuan', 'peminta', 'diputuskan'])
            ->with('counts', [
                'all' => (int) $counts->sum(),
                'pending' => (int) ($counts['pending'] ?? 0),
                'approved' => (int) ($counts['approved'] ?? 0),
                'rejected' => (int) ($counts['rejected'] ?? 0),
            ])
            ->make(true);
    }

    private function statusBadge(?string $status): string
    {
        $labels = [
            'pending' => '<span class="badge badge-warning">Menunggu approval</span>',
            'approved' => '<span class="badge badge-success">Disetujui</span>',
            'rejected' => '<span class="badge badge-danger">Ditolak</span>',
        ];
        return $labels[$status] ?? '<span class="badge badge-light">' . e($status) . '</span>';
    }

    /**
     * Print mutasi as PDF
     */
    public function print($id)
    {
        $mutasi = MutasiGudang::with($this->relations())
            ->findOrFail($id);

        foreach ($mutasi->items as $item) {
            if ($mutasi->status === 'approved') {
                // Stok right after this transfer, from the kartu stok rows it wrote
                [$item->stok_asal_sebelum, $item->stok_asal_setelah] = $this->stokSekitarMutasi($mutasi, $item->obat_id, $mutasi->gudang_asal_id);
                [$item->stok_tujuan_sebelum, $item->stok_tujuan_setelah] = $this->stokSekitarMutasi($mutasi, $item->obat_id, $mutasi->gudang_tujuan_id);
            } elseif ($mutasi->status === 'pending') {
                // Current stok (expired batches included) and the expected result
                $asal = (float) ObatStokGudang::where('obat_id', $item->obat_id)->where('gudang_id', $mutasi->gudang_asal_id)->where('stok', '>', 0)->sum('stok');
                $tujuan = (float) ObatStokGudang::where('obat_id', $item->obat_id)->where('gudang_id', $mutasi->gudang_tujuan_id)->sum('stok');
                $item->stok_asal_sebelum = $asal;
                $item->stok_asal_setelah = $asal - (float) $item->jumlah;
                $item->stok_tujuan_sebelum = $tujuan;
                $item->stok_tujuan_setelah = $tujuan + (float) $item->jumlah;
            }
            // Rejected: no stok moved, nothing to show
        }

        $alasanTolak = $mutasi->status === 'rejected' ? $mutasi->rejected_reason : null;

        $pdf = \PDF::loadView('erm.mutasi-gudang.print', compact('mutasi', 'alasanTolak'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('mutasi-' . ($mutasi->nomor_mutasi ?: $mutasi->id) . '.pdf');
    }

    /**
     * [before, after] stok of an obat in a gudang around an approved transfer: current stok minus every
     * movement written after this transfer's last kartu stok row. Null when the transfer has no rows.
     */
    private function stokSekitarMutasi(MutasiGudang $mutasi, int $obatId, int $gudangId): array
    {
        $rows = DB::table('erm_kartu_stok')
            ->where('ref_type', 'mutasi_gudang')
            ->where('ref_id', $mutasi->id)
            ->where('obat_id', $obatId)
            ->where('gudang_id', $gudangId)
            ->get(['id', 'tipe', 'qty']);
        if ($rows->isEmpty()) {
            return [null, null];
        }

        $signed = "CASE WHEN tipe = 'masuk' THEN qty WHEN tipe = 'keluar' THEN -qty ELSE 0 END";
        $now = (float) ObatStokGudang::where('obat_id', $obatId)->where('gudang_id', $gudangId)->sum('stok');
        $later = (float) DB::table('erm_kartu_stok')
            ->where('obat_id', $obatId)
            ->where('gudang_id', $gudangId)
            ->where('id', '>', $rows->max('id'))
            ->sum(DB::raw($signed));

        $after = $now - $later;
        $moved = $rows->sum(function ($r) {
            return $r->tipe === 'masuk' ? (float) $r->qty : ($r->tipe === 'keluar' ? -(float) $r->qty : 0.0);
        });

        return [$after - $moved, $after];
    }

    public function store(Request $request)
    {
        if (!$this->canDecide()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses untuk membuat transfer antar gudang.'], 403);
        }

        $items = collect($request->input('items', []))->map(function ($item) {
            if (is_array($item) && array_key_exists('jumlah', $item)) {
                $item['jumlah'] = str_replace(',', '.', (string) $item['jumlah']);
            }
            return $item;
        })->toArray();
        $request->merge(['items' => $items]);

        $request->validate([
            'gudang_asal_id' => 'required|exists:erm_gudang,id',
            'gudang_tujuan_id' => 'required|exists:erm_gudang,id|different:gudang_asal_id',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.jumlah' => 'required|numeric|min:0.01',
            'items.*.keterangan' => 'nullable|string|max:255',
        ], [
            'gudang_tujuan_id.different' => 'Gudang tujuan tidak boleh sama dengan gudang asal.',
            'items.required' => 'Tambahkan minimal satu obat.',
            'items.*.jumlah.min' => 'Jumlah setiap obat harus lebih dari 0.',
        ]);

        try {
            // Begin first: the catch below rolls back, and must only undo this method's own transaction
            DB::beginTransaction();

            // Stok in the gudang expired may only leave through Retur Pembelian or Pemusnahan,
            // and usable stok must not be sent there
            $expiredService = app(GudangExpiredService::class);
            $expiredService->assertBukanGudangExpired($request->gudang_asal_id, 'dimutasi ke gudang lain');
            if ($expiredService->isGudangExpired($request->gudang_tujuan_id)) {
                throw new \Exception('Gudang ED tidak bisa menjadi tujuan transfer. Pindahkan batch kadaluarsa lewat Manajemen Stok (Pindah ke Gudang ED).');
            }

            // One line per obat: the same obat entered twice is summed before the stok check
            $merged = [];
            foreach ($request->items as $it) {
                $obatId = (int) $it['obat_id'];
                if (!isset($merged[$obatId])) {
                    $merged[$obatId] = ['obat_id' => $obatId, 'jumlah' => 0.0, 'keterangan' => []];
                }
                $merged[$obatId]['jumlah'] += (float) $it['jumlah'];
                if (!empty($it['keterangan'])) {
                    $merged[$obatId]['keterangan'][] = trim($it['keterangan']);
                }
            }

            // Expired batches count too (the user was warned when picking the obat)
            foreach ($merged as $it) {
                $stok = (float) ObatStokGudang::where('obat_id', $it['obat_id'])
                    ->where('gudang_id', $request->gudang_asal_id)
                    ->where('stok', '>', 0)
                    ->sum('stok');
                if (round($stok, 4) < round($it['jumlah'], 4)) {
                    $nama = optional(Obat::withInactive()->find($it['obat_id']))->nama ?? "ID {$it['obat_id']}";
                    throw new \Exception("Stok {$nama} di gudang asal tidak mencukupi (tersedia " . $this->fmt($stok) . ', diminta ' . $this->fmt($it['jumlah']) . ').');
                }
            }

            $mutasi = MutasiGudang::create([
                'nomor_mutasi' => $this->nomorMutasi(),
                'gudang_asal_id' => $request->gudang_asal_id,
                'gudang_tujuan_id' => $request->gudang_tujuan_id,
                'keterangan' => $request->keterangan ?: null,
                'status' => 'pending',
                'requested_by' => Auth::id(),
            ]);

            foreach ($merged as $it) {
                MutasiGudangItem::create([
                    'mutasi_id' => $mutasi->id,
                    'obat_id' => $it['obat_id'],
                    'jumlah' => $it['jumlah'],
                    'keterangan' => $it['keterangan'] ? mb_substr(implode('; ', array_unique($it['keterangan'])), 0, 255) : null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Transfer {$mutasi->nomor_mutasi} dibuat dan menunggu approval. Stok belum berpindah.",
                'id' => $mutasi->id,
                'nomor_mutasi' => $mutasi->nomor_mutasi,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // MUT-YmdHis-NN; the column is unique, so a number taken in the same second gets another suffix
    private function nomorMutasi(): string
    {
        $base = 'MUT-' . date('YmdHis');
        for ($i = 0; $i < 20; $i++) {
            $nomor = $base . '-' . str_pad((string) random_int(1, 99), 2, '0', STR_PAD_LEFT);
            if (!MutasiGudang::where('nomor_mutasi', $nomor)->exists()) {
                return $nomor;
            }
        }
        return $base . '-' . uniqid();
    }

    private function fmt($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    }

    public function show($id)
    {
        $mutasi = MutasiGudang::with($this->relations())
            ->findOrFail($id);

        $items = $mutasi->items->count() ? $mutasi->items : collect([(object) ['obat' => $mutasi->obat, 'obat_id' => $mutasi->obat_id, 'jumlah' => $mutasi->jumlah, 'keterangan' => null]]);

        // Pending: stok in the gudang asal now, so the approver sees what will fail and what is expired
        $stokTersedia = null;
        $stokEd = collect();
        if ($mutasi->status === 'pending') {
            $rows = ObatStokGudang::where('gudang_id', $mutasi->gudang_asal_id)
                ->whereIn('obat_id', $items->pluck('obat_id')->filter()->all())
                ->where('stok', '>', 0)
                ->select('obat_id', DB::raw('SUM(stok) as stok'), DB::raw('SUM(stok * ' . ObatStokGudang::sqlSudahEd() . ') as stok_ed'))
                ->groupBy('obat_id')
                ->get()
                ->keyBy('obat_id');
            $stokTersedia = $rows->map(function ($r) {
                return (float) $r->stok;
            });
            $stokEd = $rows->map(function ($r) {
                return (float) $r->stok_ed;
            });
        }
        // Expired stok each item will take: usable batches go first, so only what they cannot cover
        $edTerpakai = $items->mapWithKeys(function ($it) use ($stokTersedia, $stokEd) {
            if ($stokTersedia === null) {
                return [$it->obat_id => 0.0];
            }
            $ed = (float) ($stokEd[$it->obat_id] ?? 0);
            $layak = (float) ($stokTersedia[$it->obat_id] ?? 0) - $ed;
            return [$it->obat_id => max(0.0, min($ed, (float) $it->jumlah - $layak))];
        });
        $jumlahEd = $edTerpakai->filter(function ($v) {
            return $v > 0;
        })->count();
        $kurang = $stokTersedia ? $items->filter(function ($it) use ($stokTersedia) {
            return round((float) ($stokTersedia[$it->obat_id] ?? 0), 4) < round((float) $it->jumlah, 4);
        })->count() : 0;

        $alasanTolak = $mutasi->status === 'rejected' ? $mutasi->rejected_reason : null;
        $statusBadge = $this->statusBadge($mutasi->status);
        $html = view('erm.mutasi-gudang._detail', compact('mutasi', 'items', 'alasanTolak', 'statusBadge', 'stokTersedia', 'kurang', 'edTerpakai', 'jumlahEd'))->render();

        return response()->json([
            'html' => $html,
            'status' => $mutasi->status,
            'nomor_mutasi' => $mutasi->nomor_mutasi,
            'gudang_asal' => optional($mutasi->gudangAsal)->nama,
            'gudang_tujuan' => optional($mutasi->gudangTujuan)->nama,
            'items' => $items->map(function ($it) use ($edTerpakai) {
                return [
                    'nama' => $it->obat->nama ?? ('Obat ID ' . $it->obat_id),
                    'jumlah' => $this->fmt($it->jumlah),
                    'satuan' => $it->obat->satuan_stok_label ?? '',
                    'jumlah_ed' => ($edTerpakai[$it->obat_id] ?? 0) > 0 ? $this->fmt($edTerpakai[$it->obat_id]) : null,
                ];
            })->values(),
            'stok_kurang' => $kurang,
            'jumlah_obat_ed' => $jumlahEd,
            'can_approve' => $mutasi->status === 'pending' && $this->canDecide(),
        ]);
    }

    public function approve($id)
    {
        if (!$this->canDecide()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menyetujui transfer.'], 403);
        }

        try {
            DB::beginTransaction();

            // Locked so a double click / two approvers cannot move the stok twice
            $mutasi = MutasiGudang::lockForUpdate()->findOrFail($id);
            if ($mutasi->status !== 'pending') {
                throw new \Exception('Transfer ini sudah diproses.');
            }

            // Re-checked at approval: requests created before these rules may still involve the gudang expired
            $expiredService = app(GudangExpiredService::class);
            $expiredService->assertBukanGudangExpired($mutasi->gudang_asal_id, 'dimutasi ke gudang lain');
            if ($expiredService->isGudangExpired($mutasi->gudang_tujuan_id)) {
                throw new \Exception('Gudang ED tidak bisa menjadi tujuan transfer. Tolak transfer ini dan pindahkan batch kadaluarsa lewat Manajemen Stok.');
            }

            $stokService = app(StokService::class);
            $gudangAsal = $mutasi->gudangAsal->nama;
            $gudangTujuan = $mutasi->gudangTujuan->nama;

            $items = $mutasi->items()->get();
            if ($items->isEmpty()) {
                // Legacy single-item transfer
                $items = collect([(object) ['obat_id' => $mutasi->obat_id, 'jumlah' => $mutasi->jumlah]]);
            }

            foreach ($items as $item) {
                // Usable batches first (FEFO), expired batches only for what they cannot cover
                $stokGudangList = ObatStokGudang::where('obat_id', $item->obat_id)
                    ->where('gudang_id', $mutasi->gudang_asal_id)
                    ->where('stok', '>', 0)
                    ->orderByRaw(ObatStokGudang::sqlSudahEd())
                    ->urutFefo()
                    ->lockForUpdate()
                    ->get();

                $jumlahMutasi = (float) $item->jumlah;
                $stokTersedia = (float) $stokGudangList->sum('stok');
                if (round($stokTersedia, 4) < round($jumlahMutasi, 4)) {
                    $namaObat = optional(Obat::withInactive()->find($item->obat_id))->nama ?? "ID {$item->obat_id}";
                    throw new \Exception("Stok {$namaObat} di {$gudangAsal} tidak mencukupi (tersedia " . $this->fmt($stokTersedia) . ', diminta ' . $this->fmt($jumlahMutasi) . ').');
                }

                foreach ($stokGudangList as $stokGudang) {
                    if ($jumlahMutasi <= 0) break;
                    $ambil = min((float) $stokGudang->stok, $jumlahMutasi);

                    // Batch and ED move along with the stok
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

            $mutasi->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => "Transfer {$mutasi->nomor_mutasi} disetujui. Stok sudah berpindah ke {$gudangTujuan}.",
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reject(Request $request, $id)
    {
        if (!$this->canDecide()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menolak transfer.'], 403);
        }

        $request->validate(
            ['reason' => 'required|string|max:200'],
            ['reason.required' => 'Alasan penolakan wajib diisi.']
        );

        try {
            // Locked like approve(): a transfer being approved cannot be marked rejected at the same time
            $nomor = DB::transaction(function () use ($id, $request) {
                $mutasi = MutasiGudang::lockForUpdate()->findOrFail($id);

                if ($mutasi->status !== 'pending') {
                    throw new \Exception('Transfer ini sudah diproses.');
                }

                $mutasi->update([
                    'status' => 'rejected',
                    'rejected_by' => Auth::id(),
                    'rejected_at' => now(),
                    'rejected_reason' => trim($request->reason),
                ]);

                return $mutasi->nomor_mutasi;
            });

            return response()->json([
                'success' => true,
                'message' => "Transfer {$nomor} ditolak. Stok tidak berubah.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
