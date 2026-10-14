<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ERM\Obat;
use App\Models\ERM\Tindakan;
use App\Models\ERM\TindakanHargaPengajuan;
use App\Services\ERM\MasterPembelianService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * Harga jual paket tindakan (erm_tindakan). Only a paket carries a selling price; its tindakan
 * (erm_kode_tindakan) only carry the obat/BHP they use. Price changes take effect after approval:
 * Marketing / Admin propose, Admin / Finance approve; only Admin may approve their own proposal.
 */
class HargaTindakanController extends Controller
{
    public const PENGAJU_ROLES = ['Admin', 'Marketing'];
    public const APPROVER_ROLES = ['Admin', 'Finance'];

    public const FILTERS = ['semua', 'menunggu', 'rugi', 'tanpa_harga', 'tanpa_isi'];

    /** Price the patient pays without promo: the discount price when the discount is on */
    private const HARGA_EFEKTIF_SQL = '(CASE WHEN erm_tindakan.diskon_active = 1 AND erm_tindakan.harga_diskon > 0 THEN erm_tindakan.harga_diskon ELSE erm_tindakan.harga END)';

    private function canApprove(): bool
    {
        return auth()->user()->hasAnyRole(self::APPROVER_ROLES);
    }

    private function canPropose(): bool
    {
        return auth()->user()->hasAnyRole(self::PENGAJU_ROLES);
    }

    /**
     * Obat/BHP cost per paket: sum over its tindakan of obat qty × modal obat (HPP, or HNA + PPN).
     */
    private static function bahanQuery()
    {
        $ppn = 1 + Obat::PPN_PERCENT / 100;

        return DB::table('erm_tindakan_kode_tindakan as tk')
            ->join('erm_kode_tindakan_obat as ko', 'ko.kode_tindakan_id', '=', 'tk.kode_tindakan_id')
            ->join('erm_obat as o', 'o.id', '=', 'ko.obat_id')
            ->groupBy('tk.tindakan_id')
            ->select('tk.tindakan_id')
            ->selectRaw("SUM(ko.qty * COALESCE(NULLIF(o.hpp, 0), o.hna * {$ppn}, 0)) as bahan");
    }

    /**
     * Obat/BHP cost per paket at HNA (excl PPN) and at HPP (HNA + PPN, the modal), for the pengajuan list. Obat never
     * bought take the Master Pembelian price (excl PPN) as an estimated HNA; bahan_estimasi counts them,
     * bahan_tanpa_hpp counts obat without any cost.
     */
    private static function bahanModalQuery()
    {
        $ppn = 1 + Obat::PPN_PERCENT / 100;
        // HNA and HPP both come from the estimate when the obat has no HPP yet, never mixed
        $estimasi = 'COALESCE(o.hpp, 0) <= 0 AND est.hpp_estimasi > 0';
        $hna = "(CASE WHEN {$estimasi} THEN est.hpp_estimasi ELSE COALESCE(o.hna, 0) END)";
        $hpp = "(CASE WHEN {$estimasi} THEN est.hpp_estimasi * {$ppn} ELSE COALESCE(NULLIF(o.hpp, 0), o.hna * {$ppn}, 0) END)";

        return DB::table('erm_tindakan_kode_tindakan as tk')
            ->join('erm_kode_tindakan_obat as ko', 'ko.kode_tindakan_id', '=', 'tk.kode_tindakan_id')
            ->join('erm_obat as o', 'o.id', '=', 'ko.obat_id')
            ->leftJoinSub(MasterPembelianService::estimasiHppQuery(), 'est', 'est.obat_id', '=', 'o.id')
            ->groupBy('tk.tindakan_id')
            ->select('tk.tindakan_id')
            ->selectRaw("SUM(ko.qty * {$hpp}) as bahan_hpp")
            ->selectRaw("SUM(ko.qty * {$hna}) as bahan_hna")
            ->selectRaw('COUNT(DISTINCT CASE WHEN COALESCE(o.hpp, 0) <= 0 AND est.hpp_estimasi > 0 THEN o.id END) as bahan_estimasi')
            ->selectRaw('COUNT(DISTINCT CASE WHEN COALESCE(o.hpp, 0) <= 0 AND COALESCE(o.hna, 0) <= 0 AND est.hpp_estimasi IS NULL THEN o.id END) as bahan_tanpa_hpp');
    }

    public static function biayaBahan(int $tindakanId): ?float
    {
        $row = self::bahanQuery()->where('tk.tindakan_id', $tindakanId)->first();

        return $row && $row->bahan > 0 ? round((float) $row->bahan, 2) : null;
    }

