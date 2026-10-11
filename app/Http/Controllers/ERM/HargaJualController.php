<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Models\ERM\Obat;
use App\Models\ERM\ObatHargaPengajuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * Harga jual obat: margin over HNA (HPP + PPN) per obat, and price changes that only take effect
 * after approval. Farmasi / Admin propose, Admin / Finance approve; only Admin may approve their own proposal.
 */
class HargaJualController extends Controller
{
    public const PENGAJU_ROLES = ['Admin', 'Farmasi'];
    public const APPROVER_ROLES = ['Admin', 'Finance'];

    public const FILTERS = ['semua', 'menunggu', 'rugi', 'bawah_target', 'aman', 'tanpa_harga', 'tanpa_hpp'];

    private function targetMargin(): float
    {
        return (float) config('pembelian.target_margin_persen', 30);
    }

    /** Cost the margin is measured against: HNA, or HPP + PPN when HNA was never stored */
    private function modalSql(string $table = 'erm_obat'): string
    {
        $ppn = 1 + Obat::PPN_PERCENT / 100;

        return "COALESCE(NULLIF({$table}.hna, 0), {$table}.hpp * {$ppn})";
    }

    private function marginSql(string $table = 'erm_obat'): string
    {
        $modal = $this->modalSql($table);

        return "(CASE WHEN {$modal} > 0 AND {$table}.harga_nonfornas > 0 THEN ({$table}.harga_nonfornas - {$modal}) / {$modal} * 100 END)";
    }

    public static function margin(?float $harga, ?float $modal): ?float
    {
        return $harga > 0 && $modal > 0 ? round(($harga - $modal) / $modal * 100, 2) : null;
    }

    public static function modal(Obat $obat): ?float
    {
        $modal = (float) $obat->hna ?: (float) $obat->hpp * (1 + Obat::PPN_PERCENT / 100);

        return $modal > 0 ? round($modal, 2) : null;
    }

    private function saran(?float $modal): ?float
    {
        if (!$modal) {
            return null;
        }
        $step = max((int) config('pembelian.pembulatan_harga_jual', 100), 1);

        return ceil(round($modal * (1 + $this->targetMargin() / 100), 4) / $step) * $step;
    }

    private function canApprove(): bool
    {
        return auth()->user()->hasAnyRole(self::APPROVER_ROLES);
    }

    private function canPropose(): bool
    {
        return auth()->user()->hasAnyRole(self::PENGAJU_ROLES);
    }

    public function index()
    {
        return view('erm.harga-jual.index', [
            'target' => $this->targetMargin(),
            'ppn' => Obat::PPN_PERCENT,
            'canPropose' => $this->canPropose(),
            'canApprove' => $this->canApprove(),
            'menunggu' => ObatHargaPengajuan::where('status', 'menunggu')->count(),
            // Finance does not work in the farmasi menu
            'navbar' => auth()->user()->hasAnyRole(['Admin', 'Farmasi']) ? 'layouts.erm.navbar-farmasi' : 'layouts.finance.navbar',
        ]);
    }

