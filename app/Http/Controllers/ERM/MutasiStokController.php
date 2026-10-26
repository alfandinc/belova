<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Gudang;
use App\Models\ERM\KartuStok;
use App\Models\ERM\MutasiStok;
use App\Models\ERM\MutasiStokItem;
use App\Models\ERM\Obat;
use App\Models\ERM\ObatStokGudang;
use App\Services\ERM\StokService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/**
 * Penyesuaian Stok (stored as Mutasi Stok): the only document for stock corrections outside
 * purchases, sales, transfers and returns.
 *
 * Flow: create -> posted immediately (below the approval threshold) or pending -> approved (posted)
 * or rejected. A posted document is never edited; it is cancelled (reversed) and a new one is made.
 */
class MutasiStokController extends Controller
{
    // Every item needs one of these reasons
    public const ALASAN = [
        'keluar' => ['Rusak', 'Hilang', 'Koreksi hitung', 'Pemakaian internal', 'Sampel', 'Lainnya'],
        'masuk' => ['Koreksi hitung', 'Ditemukan saat cek fisik', 'Lainnya'],
    ];

    // Every Penyesuaian Stok role may create masuk too; masuk always waits for approval (config stok.penyesuaian)
    public const MASUK_ROLES = ['Admin', 'Farmasi', 'Beautician', 'Lab', 'Marketing'];

    // Form value for the stok row without a batch name (an empty value means "no batch chosen")
    public const BATCH_NULL = '__null__';

    public function index(Request $request)
    {
        $gudangs = Gudang::orderBy('nama')->get();
        $alasan = self::ALASAN;
        $canMasuk = $this->canMasuk();
        $isApprover = $this->isApprover();
        $approvalThreshold = (float) config('stok.penyesuaian.approval_threshold');
        $pendingCount = MutasiStok::where('status', MutasiStok::STATUS_PENDING)->count();

        // Opened from Manajemen Stok with the form pre-filled (gudang, jenis, obat, batch, jumlah)
        $prefill = null;
        if ($request->filled('obat_id') && $request->filled('gudang_id')) {
            $obat = Obat::withInactive()->find($request->obat_id);
            if ($obat) {
                $jenis = $request->jenis === 'masuk' && $canMasuk ? 'masuk' : 'keluar';
                $prefill = [
                    'gudang_id' => (int) $request->gudang_id,
                    'jenis' => $jenis,
                    'obat_id' => $obat->id,
                    'obat_nama' => $obat->nama,
                    'satuan' => $obat->satuan_stok_label,
                    'batch' => $request->has('batch') ? (string) $request->batch : null,
                    'jumlah' => $request->filled('jumlah') ? (float) $request->jumlah : null,
                    'alasan' => in_array($request->alasan, self::ALASAN[$jenis], true) ? $request->alasan : null,
                ];
            }
        }

        return view('erm.mutasi-stok.index', compact('gudangs', 'alasan', 'canMasuk', 'isApprover', 'approvalThreshold', 'pendingCount', 'prefill'));
    }

    private function canMasuk(): bool
    {
        return (bool) optional(Auth::user())->hasAnyRole(self::MASUK_ROLES);
    }

    private function isApprover(): bool
    {
        return (bool) optional(Auth::user())->hasAnyRole(config('stok.penyesuaian.approver_roles', ['Admin']));
    }

    /**
     * Batches of an obat in a gudang for the batch picker.
     */
    public function batches(Request $request)
    {
        $request->validate([
            'gudang_id' => 'required|exists:erm_gudang,id',
            'obat_id' => 'required|exists:erm_obat,id',
        ]);

        $rows = ObatStokGudang::where('gudang_id', $request->gudang_id)
            ->where('obat_id', $request->obat_id)
            ->urutFefo()
            ->get(['batch', 'stok', 'expiration_date']);

        return response()->json(['results' => $rows->map(function ($row) {
            return [
                'batch' => $row->batch,
                'stok' => (float) $row->stok,
                'expiration_date' => optional($row->expiration_date)->format('d-m-Y'),
                'expired' => $row->isExpired(),
                'migrasi' => $row->isMigrasi(),
            ];
        })->values()]);
    }

