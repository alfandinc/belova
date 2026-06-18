<?php

namespace App\Http\Controllers;

use App\Models\Rnd\RndMasterBrand;
use App\Models\Rnd\RndMasterBahanAktif;
use App\Models\Rnd\RndMasterKemasan;
use App\Models\Rnd\RndMasterSediaan;
use App\Models\Rnd\RndMasterVendor;
use App\Models\Rnd\RndProduk;
use App\Models\Rnd\RndProdukLog;
use App\Models\Rnd\RndNotif;
use App\Models\Rnd\RndSampleLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class RndProdukController extends Controller
{
    private const STATUS_ADMINISTRASI_FPP_OPTIONS = ['review', 'revisi', 'done'];

    private const STATUS_ADMINISTRASI_SPK_OPTIONS = ['review', 'revisi', 'done'];

    private const STATUS_ADMINISTRASI_NOTIF_OPTIONS = ['review', 'revisi', 'done'];

    private const STATUS_SAMPLE_OPTIONS = [
        'review',
        'revisi',
        'done',
    ];

    private const STATUS_KEMASAN_OPTIONS = [
        'in progress',
        'done',
    ];

    private const STATUS_DESAIN_OPTIONS = [
        'in progress',
        'done',
    ];

    public function index()
    {
        $kemasans = RndMasterKemasan::query()->orderBy('nama_kemasan')->get(['id', 'nama_kemasan', 'ukuran', 'tipe_kemasan']);
        $kemasanPrimerOptions = $kemasans->where('tipe_kemasan', 'primer')->values();
        $kemasanSekunderOptions = $kemasans->where('tipe_kemasan', 'sekunder')->values();
        $vendors = RndMasterVendor::query()->orderBy('nama_vendor')->get(['id', 'nama_vendor', 'tipe_vendor']);

        return view('rnd.products.index', [
            'brands' => RndMasterBrand::query()->orderBy('nama_brand')->get(['id', 'nama_brand']),
            'bahanAktifs' => RndMasterBahanAktif::query()->orderBy('nama_bahan_aktif')->get(['id', 'nama_bahan_aktif']),
            'kemasans' => $kemasans,
            'kemasanPrimerOptions' => $kemasanPrimerOptions,
            'kemasanSekunderOptions' => $kemasanSekunderOptions,
            'produsenVendors' => $vendors->filter(fn (RndMasterVendor $vendor) => in_array('produsen', (array) $vendor->tipe_vendor, true))->values(),
            'kemasanVendors' => $vendors->filter(fn (RndMasterVendor $vendor) => in_array('kemasan', (array) $vendor->tipe_vendor, true))->values(),
            'desainVendors' => $vendors->filter(fn (RndMasterVendor $vendor) => in_array('desain', (array) $vendor->tipe_vendor, true))->values(),
            'kemasanPickerOptions' => $kemasans->map(function (RndMasterKemasan $kemasan) {
                return [
                    'id' => $kemasan->id,
                    'label' => $kemasan->nama_kemasan . ($kemasan->ukuran ? ' (' . $kemasan->ukuran . ')' : ''),
                    'tipe_kemasan' => $kemasan->tipe_kemasan,
                ];
            })->values(),
            'sediaans' => RndMasterSediaan::query()->orderBy('nama_sediaan')->get(['id', 'nama_sediaan']),
            'statusAdministrasiFppOptions' => self::STATUS_ADMINISTRASI_FPP_OPTIONS,
            'statusAdministrasiSpkOptions' => self::STATUS_ADMINISTRASI_SPK_OPTIONS,
            'statusAdministrasiNotifOptions' => self::STATUS_ADMINISTRASI_NOTIF_OPTIONS,
            'statusKemasanOptions' => self::STATUS_KEMASAN_OPTIONS,
            'statusDesainOptions' => self::STATUS_DESAIN_OPTIONS,
            'statusSampleOptions' => self::STATUS_SAMPLE_OPTIONS,
        ]);
    }

    public function data(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $query = RndProduk::query()
            ->with([
                'brand',
                'produsenVendor',
                'bahanAktif',
                'kemasanPremier',
                'kemasanSekunder',
                'kemasanPrimerVendor',
                'kemasanSekunderVendor',
                'desainKemasanPrimerVendor',
                'desainKemasanSekunderVendor',
                'sediaan',
                'latestSampleLog',
                'latestNotif',
            ])
            ->select('rnd_produk.*');

        return DataTables::of($query)
            ->addColumn('brand_name', fn (RndProduk $produk) => optional($produk->brand)->nama_brand ?? '-')
            ->addColumn('produsen_vendor_name', fn (RndProduk $produk) => optional($produk->produsenVendor)->nama_vendor)
            ->addColumn('bahan_aktif_names', function (RndProduk $produk) {
                if ($produk->bahanAktif->isEmpty()) {
                    return '-';
                }

                return $produk->bahanAktif
                    ->pluck('nama_bahan_aktif')
                    ->implode(', ');
            })
            ->addColumn('kemasan_premier_name', fn (RndProduk $produk) => $this->formatKemasan(optional($produk->kemasanPremier)->nama_kemasan, optional($produk->kemasanPremier)->ukuran))
            ->addColumn('kemasan_sekunder_name', fn (RndProduk $produk) => $this->formatKemasan(optional($produk->kemasanSekunder)->nama_kemasan, optional($produk->kemasanSekunder)->ukuran))
            ->addColumn('kemasan_primer_vendor_name', fn (RndProduk $produk) => optional($produk->kemasanPrimerVendor)->nama_vendor)
            ->addColumn('kemasan_sekunder_vendor_name', fn (RndProduk $produk) => optional($produk->kemasanSekunderVendor)->nama_vendor)
            ->addColumn('desain_kemasan_primer_vendor_name', fn (RndProduk $produk) => optional($produk->desainKemasanPrimerVendor)->nama_vendor)
            ->addColumn('desain_kemasan_sekunder_vendor_name', fn (RndProduk $produk) => optional($produk->desainKemasanSekunderVendor)->nama_vendor)
            ->addColumn('sediaan_name', fn (RndProduk $produk) => optional($produk->sediaan)->nama_sediaan ?? '-')
            ->addColumn('latest_sample_id', fn (RndProduk $produk) => optional($produk->latestSampleLog)->id)
            ->addColumn('latest_sample_no_produksi', fn (RndProduk $produk) => optional($produk->latestSampleLog)->no_produksi)
            ->addColumn('latest_sample_status', fn (RndProduk $produk) => optional($produk->latestSampleLog)->status_sample)
            ->addColumn('latest_sample_notes', fn (RndProduk $produk) => optional($produk->latestSampleLog)->notes)
            ->addColumn('latest_notif_id', fn (RndProduk $produk) => optional($produk->latestNotif)->id)
            ->addColumn('latest_notif_tanggal_selesai', fn (RndProduk $produk) => optional($produk->latestNotif)->tanggal_selesai?->toDateString())
            ->addColumn('latest_notif_document_url', fn (RndProduk $produk) => $produk->latestNotif && $produk->latestNotif->doc_path ? route('rnd.products.notif.document', $produk->latestNotif) : null)
            ->addColumn('has_sample_log', fn (RndProduk $produk) => $produk->latestSampleLog !== null)
            ->make(true);
    }

    public function show(RndProduk $produk)
    {
        $produk->load([
            'bahanAktif:id,nama_bahan_aktif',
            'sampleLogs' => fn ($query) => $query->latest(),
            'productLogs' => fn ($query) => $query->latest('log_date_time'),
        ]);

        return response()->json([
            'data' => array_merge($produk->toArray(), [
                'bahan_aktif_ids' => $produk->bahanAktif->pluck('id')->all(),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedPayload($request);
        $produk = RndProduk::create($validated['attributes']);
        $produk->bahanAktif()->sync($validated['bahan_aktif_ids']);

        $this->writeProdukLog($produk->id, 'created', 'Produk dibuat melalui halaman Produk RND.');

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil disimpan.',
            'data' => $produk->load('bahanAktif:id,nama_bahan_aktif'),
        ]);
    }

    public function update(Request $request, RndProduk $produk)
    {
        if ($request->boolean('add_sample_log')) {
            return $this->storeSampleLog($request, $produk);
        }

        if ($request->boolean('inline_relation_update')) {
            return $this->updateInlineRelation($request, $produk);
        }

        if ($request->boolean('inline_status_update')) {
            return $this->updateInlineStatus($request, $produk);
        }

        $validated = $this->validatedPayload($request, true);
        $produk->update($validated['attributes']);
        $produk->bahanAktif()->sync($validated['bahan_aktif_ids']);

        $this->writeProdukLog($produk->id, 'updated', 'Produk diperbarui melalui halaman Produk RND.');

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui.',
            'data' => $produk->fresh()->load('bahanAktif:id,nama_bahan_aktif'),
        ]);
    }

    public function destroy(RndProduk $produk)
    {
        $produk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus.',
        ]);
    }

    public function updateSampleLog(Request $request, RndSampleLog $sampleLog)
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $sampleLog->update([
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->writeProdukLog($sampleLog->produk_id, 'sample-updated', 'Catatan sample diperbarui melalui history sample RND.');

        return response()->json([
            'success' => true,
            'message' => 'Catatan sample berhasil diperbarui.',
            'data' => $sampleLog->fresh(),
        ]);
    }

    public function destroySampleLog(RndSampleLog $sampleLog)
    {
        $produkId = $sampleLog->produk_id;
        $sampleLog->delete();

        $this->writeProdukLog($produkId, 'sample-deleted', 'Sample dihapus melalui history sample RND.');

        return response()->json([
            'success' => true,
            'message' => 'Sample berhasil dihapus.',
        ]);
    }

    public function viewNotifDocument(RndNotif $notif)
    {
        if (!$notif->doc_path || !Storage::disk('public')->exists($notif->doc_path)) {
            abort(404);
        }

        $path = Storage::disk('public')->path($notif->doc_path);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . basename($notif->doc_path) . '"',
        ]);
    }

    private function validatedPayload(Request $request, bool $isUpdate = false): array
    {
        $requiredRule = $isUpdate ? 'nullable' : 'required';

        $validated = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'brand_id' => $requiredRule . '|exists:rnd_master_brand,id',
            'produsen_vendor_id' => 'nullable|exists:rnd_master_vendor,id',
            'bahan_aktif_ids' => 'nullable|array',
            'bahan_aktif_ids.*' => 'exists:rnd_master_bahan_aktif,id',
            'kemasan_premier_id' => $requiredRule . '|exists:rnd_master_kemasan,id',
            'kemasan_sekunder_id' => 'nullable|exists:rnd_master_kemasan,id',
            'kemasan_primer_vendor_id' => 'nullable|exists:rnd_master_vendor,id',
            'kemasan_sekunder_vendor_id' => 'nullable|exists:rnd_master_vendor,id',
            'desain_kemasan_primer_id' => 'nullable|exists:rnd_master_vendor,id',
            'desain_kemasan_sekunder_id' => 'nullable|exists:rnd_master_vendor,id',
            'sediaan_id' => $requiredRule . '|exists:rnd_master_sediaan,id',
            'netto' => 'nullable|string|max:255',
            'status_administrasi_fpp' => 'nullable|in:' . implode(',', self::STATUS_ADMINISTRASI_FPP_OPTIONS),
            'status_administrasi_spk' => 'nullable|in:' . implode(',', self::STATUS_ADMINISTRASI_SPK_OPTIONS),
            'status_administrasi_notif' => 'nullable|in:' . implode(',', self::STATUS_ADMINISTRASI_NOTIF_OPTIONS),
            'status_kemasan_primer' => 'nullable|string|max:255',
            'status_kemasan_sekunder' => 'nullable|string|max:255',
            'status_desain_kemasan_primer' => 'nullable|string|max:255',
            'status_desain_kemasan_sekunder' => 'nullable|string|max:255',
        ]);

        return [
            'attributes' => collect($validated)
                ->except(['bahan_aktif_ids'])
                ->all(),
            'bahan_aktif_ids' => $validated['bahan_aktif_ids'] ?? [],
        ];
    }

    private function updateInlineStatus(Request $request, RndProduk $produk)
    {
        $fieldRules = [
            'status_kemasan_primer' => ['nullable', Rule::in(self::STATUS_KEMASAN_OPTIONS)],
            'status_kemasan_sekunder' => ['nullable', Rule::in(self::STATUS_KEMASAN_OPTIONS)],
            'status_desain_kemasan_primer' => ['nullable', Rule::in(self::STATUS_DESAIN_OPTIONS)],
            'status_desain_kemasan_sekunder' => ['nullable', Rule::in(self::STATUS_DESAIN_OPTIONS)],
            'status_administrasi_fpp' => ['nullable', Rule::in(self::STATUS_ADMINISTRASI_FPP_OPTIONS)],
            'status_administrasi_spk' => ['nullable', Rule::in(self::STATUS_ADMINISTRASI_SPK_OPTIONS)],
            'status_administrasi_notif' => ['nullable', Rule::in(self::STATUS_ADMINISTRASI_NOTIF_OPTIONS)],
            'status_sample' => ['nullable', Rule::in(self::STATUS_SAMPLE_OPTIONS)],
        ];

        $field = $request->validate([
            'field' => ['required', 'string', Rule::in(array_keys($fieldRules))],
        ])['field'];

        $request->merge([
            $field => $request->input('value') !== '' ? $request->input('value') : null,
        ]);

        $validated = $request->validate([
            $field => $fieldRules[$field],
            'log_notes' => ['nullable', 'string'],
        ]);

        $logNotes = $this->buildStatusLogNotes($field, $validated[$field] ?? null, $validated['log_notes'] ?? null);

        if ($field === 'status_sample') {
            $latestSampleLog = $produk->latestSampleLog;

            if (!$latestSampleLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Belum ada sample untuk produk ini.',
                ], 422);
            }

            $latestSampleLog->update([
                'status_sample' => $validated[$field] ?? null,
            ]);

            $this->writeProdukLog($produk->id, 'updated', $logNotes);

            return response()->json([
                'success' => true,
                'message' => 'Status sample berhasil diperbarui.',
                'data' => $latestSampleLog->fresh(),
            ]);
        }

        if ($field === 'status_administrasi_notif' && (($validated[$field] ?? null) === 'done')) {
            $notifValidated = $request->validate([
                'tanggal_mulai' => ['required', 'date'],
                'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
                'notif_doc' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            ]);

            $file = $request->file('notif_doc');
            $path = $file->store('rnd/notif', 'public');

            RndNotif::create([
                'produk_id' => $produk->id,
                'doc_path' => $path,
                'tanggal_mulai' => $notifValidated['tanggal_mulai'],
                'tanggal_selesai' => $notifValidated['tanggal_selesai'],
            ]);

            $produk->update([
                $field => 'done',
            ]);

            $this->writeProdukLog($produk->id, 'updated', $logNotes);

            return response()->json([
                'success' => true,
                'message' => 'Status notif berhasil diperbarui.',
                'data' => $produk->fresh('latestNotif'),
            ]);
        }

        $produk->update([
            $field => $validated[$field] ?? null,
        ]);

        $this->writeProdukLog($produk->id, 'updated', $logNotes);

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diperbarui.',
            'data' => $produk->fresh(),
        ]);
    }

    private function updateInlineRelation(Request $request, RndProduk $produk)
    {
        $fieldRules = [
            'kemasan_premier_id' => ['required', 'integer', 'exists:rnd_master_kemasan,id'],
            'kemasan_sekunder_id' => ['nullable', 'integer', 'exists:rnd_master_kemasan,id'],
        ];

        $field = $request->validate([
            'field' => ['required', 'string', Rule::in(array_keys($fieldRules))],
        ])['field'];

        $request->merge([
            $field => $request->input('value') !== '' ? $request->input('value') : null,
        ]);

        $validated = $request->validate([
            $field => $fieldRules[$field],
            'kemasan_primer_vendor_id' => ['nullable', 'integer', 'exists:rnd_master_vendor,id'],
            'kemasan_sekunder_vendor_id' => ['nullable', 'integer', 'exists:rnd_master_vendor,id'],
            'desain_kemasan_primer_id' => ['nullable', 'integer', 'exists:rnd_master_vendor,id'],
            'desain_kemasan_sekunder_id' => ['nullable', 'integer', 'exists:rnd_master_vendor,id'],
        ]);

        $statusFieldMap = [
            'kemasan_premier_id' => 'status_kemasan_primer',
            'kemasan_sekunder_id' => 'status_kemasan_sekunder',
        ];

        $statusField = $statusFieldMap[$field];
        $kemasanValue = $validated[$field] ?? null;
        $vendorFieldMap = [
            'kemasan_premier_id' => ['kemasan_primer_vendor_id', 'desain_kemasan_primer_id'],
            'kemasan_sekunder_id' => ['kemasan_sekunder_vendor_id', 'desain_kemasan_sekunder_id'],
        ];
        $relatedFields = $vendorFieldMap[$field];

        $produk->update([
            $field => $kemasanValue,
            $statusField => $kemasanValue ? self::STATUS_KEMASAN_OPTIONS[0] : null,
            $relatedFields[0] => $kemasanValue ? ($validated[$relatedFields[0]] ?? null) : null,
            $relatedFields[1] => $kemasanValue ? ($validated[$relatedFields[1]] ?? null) : null,
        ]);

        $this->writeProdukLog($produk->id, 'updated', 'Kemasan produk diperbarui melalui tabel Produk RND.');

        return response()->json([
            'success' => true,
            'message' => 'Kemasan berhasil diperbarui.',
            'data' => $produk->fresh()->load(['kemasanPremier', 'kemasanSekunder']),
        ]);
    }

    private function storeSampleLog(Request $request, RndProduk $produk)
    {
        $validated = $request->validate([
            'no_produksi' => ['required', 'string', 'max:255'],
            'status_sample' => ['nullable', Rule::in(self::STATUS_SAMPLE_OPTIONS)],
            'notes' => ['nullable', 'string'],
        ]);

        $sampleLog = RndSampleLog::create([
            'produk_id' => $produk->id,
            'no_produksi' => $validated['no_produksi'],
            'status_sample' => $validated['status_sample'] ?? self::STATUS_SAMPLE_OPTIONS[0],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->writeProdukLog($produk->id, 'sample-added', 'Sample log ditambahkan melalui halaman Produk RND.');

        return response()->json([
            'success' => true,
            'message' => 'Sample berhasil ditambahkan.',
            'data' => $sampleLog,
        ]);
    }

    private function writeProdukLog(int $produkId, string $statusActivity, ?string $notes = null): void
    {
        RndProdukLog::create([
            'produk_id' => $produkId,
            'log_date_time' => now(),
            'status_activity' => $statusActivity,
            'notes' => $notes,
        ]);
    }

    private function buildStatusLogNotes(string $field, ?string $value, ?string $userNotes): string
    {
        $labels = [
            'status_kemasan_primer' => 'Status Kemasan Primer',
            'status_kemasan_sekunder' => 'Status Kemasan Sekunder',
            'status_desain_kemasan_primer' => 'Status Desain Kemasan Primer',
            'status_desain_kemasan_sekunder' => 'Status Desain Kemasan Sekunder',
            'status_administrasi_fpp' => 'Status Administrasi FPP',
            'status_administrasi_spk' => 'Status Administrasi SPK',
            'status_administrasi_notif' => 'Status Administrasi Notif',
            'status_sample' => 'Status Sample',
        ];

        $base = ($labels[$field] ?? $field) . ' diubah menjadi ' . ($value ?: '-');

        if ($userNotes && trim($userNotes) !== '') {
            return $base . '. Catatan: ' . trim($userNotes);
        }

        return $base . '.';
    }

    private function formatKemasan(?string $nama, ?string $ukuran): string
    {
        if (!$nama) {
            return '-';
        }

        return $ukuran ? $nama . ' (' . $ukuran . ')' : $nama;
    }
}