    /**
     * Obat price table (active obat), with the filter cards' counts.
     */
    public function data(Request $request)
    {
        $modal = $this->modalSql();
        $margin = $this->marginSql();
        $target = $this->targetMargin();

        $pending = ObatHargaPengajuan::query()
            ->where('status', 'menunggu')
            ->select('obat_id', DB::raw('MAX(id) as pengajuan_id'))
            ->groupBy('obat_id');

        $query = Obat::query()
            ->leftJoinSub($pending, 'pj', 'pj.obat_id', '=', 'erm_obat.id')
            ->leftJoin('erm_obat_harga_pengajuan as p', 'p.id', '=', 'pj.pengajuan_id')
            ->leftJoin('users as pu', 'pu.id', '=', 'p.diajukan_by')
            ->select([
                'erm_obat.id', 'erm_obat.kode_obat', 'erm_obat.nama', 'erm_obat.kategori', 'erm_obat.hpp', 'erm_obat.harga_nonfornas',
                'p.id as pengajuan_id', 'p.harga_baru as pengajuan_harga', 'p.margin_baru as pengajuan_margin', 'pu.name as pengajuan_oleh',
            ])
            ->selectRaw("{$modal} as modal")
            ->selectRaw("{$margin} as margin");

        $filter = in_array($request->input('filter'), self::FILTERS, true) ? $request->input('filter') : 'semua';
        $this->applyFilter($query, $filter, $modal, $margin, $target);

        return DataTables::eloquent($query)
            ->only(['id', 'kode_obat', 'nama', 'kategori', 'hpp', 'modal', 'harga_nonfornas', 'margin', 'saran', 'pengajuan_id', 'pengajuan_harga', 'pengajuan_margin', 'pengajuan_oleh'])
            ->addColumn('saran', fn ($o) => $this->saran($o->modal !== null ? (float) $o->modal : null))
            ->editColumn('hpp', fn ($o) => $o->hpp !== null ? (float) $o->hpp : null)
            ->editColumn('modal', fn ($o) => $o->modal !== null ? round((float) $o->modal, 2) : null)
            ->editColumn('harga_nonfornas', fn ($o) => $o->harga_nonfornas !== null ? (float) $o->harga_nonfornas : null)
            ->editColumn('margin', fn ($o) => $o->margin !== null ? round((float) $o->margin, 2) : null)
            ->filterColumn('nama', function ($q, $keyword) {
                $q->where(function ($w) use ($keyword) {
                    $w->where('erm_obat.nama', 'like', "%{$keyword}%")->orWhere('erm_obat.kode_obat', 'like', "%{$keyword}%");
                });
            })
            ->orderColumn('nama', 'erm_obat.nama $1')
            ->orderColumn('hpp', 'erm_obat.hpp $1')
            ->orderColumn('modal', "{$modal} \$1")
            ->orderColumn('harga_nonfornas', 'erm_obat.harga_nonfornas $1')
            // Obat without a margin go last in both directions
            ->orderColumn('margin', "({$margin} IS NULL), {$margin} \$1")
            ->escapeColumns([])
            ->with('counts', $this->counts($modal, $margin, $target))
            ->make(true);
    }

    private function applyFilter($query, string $filter, string $modal, string $margin, float $target): void
    {
        switch ($filter) {
            case 'menunggu':
                $query->whereNotNull('pj.pengajuan_id');
                break;
            case 'rugi':
                $query->whereRaw("{$margin} < 0");
                break;
            case 'bawah_target':
                $query->whereRaw("{$margin} >= 0 AND {$margin} < ?", [$target]);
                break;
            case 'aman':
                $query->whereRaw("{$margin} >= ?", [$target]);
                break;
            case 'tanpa_harga':
                $query->where(fn ($q) => $q->whereNull('erm_obat.harga_nonfornas')->orWhere('erm_obat.harga_nonfornas', '<=', 0));
                break;
            case 'tanpa_hpp':
                $query->whereRaw("({$modal} IS NULL OR {$modal} <= 0)");
                break;
        }
    }

    private function counts(string $modal, string $margin, float $target): array
    {
        $row = DB::table('erm_obat')
            ->where('status_aktif', 1)
            ->selectRaw('COUNT(*) as semua')
            ->selectRaw("SUM({$margin} < 0) as rugi")
            ->selectRaw("SUM({$margin} >= 0 AND {$margin} < ?) as bawah_target", [$target])
            ->selectRaw("SUM({$margin} >= ?) as aman", [$target])
            ->selectRaw('SUM(harga_nonfornas IS NULL OR harga_nonfornas <= 0) as tanpa_harga')
            ->selectRaw("SUM({$modal} IS NULL OR {$modal} <= 0) as tanpa_hpp")
            ->first();

        $counts = array_map('intval', (array) $row);
        $counts['menunggu'] = ObatHargaPengajuan::query()
            ->where('status', 'menunggu')
            ->whereHas('obat', fn ($q) => $q->where('status_aktif', 1))
            ->distinct('obat_id')
            ->count('obat_id');

        return $counts;
    }

