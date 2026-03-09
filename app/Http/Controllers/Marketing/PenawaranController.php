<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ERM\GudangMapping;
use App\Models\ERM\Klinik;
use App\Models\ERM\Dokter;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Obat;
use App\Models\ERM\ObatStokGudang;
use App\Models\ERM\ResepDetail;
use App\Models\ERM\ResepFarmasi;
use App\Models\ERM\Visitation;
use App\Models\Marketing\Penawaran;
use App\Models\Marketing\PenawaranItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class PenawaranController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $q = Penawaran::query()
                ->with(['pasien', 'items.obat', 'klinik', 'dokter.user', 'metodeBayar'])
                ->orderByDesc('id');

            return DataTables::of($q)
                ->addIndexColumn()
                ->addColumn('pasien_info', function (Penawaran $p) {
                    $nama = optional($p->pasien)->nama ?? '-';
                    $rm = $p->pasien_id ?? '';
                    $tanggal = optional($p->created_at)->format('Y-m-d H:i') ?? '-';

                    $pasien = trim($nama . ($rm ? ' (' . $rm . ')' : ''));
                    return '<div>' . e($pasien) . '</div>'
                        . '<div class="text-muted small">' . e($tanggal) . '</div>';
                })
                ->addColumn('items_list', function (Penawaran $p) {
                    $names = [];
                    foreach ($p->items as $it) {
                        $n = optional($it->obat)->nama;
                        if ($n) $names[] = $n;
                    }
                    $names = array_values(array_unique($names));
                    if (count($names) === 0) return '-';

                    $lis = '';
                    foreach ($names as $n) {
                        $lis .= '<li>' . e($n) . '</li>';
                    }
                    return '<ul class="mb-0 pl-3">' . $lis . '</ul>';
                })
                ->addColumn('status', function (Penawaran $p) {
                    $status = (string) ($p->status ?? '');
                    $map = [
                        'ditawarkan' => 'badge badge-secondary',
                        'disetujui' => 'badge badge-info',
                        'diproses' => 'badge badge-warning',
                        'selesai' => 'badge badge-success',
                    ];
                    $class = $map[$status] ?? 'badge badge-light';
                    $label = e($status !== '' ? $status : '-');
                    return '<span class="' . $class . '">' . $label . '</span>';
                })
                ->addColumn('action', function (Penawaran $p) {
                    $btnDetail = '<button class="btn btn-sm btn-info btn-penawaran-detail" data-id="' . $p->id . '">Detail</button>';

                    if ($p->status !== 'ditawarkan') {
                        return '<div class="btn-group" role="group">' . $btnDetail . '</div>';
                    }

                    $klinikText = optional($p->klinik)->nama;
                    $dokterText = optional(optional($p->dokter)->user)->name;
                    $metodeText = optional($p->metodeBayar)->nama;

                    $btnSubmit = '<button class="btn btn-sm btn-success btn-penawaran-submit"'
                        . ' data-id="' . $p->id . '"'
                        . ' data-klinik-id="' . e($p->klinik_id) . '" data-klinik-text="' . e($klinikText) . '"'
                        . ' data-dokter-id="' . e($p->dokter_id) . '" data-dokter-text="' . e($dokterText) . '"'
                        . ' data-metode-id="' . e($p->metode_bayar_id) . '" data-metode-text="' . e($metodeText) . '">'
                        . 'Submit</button>';

                    return '<div class="btn-group" role="group">' . $btnDetail . $btnSubmit . '</div>';
                })
                ->rawColumns(['action', 'status', 'pasien_info', 'items_list'])
                ->make(true);
        }

        return view('marketing.penawaran.index');
    }

    public function pasienSelect2(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        // Enforce minimum input length to avoid huge unfiltered lists.
        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $query = \App\Models\ERM\Pasien::query();
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('nama', 'like', '%' . $term . '%')
                    ->orWhere('id', 'like', '%' . $term . '%')
                    ->orWhere('nik', 'like', '%' . $term . '%');
            });
        }

        $items = $query->orderBy('nama')->limit(20)->get(['id', 'nama']);

        $results = $items->map(function ($p) {
            $name = trim((string) ($p->nama ?? ''));
            $name = preg_replace('/\s+/', ' ', $name);
            $label = $name !== ''
                ? mb_strtoupper($name) . ' (' . $p->id . ')'
                : '(' . $p->id . ')';

            return [
                'id' => $p->id,
                'text' => $label,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function obatSelect2(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $gudangId = GudangMapping::getDefaultGudangId('resep');

        $query = Obat::query();
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('nama', 'like', '%' . $term . '%')
                    ->orWhere('id', 'like', '%' . $term . '%');
            });
        }

        $items = $query->orderBy('nama')->limit(20)->get(['id', 'nama', 'harga_nonfornas']);

        $results = $items->map(function ($o) use ($gudangId) {
            $stokTersedia = null;
            if ($gudangId) {
                $stokTersedia = ObatStokGudang::query()
                    ->where('obat_id', $o->id)
                    ->where('gudang_id', $gudangId)
                    ->sum('stok');
            }

            return [
                'id' => $o->id,
                'text' => trim(($o->nama ?? '-') . ' (#' . $o->id . ')'),
                'harga_nonfornas' => $o->harga_nonfornas,
                'stok_tersedia' => $stokTersedia,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function klinikSelect2(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $q = Klinik::query();
        if ($term !== '') {
            $q->where('nama', 'like', '%' . $term . '%')
                ->orWhere('id', 'like', '%' . $term . '%');
        }

        $items = $q->orderBy('nama')->limit(20)->get(['id', 'nama']);

        $results = $items->map(function ($k) {
            return [
                'id' => $k->id,
                'text' => trim(($k->nama ?? '-') . ' (#' . $k->id . ')'),
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function metodeBayarSelect2(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $q = MetodeBayar::query();
        if ($term !== '') {
            $q->where('nama', 'like', '%' . $term . '%')
                ->orWhere('id', 'like', '%' . $term . '%');
        }

        $items = $q->orderBy('nama')->limit(20)->get(['id', 'nama']);

        $results = $items->map(function ($m) {
            return [
                'id' => $m->id,
                'text' => trim(($m->nama ?? '-') . ' (#' . $m->id . ')'),
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function dokterSelect2(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $q = Dokter::query()->with('user');
        if ($term !== '') {
            $q->where(function ($qq) use ($term) {
                $qq->where('id', 'like', '%' . $term . '%')
                    ->orWhereHas('user', function ($u) use ($term) {
                        $u->where('name', 'like', '%' . $term . '%');
                    });
            });
        }

        $items = $q->orderByDesc('id')->limit(20)->get();

        $results = $items->map(function (Dokter $d) {
            $name = optional($d->user)->name ?: 'Dokter';
            return [
                'id' => $d->id,
                'text' => trim($name . ' (#' . $d->id . ')'),
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function submit(Request $request, $id)
    {
        $validated = $request->validate([
            'klinik_id' => 'required|exists:erm_klinik,id',
            'dokter_id' => 'required|exists:erm_dokters,id',
            'metode_bayar_id' => 'required|exists:erm_metode_bayar,id',
        ]);

        $p = Penawaran::with(['items.obat'])->findOrFail($id);
        if ($p->status !== 'ditawarkan') {
            return response()->json(['success' => false, 'message' => 'Penawaran hanya bisa disubmit saat status ditawarkan.'], 422);
        }
        if ($p->status === 'selesai') {
            return response()->json(['success' => false, 'message' => 'Penawaran sudah selesai.'], 422);
        }

        $gudangId = GudangMapping::getDefaultGudangId('resep');
        if (!$gudangId) {
            return response()->json(['success' => false, 'message' => 'Gudang Resep Farmasi belum dimapping.'], 422);
        }

        $obatIds = $p->items->pluck('obat_id')->filter()->unique()->values();
        if ($obatIds->count() > 0) {
            $stokByObat = ObatStokGudang::query()
                ->where('gudang_id', $gudangId)
                ->whereIn('obat_id', $obatIds)
                ->selectRaw('obat_id, SUM(stok) as stok')
                ->groupBy('obat_id')
                ->pluck('stok', 'obat_id');

            $insufficient = [];
            foreach ($p->items as $it) {
                $qty = $it->jumlah !== null ? (float) $it->jumlah : 1.0;
                $stok = isset($stokByObat[$it->obat_id]) ? (float) $stokByObat[$it->obat_id] : 0.0;
                if ($qty > $stok) {
                    $nama = optional($it->obat)->nama ?? ('Obat #' . $it->obat_id);
                    $insufficient[] = $nama . ' (stok ' . $stok . ')';
                }
            }

            if (count($insufficient) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok tidak mencukupi untuk: ' . implode(', ', $insufficient),
                ], 422);
            }
        }

        $p->klinik_id = $validated['klinik_id'];
        $p->dokter_id = $validated['dokter_id'];
        $p->metode_bayar_id = $validated['metode_bayar_id'];
        $p->status = 'diproses';
        $p->updated_by = Auth::id();
        $p->save();

        return response()->json(['success' => true, 'message' => 'Penawaran berhasil disubmit.']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pasien_id' => 'required|exists:erm_pasiens,id',
            'items' => 'required|array|min:1',
            'items.*.obat_id' => 'required|exists:erm_obat,id',
            'items.*.jumlah' => 'nullable|integer|min:1',
            'items.*.harga' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $penawaran = Penawaran::create([
                'pasien_id' => $validated['pasien_id'],
                'status' => 'ditawarkan',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $row) {
                $qty = isset($row['jumlah']) ? (int) $row['jumlah'] : null;
                $harga = isset($row['harga']) ? (float) $row['harga'] : null;

                $total = null;
                if ($qty !== null && $harga !== null) {
                    $total = $harga * $qty;
                }

                PenawaranItem::create([
                    'penawaran_id' => $penawaran->id,
                    'obat_id' => $row['obat_id'],
                    'jumlah' => $qty,
                    'harga' => $harga,
                    'total' => $total,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Penawaran berhasil dibuat.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal membuat penawaran.'], 500);
        }
    }

    public function items($id)
    {
        $p = Penawaran::with(['pasien', 'items.obat'])->findOrFail($id);

        $rows = $p->items->map(function (PenawaranItem $it) {
            return [
                'id' => $it->id,
                'obat_id' => $it->obat_id,
                'obat_nama' => optional($it->obat)->nama ?? '-',
                'jumlah' => $it->jumlah,
                'aturan_pakai' => $it->aturan_pakai,
                'diskon' => $it->diskon,
                'harga' => $it->harga,
                'total' => $it->total,
            ];
        })->values();

        return response()->json([
            'id' => $p->id,
            'pasien_id' => $p->pasien_id,
            'nama_pasien' => optional($p->pasien)->nama ?? '-',
            'status' => $p->status,
            'items' => $rows,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:ditawarkan,disetujui,diproses,selesai',
        ]);

        $p = Penawaran::findOrFail($id);
        $p->status = $validated['status'];
        $p->updated_by = Auth::id();
        $p->save();

        return response()->json(['success' => true, 'message' => 'Status penawaran diupdate.']);
    }

    /**
     * Farmasi modal list: only disetujui penawarans
     */
    public function farmasiData(Request $request)
    {
        $q = Penawaran::query()
            ->with(['pasien', 'items.obat'])
            ->whereIn('status', ['disetujui', 'diproses'])
            ->orderByDesc('id');

        return DataTables::of($q)
            ->addIndexColumn()
            ->addColumn('pasien_info', function (Penawaran $p) {
                $nama = optional($p->pasien)->nama ?? '-';
                $rm = $p->pasien_id ?? '';
                $tanggal = optional($p->created_at)->format('Y-m-d H:i') ?? '-';

                $pasien = trim($nama . ($rm ? ' (' . $rm . ')' : ''));
                return '<div>' . e($pasien) . '</div>'
                    . '<div class="text-muted small">' . e($tanggal) . '</div>';
            })
            ->addColumn('items_list', function (Penawaran $p) {
                $names = [];
                foreach ($p->items as $it) {
                    $n = optional($it->obat)->nama;
                    if ($n) $names[] = $n;
                }
                $names = array_values(array_unique($names));
                if (count($names) === 0) return '-';

                $lis = '';
                foreach ($names as $n) {
                    $lis .= '<li>' . e($n) . '</li>';
                }
                return '<ul class="mb-0 pl-3">' . $lis . '</ul>';
            })
            ->addColumn('action', function (Penawaran $p) {
                return '<button class="btn btn-sm btn-success btn-proses-penawaran" data-id="' . $p->id . '">Proses Penawaran</button>';
            })
            ->rawColumns(['action', 'pasien_info', 'items_list'])
            ->make(true);
    }

    public function farmasiReadyCount(Request $request)
    {
        $count = Penawaran::query()
            ->whereIn('status', ['disetujui', 'diproses'])
            ->count();

        return response()->json(['count' => $count]);
    }

    public function process(Request $request, $id)
    {
        $p = Penawaran::with(['items', 'items.obat'])->findOrFail($id);

        if (!in_array($p->status, ['disetujui', 'diproses'], true)) {
            return response()->json(['success' => false, 'message' => 'Penawaran belum siap diproses.'], 422);
        }
        if ($p->visitation_id) {
            return response()->json(['success' => false, 'message' => 'Penawaran ini sudah diproses.'], 422);
        }
        if (!$p->klinik_id || !$p->dokter_id || !$p->metode_bayar_id) {
            return response()->json(['success' => false, 'message' => 'Silakan Submit penawaran terlebih dahulu (isi klinik, dokter, metode bayar).'], 422);
        }

        DB::beginTransaction();
        try {
            $visitationId = now()->format('YmdHis') . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);

            Visitation::create([
                'id' => $visitationId,
                'pasien_id' => $p->pasien_id,
                'metode_bayar_id' => $p->metode_bayar_id,
                'dokter_id' => $p->dokter_id,
                'klinik_id' => $p->klinik_id,
                'jenis_kunjungan' => 2,
                'tanggal_visitation' => now()->toDateString(),
                'status_kunjungan' => 2,
                'user_id' => Auth::id(),
            ]);

            ResepDetail::create([
                'visitation_id' => $visitationId,
                'no_resep' => 'RSP' . $visitationId,
                'catatan_dokter' => null,
                'status' => 0,
            ]);

            foreach ($p->items as $it) {
                $obat = $it->relationLoaded('obat') ? $it->obat : null;
                $harga = $it->harga;
                if ($harga === null && $obat) {
                    $harga = $obat->harga_nonfornas ?? null;
                }

                $qty = $it->jumlah ?? 1;
                $diskon = $it->diskon ?? 0;

                $total = null;
                if ($harga !== null) {
                    $unit = (float) $harga;
                    if ($diskon > 0) {
                        $unit = max(0, $unit - ($unit * ((float) $diskon / 100)));
                    }
                    $total = $unit * (float) $qty;
                }

                do {
                    $customId = now()->format('YmdHis') . strtoupper(Str::random(7));
                } while (ResepFarmasi::where('id', $customId)->exists());

                ResepFarmasi::create([
                    'id' => $customId,
                    'visitation_id' => $visitationId,
                    'obat_id' => $it->obat_id,
                    'jumlah' => $it->jumlah,
                    'dosis' => $it->dosis,
                    'bungkus' => $it->bungkus,
                    'racikan_ke' => $it->racikan_ke,
                    'aturan_pakai' => $it->aturan_pakai,
                    'wadah_id' => $it->wadah_id,
                    'harga' => $harga,
                    'diskon' => $diskon,
                    'total' => $total,
                    'user_id' => Auth::id(),
                    'created_at' => now(),
                ]);
            }

            $p->visitation_id = $visitationId;
            $p->status = 'selesai';
            $p->updated_by = Auth::id();
            $p->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Penawaran berhasil diproses.',
                'redirect' => route('erm.eresepfarmasi.create', ['visitation_id' => $visitationId]),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal memproses penawaran.'], 500);
        }
    }
}