    private static function isiQuery()
    {
        return DB::table('erm_tindakan_kode_tindakan as tk')
            ->join('erm_kode_tindakan as k', 'k.id', '=', 'tk.kode_tindakan_id')
            ->groupBy('tk.tindakan_id')
            ->select('tk.tindakan_id')
            ->selectRaw('COUNT(*) as jumlah_isi')
            ->selectRaw("GROUP_CONCAT(k.nama ORDER BY k.nama SEPARATOR ', ') as isi");
    }

    /** Current price set of a paket, keyed like TindakanHargaPengajuan::FIELDS */
    private static function hargaSekarang(Tindakan $t): array
    {
        return [
            'harga' => $t->harga !== null ? round((float) $t->harga, 2) : null,
            'harga_diskon' => $t->harga_diskon !== null ? round((float) $t->harga_diskon, 2) : null,
            'diskon_active' => (bool) $t->diskon_active,
            'harga_paket_visit' => $t->harga_paket_visit !== null ? round((float) $t->harga_paket_visit, 2) : null,
        ];
    }

    /**
     * Open a proposal for one paket, replacing any proposal still waiting. Returns null when nothing changes.
     * Also used when a new paket is created from the Paket Tindakan form.
     */
    public static function ajukan(Tindakan $tindakan, array $baru, string $alasan): ?TindakanHargaPengajuan
    {
        $baru = [
            'harga' => round((float) $baru['harga'], 2),
            'harga_diskon' => isset($baru['harga_diskon']) && $baru['harga_diskon'] !== '' ? round((float) $baru['harga_diskon'], 2) : null,
            'diskon_active' => !empty($baru['diskon_active']),
            'harga_paket_visit' => isset($baru['harga_paket_visit']) && $baru['harga_paket_visit'] !== '' ? round((float) $baru['harga_paket_visit'], 2) : null,
        ];
        $lama = self::hargaSekarang($tindakan);
        if ($baru == $lama) {
            return null;
        }

        TindakanHargaPengajuan::where('tindakan_id', $tindakan->id)->where('status', 'menunggu')->lockForUpdate()->get()
            ->each(fn ($p) => $p->update(['status' => 'dibatalkan', 'dibatalkan_at' => now(), 'alasan_tolak' => 'Digantikan pengajuan baru']));

        $row = ['tindakan_id' => $tindakan->id, 'biaya_bahan' => self::biayaBahan($tindakan->id), 'alasan' => trim($alasan), 'status' => 'menunggu', 'diajukan_by' => auth()->id()];
        foreach (TindakanHargaPengajuan::FIELDS as $f) {
            $row[$f . '_lama'] = $lama[$f];
            $row[$f . '_baru'] = $baru[$f];
        }

        return TindakanHargaPengajuan::create($row);
    }

    public function index()
    {
        return view('marketing.harga_tindakan.index', [
            'canPropose' => $this->canPropose(),
            'canApprove' => $this->canApprove(),
            'menunggu' => TindakanHargaPengajuan::where('status', 'menunggu')->count(),
        ]);
    }

    /**
     * Paket price table, with the filter cards' counts.
     */
    public function data(Request $request)
    {
        $pending = TindakanHargaPengajuan::query()
            ->where('status', 'menunggu')
            ->select('tindakan_id', DB::raw('MAX(id) as pengajuan_id'))
            ->groupBy('tindakan_id');

        $query = Tindakan::query()
            ->leftJoinSub(self::bahanQuery(), 'b', 'b.tindakan_id', '=', 'erm_tindakan.id')
            ->leftJoinSub(self::isiQuery(), 'i', 'i.tindakan_id', '=', 'erm_tindakan.id')
            ->leftJoinSub($pending, 'pj', 'pj.tindakan_id', '=', 'erm_tindakan.id')
            ->leftJoin('erm_tindakan_harga_pengajuan as p', 'p.id', '=', 'pj.pengajuan_id')
            ->leftJoin('users as pu', 'pu.id', '=', 'p.diajukan_by')
            ->leftJoin('erm_spesialisasis as s', 's.id', '=', 'erm_tindakan.spesialis_id')
            ->select([
                'erm_tindakan.id', 'erm_tindakan.nama', 'erm_tindakan.is_active', 'erm_tindakan.harga', 'erm_tindakan.harga_diskon',
                'erm_tindakan.diskon_active', 'erm_tindakan.harga_paket_visit', 'erm_tindakan.multi_visit_total',
                's.nama as spesialis', 'b.bahan', 'i.jumlah_isi', 'i.isi',
                'p.id as pengajuan_id', 'p.harga_baru as pengajuan_harga', 'p.harga_diskon_baru as pengajuan_harga_diskon',
                'p.diskon_active_baru as pengajuan_diskon_active', 'p.harga_paket_visit_baru as pengajuan_harga_paket_visit', 'pu.name as pengajuan_oleh',
            ])
            ->selectRaw(self::HARGA_EFEKTIF_SQL . ' as harga_efektif');

        // Only active paket can be sold, so only they are priced here
        $query->where('erm_tindakan.is_active', true);
        $filter = in_array($request->input('filter'), self::FILTERS, true) ? $request->input('filter') : 'semua';
        $this->applyFilter($query, $filter);

        return DataTables::eloquent($query)
            ->editColumn('bahan', fn ($t) => $t->bahan > 0 ? round((float) $t->bahan, 2) : null)
            ->filterColumn('nama', function ($q, $keyword) {
                $q->where('erm_tindakan.nama', 'like', "%{$keyword}%");
            })
            ->orderColumn('nama', 'erm_tindakan.nama $1')
            ->orderColumn('harga', 'erm_tindakan.harga $1')
            ->orderColumn('bahan', '(b.bahan IS NULL), b.bahan $1')
            ->escapeColumns([])
            ->with('counts', $this->counts())
            ->make(true);
    }