    /**
     * List of proposals (approval queue and history).
     */
    public function pengajuanData(Request $request)
    {
        $me = auth()->id();
        $canApprove = $this->canApprove();
        $isAdmin = auth()->user()->hasRole('Admin');
        $status = $request->input('status', 'menunggu');

        $query = ObatHargaPengajuan::query()
            ->join('erm_obat as o', 'o.id', '=', 'erm_obat_harga_pengajuan.obat_id')
            ->leftJoin('users as pu', 'pu.id', '=', 'erm_obat_harga_pengajuan.diajukan_by')
            ->leftJoin('users as su', 'su.id', '=', 'erm_obat_harga_pengajuan.disetujui_by')
            ->leftJoin('users as tu', 'tu.id', '=', 'erm_obat_harga_pengajuan.ditolak_by')
            ->select([
                'erm_obat_harga_pengajuan.*',
                'o.nama as obat_nama', 'o.kode_obat', 'o.harga_nonfornas as harga_sekarang', 'o.hna as hna_sekarang', 'o.hpp as hpp_sekarang',
                'pu.name as pengaju', 'su.name as penyetuju', 'tu.name as penolak',
            ])
            ->when($status !== 'semua', fn ($q) => $q->where('erm_obat_harga_pengajuan.status', $status));

        return DataTables::eloquent($query)
            ->addColumn('obat', fn ($p) => $p->obat_nama)
            ->addColumn('status_label', fn ($p) => ObatHargaPengajuan::STATUS_LABEL[$p->status] ?? $p->status)
            ->addColumn('tanggal', fn ($p) => $p->created_at?->format('d/m/Y H:i'))
            ->addColumn('keputusan', function ($p) {
                if ($p->status === 'disetujui') {
                    return trim(($p->penyetuju ?? '') . ' · ' . $p->disetujui_at?->format('d/m/Y H:i'), ' ·');
                }
                if ($p->status === 'ditolak') {
                    return trim(($p->penolak ?? '') . ' · ' . $p->ditolak_at?->format('d/m/Y H:i'), ' ·');
                }
                if ($p->status === 'dibatalkan') {
                    return $p->dibatalkan_at?->format('d/m/Y H:i');
                }

                return null;
            })
            // Price or cost changed after the proposal was made: the approver should know
            ->addColumn('harga_berubah', fn ($p) => $p->status === 'menunggu' && round((float) $p->harga_sekarang, 2) !== round((float) $p->harga_lama, 2))
            ->addColumn('margin_sekarang_hna', function ($p) {
                $modal = (float) $p->hna_sekarang ?: (float) $p->hpp_sekarang * (1 + Obat::PPN_PERCENT / 100);

                return $p->status === 'menunggu' ? self::margin((float) $p->harga_baru, $modal ?: null) : null;
            })
            ->addColumn('bisa_setujui', fn ($p) => $p->status === 'menunggu' && $canApprove && ((int) $p->diajukan_by !== (int) $me || $isAdmin))
            ->addColumn('bisa_batal', fn ($p) => $p->status === 'menunggu' && ((int) $p->diajukan_by === (int) $me || $isAdmin))
            ->addColumn('milik_sendiri', fn ($p) => (int) $p->diajukan_by === (int) $me)
            ->filterColumn('obat', function ($q, $keyword) {
                $q->where(fn ($w) => $w->where('o.nama', 'like', "%{$keyword}%")->orWhere('o.kode_obat', 'like', "%{$keyword}%"));
            })
            ->orderColumn('obat', 'o.nama $1')
            ->orderColumn('tanggal', 'erm_obat_harga_pengajuan.created_at $1')
            ->escapeColumns([])
            ->with('menunggu', ObatHargaPengajuan::where('status', 'menunggu')->count())
            ->make(true);
    }

