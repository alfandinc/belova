<?php

namespace App\Http\Controllers\ERM;

use App\Exports\DokterExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ERM\Dokter;
use App\Models\ERM\Spesialisasi;
use App\Models\ERM\Klinik;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DokterController extends Controller
{
    // Quick filters (chips) on the list: SIP / STR berakhir, data belum lengkap
    private const QUICK = ['sip_lewat', 'sip_segera', 'str_lewat', 'str_segera', 'tanpa_tanggal', 'tanpa_ttd'];

    // Tables with a dokter_id pointing to erm_dokters: a dokter used in any of them is not deleted
    private const DOKTER_REFERENCES = [
        'erm_visitations' => 'kunjungan pasien',
        'erm_resepfarmasi' => 'resep farmasi',
        'erm_lab_permintaan' => 'permintaan lab',
        'erm_radiologi_permintaan' => 'permintaan radiologi',
        'erm_spk' => 'SPK',
        'erm_slimming' => 'slimming',
        'erm_suratistirahat' => 'surat istirahat',
        'erm_suratmondok' => 'surat mondok',
        'erm_lab_configs' => 'konfigurasi lab',
        'hrd_dokter_schedules' => 'jadwal dokter',
        'hrd_shifts_dokter' => 'shift dokter',
        'pr_slip_gaji_dokter' => 'slip gaji dokter',
        'marketing_penawarans' => 'penawaran marketing',
        'satusehat_dokter_mappings' => 'mapping SatuSehat',
    ];

    /** Hrd / Admin change dokter data (see routes); Ceo / Head Manager only view it. */
    public static function canManage(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['Hrd', 'Admin']);
    }

    /** Navbar badge: dokter aktif whose SIP or STR has ended or ends within 30 days. */
    public static function izinAlertCount(): int
    {
        return Cache::remember('hrd_dokter_izin_alert', 600, function () {
            $soon = now()->addDays(30)->toDateString();
            return Dokter::active()
                ->where(fn ($q) => $q->where('due_date_sip', '<=', $soon)->orWhere('due_date_str', '<=', $soon))
                ->count();
        });
    }

    // Lists that show dokters (Rawat Jalan / Billing filters, navbar badge) are cached
    private function forgetCaches(): void
    {
        Cache::forget('erm_dokters_list');
        Cache::forget('hrd_dokter_izin_alert');
        Cache::forget('erm_referral_dokters');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $dokters = $this->filteredQuery($request)
                ->select('erm_dokters.*')
                ->leftJoin('users', 'users.id', '=', 'erm_dokters.user_id')
                ->leftJoin('erm_spesialisasis as sp', 'sp.id', '=', 'erm_dokters.spesialisasi_id')
                ->with(['user:id,name,email', 'spesialisasi:id,nama', 'klinik:id,nama', 'kliniks:id,nama']);
            if (in_array($request->quick, self::QUICK, true)) {
                $this->applyQuick($dokters, $request->quick);
            }

            $spesialisasiNames = Spesialisasi::pluck('nama', 'id');

            return datatables()->eloquent($dokters)
                ->addColumn('nama', fn ($d) => optional($d->user)->name)
                ->addColumn('email', fn ($d) => optional($d->user)->email)
                ->addColumn('spesialisasi', fn ($d) => optional($d->spesialisasi)->nama)
                ->addColumn('photo_url', fn ($d) => $d->photo ? asset('storage/' . $d->photo) : null)
                ->addColumn('ttd_url', fn ($d) => $d->ttd ? asset('img/qr/' . $d->ttd) : null)
                ->addColumn('klinik_daftar', function ($d) use ($spesialisasiNames) {
                    // Klinik utama first, then the others; spesialisasi only when set per klinik
                    $list = $d->kliniks->map(fn ($k) => [
                        'nama' => $k->nama,
                        'utama' => (int) $k->id === (int) $d->klinik_id,
                        'spesialisasi' => $k->pivot->spesialisasi_id ? ($spesialisasiNames[$k->pivot->spesialisasi_id] ?? null) : null,
                    ]);
                    if ($d->klinik && !$list->contains('utama', true)) {
                        $list->push(['nama' => $d->klinik->nama, 'utama' => true, 'spesialisasi' => null]);
                    }
                    return $list->sortByDesc('utama')->values()->all();
                })
                ->filterColumn('nama', fn ($q, $keyword) => $q->where('users.name', 'like', "%{$keyword}%"))
                ->filterColumn('spesialisasi', fn ($q, $keyword) => $q->where('sp.nama', 'like', "%{$keyword}%"))
                ->orderColumn('nama', 'users.name $1')
                ->orderColumn('spesialisasi', 'sp.nama $1')
                ->removeColumn('user', 'klinik', 'kliniks')
                ->with('summary', $this->summary($request))
                ->make(true);
        }

        $spesialisasis = Spesialisasi::orderBy('nama')->get();
        $kliniks = Klinik::orderBy('nama')->get();
        $canManage = self::canManage();

        return view('erm.dokters.index', compact('spesialisasis', 'kliniks', 'canManage'));
    }

    public function export(Request $request)
    {
        $dokters = $this->filteredQuery($request)
            ->select('erm_dokters.*')
            ->leftJoin('users', 'users.id', '=', 'erm_dokters.user_id')
            ->leftJoin('erm_spesialisasis as sp', 'sp.id', '=', 'erm_dokters.spesialisasi_id')
            ->with(['user', 'spesialisasi', 'klinik', 'kliniks'])
            ->when(in_array($request->quick, self::QUICK, true), fn ($q) => $this->applyQuick($q, $request->quick))
            // Same text as the table's search box
            ->when(trim((string) $request->input('search.value')), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('users.name', 'like', "%$s%")
                ->orWhere('sp.nama', 'like', "%$s%")
                ->orWhere('erm_dokters.sip', 'like', "%$s%")
                ->orWhere('erm_dokters.str', 'like', "%$s%")
                ->orWhere('erm_dokters.nik', 'like', "%$s%")
                ->orWhere('erm_dokters.no_hp', 'like', "%$s%")))
            ->orderBy('users.name')
            ->get();

        return Excel::download(new DokterExport($dokters), 'data-dokter-' . now()->format('Ymd') . '.xlsx');
    }

    /** Nonaktif instead of delete: the dokter keeps its history but leaves the pickers for new data. */
    public function updateStatus(Request $request, $id)
    {
        $dokter = Dokter::findOrFail($id);
        $aktif = $request->boolean('aktif');

        $request->validate([
            'nonaktif_tanggal' => $aktif ? 'nullable' : 'required|date',
            'nonaktif_keterangan' => 'nullable|string|max:255',
        ], [
            'nonaktif_tanggal.required' => 'Tanggal nonaktif wajib diisi.',
        ]);

        $dokter->update($aktif
            ? ['is_active' => true, 'nonaktif_tanggal' => null, 'nonaktif_keterangan' => null]
            : ['is_active' => false, 'nonaktif_tanggal' => $request->nonaktif_tanggal, 'nonaktif_keterangan' => $request->nonaktif_keterangan]);
        $this->forgetCaches();

        return response()->json([
            'success' => true,
            'message' => $aktif ? 'Dokter diaktifkan kembali.' : 'Dokter dinonaktifkan.',
        ]);
    }

    // Dokter list with the toolbar filters (aktif, spesialisasi, klinik, status); the chips come on top
    private function filteredQuery(Request $request)
    {
        $dokters = Dokter::query();

        $aktif = $request->input('aktif', 'aktif');
        if ($aktif === 'aktif') {
            $dokters->where('erm_dokters.is_active', true);
        } elseif ($aktif === 'nonaktif') {
            $dokters->where('erm_dokters.is_active', false);
        }

        if ($request->filled('spesialisasi_id')) {
            $dokters->where('erm_dokters.spesialisasi_id', $request->spesialisasi_id);
        }
        if ($request->filled('klinik_id')) {
            $klinikId = $request->klinik_id;
            $dokters->where(function ($q) use ($klinikId) {
                $q->where('erm_dokters.klinik_id', $klinikId)
                  ->orWhereHas('kliniks', fn ($k) => $k->where('erm_klinik.id', $klinikId));
            });
        }
        if ($request->filled('status')) {
            $dokters->where('erm_dokters.status', $request->status);
        }

        return $dokters;
    }

    private function applyQuick($query, string $quick): void
    {
        $today = now()->toDateString();
        $soon = now()->addDays(30)->toDateString();

        match ($quick) {
            'sip_lewat' => $query->where('erm_dokters.due_date_sip', '<', $today),
            'sip_segera' => $query->whereBetween('erm_dokters.due_date_sip', [$today, $soon]),
            'str_lewat' => $query->where('erm_dokters.due_date_str', '<', $today),
            'str_segera' => $query->whereBetween('erm_dokters.due_date_str', [$today, $soon]),
            'tanpa_tanggal' => $query->where(fn ($q) => $q->whereNull('erm_dokters.due_date_sip')->orWhereNull('erm_dokters.due_date_str')),
            'tanpa_ttd' => $query->where(fn ($q) => $q->whereNull('erm_dokters.ttd')->orWhere('erm_dokters.ttd', '')),
        };
    }

    // Chip counts and Tetap / Kontrak totals, within the toolbar filters (not the search box)
    private function summary(Request $request): array
    {
        $summary = [];
        foreach (self::QUICK as $quick) {
            $query = $this->filteredQuery($request);
            $this->applyQuick($query, $quick);
            $summary[$quick] = $query->count();
        }

        $base = $this->filteredQuery($request);
        if (in_array($request->quick, self::QUICK, true)) {
            $this->applyQuick($base, $request->quick);
        }
        $status = $base->toBase()->selectRaw('erm_dokters.status, count(*) as total')->groupBy('erm_dokters.status')->pluck('total', 'status');
        $summary['total'] = (int) $status->sum();
        $summary['tetap'] = (int) ($status['Tetap'] ?? 0);
        $summary['kontrak'] = (int) ($status['Kontrak'] ?? 0);

        return $summary;
    }

    public function create(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('hrd.dokters.index', ['create' => 1]);
        }

        return response()->json([
            'success' => true,
            'html' => view('erm.dokters._form', $this->formData())->render(),
        ]);
    }

    public function edit(Request $request, $id)
    {
        if (!$request->ajax()) {
            return redirect()->route('hrd.dokters.index', ['edit' => $id]);
        }

        $dokter = Dokter::with(['user', 'spesialisasi', 'klinik', 'kliniks'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'html' => view('erm.dokters._form', $this->formData($dokter) + compact('dokter'))->render(),
        ]);
    }

    private function formData(?Dokter $dokter = null): array
    {
        // Users with role dokter that are not linked yet, plus the current one when editing
        $users = User::role('dokter')->where(function ($q) use ($dokter) {
            $q->doesntHave('dokter');
            if ($dokter) {
                $q->orWhere('id', $dokter->user_id);
            }
        })->orderBy('name')->get();

        return [
            'users' => $users,
            'spesialisasis' => Spesialisasi::orderBy('nama')->get(),
            'kliniks' => Klinik::orderBy('nama')->get(),
        ];
    }

    public function destroy($id)
    {
        $dokter = Dokter::findOrFail($id);

        // Most of these have no foreign key: a delete would leave rows pointing to a dokter that no longer exists
        $usedIn = collect(self::DOKTER_REFERENCES)
            ->filter(fn ($label, $table) => DB::table($table)->where('dokter_id', $dokter->id)->exists())
            ->values();
        if ($usedIn->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Dokter tidak dapat dihapus karena sudah memiliki data: ' . $usedIn->implode(', ') . '. Gunakan Nonaktifkan bila dokter sudah berhenti praktik.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($dokter) {
                $dokter->kliniks()->detach();
                $dokter->delete();
            });
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dokter masih dipakai di data lain, tidak dapat dihapus.',
            ], 422);
        }

        if ($dokter->photo) {
            Storage::disk('public')->delete($dokter->photo);
        }
        if ($dokter->ttd && file_exists(public_path('img/qr/' . $dokter->ttd))) {
            @unlink(public_path('img/qr/' . $dokter->ttd));
        }
        $this->forgetCaches();

        return response()->json(['success' => true, 'message' => 'Data dokter berhasil dihapus.']);
    }

    /**
     * Store or update dokter data (AJAX only)
     */
    public function store(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:erm_dokters,id',
            // One dokter per user account
            'user_id' => 'required|exists:users,id|unique:erm_dokters,user_id' . ($request->id ? ',' . (int) $request->id : ''),
            'sip' => 'required|string|max:255',
            'spesialisasi_id' => 'required|exists:erm_spesialisasis,id',
            'klinik_id' => 'required|exists:erm_klinik,id',
            'klinik_ids' => 'nullable|array',
            'klinik_ids.*' => 'exists:erm_klinik,id',
            'klinik_spesialisasi' => 'nullable|array',
            'klinik_spesialisasi.*' => 'nullable|exists:erm_spesialisasis,id',
            'due_date_sip' => 'nullable|date',
            'photo' => 'nullable|file|image|max:5120',
            'ttd' => 'nullable|file|image|max:5120',
            'nik' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'no_hp' => 'nullable|string|max:20',
            'status' => 'nullable|in:Kontrak,Tetap',
            'str' => 'nullable|string|max:255',
            'due_date_str' => 'nullable|date',
        ], [
            'user_id.unique' => 'Akun user ini sudah terhubung ke data dokter lain.',
            'user_id.required' => 'Akun user wajib dipilih.',
            'sip.required' => 'Nomor SIP wajib diisi.',
            'spesialisasi_id.required' => 'Spesialisasi wajib dipilih.',
            'klinik_id.required' => 'Klinik utama wajib dipilih.',
            'status.in' => 'Status harus Kontrak atau Tetap.',
            'photo.image' => 'Foto harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
            'ttd.image' => 'TTD harus berupa gambar.',
            'ttd.max' => 'Ukuran TTD maksimal 5 MB.',
        ]);

        $data = $request->only([
            'user_id',
            'sip',
            'spesialisasi_id',
            'klinik_id',
            'due_date_sip',
            'nik',
            'alamat',
            'no_hp',
            'status',
            'str',
            'due_date_str',
        ]);

        $old = $request->id ? Dokter::find($request->id) : null;

        // Photo (storage/public/dokter_photos): new file, removed, or unchanged
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('dokter_photos', 'public');
        } elseif ($request->boolean('remove_photo')) {
            $data['photo'] = null;
        }

        // TTD (public/img/qr, only the filename in the db; read by the lab / surat prints)
        if ($request->hasFile('ttd')) {
            $file = $request->file('ttd');
            $filename = uniqid('ttd_') . '.' . $file->getClientOriginalExtension();
            $destination = public_path('img/qr');
            if (!file_exists($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $filename);
            $data['ttd'] = $filename;
        } elseif ($request->boolean('remove_ttd')) {
            $data['ttd'] = null;
        }

        $klinikIds = collect($request->input('klinik_ids', []))
            ->push((int) $request->klinik_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Optional per-klinik spesialisasi; empty means use the dokter's default spesialisasi.
        $klinikSpesialisasi = $request->input('klinik_spesialisasi', []);

        // Dokter and its klinik list together: no dokter without klinik when the sync fails
        $dokter = DB::transaction(function () use ($request, $data, $klinikIds, $klinikSpesialisasi) {
            $dokter = Dokter::updateOrCreate(['id' => $request->id], $data);
            $dokter->kliniks()->sync(
                collect($klinikIds)->mapWithKeys(fn ($klinikId) => [
                    $klinikId => ['spesialisasi_id' => ($klinikSpesialisasi[$klinikId] ?? null) ?: null],
                ])->all()
            );
            return $dokter;
        });

        // Replaced / removed files are not used anywhere else
        if ($old && $old->photo && $old->photo !== $dokter->photo) {
            Storage::disk('public')->delete($old->photo);
        }
        if ($old && $old->ttd && $old->ttd !== $dokter->ttd && file_exists(public_path('img/qr/' . $old->ttd))) {
            @unlink(public_path('img/qr/' . $old->ttd));
        }

        $this->forgetCaches();

        return response()->json([
            'success' => true,
            'message' => $request->id ? 'Data dokter berhasil diupdate.' : 'Data dokter berhasil ditambahkan.',
            'dokter' => $dokter
        ]);
    }

}