    private function applyFilter($query, string $filter): void
    {
        switch ($filter) {
            case 'menunggu':
                $query->whereNotNull('pj.pengajuan_id');
                break;
            case 'rugi':
                $query->whereRaw('b.bahan > 0 AND ' . self::HARGA_EFEKTIF_SQL . ' > 0 AND ' . self::HARGA_EFEKTIF_SQL . ' < b.bahan');
                break;
            case 'tanpa_harga':
                $query->where(fn ($q) => $q->whereNull('erm_tindakan.harga')->orWhere('erm_tindakan.harga', '<=', 0));
                break;
            case 'tanpa_isi':
                $query->whereNull('i.tindakan_id');
                break;
        }
    }

    private function counts(): array
    {
        $efektif = self::HARGA_EFEKTIF_SQL;
        $row = DB::table('erm_tindakan')
            ->leftJoinSub(self::bahanQuery(), 'b', 'b.tindakan_id', '=', 'erm_tindakan.id')
            ->leftJoinSub(self::isiQuery(), 'i', 'i.tindakan_id', '=', 'erm_tindakan.id')
            ->where('erm_tindakan.is_active', true)
            ->selectRaw('COUNT(*) as semua')
            ->selectRaw("SUM(b.bahan > 0 AND {$efektif} > 0 AND {$efektif} < b.bahan) as rugi")
            ->selectRaw('SUM(erm_tindakan.harga IS NULL OR erm_tindakan.harga <= 0) as tanpa_harga')
            ->selectRaw('SUM(i.tindakan_id IS NULL) as tanpa_isi')
            ->first();

        $counts = array_map('intval', (array) $row);
        $counts['menunggu'] = TindakanHargaPengajuan::query()
            ->where('status', 'menunggu')
            ->whereHas('tindakan', fn ($t) => $t->where('is_active', true))
            ->distinct('tindakan_id')
            ->count('tindakan_id');

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

        $query = TindakanHargaPengajuan::query()
            ->join('erm_tindakan as t', 't.id', '=', 'erm_tindakan_harga_pengajuan.tindakan_id')
            ->leftJoin('users as pu', 'pu.id', '=', 'erm_tindakan_harga_pengajuan.diajukan_by')
            ->leftJoin('users as su', 'su.id', '=', 'erm_tindakan_harga_pengajuan.disetujui_by')
            ->leftJoin('users as tu', 'tu.id', '=', 'erm_tindakan_harga_pengajuan.ditolak_by')
            ->leftJoinSub(self::bahanModalQuery(), 'bm', 'bm.tindakan_id', '=', 'erm_tindakan_harga_pengajuan.tindakan_id')
            ->select([
                'erm_tindakan_harga_pengajuan.*',
                'bm.bahan_hpp', 'bm.bahan_hna', 'bm.bahan_estimasi', 'bm.bahan_tanpa_hpp',
                't.nama as tindakan_nama', 't.harga as harga_sekarang', 't.harga_diskon as harga_diskon_sekarang',
                't.diskon_active as diskon_active_sekarang', 't.harga_paket_visit as harga_paket_visit_sekarang',
                'pu.name as pengaju', 'su.name as penyetuju', 'tu.name as penolak',
            ])
            ->when($status !== 'semua', fn ($q) => $q->where('erm_tindakan_harga_pengajuan.status', $status));

        return DataTables::eloquent($query)
            ->addColumn('tindakan', fn ($p) => $p->tindakan_nama)
            ->addColumn('status_label', fn ($p) => TindakanHargaPengajuan::STATUS_LABEL[$p->status] ?? $p->status)
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
            // Prices changed after the proposal was made (another proposal approved): the approver should know
            ->addColumn('harga_berubah', fn ($p) => $p->status === 'menunggu' && (
                round((float) $p->harga_sekarang, 2) !== round((float) $p->harga_lama, 2)
                || round((float) $p->harga_diskon_sekarang, 2) !== round((float) $p->harga_diskon_lama, 2)
                || (bool) $p->diskon_active_sekarang !== (bool) $p->diskon_active_lama
                || round((float) $p->harga_paket_visit_sekarang, 2) !== round((float) $p->harga_paket_visit_lama, 2)
            ))
            ->editColumn('bahan_hpp', fn ($p) => $p->bahan_hpp > 0 ? round((float) $p->bahan_hpp, 2) : null)
            ->editColumn('bahan_hna', fn ($p) => $p->bahan_hna > 0 ? round((float) $p->bahan_hna, 2) : null)
            ->editColumn('bahan_estimasi', fn ($p) => (int) $p->bahan_estimasi)
            ->editColumn('bahan_tanpa_hpp', fn ($p) => (int) $p->bahan_tanpa_hpp)
            ->addColumn('bisa_setujui', fn ($p) => $p->status === 'menunggu' && $canApprove && ((int) $p->diajukan_by !== (int) $me || $isAdmin))
            ->addColumn('bisa_batal', fn ($p) => $p->status === 'menunggu' && ((int) $p->diajukan_by === (int) $me || $isAdmin))
            ->addColumn('milik_sendiri', fn ($p) => (int) $p->diajukan_by === (int) $me)
            ->filterColumn('tindakan', function ($q, $keyword) {
                $q->where('t.nama', 'like', "%{$keyword}%");
            })
            ->orderColumn('tindakan', 't.nama $1')
            ->orderColumn('tanggal', 'erm_tindakan_harga_pengajuan.created_at $1')
            ->escapeColumns([])
            ->with('menunggu', TindakanHargaPengajuan::where('status', 'menunggu')->count())
            ->make(true);
    }