    /**
     * Propose new prices: items[] = {obat_id, harga_baru}, one reason for all.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1|max:500',
            'items.*.obat_id' => 'required|integer|exists:erm_obat,id',
            'items.*.harga_baru' => 'required|numeric|min:1|max:9999999999',
            'alasan' => 'required|string|max:500',
        ], [
            'items.required' => 'Pilih minimal satu obat.',
            'items.*.harga_baru.required' => 'Harga baru wajib diisi.',
            'items.*.harga_baru.min' => 'Harga baru harus lebih dari 0.',
            'alasan.required' => 'Alasan perubahan harga wajib diisi.',
        ]);

        $dibuat = 0;
        $dilewati = [];
        DB::transaction(function () use ($data, &$dibuat, &$dilewati) {
            foreach (collect($data['items'])->keyBy('obat_id') as $item) {
                $obat = Obat::withInactive()->lockForUpdate()->findOrFail($item['obat_id']);
                $harga = round((float) $item['harga_baru'], 2);
                $lama = $obat->harga_nonfornas !== null ? round((float) $obat->harga_nonfornas, 2) : null;
                if ($lama === $harga) {
                    $dilewati[] = $obat->nama . ' (harga sama dengan sekarang)';
                    continue;
                }

                // One open proposal per obat: a new one replaces the previous
                ObatHargaPengajuan::where('obat_id', $obat->id)->where('status', 'menunggu')->lockForUpdate()->get()
                    ->each(fn ($p) => $p->update(['status' => 'dibatalkan', 'dibatalkan_at' => now(), 'alasan_tolak' => 'Digantikan pengajuan baru']));

                $modal = self::modal($obat);
                ObatHargaPengajuan::create([
                    'obat_id' => $obat->id,
                    'harga_lama' => $lama,
                    'harga_baru' => $harga,
                    'hpp' => $obat->hpp !== null ? (float) $obat->hpp : null,
                    'hna' => $modal,
                    'margin_lama' => self::margin($lama, $modal),
                    'margin_baru' => self::margin($harga, $modal),
                    'alasan' => trim($data['alasan']),
                    'status' => 'menunggu',
                    'diajukan_by' => auth()->id(),
                ]);
                $dibuat++;
            }
        });

        if (!$dibuat) {
            return response()->json(['success' => false, 'message' => 'Tidak ada pengajuan yang dibuat.', 'dilewati' => $dilewati], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $dibuat . ' pengajuan harga dibuat dan menunggu approval Admin / Finance.',
            'dilewati' => $dilewati,
        ]);
    }

    /**
     * Approve one or more proposals: the new price is written to the obat.
     */
    public function approve(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        $disetujui = 0;
        $gagal = [];
        foreach (array_unique($data['ids']) as $id) {
            try {
                DB::transaction(function () use ($id, &$disetujui) {
                    $p = ObatHargaPengajuan::lockForUpdate()->find($id);
                    if (!$p || $p->status !== 'menunggu') {
                        throw new \DomainException('sudah diproses');
                    }
                    if ((int) $p->diajukan_by === (int) auth()->id() && !auth()->user()->hasRole('Admin')) {
                        throw new \DomainException('tidak bisa menyetujui pengajuan sendiri');
                    }
                    $obat = Obat::withInactive()->lockForUpdate()->findOrFail($p->obat_id);
                    // History keeps the price that was actually replaced
                    $p->harga_lama = $obat->harga_nonfornas !== null ? (float) $obat->harga_nonfornas : null;
                    $p->margin_lama = self::margin($p->harga_lama, self::modal($obat));
                    $p->status = 'disetujui';
                    $p->disetujui_by = auth()->id();
                    $p->disetujui_at = now();
                    $p->save();

                    $obat->harga_nonfornas = $p->harga_baru;
                    $obat->save();
                    $disetujui++;
                });
            } catch (\DomainException $e) {
                $nama = ObatHargaPengajuan::with('obat:id,nama')->find($id)?->obat?->nama ?? ('#' . $id);
                $gagal[] = $nama . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => $disetujui > 0,
            'message' => $disetujui ? $disetujui . ' harga jual disetujui dan langsung berlaku.' : 'Tidak ada pengajuan yang disetujui.',
            'gagal' => $gagal,
        ], $disetujui ? 200 : 422);
    }