    public function data(Request $request)
    {
        $query = MutasiStok::with(['gudang', 'user', 'items.obat', 'approvedBy']);

        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }

        if ($request->filled('jenis_mutasi')) {
            $query->where('jenis_mutasi', $request->jenis_mutasi);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('tanggal', function (MutasiStok $mutasi) {
                return optional($mutasi->tanggal_mutasi)->format('d/m/Y');
            })
            ->addColumn('tanggal_input', function (MutasiStok $mutasi) {
                return optional($mutasi->tanggal_input)->format('d/m/Y H:i');
            })
            ->addColumn('gudang_nama', function (MutasiStok $mutasi) {
                return optional($mutasi->gudang)->nama ?: '-';
            })
            ->addColumn('jenis_label', function (MutasiStok $mutasi) {
                return $mutasi->jenis_mutasi === 'masuk'
                    ? '<span class="badge badge-success">Masuk</span>'
                    : '<span class="badge badge-danger">Keluar</span>';
            })
            ->addColumn('item_summary', function (MutasiStok $mutasi) {
                $items = $mutasi->items;

                if ($items->isEmpty()) {
                    return '-';
                }

                $summary = $items->take(3)->map(function ($item) {
                    $namaObat = optional($item->obat)->nama ?: 'Obat #' . $item->obat_id;
                    $alasan = $item->alasan ? ' <span class="badge badge-light border">' . e($item->alasan) . '</span>' : '';

                    return e($namaObat) . ' <small class="text-muted">(' . $this->fmt($item->jumlah) . ')</small>' . $alasan;
                })->implode('<br>');

                if ($items->count() > 3) {
                    $summary .= '<br><small class="text-muted">+' . ($items->count() - 3) . ' item lainnya</small>';
                }

                return $summary;
            })
            ->addColumn('nilai', function (MutasiStok $mutasi) {
                return (float) $mutasi->total_nilai > 0 ? 'Rp ' . number_format((float) $mutasi->total_nilai, 0, ',', '.') : '-';
            })
            ->addColumn('user_name', function (MutasiStok $mutasi) {
                return optional($mutasi->user)->name ?: '-';
            })
            ->addColumn('status_label', function (MutasiStok $mutasi) {
                return $this->statusBadge($mutasi);
            })
            ->addColumn('action', function (MutasiStok $mutasi) {
                $buttons = '<div class="btn-group" role="group" aria-label="Aksi">';
                $buttons .= '<button type="button" class="btn btn-info btn-sm btn-detail" data-id="' . $mutasi->id . '" title="Detail"><i class="fas fa-eye"></i></button>';
                $buttons .= '<a href="' . route('erm.mutasi-stok.print', $mutasi->id) . '" class="btn btn-secondary btn-sm" target="_blank" title="Cetak"><i class="fas fa-print"></i></a>';
                if ($mutasi->status === MutasiStok::STATUS_PENDING && $this->canDecide($mutasi)) {
                    $buttons .= '<button type="button" class="btn btn-warning btn-sm btn-detail" data-id="' . $mutasi->id . '" title="Perlu approval Anda"><i class="fas fa-user-check"></i></button>';
                }
                $buttons .= '</div>';

                return $buttons;
            })
            ->rawColumns(['jenis_label', 'item_summary', 'status_label', 'action'])
            ->make(true);
    }

