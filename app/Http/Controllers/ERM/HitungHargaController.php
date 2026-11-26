<?php

namespace App\Http\Controllers\ERM;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Marketing\HargaTindakanController;
use App\Models\ERM\BiayaLain;
use App\Models\ERM\Tindakan;
use App\Models\ERM\TindakanHargaPengajuan;
use App\Models\Inventory\Barang;
use App\Services\ERM\HitungHargaService as HH;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Hitung Harga Paket (Admin / Finance / Manager): modal of a paket tindakan from its BHP, jasa medis, alat
 * and biaya lain, a suggested price for a target untung, and the proposal into the existing harga approval
 * (approved by Admin / Finance in Harga Paket Tindakan). Also the alat tindakan list (biaya per pakai,
 * balik modal) and the Biaya Lain master.
 */
class HitungHargaController extends Controller
{
    public const ROLES = ['Admin', 'Finance', 'Manager'];

    private const TINDAKAN_COLS = ['id', 'nama', 'harga', 'harga_diskon', 'diskon_active', 'harga_paket_visit', 'multi_visit_total', 'is_active', 'spesialis_id'];

    public function index()
    {
        return view('erm.hitung_harga.index', [
            'untungMinimal' => HH::UNTUNG_MINIMAL,
            'untungTarget' => HH::UNTUNG_TARGET,
            'pembulatan' => HH::PEMBULATAN,
            'canApprove' => HargaTindakanController::me()->hasAnyRole(HargaTindakanController::APPROVER_ROLES),
        ]);
    }

    // ================================================================= Paket list

    public function data()
    {
        $tindakans = Tindakan::where('is_active', true)->orderBy('nama')->get(self::TINDAKAN_COLS);
        $pending = TindakanHargaPengajuan::where('status', 'menunggu')->whereIn('tindakan_id', $tindakans->pluck('id'))
            ->orderByDesc('id')->get(['id', 'tindakan_id', 'harga_baru'])->unique('tindakan_id')->keyBy('tindakan_id');
        $spesialis = DB::table('erm_spesialisasis')->pluck('nama', 'id');

        $rows = HH::hitungBanyak($tindakans)->map(function ($h) use ($tindakans, $pending, $spesialis) {
            $t = $tindakans->firstWhere('id', $h['id']);
            $p = $pending->get($h['id']);

            return [
                'id' => $h['id'],
                'nama' => $h['nama'],
                'spesialis' => $spesialis[$t->spesialis_id] ?? null,
                'jumlah_tindakan' => count($h['tindakan']),
                'isi' => collect($h['tindakan'])->pluck('nama')->implode(', '),
                'bahan' => $h['bahan'],
                'jasa' => $h['jasa'],
                'biaya_alat' => $h['biaya_alat'],
                'biaya_lain_total' => $h['biaya_lain_total'],
                'jumlah_alat' => count($h['alat']),
                'jumlah_biaya_lain' => count($h['biaya_lain']),
                'modal' => $h['modal'],
                'harga' => $h['harga'],
                'harga_diskon' => $h['harga_diskon'],
                'diskon_active' => $h['diskon_active'],
                'harga_efektif' => $h['harga_efektif'],
                'untung_persen' => $h['untung_persen'],
                'harga_paket_visit' => $h['harga_paket_visit'],
                'jumlah_visit' => $h['jumlah_visit'],
                'untung_persen_paket_visit' => $h['untung_persen_paket_visit'],
                'jasa_persen' => $h['jasa_persen'],
                'status' => HH::status($h),
                'peringatan' => $h['peringatan'],
                'pengajuan' => $p ? ['id' => $p->id, 'harga' => (float) $p->harga_baru] : null,
            ];
        })->values();

        $counts = [
            'semua' => $rows->count(),
            'rugi' => $rows->where('status', 'rugi')->count(),
            'tipis' => $rows->where('status', 'tipis')->count(),
            'tanpa_harga' => $rows->where('status', 'tanpa_harga')->count(),
            'belum_lengkap' => $rows->filter(fn ($r) => $r['peringatan'])->count(),
        ];

        return response()->json(['data' => $rows, 'counts' => $counts]);
    }

    // ================================================================= One paket

