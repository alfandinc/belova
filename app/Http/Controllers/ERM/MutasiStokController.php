<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Gudang;
use App\Models\ERM\KartuStok;
use App\Models\ERM\MutasiStok;
use App\Models\ERM\Obat;
use App\Models\ERM\ObatStokGudang;
use App\Services\ERM\StokService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MutasiStokController extends Controller
{
    public function index()
    {
        $gudangs = Gudang::orderBy('nama')->get();

        return view('erm.mutasi-stok.index', compact('gudangs'));
    }

    public function data(Request $request)
    {
        $query = MutasiStok::with(['gudang', 'user', 'items.obat', 'revisedFrom']);

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
                return optional($mutasi->created_at)->format('d/m/Y H:i');
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

                    return e($namaObat) . ' <small class="text-muted">(' . rtrim(rtrim(number_format((float) $item->jumlah, 2, '.', ''), '0'), '.') . ')</small>';
                })->implode('<br>');

                if ($items->count() > 3) {
                    $summary .= '<br><small class="text-muted">+' . ($items->count() - 3) . ' item lainnya</small>';
                }

                return $summary;
            })
            ->addColumn('user_name', function (MutasiStok $mutasi) {
                return optional($mutasi->user)->name ?: '-';
            })
            ->addColumn('status_label', function (MutasiStok $mutasi) {
                $map = [
                    'done' => '<span class="badge badge-primary">Done</span>',
                    'draft' => '<span class="badge badge-secondary">Draft</span>',
                    'cancelled' => '<span class="badge badge-dark">Cancelled</span>',
                ];

                return $map[$mutasi->status] ?? '<span class="badge badge-light">' . e(ucfirst($mutasi->status)) . '</span>';
            })
            ->addColumn('action', function (MutasiStok $mutasi) {
                $buttons = '<div class="btn-group" role="group" aria-label="Aksi">';
                $buttons .= '<button type="button" class="btn btn-info btn-sm btn-detail" data-id="' . $mutasi->id . '" title="Detail"><i class="fas fa-eye"></i></button>';
                $buttons .= '<a href="' . route('erm.mutasi-stok.print', $mutasi->id) . '" class="btn btn-secondary btn-sm" target="_blank" title="Cetak"><i class="fas fa-print"></i></a>';

                if ($mutasi->status === 'done') {
                    $buttons .= '<button type="button" class="btn btn-warning btn-sm btn-edit" data-id="' . $mutasi->id . '" title="Ubah"><i class="fas fa-edit"></i></button>';
                    $buttons .= '<button type="button" class="btn btn-danger btn-sm btn-cancel" data-id="' . $mutasi->id . '" title="Batalkan"><i class="fas fa-ban"></i></button>';
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
                'erm_obat.satuan',
                DB::raw('COALESCE(SUM(osg.stok), 0) as stok_gudang')
            )
            ->where('erm_obat.status_aktif', 1)
            ->groupBy('erm_obat.id', 'erm_obat.nama', 'erm_obat.kode_obat', 'erm_obat.satuan')
            ->orderBy('erm_obat.nama')
            ->limit(20);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('erm_obat.nama', 'like', "%{$search}%")
                    ->orWhere('erm_obat.kode_obat', 'like', "%{$search}%");
            });
        }

        if ($jenisMutasi === 'keluar') {
            $query->havingRaw('COALESCE(SUM(osg.stok), 0) > 0');
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
                'stok_display' => rtrim(rtrim(number_format($stok, 2, '.', ''), '0'), '.') . $satuan,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function store(Request $request, StokService $stokService)
    {
        $request->validate([
            'gudang_id' => 'required|exists:erm_gudang,id',
            'jenis_mutasi' => 'required|in:masuk,keluar',
            'edit_id' => 'nullable|exists:erm_mutasi_stok,id',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.jumlah' => 'required|numeric|min:0.01',
            'items.*.keterangan' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request, $stokService) {
                $revisedFromId = null;

                if ($request->filled('edit_id')) {
                    $original = MutasiStok::with(['items.obat'])->lockForUpdate()->findOrFail($request->edit_id);

                    if ($original->status !== 'done') {
                        throw new \RuntimeException('Hanya transaksi berstatus done yang bisa diubah.');
                    }

                    $this->reverseMutasi($original, $stokService, true);
                    $revisedFromId = $original->id;
                }

                $mutasi = MutasiStok::create([
                    'nomor_mutasi' => $this->generateNomorMutasi($request->jenis_mutasi),
                    'gudang_id' => $request->gudang_id,
                    'jenis_mutasi' => $request->jenis_mutasi,
                    'status' => 'done',
                    'user_id' => Auth::id(),
                    'revised_from_id' => $revisedFromId,
                ]);

                foreach ($request->items as $itemData) {
                    $item = $mutasi->items()->create([
                        'obat_id' => $itemData['obat_id'],
                        'jumlah' => $itemData['jumlah'],
                        'keterangan' => $itemData['keterangan'] ?? null,
                    ]);

                    $keterangan = $item->keterangan ?: 'Mutasi stok ' . $mutasi->jenis_mutasi;

                    if ($mutasi->jenis_mutasi === 'masuk') {
                        $stokService->tambahStok(
                            $item->obat_id,
                            $mutasi->gudang_id,
                            $item->jumlah,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            'mutasi_stok',
                            $mutasi->id,
                            $keterangan
                        );

                        continue;
                    }

                    $this->processOutgoingItem($mutasi, $item, $stokService, $keterangan);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Mutasi stok berhasil disimpan.',
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function show($id)
    {
        /** @var MutasiStok $mutasi */
        $mutasi = MutasiStok::with(['gudang', 'user', 'items.obat', 'cancelledBy', 'revisedFrom'])->findOrFail($id);
        $items = $mutasi->items;

        return response()->json([
            'id' => $mutasi->id,
            'nomor_mutasi' => $mutasi->nomor_mutasi,
            'gudang_id' => $mutasi->gudang_id,
            'gudang' => optional($mutasi->gudang)->nama,
            'jenis_mutasi' => $mutasi->jenis_mutasi,
            'status' => $mutasi->status,
            'user' => optional($mutasi->user)->name,
            'tanggal' => optional($mutasi->created_at)->format('d/m/Y H:i'),
            'can_cancel' => $mutasi->status === 'done',
            'can_edit' => $mutasi->status === 'done',
            'cancelled_by' => optional($mutasi->cancelledBy)->name,
            'cancelled_at' => optional($mutasi->cancelled_at)->format('d/m/Y H:i'),
            'revised_from' => optional($mutasi->revisedFrom)->nomor_mutasi,
            'print_url' => route('erm.mutasi-stok.print', $mutasi->id),
            'items' => $items->map(function ($item) {
                return [
                    'obat_id' => $item->obat_id,
                    'nama' => optional($item->obat)->nama ?: 'Obat #' . $item->obat_id,
                    'obat_nama' => optional($item->obat)->nama ?: 'Obat #' . $item->obat_id,
                    'jumlah' => rtrim(rtrim(number_format((float) $item->jumlah, 2, '.', ''), '0'), '.'),
                    'jumlah_raw' => (float) $item->jumlah,
                    'satuan' => optional($item->obat)->satuan,
                    'keterangan' => $item->keterangan,
                ];
            })->values(),
        ]);
    }

    public function print($id)
    {
        $mutasi = MutasiStok::with(['gudang', 'user', 'items.obat', 'cancelledBy', 'revisedFrom'])->findOrFail($id);

        return Pdf::loadView('erm.mutasi-stok.print', compact('mutasi'))
            ->setPaper('a4', 'portrait')
            ->stream('mutasi-stok-' . $mutasi->nomor_mutasi . '.pdf');
    }

    public function cancel($id, StokService $stokService)
    {
        try {
            DB::transaction(function () use ($id, $stokService) {
                $mutasi = MutasiStok::lockForUpdate()->findOrFail($id);
                $this->reverseMutasi($mutasi, $stokService, false);
            });

            return response()->json([
                'success' => true,
                'message' => 'Mutasi stok berhasil dibatalkan.',
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    protected function processOutgoingItem(MutasiStok $mutasi, $item, StokService $stokService, string $keterangan): void
    {
        $remaining = (float) $item->jumlah;

        $stokGudang = ObatStokGudang::where('obat_id', $item->obat_id)
            ->where('gudang_id', $mutasi->gudang_id)
            ->where('stok', '>', 0)
            ->lockForUpdate()
            ->orderByRaw('CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiration_date')
            ->orderBy('batch')
            ->get();

        $stokTersedia = (float) $stokGudang->sum('stok');

        if ($stokTersedia < $remaining) {
            $namaObat = optional($item->obat)->nama ?: 'Obat #' . $item->obat_id;
            throw new \RuntimeException('Stok di gudang tidak mencukupi untuk ' . $namaObat . '.');
        }

        foreach ($stokGudang as $stokBatch) {
            if ($remaining <= 0) {
                break;
            }

            $qty = min((float) $stokBatch->stok, $remaining);

            $stokService->kurangiStok(
                $item->obat_id,
                $mutasi->gudang_id,
                $qty,
                $stokBatch->batch,
                'mutasi_stok',
                $mutasi->id,
                $keterangan
            );

            $remaining -= $qty;
        }
    }

    protected function reverseMutasi(MutasiStok $mutasi, StokService $stokService, bool $isRevision): void
    {
        if ($mutasi->status !== 'done') {
            throw new \RuntimeException('Transaksi ini tidak bisa dibatalkan.');
        }

        $kartuStokRows = KartuStok::where('ref_type', 'mutasi_stok')
            ->where('ref_id', $mutasi->id)
            ->orderByDesc('id')
            ->get();

        if ($kartuStokRows->isEmpty()) {
            throw new \RuntimeException('Riwayat kartu stok untuk mutasi ini tidak ditemukan.');
        }

        foreach ($kartuStokRows as $row) {
            $keterangan = $row->keterangan ?: 'Pembatalan mutasi stok';
            $reverseNote = ($isRevision ? 'Revisi ' : 'Pembatalan ') . $mutasi->nomor_mutasi . ' - ' . $keterangan;

            if ($row->tipe === 'masuk') {
                $stokService->kurangiStok(
                    $row->obat_id,
                    $row->gudang_id,
                    (float) $row->qty,
                    $row->batch,
                    'mutasi_stok_cancel',
                    $mutasi->id,
                    $reverseNote
                );

                continue;
            }

            if ($row->tipe === 'keluar') {
                $stokService->tambahStok(
                    $row->obat_id,
                    $row->gudang_id,
                    (float) $row->qty,
                    $row->batch,
                    null,
                    null,
                    null,
                    null,
                    null,
                    'mutasi_stok_cancel',
                    $mutasi->id,
                    $reverseNote
                );
            }
        }

        $mutasi->update([
            'status' => 'cancelled',
            'cancelled_by' => Auth::id(),
            'cancelled_at' => now(),
        ]);
    }

    protected function generateNomorMutasi(string $jenisMutasi): string
    {
        $prefix = $jenisMutasi === 'masuk' ? 'IN' : 'OUT';

        do {
            $nomor = 'MTS-' . $prefix . '-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
        } while (MutasiStok::where('nomor_mutasi', $nomor)->exists());

        return $nomor;
    }
}