    private function itemRules(string $prefix): array
    {
        return [
            $prefix . 'harga' => 'required|numeric|min:1|max:9999999999',
            $prefix . 'harga_diskon' => 'nullable|numeric|min:1|max:9999999999|lt:' . $prefix . 'harga|required_if_accepted:' . $prefix . 'diskon_active',
            $prefix . 'diskon_active' => 'nullable|boolean',
            $prefix . 'harga_paket_visit' => 'nullable|numeric|min:1|max:9999999999',
        ];
    }

    public static function itemMessages(string $prefix): array
    {
        return [
            $prefix . 'harga.required' => 'Harga normal wajib diisi.',
            $prefix . 'harga.min' => 'Harga normal harus lebih dari 0.',
            $prefix . 'harga_diskon.lt' => 'Harga diskon harus lebih kecil dari harga normal.',
            $prefix . 'harga_diskon.required_if_accepted' => 'Harga diskon wajib diisi jika diskon aktif.',
        ];
    }

    /**
     * Propose new prices: items[] = {tindakan_id, harga, harga_diskon, diskon_active, harga_paket_visit}, one reason for all.
     */
    public function store(Request $request)
    {
        $data = $request->validate(array_merge([
            'items' => 'required|array|min:1|max:500',
            'items.*.tindakan_id' => 'required|integer|exists:erm_tindakan,id',
            'alasan' => 'required|string|max:500',
        ], $this->itemRules('items.*.')), array_merge([
            'items.required' => 'Pilih minimal satu paket tindakan.',
            'alasan.required' => 'Alasan perubahan harga wajib diisi.',
        ], self::itemMessages('items.*.')));

        $dibuat = 0;
        $dilewati = [];
        DB::transaction(function () use ($data, &$dibuat, &$dilewati) {
            foreach (collect($data['items'])->keyBy('tindakan_id') as $item) {
                $tindakan = Tindakan::lockForUpdate()->findOrFail($item['tindakan_id']);
                if (!self::ajukan($tindakan, $item, $data['alasan'])) {
                    $dilewati[] = $tindakan->nama . ' (harga sama dengan sekarang)';
                    continue;
                }
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
     * Approve one or more proposals: the new price set is written to the paket.
     */
    public function approve(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        $disetujui = 0;
        $gagal = [];
        foreach (array_unique($data['ids']) as $id) {
            try {
                DB::transaction(function () use ($id, &$disetujui) {
                    $p = TindakanHargaPengajuan::lockForUpdate()->find($id);
                    if (!$p || $p->status !== 'menunggu') {
                        throw new \DomainException('sudah diproses');
                    }
                    if ((int) $p->diajukan_by === (int) auth()->id() && !auth()->user()->hasRole('Admin')) {
                        throw new \DomainException('tidak bisa menyetujui pengajuan sendiri');
                    }
                    $tindakan = Tindakan::lockForUpdate()->findOrFail($p->tindakan_id);
                    // History keeps the prices that were actually replaced
                    foreach (self::hargaSekarang($tindakan) as $f => $v) {
                        $p->{$f . '_lama'} = $v;
                    }
                    $p->status = 'disetujui';
                    $p->disetujui_by = auth()->id();
                    $p->disetujui_at = now();
                    $p->save();

                    foreach (TindakanHargaPengajuan::FIELDS as $f) {
                        $tindakan->{$f} = $p->{$f . '_baru'};
                    }
                    $tindakan->save();
                    $disetujui++;
                });
            } catch (\DomainException $e) {
                $nama = TindakanHargaPengajuan::with('tindakan:id,nama')->find($id)?->tindakan?->nama ?? ('#' . $id);
                $gagal[] = $nama . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => $disetujui > 0,
            'message' => $disetujui ? $disetujui . ' harga paket tindakan disetujui dan langsung berlaku.' : 'Tidak ada pengajuan yang disetujui.',
            'gagal' => $gagal,
        ], $disetujui ? 200 : 422);
    }

    public function reject(Request $request, $id)
    {
        $data = $request->validate(['alasan' => 'required|string|max:500'], ['alasan.required' => 'Alasan penolakan wajib diisi.']);

        return DB::transaction(function () use ($id, $data) {
            $p = TindakanHargaPengajuan::lockForUpdate()->findOrFail($id);
            if ($p->status !== 'menunggu') {
                return response()->json(['success' => false, 'message' => 'Pengajuan ini sudah diproses.'], 422);
            }
            $p->update(['status' => 'ditolak', 'ditolak_by' => auth()->id(), 'ditolak_at' => now(), 'alasan_tolak' => trim($data['alasan'])]);

            return response()->json(['success' => true, 'message' => 'Pengajuan ditolak. Harga paket tidak berubah.']);
        });
    }

    public function batal($id)
    {
        return DB::transaction(function () use ($id) {
            $p = TindakanHargaPengajuan::lockForUpdate()->findOrFail($id);
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
     * Price history of one paket.
     */
    public function riwayat($tindakanId)
    {
        $tindakan = Tindakan::findOrFail($tindakanId);
        $rows = TindakanHargaPengajuan::with(['pengaju:id,name', 'penyetuju:id,name', 'penolak:id,name'])
            ->where('tindakan_id', $tindakan->id)
            ->latest('id')
            ->get()
            ->map(function ($p) {
                $row = [
                    'tanggal' => $p->created_at?->format('d/m/Y H:i'),
                    'biaya_bahan' => $p->biaya_bahan,
                    'alasan' => $p->alasan,
                    'status' => $p->status,
                    'status_label' => TindakanHargaPengajuan::STATUS_LABEL[$p->status] ?? $p->status,
                    'pengaju' => $p->pengaju?->name,
                    'keputusan_oleh' => $p->penyetuju?->name ?? $p->penolak?->name,
                    'keputusan_at' => ($p->disetujui_at ?? $p->ditolak_at ?? $p->dibatalkan_at)?->format('d/m/Y H:i'),
                    'alasan_tolak' => $p->alasan_tolak,
                ];
                foreach (TindakanHargaPengajuan::FIELDS as $f) {
                    $row[$f . '_lama'] = $p->{$f . '_lama'};
                    $row[$f . '_baru'] = $p->{$f . '_baru'};
                }

                return $row;
            });

        $isi = DB::table('erm_tindakan_kode_tindakan as tk')
            ->join('erm_kode_tindakan as k', 'k.id', '=', 'tk.kode_tindakan_id')
            ->where('tk.tindakan_id', $tindakan->id)
            ->orderBy('k.nama')
            ->pluck('k.nama');

        return response()->json([
            'tindakan' => array_merge([
                'id' => $tindakan->id,
                'nama' => $tindakan->nama,
                'multi_visit_total' => $tindakan->multi_visit_total,
                'bahan' => self::biayaBahan($tindakan->id),
                'isi' => $isi->implode(', '),
                'jumlah_isi' => $isi->count(),
            ], self::hargaSekarang($tindakan)),
            'rows' => $rows,
        ]);
    }
}