    /** Hitung Harga form data: the breakdown plus the alat / biaya lain to choose from */
    public function show(string|int $id)
    {
        $tindakan = Tindakan::findOrFail($id, self::TINDAKAN_COLS);
        $pending = TindakanHargaPengajuan::where('tindakan_id', $tindakan->id)->where('status', 'menunggu')->latest('id')->first();

        return response()->json([
            'hitung' => HH::hitung($tindakan),
            'pengajuan' => $pending ? ['id' => $pending->id, 'harga' => $pending->harga_baru, 'tanggal' => $pending->created_at?->format('d/m/Y H:i')] : null,
            'opsi_alat' => HH::alatInfo()->map(fn ($a) => [
                'id' => $a['id'], 'nama' => $a['nama'], 'ruangan' => $a['ruangan'], 'per_pakai' => $a['per_pakai'], 'peringatan' => $a['peringatan'],
            ])->values(),
            'opsi_biaya_lain' => BiayaLain::where('is_active', true)->orderBy('nama')->get(['id', 'nama', 'nominal', 'keterangan']),
        ]);
    }

    private function validateKomponen(Request $request): array
    {
        $data = $request->validate([
            'alat' => 'nullable|array|max:30',
            'alat.*.barang_id' => 'required|integer|distinct|exists:inv_barang,id',
            'alat.*.qty' => 'required|numeric|min:0.01|max:999',
            'biaya_lain' => 'nullable|array|max:30',
            'biaya_lain.*.biaya_lain_id' => 'required|integer|distinct|exists:erm_biaya_lain,id',
            'biaya_lain.*.nominal' => 'required|numeric|min:0|max:999999999',
        ], [
            'alat.*.barang_id.distinct' => 'Alat yang sama dipilih dua kali.',
            'alat.*.qty.min' => 'Jumlah pemakaian alat minimal 0,01.',
            'biaya_lain.*.biaya_lain_id.distinct' => 'Biaya lain yang sama dipilih dua kali.',
        ]);

        return [
            'alat' => collect($data['alat'] ?? [])->map(fn ($a) => ['barang_id' => (int) $a['barang_id'], 'qty' => round((float) $a['qty'], 2)])->values()->all(),
            'biaya_lain' => collect($data['biaya_lain'] ?? [])->map(fn ($b) => ['biaya_lain_id' => (int) $b['biaya_lain_id'], 'nominal' => round((float) $b['nominal'], 2)])->values()->all(),
        ];
    }

    private function simpanKomponen(Tindakan $tindakan, array $k): void
    {
        $tindakan->alats()->sync(collect($k['alat'])->mapWithKeys(fn ($a) => [$a['barang_id'] => ['qty' => $a['qty']]])->all());
        $tindakan->biayaLains()->sync(collect($k['biaya_lain'])->mapWithKeys(fn ($b) => [$b['biaya_lain_id'] => ['nominal' => $b['nominal']]])->all());
    }

    /** Save the alat and biaya lain of a paket */
    public function simpan(Request $request, string|int $id)
    {
        $tindakan = Tindakan::findOrFail($id);
        $k = $this->validateKomponen($request);
        DB::transaction(fn () => $this->simpanKomponen($tindakan, $k));

        return response()->json([
            'success' => true,
            'message' => 'Komponen biaya paket disimpan.',
            'hitung' => HH::hitung(Tindakan::find($tindakan->id, self::TINDAKAN_COLS)),
        ]);
    }