    public function getObatGudang(Request $request)
    {
        $request->validate([
            'gudang_id' => 'required|exists:erm_gudang,id',
            'jenis_mutasi' => 'required|in:masuk,keluar',
            'q' => 'nullable|string',
        ]);

        $search = trim((string) $request->get('q', ''));
        $gudangId = (int) $request->gudang_id;
        $jenisMutasi = $request->jenis_mutasi;

        $query = Obat::query()
            ->leftJoin('erm_obat_stok_gudang as osg', function ($join) use ($gudangId) {
                $join->on('osg.obat_id', '=', 'erm_obat.id')
                    ->where('osg.gudang_id', '=', $gudangId)
                    ->whereNull('osg.deleted_at');
            })
            ->select(
                'erm_obat.id',
                'erm_obat.nama',
                'erm_obat.kode_obat',
                DB::raw("COALESCE(NULLIF(erm_obat.satuan_stok, ''), erm_obat.satuan) as satuan"),
                // For keluar only usable (not expired) batches count, matching the FEFO allocation
                DB::raw($jenisMutasi === 'keluar'
                    ? "COALESCE(SUM(CASE WHEN osg.expiration_date IS NULL OR osg.expiration_date >= CURDATE() OR osg.batch LIKE '" . ObatStokGudang::MIGRASI_BATCH_PREFIX . "%' THEN osg.stok ELSE 0 END), 0) as stok_gudang"
                    : 'COALESCE(SUM(osg.stok), 0) as stok_gudang')
            )
            ->where('erm_obat.status_aktif', 1)
            ->groupBy('erm_obat.id', 'erm_obat.nama', 'erm_obat.kode_obat', 'erm_obat.satuan', 'erm_obat.satuan_stok')
            ->orderBy('erm_obat.nama')
            ->limit(20);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('erm_obat.nama', 'like', "%{$search}%")
                    ->orWhere('erm_obat.kode_obat', 'like', "%{$search}%");
            });
        }

        if ($jenisMutasi === 'keluar') {
            $query->havingRaw('stok_gudang > 0');
        }

        $results = $query->get()->map(function ($obat) {
            $stok = (float) $obat->stok_gudang;
            $satuan = $obat->satuan ? ' ' . $obat->satuan : '';

            return [
                'id' => $obat->id,
                'text' => $obat->nama,
                'nama' => $obat->nama,
                'kode_obat' => $obat->kode_obat,
                'satuan' => $obat->satuan,
                'stok' => $stok,
                'stok_display' => $this->fmt($stok) . $satuan,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Create a document, or update a pending one (edit_id). Below the approval threshold the stok
     * changes immediately; otherwise the document waits for an approver.
     */
    public function store(Request $request, StokService $stokService)
    {
        $request->validate([
            'gudang_id' => 'required|exists:erm_gudang,id',
            'jenis_mutasi' => 'required|in:masuk,keluar',
            'tanggal_mutasi' => 'required|date',
            'edit_id' => 'nullable|exists:erm_mutasi_stok,id',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.jumlah' => 'required|numeric|min:0.01',
            'items.*.alasan' => ['required', Rule::in(self::ALASAN[$request->jenis_mutasi] ?? [])],
            'items.*.keterangan' => 'required|string|max:200',
            // Keluar: a batch, or empty for FEFO. Masuk: an existing batch, or batch_baru + expiration_date
            'items.*.batch' => 'nullable|string|max:255',
            'items.*.batch_baru' => 'nullable|string|max:255',
            'items.*.expiration_date' => 'nullable|date',
        ], [
            'items.*.alasan.required' => 'Alasan wajib dipilih untuk setiap item.',
            'items.*.alasan.in' => 'Alasan tidak sesuai dengan jenis penyesuaian.',
            'items.*.keterangan.required' => 'Keterangan wajib diisi untuk setiap item.',
        ]);

        if ($request->jenis_mutasi === 'masuk' && !$this->canMasuk()) {
            return response()->json([
                'success' => false,
                'message' => 'Penyesuaian masuk (menambah stok tanpa dokumen pembelian) tidak diizinkan untuk role Anda.',
            ], 403);
        }

        try {
            if ($request->jenis_mutasi === 'keluar') {
                // Stok in the gudang expired may only leave through Retur Pembelian or Pemusnahan
                app(\App\Services\ERM\GudangExpiredService::class)
                    ->assertBukanGudangExpired($request->gudang_id, 'dikeluarkan lewat penyesuaian stok');
            }

            $mutasi = DB::transaction(function () use ($request, $stokService) {
                if ($request->filled('edit_id')) {
                    $mutasi = MutasiStok::lockForUpdate()->findOrFail($request->edit_id);
                    if ($mutasi->status !== MutasiStok::STATUS_PENDING) {
                        throw new \RuntimeException('Dokumen yang sudah diposting tidak bisa diubah. Batalkan dokumen ini lalu buat dokumen baru.');
                    }
                    if ((int) $mutasi->user_id !== (int) Auth::id() && !$this->isApprover()) {
                        throw new \RuntimeException('Hanya pembuat dokumen atau approver yang bisa mengubah dokumen ini.');
                    }
                    $mutasi->items()->delete();
                    $mutasi->update([
                        'gudang_id' => $request->gudang_id,
                        'jenis_mutasi' => $request->jenis_mutasi,
                        'tanggal_mutasi' => Carbon::parse($request->tanggal_mutasi),
                    ]);
                } else {
                    $mutasi = MutasiStok::create([
                        'nomor_mutasi' => $this->generateNomorMutasi($request->jenis_mutasi),
                        'gudang_id' => $request->gudang_id,
                        'jenis_mutasi' => $request->jenis_mutasi,
                        'tanggal_mutasi' => Carbon::parse($request->tanggal_mutasi),
                        'tanggal_input' => now(),
                        'status' => MutasiStok::STATUS_PENDING,
                        'user_id' => Auth::id(),
                    ]);
                }

                $hppPerObat = Obat::withInactive()
                    ->whereIn('id', collect($request->items)->pluck('obat_id'))
                    ->pluck('hpp', 'id');

                $total = 0;
                foreach ($request->items as $itemData) {
                    $batchDipilih = isset($itemData['batch']) && $itemData['batch'] !== '';
                    $batchBaru = trim((string) ($itemData['batch_baru'] ?? ''));
                    $hpp = (float) ($hppPerObat[$itemData['obat_id']] ?? 0);
                    $nilai = round($hpp * (float) $itemData['jumlah'], 2);
                    $total += $nilai;

                    $item = $mutasi->items()->create([
                        'obat_id' => $itemData['obat_id'],
                        'jumlah' => $itemData['jumlah'],
                        'alasan' => $itemData['alasan'],
                        'keterangan' => trim($itemData['keterangan']),
                        'batch_dipilih' => $batchBaru !== '' || $batchDipilih,
                        'batch' => $batchBaru !== '' ? $batchBaru : ($batchDipilih && $itemData['batch'] !== self::BATCH_NULL ? $itemData['batch'] : null),
                        'batch_baru' => $batchBaru !== '',
                        'expiration_date' => $batchBaru !== '' ? ($itemData['expiration_date'] ?? null) : null,
                        'hpp' => $hpp,
                        'nilai' => $nilai,
                    ]);

                    // Fail now rather than at approval: batch, ED and available stok must be valid
                    $this->validateItem($mutasi, $item);
                }

                $butuhApproval = $this->needsApproval($mutasi->jenis_mutasi, $total);
                $mutasi->update(['total_nilai' => round($total, 2), 'butuh_approval' => $butuhApproval]);

                if (!$butuhApproval) {
                    $this->post($mutasi->fresh('items.obat'), $stokService, null);
                }

                return $mutasi->fresh();
            });

            $message = $mutasi->status === MutasiStok::STATUS_PENDING
                ? 'Penyesuaian ' . $mutasi->nomor_mutasi . ' disimpan dan menunggu approval (nilai Rp ' . number_format((float) $mutasi->total_nilai, 0, ',', '.') . '). Stok belum berubah.'
                : 'Penyesuaian ' . $mutasi->nomor_mutasi . ' disimpan dan stok sudah diperbarui.';

            return response()->json(['success' => true, 'message' => $message, 'status' => $mutasi->status]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function approve($id, StokService $stokService)
    {
        try {
            $mutasi = DB::transaction(function () use ($id, $stokService) {
                $mutasi = MutasiStok::with('items.obat')->lockForUpdate()->findOrFail($id);
                $this->assertCanDecide($mutasi);
                $this->post($mutasi, $stokService, Auth::id());
                return $mutasi;
            });

            return response()->json(['success' => true, 'message' => 'Penyesuaian ' . $mutasi->nomor_mutasi . ' di-approve dan stok sudah diperbarui.']);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:500'], ['reason.required' => 'Alasan penolakan wajib diisi.']);

        try {
            $mutasi = DB::transaction(function () use ($request, $id) {
                $mutasi = MutasiStok::lockForUpdate()->findOrFail($id);
                $this->assertCanDecide($mutasi);
                $mutasi->update([
                    'status' => MutasiStok::STATUS_REJECTED,
                    'rejected_by' => Auth::id(),
                    'rejected_at' => now(),
                    'rejected_reason' => $request->reason,
                ]);
                return $mutasi;
            });

            return response()->json(['success' => true, 'message' => 'Penyesuaian ' . $mutasi->nomor_mutasi . ' ditolak. Stok tidak berubah.']);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function show($id)
    {
        /** @var MutasiStok $mutasi */
        $mutasi = MutasiStok::with(['gudang', 'user', 'items.obat', 'cancelledBy', 'revisedFrom', 'approvedBy', 'rejectedBy'])->findOrFail($id);

        // Older documents keep the batch only in kartu stok
        $batchPerObat = KartuStok::where('ref_type', 'mutasi_stok')
            ->where('ref_id', $mutasi->id)
            ->get(['obat_id', 'batch'])
            ->groupBy('obat_id')
            ->map(function ($rows) {
                $batches = $rows->pluck('batch')->unique();
                if ($batches->count() !== 1) {
                    return null;
                }
                return $batches->first() ?? self::BATCH_NULL;
            });

        return response()->json([
            'id' => $mutasi->id,
            'nomor_mutasi' => $mutasi->nomor_mutasi,
            'gudang_id' => $mutasi->gudang_id,
            'gudang' => optional($mutasi->gudang)->nama,
            'jenis_mutasi' => $mutasi->jenis_mutasi,
            'tanggal_mutasi' => optional($mutasi->tanggal_mutasi)->format('Y-m-d\TH:i'),
            'tanggal_mutasi_display' => optional($mutasi->tanggal_mutasi)->format('d/m/Y H:i'),
            'tanggal_input' => optional($mutasi->tanggal_input)->format('d/m/Y H:i'),
            'status' => $mutasi->status,
            'status_label' => strip_tags($this->statusBadge($mutasi)),
            'user' => optional($mutasi->user)->name,
            'total_nilai' => (float) $mutasi->total_nilai,
            'butuh_approval' => (bool) $mutasi->butuh_approval,
            'approved_by' => optional($mutasi->approvedBy)->name,
            'approved_at' => optional($mutasi->approved_at)->format('d/m/Y H:i'),
            'rejected_by' => optional($mutasi->rejectedBy)->name,
            'rejected_at' => optional($mutasi->rejected_at)->format('d/m/Y H:i'),
            'rejected_reason' => $mutasi->rejected_reason,
            'can_approve' => $mutasi->status === MutasiStok::STATUS_PENDING && $this->canDecide($mutasi),
            // Own pending document the current user may not approve (only when self-approval is disabled)
            'is_own_pending' => $mutasi->status === MutasiStok::STATUS_PENDING
                && (int) $mutasi->user_id === (int) Auth::id()
                && $this->isApprover()
                && !$this->canDecide($mutasi),
            'can_edit' => $mutasi->status === MutasiStok::STATUS_PENDING
                && ((int) $mutasi->user_id === (int) Auth::id() || $this->isApprover()),
            'can_cancel' => $this->canCancel($mutasi),
            'cancelled_by' => optional($mutasi->cancelledBy)->name,
            'cancelled_at' => optional($mutasi->cancelled_at)->format('d/m/Y H:i'),
            'revised_from' => optional($mutasi->revisedFrom)->nomor_mutasi,
            'print_url' => route('erm.mutasi-stok.print', $mutasi->id),
            'items' => $mutasi->items->map(function ($item) use ($batchPerObat) {
                [$alasan, $keterangan] = $this->splitKeterangan($item);
                $batch = $item->batch_dipilih
                    ? ($item->batch ?? self::BATCH_NULL)
                    : ($item->alasan ? null : $batchPerObat->get($item->obat_id));

                return [
                    'obat_id' => $item->obat_id,
                    'nama' => optional($item->obat)->nama ?: 'Obat #' . $item->obat_id,
                    'obat_nama' => optional($item->obat)->nama ?: 'Obat #' . $item->obat_id,
                    'jumlah' => $this->fmt($item->jumlah),
                    'jumlah_raw' => (float) $item->jumlah,
                    'satuan' => optional($item->obat)->satuan_stok_label,
                    'alasan' => $alasan,
                    'keterangan_saja' => $keterangan,
                    'keterangan' => $item->keterangan,
                    'batch' => $item->batch_baru ? null : $batch,
                    'batch_label' => $item->batch_dipilih ? ($item->batch ?? '(tanpa batch)') : ($batch ? ($batch === self::BATCH_NULL ? '(tanpa batch)' : $batch) : 'Otomatis (ED terdekat)'),
                    'batch_baru' => $item->batch_baru ? $item->batch : null,
                    'expiration_date' => optional($item->expiration_date)->format('Y-m-d'),
                    'hpp' => (float) $item->hpp,
                    'nilai' => (float) $item->nilai,
                ];
            })->values(),
        ]);
    }

    public function print($id)
    {
        $mutasi = MutasiStok::with(['gudang', 'user', 'items.obat', 'cancelledBy', 'revisedFrom', 'approvedBy', 'rejectedBy'])->findOrFail($id);

        return Pdf::loadView('erm.mutasi-stok.print', compact('mutasi'))
            ->setPaper('a4', 'portrait')
            ->stream('penyesuaian-stok-' . $mutasi->nomor_mutasi . '.pdf');
    }

    /**
     * Pending: withdrawn without touching stok (creator or approver).
     * Posted: reversed through kartu stok (approver only).
     */
    public function cancel($id, StokService $stokService)
    {
        try {
            DB::transaction(function () use ($id, $stokService) {
                $mutasi = MutasiStok::lockForUpdate()->findOrFail($id);
                if (!$this->canCancel($mutasi)) {
                    throw new \RuntimeException($mutasi->status === MutasiStok::STATUS_DONE
                        ? 'Membatalkan dokumen yang sudah diposting hanya bisa dilakukan approver.'
                        : 'Dokumen ini tidak bisa dibatalkan.');
                }

                if ($mutasi->status === MutasiStok::STATUS_DONE) {
                    $this->reverseMutasi($mutasi, $stokService);
                }

                $mutasi->update([
                    'status' => MutasiStok::STATUS_CANCELLED,
                    'cancelled_by' => Auth::id(),
                    'cancelled_at' => now(),
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Penyesuaian stok berhasil dibatalkan.']);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    private function needsApproval(string $jenis, float $total): bool
    {
        if ($jenis === 'masuk' && config('stok.penyesuaian.masuk_selalu_approval', true)) {
            return true;
        }

        return $total >= (float) config('stok.penyesuaian.approval_threshold', 1000000);
    }

    // An approver (config stok.penyesuaian.approver_roles); approving one's own document depends on config (allowed by default)
    private function canDecide(MutasiStok $mutasi): bool
    {
        if (!$this->isApprover()) {
            return false;
        }

        return config('stok.penyesuaian.boleh_approve_sendiri', true)
            || (int) $mutasi->user_id !== (int) Auth::id();
    }

    private function assertCanDecide(MutasiStok $mutasi): void
    {
        if ($mutasi->status !== MutasiStok::STATUS_PENDING) {
            throw new \RuntimeException('Dokumen ini sudah diproses (status: ' . $mutasi->status . ').');
        }
        if (!$this->isApprover()) {
            throw new \RuntimeException('Hanya Admin, Farmasi, Marketing atau Beautician yang bisa meng-approve atau menolak penyesuaian stok.');
        }
        if (!$this->canDecide($mutasi)) {
            throw new \RuntimeException('Pembuat dokumen tidak boleh meng-approve atau menolak dokumennya sendiri.');
        }
    }

    private function canCancel(MutasiStok $mutasi): bool
    {
        if ($mutasi->status === MutasiStok::STATUS_PENDING) {
            return (int) $mutasi->user_id === (int) Auth::id() || $this->isApprover();
        }

        return $mutasi->status === MutasiStok::STATUS_DONE && $this->isApprover();
    }

    /**
     * Apply every item to stok and mark the document posted. Called inside a transaction.
     */
    private function post(MutasiStok $mutasi, StokService $stokService, ?int $approverId): void
    {
        if ($mutasi->status !== MutasiStok::STATUS_PENDING) {
            throw new \RuntimeException('Dokumen ini sudah diproses (status: ' . $mutasi->status . ').');
        }

        foreach ($mutasi->items as $item) {
            // kartu stok keterangan is varchar(191)
            $keterangan = Str::limit($mutasi->nomor_mutasi . ' [' . $item->alasan . '] ' . $item->keterangan, 188);

            if ($mutasi->jenis_mutasi === 'masuk') {
                $this->postIncomingItem($mutasi, $item, $stokService, $keterangan);
            } else {
                $this->postOutgoingItem($mutasi, $item, $stokService, $keterangan);
            }
        }

        $mutasi->update([
            'status' => MutasiStok::STATUS_DONE,
            'approved_by' => $approverId,
            'approved_at' => $approverId ? now() : null,
        ]);
    }

    /**
     * The same checks posting makes, run when the document is saved (stok may still change before approval).
     */
    private function validateItem(MutasiStok $mutasi, MutasiStokItem $item): void
    {
        $namaObat = optional($item->obat)->nama ?: 'Obat #' . $item->obat_id;

        if ($mutasi->jenis_mutasi === 'masuk') {
            if ($item->batch_baru) {
                if (!$item->expiration_date) {
                    throw new \RuntimeException("Tanggal kadaluarsa wajib diisi untuk batch baru {$item->batch} ({$namaObat}).");
                }
                return;
            }
            if (!$item->batch_dipilih) {
                throw new \RuntimeException("Pilih batch tujuan atau isi batch baru untuk {$namaObat}.");
            }
            $exists = ObatStokGudang::withTrashed()->where('obat_id', $item->obat_id)->where('gudang_id', $mutasi->gudang_id)->where('batch', $item->batch)->exists();
            if (!$exists) {
                throw new \RuntimeException('Batch ' . ($item->batch ?? '(tanpa batch)') . " tidak ditemukan untuk {$namaObat} di gudang ini.");
            }
            return;
        }

        $available = $item->batch_dipilih
            ? (float) ObatStokGudang::where('obat_id', $item->obat_id)->where('gudang_id', $mutasi->gudang_id)->where('batch', $item->batch)->value('stok')
            : (float) ObatStokGudang::where('obat_id', $item->obat_id)->where('gudang_id', $mutasi->gudang_id)->where('stok', '>', 0)->layakPakai()->sum('stok');

        if (round($available, 4) < round((float) $item->jumlah, 4)) {
            $where = $item->batch_dipilih ? 'batch ' . ($item->batch ?? '(tanpa batch)') : 'gudang (batch layak pakai)';
            throw new \RuntimeException("Stok {$where} untuk {$namaObat} tidak mencukupi (tersedia " . $this->fmt($available) . ', diminta ' . $this->fmt($item->jumlah) . ').');
        }
    }

    private function postIncomingItem(MutasiStok $mutasi, MutasiStokItem $item, StokService $stokService, string $keterangan): void
    {
        $this->validateItem($mutasi, $item);

        $expDate = $item->batch_baru
            ? $item->expiration_date
            : ObatStokGudang::withTrashed()->where('obat_id', $item->obat_id)->where('gudang_id', $mutasi->gudang_id)->where('batch', $item->batch)->value('expiration_date');

        $stokService->tambahStok(
            $item->obat_id,
            $mutasi->gudang_id,
            $item->jumlah,
            $item->batch,
            $expDate,
            null,
            null,
            null,
            null,
            'mutasi_stok',
            $mutasi->id,
            $keterangan,
            null // kartu stok follows the posting time; the document keeps tanggal_mutasi
        );
    }

    private function postOutgoingItem(MutasiStok $mutasi, MutasiStokItem $item, StokService $stokService, string $keterangan): void
    {
        // Stok may have changed since the document was saved (pending documents)
        $this->validateItem($mutasi, $item);

        // A chosen batch (e.g. that batch is damaged) is taken as is, also when expired
        if ($item->batch_dipilih) {
            $stokService->kurangiStok($item->obat_id, $mutasi->gudang_id, (float) $item->jumlah, $item->batch, 'mutasi_stok', $mutasi->id, $keterangan);
            return;
        }

        // No batch chosen: FEFO over usable batches; expired stok leaves through the gudang expired
        $remaining = (float) $item->jumlah;
        $batches = ObatStokGudang::where('obat_id', $item->obat_id)
            ->where('gudang_id', $mutasi->gudang_id)
            ->where('stok', '>', 0)
            ->layakPakai()
            ->lockForUpdate()
            ->urutFefo()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }
            $qty = min((float) $batch->stok, $remaining);
            $stokService->kurangiStok($item->obat_id, $mutasi->gudang_id, $qty, $batch->batch, 'mutasi_stok', $mutasi->id, $keterangan);
            $remaining -= $qty;
        }
    }

    protected function reverseMutasi(MutasiStok $mutasi, StokService $stokService): void
    {
        $kartuStokRows = KartuStok::where('ref_type', 'mutasi_stok')
            ->where('ref_id', $mutasi->id)
            ->orderByDesc('id')
            ->get();

        if ($kartuStokRows->isEmpty()) {
            throw new \RuntimeException('Riwayat kartu stok untuk dokumen ini tidak ditemukan.');
        }

        foreach ($kartuStokRows as $row) {
            $reverseNote = Str::limit('Pembatalan ' . $mutasi->nomor_mutasi . ' - ' . ($row->keterangan ?: 'penyesuaian stok'), 188);

            if ($row->tipe === 'masuk') {
                $stokService->kurangiStok($row->obat_id, $row->gudang_id, (float) $row->qty, $row->batch, 'mutasi_stok_cancel', $mutasi->id, $reverseNote);
                continue;
            }

            if ($row->tipe === 'keluar') {
                $stokService->tambahStok($row->obat_id, $row->gudang_id, (float) $row->qty, $row->batch, null, null, null, null, null, 'mutasi_stok_cancel', $mutasi->id, $reverseNote);
            }
        }
    }

    /**
     * Older items stored the reason as "[Alasan] keterangan".
     */
    private function splitKeterangan($item): array
    {
        if ($item->alasan) {
            return [$item->alasan, $item->keterangan];
        }
        if ($item->keterangan && preg_match('/^\[([^\]]+)\]\s*(.*)$/s', $item->keterangan, $m)) {
            return [$m[1], $m[2]];
        }

        return [null, $item->keterangan];
    }

    private function statusBadge(MutasiStok $mutasi): string
    {
        switch ($mutasi->status) {
            case MutasiStok::STATUS_PENDING:
                return '<span class="badge badge-warning">Menunggu Approval</span>';
            case MutasiStok::STATUS_DONE:
                return '<span class="badge badge-primary">Diposting</span>';
            case MutasiStok::STATUS_REJECTED:
                return '<span class="badge badge-danger">Ditolak</span>';
            case MutasiStok::STATUS_CANCELLED:
                return '<span class="badge badge-dark">Dibatalkan</span>';
        }

        return '<span class="badge badge-light">' . e(ucfirst((string) $mutasi->status)) . '</span>';
    }

    private function fmt($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    protected function generateNomorMutasi(string $jenisMutasi): string
    {
        $prefix = $jenisMutasi === 'masuk' ? 'IN' : 'OUT';

        do {
            $nomor = 'PS-' . $prefix . '-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
        } while (MutasiStok::where('nomor_mutasi', $nomor)->exists());

        return $nomor;
    }
}