    public function reject(Request $request, $id)
    {
        $data = $request->validate(['alasan' => 'required|string|max:500'], ['alasan.required' => 'Alasan penolakan wajib diisi.']);

        return DB::transaction(function () use ($id, $data) {
            $p = ObatHargaPengajuan::lockForUpdate()->findOrFail($id);
            if ($p->status !== 'menunggu') {
                return response()->json(['success' => false, 'message' => 'Pengajuan ini sudah diproses.'], 422);
            }
            $p->update(['status' => 'ditolak', 'ditolak_by' => auth()->id(), 'ditolak_at' => now(), 'alasan_tolak' => trim($data['alasan'])]);

            return response()->json(['success' => true, 'message' => 'Pengajuan ditolak. Harga jual tidak berubah.']);
        });
    }

    public function batal($id)
    {
        return DB::transaction(function () use ($id) {
            $p = ObatHargaPengajuan::lockForUpdate()->findOrFail($id);
            if ($p->status !== 'menunggu') {
                return response()->json(['success' => false, 'message' => 'Pengajuan ini sudah diproses.'], 422);
            }
            if ((int) $p->diajukan_by !== (int) auth()->id() && !auth()->user()->hasRole('Admin')) {
                return response()->json(['success' => false, 'message' => 'Hanya pengaju atau Admin yang bisa membatalkan.'], 403);
            }
            $p->update(['status' => 'dibatalkan', 'dibatalkan_at' => now()]);

            return response()->json(['success' => true, 'message' => 'Pengajuan dibatalkan.']);
        });
    }

    /**
     * Price history of one obat.
     */
    public function riwayat($obatId)
    {
        $obat = Obat::withInactive()->findOrFail($obatId, ['id', 'nama', 'kode_obat', 'hpp', 'hna', 'harga_nonfornas']);
        $rows = ObatHargaPengajuan::with(['pengaju:id,name', 'penyetuju:id,name', 'penolak:id,name'])
            ->where('obat_id', $obat->id)
            ->latest('id')
            ->get()
            ->map(fn ($p) => [
                'tanggal' => $p->created_at?->format('d/m/Y H:i'),
                'harga_lama' => $p->harga_lama,
                'harga_baru' => $p->harga_baru,
                'margin_lama' => $p->margin_lama,
                'margin_baru' => $p->margin_baru,
                'hna' => $p->hna,
                'alasan' => $p->alasan,
                'status' => $p->status,
                'status_label' => ObatHargaPengajuan::STATUS_LABEL[$p->status] ?? $p->status,
                'pengaju' => $p->pengaju?->name,
                'keputusan_oleh' => $p->penyetuju?->name ?? $p->penolak?->name,
                'keputusan_at' => ($p->disetujui_at ?? $p->ditolak_at ?? $p->dibatalkan_at)?->format('d/m/Y H:i'),
                'alasan_tolak' => $p->alasan_tolak,
            ]);

        $modal = self::modal($obat);

        return response()->json([
            'obat' => [
                'id' => $obat->id,
                'nama' => $obat->nama,
                'kode_obat' => $obat->kode_obat,
                'hpp' => $obat->hpp !== null ? (float) $obat->hpp : null,
                'modal' => $modal,
                'harga' => $obat->harga_nonfornas !== null ? (float) $obat->harga_nonfornas : null,
                'margin' => self::margin($obat->harga_nonfornas !== null ? (float) $obat->harga_nonfornas : null, $modal),
                'saran' => $this->saran($modal),
            ],
            'rows' => $rows,
        ]);
    }
}