    /**
     * Save the components and propose the new price into the Harga Paket Tindakan approval, with the
     * modal breakdown. Harga diskon / diskon aktif stay as they are.
     */
    public function ajukan(Request $request, string|int $id)
    {
        $k = $this->validateKomponen($request);
        $data = $request->validate([
            'harga' => 'required|numeric|min:1|max:9999999999',
            'harga_paket_visit' => 'nullable|numeric|min:1|max:9999999999',
            'untung_target' => 'nullable|numeric|min:0|max:99',
            'alasan' => 'required|string|max:500',
        ], [
            'harga.required' => 'Harga yang diajukan wajib diisi.',
            'alasan.required' => 'Alasan pengajuan wajib diisi.',
        ]);

        return DB::transaction(function () use ($id, $k, $data) {
            $tindakan = Tindakan::lockForUpdate()->findOrFail($id);
            if ($tindakan->harga_diskon > 0 && (float) $tindakan->harga_diskon >= (float) $data['harga']) {
                return response()->json(['success' => false, 'message' => 'Harga diskon sekarang (Rp ' . number_format($tindakan->harga_diskon, 0, ',', '.') . ') tidak lebih kecil dari harga baru. Ubah harga diskonnya lewat menu Harga Paket Tindakan.'], 422);
            }
            $this->simpanKomponen($tindakan, $k);
            $h = HH::hitung(Tindakan::find($tindakan->id, self::TINDAKAN_COLS));

            $hargaVisit = array_key_exists('harga_paket_visit', $data) && $data['harga_paket_visit'] !== null ? (float) $data['harga_paket_visit'] : $tindakan->harga_paket_visit;
            $untungTarget = isset($data['untung_target']) ? (float) $data['untung_target'] : null;
            $rincian = [
                'versi' => 1,
                'dihitung_at' => now()->toDateTimeString(),
                'tindakan' => $h['tindakan'],
                'alat' => $h['alat'],
                'biaya_lain' => $h['biaya_lain'],
                'modal' => $h['modal'],
                'jumlah_visit' => $h['jumlah_visit'],
                'untung_target' => $untungTarget,
                'harga_saran' => $untungTarget !== null ? HH::hargaSaran($h['modal'], $untungTarget) : null,
                'untung_persen_baru' => HH::untungPersen((float) $data['harga'], $h['modal']),
                'pembulatan' => HH::PEMBULATAN,
                'peringatan' => $h['peringatan'],
            ];

            $p = HargaTindakanController::ajukan($tindakan, [
                'harga' => $data['harga'],
                'harga_diskon' => $tindakan->harga_diskon,
                'diskon_active' => $tindakan->diskon_active,
                'harga_paket_visit' => $hargaVisit,
            ], $data['alasan'], [
                'biaya_bahan' => $h['bahan'],
                'biaya_jasa' => $h['jasa'],
                'biaya_alat' => $h['biaya_alat'],
                'biaya_lain' => $h['biaya_lain_total'],
                'rincian' => $rincian,
            ]);

            if (!$p) {
                return response()->json(['success' => false, 'message' => 'Harga yang diajukan sama dengan harga sekarang. Komponen biaya sudah disimpan.', 'hitung' => $h], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan harga dikirim dan menunggu approval Admin / Finance di menu Harga Paket Tindakan.',
                'hitung' => $h,
            ]);
        });
    }

    // ================================================================= Alat tindakan

    public function alatData()
    {
        $alat = HH::alatInfo();
        $balik = HH::balikModal($alat);

        return response()->json([
            'data' => $alat->map(fn ($a) => array_merge($a, ['balik_modal' => $balik->get($a['id'])]))->values(),
        ]);
    }

    /** Inventory items to mark as alat tindakan */
    public function alatCari(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $items = Barang::query()->leftJoin('inv_ruangan as r', 'r.id', '=', 'inv_barang.ruangan_id')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('inv_barang.name', 'like', "%{$q}%")->orWhere('inv_barang.kode', 'like', "%{$q}%")))
            ->orderByDesc('inv_barang.is_alat_tindakan')->orderBy('inv_barang.name')->limit(30)
            ->get(['inv_barang.id', 'inv_barang.name', 'inv_barang.kode', 'inv_barang.is_alat_tindakan', 'r.name as ruangan']);

        return response()->json(['results' => $items->map(fn ($b) => [
            'id' => $b->id,
            'text' => $b->name . ($b->kode ? ' [' . $b->kode . ']' : '') . ($b->ruangan ? ' — ' . $b->ruangan : '') . ($b->is_alat_tindakan ? ' (sudah alat tindakan)' : ''),
        ])]);
    }

    public function alatShow(string|int $barangId)
    {
        $info = HH::alatInfo([(int) $barangId])->first();
        abort_unless($info, 404);

        return response()->json($info);
    }

    /** Set the alat tindakan data of an inventory item */
    public function alatSimpan(Request $request, string|int $barangId)
    {
        $barang = Barang::findOrFail($barangId);
        $data = $request->validate([
            'is_alat_tindakan' => 'required|boolean',
            'harga_perolehan' => 'nullable|numeric|min:0|max:99999999999',
            'tanggal_perolehan' => 'nullable|date|before_or_equal:today',
            'umur_pakai_tahun' => 'nullable|integer|min:1|max:50',
            'target_pakai_per_bulan' => 'nullable|integer|min:1|max:100000',
        ], [
            'tanggal_perolehan.before_or_equal' => 'Tanggal perolehan tidak boleh di masa depan.',
            'umur_pakai_tahun.min' => 'Umur pakai minimal 1 tahun.',
            'target_pakai_per_bulan.min' => 'Target pemakaian minimal 1 kali per bulan.',
        ]);

        $barang->fill([
            'is_alat_tindakan' => (bool) $data['is_alat_tindakan'],
            'harga_perolehan' => isset($data['harga_perolehan']) && $data['harga_perolehan'] > 0 ? $data['harga_perolehan'] : null,
            'tanggal_perolehan' => $data['tanggal_perolehan'] ?? null,
            'umur_pakai_tahun' => $data['umur_pakai_tahun'] ?? null,
            'target_pakai_per_bulan' => $data['target_pakai_per_bulan'] ?? null,
        ])->save();

        $dipakai = DB::table('erm_tindakan_alat')->where('barang_id', $barang->id)->count();

        return response()->json([
            'success' => true,
            'message' => $barang->is_alat_tindakan ? 'Data alat tindakan disimpan.'
                : 'Barang tidak lagi ditandai alat tindakan.' . ($dipakai ? ' Masih tercatat di ' . $dipakai . ' paket; hapus dari paketnya lewat Hitung Harga.' : ''),
        ]);
    }

    // ================================================================= Biaya Lain master

    public function biayaLainData()
    {
        $pakai = DB::table('erm_tindakan_biaya_lain')->groupBy('biaya_lain_id')->selectRaw('biaya_lain_id, COUNT(*) as n')->pluck('n', 'biaya_lain_id');

        return response()->json(['data' => BiayaLain::orderByDesc('is_active')->orderBy('nama')->get()
            ->map(fn ($b) => $b->only(['id', 'nama', 'nominal', 'keterangan', 'is_active']) + ['dipakai' => (int) ($pakai[$b->id] ?? 0), 'diubah' => $b->updated_at?->format('d/m/Y')])]);
    }

    private function biayaLainRules(?int $id = null): array
    {
        return [
            'nama' => 'required|string|max:100|unique:erm_biaya_lain,nama' . ($id ? ',' . $id : ''),
            'nominal' => 'required|numeric|min:0|max:999999999',
            'keterangan' => 'nullable|string|max:255',
        ];
    }

    private const BIAYA_LAIN_MESSAGES = [
        'nama.required' => 'Nama biaya wajib diisi.',
        'nama.unique' => 'Nama biaya ini sudah ada.',
        'nominal.required' => 'Nominal wajib diisi (boleh 0).',
    ];

    public function biayaLainStore(Request $request)
    {
        $data = $request->validate($this->biayaLainRules(), self::BIAYA_LAIN_MESSAGES);
        BiayaLain::create($data + ['is_active' => true, 'updated_by' => Auth::id()]);

        return response()->json(['success' => true, 'message' => 'Biaya lain ditambahkan.']);
    }

    /**
     * Update a biaya lain. terapkan_ke_paket: also set the new nominal on the paket that still use the old one.
     */
    public function biayaLainUpdate(Request $request, string|int $id)
    {
        $b = BiayaLain::findOrFail($id);
        $data = $request->validate($this->biayaLainRules($b->id) + ['terapkan_ke_paket' => 'nullable|boolean'], self::BIAYA_LAIN_MESSAGES);

        $diterapkan = 0;
        DB::transaction(function () use ($b, $data, &$diterapkan) {
            $lama = (float) $b->nominal;
            $b->update(['nama' => $data['nama'], 'nominal' => $data['nominal'], 'keterangan' => $data['keterangan'] ?? null, 'updated_by' => Auth::id()]);
            if (!empty($data['terapkan_ke_paket']) && round($lama, 2) !== round((float) $data['nominal'], 2)) {
                $diterapkan = DB::table('erm_tindakan_biaya_lain')->where('biaya_lain_id', $b->id)->where('nominal', $lama)
                    ->update(['nominal' => $data['nominal'], 'updated_at' => now()]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Biaya lain disimpan.' . ($diterapkan ? ' Nominal baru diterapkan ke ' . $diterapkan . ' paket.' : '')]);
    }

    public function biayaLainToggle(string|int $id)
    {
        $b = BiayaLain::findOrFail($id);
        $b->update(['is_active' => !$b->is_active, 'updated_by' => Auth::id()]);

        return response()->json(['success' => true, 'message' => $b->is_active ? 'Biaya lain diaktifkan.' : 'Biaya lain dinonaktifkan. Paket yang sudah memakainya tetap menghitungnya sampai dihapus dari paket.']);
    }
}
