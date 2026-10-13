<?php

namespace App\Http\Controllers;

use App\Models\Rnd\RndMasterBrand;
use App\Models\ERM\MetodeBayar;
use App\Models\ERM\Obat;
use App\Models\ERM\Satuan;
use App\Models\ERM\ZatAktif;
use App\Models\Rnd\RndMasterKemasan;
use App\Models\Rnd\RndMasterVendor;
use App\Models\Rnd\RndProduk;
use App\Models\Rnd\RndProdukLog;
use App\Models\Rnd\RndProdukSertifikasi;
use App\Models\Rnd\RndProdukTimeline;
use App\Models\Rnd\RndSampleLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        return view('rnd.products.index', [
            'masters' => RndMasterController::definitions(),
            'masterOptions' => $this->masterOptions(),
            'zatAktifs' => ZatAktif::query()->orderBy('nama')->get(['id', 'nama']),
            'sediaans' => Satuan::allOptions(),
            'nettoSatuans' => Satuan::dosisOptions(),
            'statusProdukOptions' => RndProduk::STATUS_PRODUK,
            'jenisSertifikasi' => RndProdukSertifikasi::JENIS,
            'evaluasiFields' => RndSampleLog::EVALUASI,
            'statusAdministrasiFppOptions' => self::STATUS_ADMINISTRASI_FPP_OPTIONS,
            'statusAdministrasiSpkOptions' => self::STATUS_ADMINISTRASI_SPK_OPTIONS,
            'statusAdministrasiNotifOptions' => self::STATUS_ADMINISTRASI_NOTIF_OPTIONS,
            'statusKemasanOptions' => self::STATUS_KEMASAN_OPTIONS,
            'statusDesainOptions' => self::STATUS_DESAIN_OPTIONS,
            'statusSampleOptions' => self::STATUS_SAMPLE_OPTIONS,
        ]);
    }

    /** Dropdown options from the Brand / Kemasan / Vendor masters; reloaded after a "Kelola ..." modal changes them. */
    public function options()
    {
        return response()->json($this->masterOptions());
    }

    private function masterOptions(): array
    {
        $kemasans = RndMasterKemasan::query()->orderBy('nama_kemasan')->get(['id', 'nama_kemasan', 'ukuran', 'tipe_kemasan']);
        $vendors = RndMasterVendor::query()->orderBy('nama_vendor')->get(['id', 'nama_vendor', 'tipe_vendor']);
        $vendorsOf = fn (string $tipe) => $vendors
            ->filter(fn (RndMasterVendor $vendor) => in_array($tipe, (array) $vendor->tipe_vendor, true))
            ->map(fn (RndMasterVendor $vendor) => ['id' => $vendor->id, 'label' => $vendor->nama_vendor])
            ->values();

        return [
            'brands' => RndMasterBrand::query()->orderBy('nama_brand')->get(['id', 'nama_brand'])
                ->map(fn (RndMasterBrand $brand) => ['id' => $brand->id, 'label' => $brand->nama_brand])->values(),
            'kemasans' => $kemasans->map(fn (RndMasterKemasan $kemasan) => [
                'id' => $kemasan->id,
                'label' => $this->formatKemasan($kemasan->nama_kemasan, $kemasan->ukuran),
                'tipe_kemasan' => $kemasan->tipe_kemasan,
            ])->values(),
            'vendors' => [
                'produsen' => $vendorsOf('produsen'),
                'kemasan' => $vendorsOf('kemasan'),
                'desain' => $vendorsOf('desain'),
            ],
            // Timeline filter and "Tambah Catatan" product choice
            'produks' => RndProduk::query()->with('brand:id,nama_brand')->orderBy('nama_produk')->get()
                ->map(fn (RndProduk $produk) => ['id' => $produk->id, 'label' => self::releaseNama($produk)])->values(),
        ];
    }

    /** Card keys of the summary row, in display order. */
    public const TAHAP = ['semua', 'proses', 'siap_rilis', 'dirilis', 'ditunda', 'perpanjangan'];

    /**
     * Stage filter shared by the table, the summary cards and the Excel export:
     * proses = active and not ready yet, siap_rilis = every release requirement met (RndProduk::releaseChecklist),
     * dirilis = linked to an obat, ditunda = paused or cancelled, perpanjangan = a sertifikasi ends within 90 days or has ended.
     */
    public static function applyTahapFilter($query, ?string $tahap)
    {
        return match ($tahap) {
            'proses' => $query->whereNull('rnd_produk.obat_id')
                ->where('rnd_produk.status_produk', RndProduk::STATUS_AKTIF)
                // NOT IN on ids, not NOT(...): status columns can be NULL and NOT(NULL) would drop the row
                ->whereNotIn('rnd_produk.id', RndProduk::query()->siapRilis()->select('rnd_produk.id')),
            'siap_rilis' => $query->siapRilis(),
            'dirilis' => $query->whereNotNull('rnd_produk.obat_id'),
            'ditunda' => $query->whereNull('rnd_produk.obat_id')->where('rnd_produk.status_produk', '!=', RndProduk::STATUS_AKTIF),
            'perpanjangan' => $query->whereHas('sertifikasi', fn ($s) => $s->perluPerpanjangan()),
            default => $query,
        };
    }

    /** Brand + search filters (search covers what the table shows: nama, brand, zat aktif, sediaan, no produksi, obat, PIC). */
    public static function applyListFilters($query, Request $request)
    {
        $brandId = $request->input('brand_id');
        $search = trim((string) ($request->input('search.value') ?? $request->input('q', '')));

        $query->when($brandId, fn ($q) => $q->where('rnd_produk.brand_id', $brandId));

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('rnd_produk.nama_produk', 'like', $like)
                    ->orWhere('rnd_produk.sediaan', 'like', $like)
                    ->orWhereHas('brand', fn ($b) => $b->where('nama_brand', 'like', $like))
                    ->orWhereHas('zatAktifs', fn ($z) => $z->where('nama', 'like', $like))
                    ->orWhereHas('sampleLogs', fn ($s) => $s->where('no_produksi', 'like', $like))
                    ->orWhereHas('obat', fn ($o) => $o->where('kode_obat', 'like', $like)->orWhere('nama', 'like', $like))
                    ->orWhereHas('pic', fn ($u) => $u->where('name', 'like', $like));
            });
        }

        return $query;
    }

    /** Table chips: every wajib jenis (also when missing) plus optional ones the product has. */
    private function legalitasSummary(RndProduk $produk): array
    {
        $today = \Illuminate\Support\Carbon::today();
        $chips = [];

        foreach (RndProdukSertifikasi::JENIS as $jenis => $meta) {
            $current = $produk->sertifikasi->where('jenis', $jenis)->sortByDesc(fn ($s) => [$s->tanggal_selesai, $s->id])->first();
            if (!$current && !$meta['wajib']) {
                continue;
            }
            $chips[] = [
                'jenis' => $jenis,
                'short' => $meta['short'],
                'label' => $meta['label'],
                'wajib' => $meta['wajib'],
                'state' => $current ? $current->masaBerlaku($today) : 'belum',
                'berlaku_sampai' => optional($current)->tanggal_selesai?->toDateString(),
            ];
        }

        return $chips;
    }

    public function data(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $counts = [];
        foreach (self::TAHAP as $tahap) {
            $counts[$tahap] = self::applyListFilters(self::applyTahapFilter(RndProduk::query(), $tahap), $request)->count();
        }

        $query = RndProduk::query()
            ->with([
                'brand',
                'produsenVendor',
                'zatAktifs',
                'kemasanPremier',
                'kemasanSekunder',
                'kemasanPrimerVendor',
                'kemasanSekunderVendor',
                'desainKemasanPrimerVendor',
                'desainKemasanSekunderVendor',
                'latestSampleLog',
                'sertifikasi',
                'pic:id,name',
                'obat:id,kode_obat,nama,status_aktif',
            ])
            ->select('rnd_produk.*')
            // Newest first unless the user sorts a column (the No column is not sortable, so nothing was ordered before)
            ->when(!$request->filled('order'), fn ($q) => $q->orderByDesc('rnd_produk.id'));
        self::applyTahapFilter($query, $request->input('tahap'));
        self::applyListFilters($query, $request);

        return DataTables::of($query)
            ->with('counts', $counts)
            // Search is applied above (shared with the export); an empty callback turns off the default global search
            ->filter(function () {
            })
            ->addColumn('pic_name', fn (RndProduk $produk) => optional($produk->pic)->name)
            ->addColumn('legalitas', fn (RndProduk $produk) => $this->legalitasSummary($produk))
            ->addColumn('siap_rilis', fn (RndProduk $produk) => $produk->obat_id === null
                && collect($produk->releaseChecklist())->every(fn ($item) => $item['ok']))
            ->addColumn('brand_name', fn (RndProduk $produk) => optional($produk->brand)->nama_brand ?? '-')
            ->addColumn('produsen_vendor_name', fn (RndProduk $produk) => optional($produk->produsenVendor)->nama_vendor)
            ->addColumn('zat_aktif_names', function (RndProduk $produk) {
                if ($produk->zatAktifs->isEmpty()) {
                    return '-';
                }

                return $produk->zatAktifs
                    ->pluck('nama')
                    ->implode(', ');
            })
            ->addColumn('kemasan_premier_name', fn (RndProduk $produk) => $this->formatKemasan(optional($produk->kemasanPremier)->nama_kemasan, optional($produk->kemasanPremier)->ukuran))
            ->addColumn('kemasan_sekunder_name', fn (RndProduk $produk) => $this->formatKemasan(optional($produk->kemasanSekunder)->nama_kemasan, optional($produk->kemasanSekunder)->ukuran))
            ->addColumn('kemasan_primer_vendor_name', fn (RndProduk $produk) => optional($produk->kemasanPrimerVendor)->nama_vendor)
            ->addColumn('kemasan_sekunder_vendor_name', fn (RndProduk $produk) => optional($produk->kemasanSekunderVendor)->nama_vendor)
            ->addColumn('desain_kemasan_primer_vendor_name', fn (RndProduk $produk) => optional($produk->desainKemasanPrimerVendor)->nama_vendor)
            ->addColumn('desain_kemasan_sekunder_vendor_name', fn (RndProduk $produk) => optional($produk->desainKemasanSekunderVendor)->nama_vendor)
            ->addColumn('sediaan_name', fn (RndProduk $produk) => $produk->sediaan ?: '-')
            ->addColumn('obat_kode', fn (RndProduk $produk) => optional($produk->obat)->kode_obat)
            ->addColumn('obat_nama', fn (RndProduk $produk) => optional($produk->obat)->nama)
            ->addColumn('latest_sample_id', fn (RndProduk $produk) => optional($produk->latestSampleLog)->id)
            ->addColumn('latest_sample_no_produksi', fn (RndProduk $produk) => optional($produk->latestSampleLog)->no_produksi)
            ->addColumn('latest_sample_status', fn (RndProduk $produk) => optional($produk->latestSampleLog)->status_sample)
            ->addColumn('latest_sample_notes', fn (RndProduk $produk) => optional($produk->latestSampleLog)->notes)
            ->addColumn('latest_notif_nomor', fn (RndProduk $produk) => optional($this->currentSertifikasi($produk, 'notif_bpom'))->nomor)
            ->addColumn('latest_notif_tanggal_selesai', fn (RndProduk $produk) => optional($this->currentSertifikasi($produk, 'notif_bpom'))->tanggal_selesai?->toDateString())
            ->addColumn('latest_notif_document_url', function (RndProduk $produk) {
                $notif = $this->currentSertifikasi($produk, 'notif_bpom');
                return $notif && $notif->doc_path ? route('rnd.sertifikasi.document', $notif) : null;
            })
            ->addColumn('has_sample_log', fn (RndProduk $produk) => $produk->latestSampleLog !== null)
            ->make(true);
    }

    public function timelineData(Request $request)
    {
        abort_unless($request->ajax(), 404);

        if (!$this->hasTimelineTable()) {
            return response()->json([
                'data' => [],
            ]);
        }

        $produkId = $request->integer('produk_id') ?: null;
        $produkColumns = 'produk:id,nama_produk,brand_id,netto,netto_satuan,sediaan';

        // Catatan timeline (editable)
        $items = RndProdukTimeline::query()
            ->with([$produkColumns, 'produk.brand:id,nama_brand'])
            ->when($produkId, fn ($q) => $q->where('produk_id', $produkId))
            ->orderByDesc('timeline_date')
            ->latest('id')
            ->get()
            ->map(fn (RndProdukTimeline $timeline) => [
                'type' => 'catatan',
                'id' => $timeline->id,
                'produk_id' => $timeline->produk_id,
                'produk_name' => $timeline->produk ? self::releaseNama($timeline->produk) : '-',
                'timeline_date' => $timeline->timeline_date?->toDateString(),
                'notes' => $timeline->notes,
                'created_at' => optional($timeline->created_at)?->toIso8601String(),
            ]);

        // Target rilis of products still being developed (read-only, edited on the product)
        $targets = RndProduk::query()
            ->with('brand:id,nama_brand')
            ->whereNotNull('target_rilis')
            ->whereNull('obat_id')
            ->where('status_produk', RndProduk::STATUS_AKTIF)
            ->when($produkId, fn ($q) => $q->whereKey($produkId))
            ->get()
            ->map(fn (RndProduk $produk) => [
                'type' => 'target_rilis',
                'id' => 'target-' . $produk->id,
                'produk_id' => $produk->id,
                'produk_name' => self::releaseNama($produk),
                'timeline_date' => $produk->target_rilis->toDateString(),
                'notes' => 'Target rilis produk.',
                'created_at' => null,
            ]);

        // End date of each current sertifikasi (read-only, renewed on the Legalitas tab)
        $expiries = RndProdukSertifikasi::query()
            ->terkini()
            ->with([$produkColumns, 'produk.brand:id,nama_brand'])
            ->whereHas('produk', fn ($p) => $p->where('status_produk', '!=', RndProduk::STATUS_DIBATALKAN))
            ->when($produkId, fn ($q) => $q->where('produk_id', $produkId))
            ->get()
            ->map(fn (RndProdukSertifikasi $s) => [
                'type' => 'sertifikat',
                'id' => 'sertifikat-' . $s->id,
                'produk_id' => $s->produk_id,
                'produk_name' => $s->produk ? self::releaseNama($s->produk) : '-',
                'timeline_date' => $s->tanggal_selesai->toDateString(),
                'notes' => RndProdukSertifikasi::label($s->jenis) . ($s->nomor ? " {$s->nomor}" : '') . ' berakhir.',
                'created_at' => null,
            ]);

        return response()->json([
            'data' => $items->concat($targets)->concat($expiries)->sortByDesc('timeline_date')->values(),
        ]);
    }

    public function show(RndProduk $produk)
    {
        $relations = [
            // Names let the form keep a value whose master no longer fits the dropdown (e.g. vendor tipe changed)
            'brand:id,nama_brand',
            'produsenVendor:id,nama_vendor',
            'kemasanPremier:id,nama_kemasan,ukuran',
            'kemasanSekunder:id,nama_kemasan,ukuran',
            'kemasanPrimerVendor:id,nama_vendor',
            'kemasanSekunderVendor:id,nama_vendor',
            'desainKemasanPrimerVendor:id,nama_vendor',
            'desainKemasanSekunderVendor:id,nama_vendor',
            'zatAktifs:id,nama',
            'pic:id,name',
            'sampleLogs' => fn ($query) => $query->latest('id'),
            'productLogs' => fn ($query) => $query->latest('log_date_time'),
        ];

        if ($this->hasAdditionalDocumentsTable()) {
            $relations['additionalDocuments'] = fn ($query) => $query->latest();
        }

        $produk->load($relations);

        return response()->json([
            'data' => array_merge($produk->toArray(), [
                'zat_aktif_ids' => $produk->zatAktifs->pluck('id')->all(),
                'sertifikasi' => RndSertifikasiController::listFor($produk),
                'release_checklist' => $produk->releaseChecklist(),
                'additional_documents' => ($this->hasAdditionalDocumentsTable() ? $produk->additionalDocuments : collect())->map(function (\App\Models\Rnd\RndProdukDocument $document) {
                    return [
                        'id' => $document->id,
                        'original_name' => $document->original_name,
                        'url' => route('rnd.products.documents.show', $document),
                        'size_bytes' => $document->size_bytes,
                        'created_at' => optional($document->created_at)?->toIso8601String(),
                    ];
                })->values()->all(),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedPayload($request);
        $produk = RndProduk::create($validated['attributes']);
        $produk->zatAktifs()->sync($validated['zat_aktif_ids']);
        $uploadedCount = $this->storeAdditionalDocuments($request, $produk);

        $this->writeProdukLog($produk->id, 'created', $uploadedCount > 0
            ? 'Produk dibuat melalui halaman Produk RND. Dokumen tambahan diunggah: ' . $uploadedCount . ' file.'
            : 'Produk dibuat melalui halaman Produk RND.');

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil disimpan.',
            'data' => $produk->load(['zatAktifs:id,nama', 'additionalDocuments']),
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

        $validated = $this->validatedPayload($request, $produk);
        $produk->update($validated['attributes']);
        $produk->zatAktifs()->sync($validated['zat_aktif_ids']);

        $this->writeProdukLog($produk->id, 'updated', 'Produk diperbarui melalui halaman Produk RND.');

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui.',
            'data' => $produk->fresh()->load(['zatAktifs:id,nama', 'additionalDocuments']),
        ]);
    }

    public function uploadProdukDocuments(Request $request, RndProduk $produk)
    {
        abort_unless($this->hasAdditionalDocumentsTable(), 404);

        $request->validate([
            'additional_documents' => 'required|array|min:1',
            'additional_documents.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
        ]);

        $uploadedCount = $this->storeAdditionalDocuments($request, $produk);

        $this->writeProdukLog($produk->id, 'document-uploaded', 'Dokumen tambahan diunggah: ' . $uploadedCount . ' file.');

        $produk->load(['additionalDocuments' => fn ($query) => $query->latest()]);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen tambahan berhasil diunggah.',
            'data' => [
                'additional_documents' => $produk->additionalDocuments->map(function (\App\Models\Rnd\RndProdukDocument $document) {
                    return [
                        'id' => $document->id,
                        'original_name' => $document->original_name,
                        'url' => route('rnd.products.documents.show', $document),
                        'size_bytes' => $document->size_bytes,
                        'created_at' => optional($document->created_at)?->toIso8601String(),
                    ];
                })->values()->all(),
            ],
        ]);
    }

    public function storeProdukTimeline(Request $request, RndProduk $produk)
    {
        abort_unless($this->hasTimelineTable(), 404);

        $validated = $request->validate([
            'timeline_date' => ['required', 'date'],
            'notes' => ['required', 'string'],
        ]);

        $produk->timelines()->create([
            'timeline_date' => $validated['timeline_date'],
            'notes' => trim($validated['notes']),
        ]);

        $this->writeProdukLog($produk->id, 'timeline-added', 'Timeline produk ditambahkan untuk tanggal ' . $validated['timeline_date'] . '.');

        $produk->load([
            'timelines' => fn ($query) => $query->orderByDesc('timeline_date')->latest('id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Timeline produk berhasil ditambahkan.',
            'data' => [
                'product_timelines' => $produk->timelines->map(function ($timeline) {
                    return [
                        'id' => $timeline->id,
                        'timeline_date' => $timeline->timeline_date?->toDateString(),
                        'notes' => $timeline->notes,
                        'created_at' => optional($timeline->created_at)?->toIso8601String(),
                    ];
                })->values()->all(),
            ],
        ]);
    }

    public function updateProdukTimeline(Request $request, RndProdukTimeline $timeline)
    {
        $validated = $request->validate([
            'timeline_date' => ['required', 'date'],
            'notes' => ['required', 'string'],
        ]);

        $timeline->update(['timeline_date' => $validated['timeline_date'], 'notes' => trim($validated['notes'])]);
        $this->writeProdukLog($timeline->produk_id, 'timeline-updated', 'Timeline produk tanggal ' . $validated['timeline_date'] . ' diperbarui.');

        return response()->json(['success' => true, 'message' => 'Timeline berhasil diperbarui.']);
    }

    public function destroyProdukTimeline(RndProdukTimeline $timeline)
    {
        $date = $timeline->timeline_date?->toDateString();
        $timeline->delete();
        $this->writeProdukLog($timeline->produk_id, 'timeline-deleted', "Timeline produk tanggal {$date} dihapus.");

        return response()->json(['success' => true, 'message' => 'Timeline berhasil dihapus.']);
    }

    public function destroy(RndProduk $produk)
    {
        // Rows cascade with the product; the uploaded files would stay behind on disk
        $files = $produk->sertifikasi()->pluck('doc_path');
        if ($this->hasAdditionalDocumentsTable()) {
            $files = $files->merge($produk->additionalDocuments()->pluck('file_path'));
        }

        $produk->delete();

        $files = $files->filter()->values()->all();
        if ($files !== []) {
            Storage::disk('public')->delete($files);
        }

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus.',
        ]);
    }

    // ----------------------------------------------------------- Rilis ke Master Obat

    /** Pre-filled values for the release form (all editable before saving). */
    public function releaseForm(RndProduk $produk)
    {
        $produk->loadMissing(['brand', 'kemasanPremier', 'zatAktifs:id,nama', 'obat:id,kode_obat,nama']);
        $checklist = $produk->releaseChecklist();

        return response()->json([
            'released' => $produk->obat_id !== null,
            'obat' => $produk->obat ? ['id' => $produk->obat->id, 'kode_obat' => $produk->obat->kode_obat, 'nama' => $produk->obat->nama] : null,
            'checklist' => $checklist,
            'can_release_new' => collect($checklist)->every(fn ($item) => $item['ok']),
            'defaults' => [
                'nama' => $this->releaseNama($produk),
                'dosis' => $this->nettoNumber($produk->netto),
                'satuan' => $this->releaseSatuan($produk),
                'satuan_stok' => $this->guessSatuanStok($produk),
                'zat_aktif' => $produk->zatAktifs->pluck('nama')->values(),
            ],
            'options' => [
                'satuan_stok' => Satuan::stokOptions(),
                'satuan_dosis' => Satuan::dosisOptions(),
                'metode_bayar' => MetodeBayar::query()->orderBy('nama')->get(['id', 'nama']),
            ],
        ]);
    }

    /**
     * Release the product into Master Obat: create a new obat (kategori Produk) or link an existing one.
     * A new obat needs every item of RndProduk::releaseChecklist() (all sertifikasi valid). Linking is for older
     * R&D products that are already in Master Obat, so it has no requirements.
     * The obat is owned by Farmasi afterwards (no automatic sync back).
     */
    public function release(Request $request, RndProduk $produk)
    {
        if ($produk->obat_id !== null) {
            return response()->json(['message' => 'Produk ini sudah terhubung ke Master Obat.'], 422);
        }

        $mode = $request->input('mode') === 'link' ? 'link' : 'new';

        if ($mode === 'link') {
            $validated = $request->validate([
                'obat_id' => ['required', 'integer', 'exists:erm_obat,id', Rule::unique('rnd_produk', 'obat_id')],
            ], [
                'obat_id.required' => 'Pilih obat yang akan dihubungkan.',
                'obat_id.unique' => 'Obat ini sudah terhubung ke produk R&D lain.',
            ]);
            $obat = Obat::withInactive()->findOrFail($validated['obat_id']);
            $produk->update(['obat_id' => $obat->id]);
            $this->writeProdukLog($produk->id, 'released', "Dihubungkan ke Master Obat: {$obat->kode_obat} - {$obat->nama}.");

            return response()->json(['success' => true, 'message' => "Produk dihubungkan ke {$obat->kode_obat} - {$obat->nama}."]);
        }

        $missing = collect($produk->releaseChecklist())->reject(fn ($item) => $item['ok']);
        if ($missing->isNotEmpty()) {
            return response()->json([
                'message' => 'Belum bisa dirilis sebagai obat baru. Lengkapi dulu: '
                    . $missing->map(fn ($item) => $item['label'] . ' (' . $item['detail'] . ')')->implode('; ') . '.',
            ], 422);
        }

        $validated = $request->validate([
            'nama' => 'required|string|max:191',
            'dosis' => 'nullable|string|max:191',
            'satuan' => ['nullable', Rule::in(Satuan::dosisOptions())],
            'satuan_stok' => ['required', Rule::in(Satuan::stokOptions())],
            'metode_bayar_id' => 'required|exists:erm_metode_bayar,id',
        ], [
            'satuan_stok.required' => 'Satuan stok wajib dipilih.',
            'metode_bayar_id.required' => 'Metode bayar wajib dipilih.',
        ]);

        $obat = DB::transaction(function () use ($produk, $validated) {
            $obat = Obat::create($validated + ['kategori' => 'Produk', 'status_aktif' => 1, 'is_generik' => false]);
            $obat->zatAktifs()->sync($produk->zatAktifs()->pluck('erm_zataktif.id')->all());
            $produk->update(['obat_id' => $obat->id]);

            return $obat;
        });
        $this->writeProdukLog($produk->id, 'released', "Dirilis ke Master Obat sebagai {$obat->kode_obat} - {$obat->nama}.");

        return response()->json(['success' => true, 'message' => "Produk dirilis ke Master Obat sebagai {$obat->kode_obat}."]);
    }

    /** Remove the link only; the obat stays in Master Obat. */
    public function unrelease(RndProduk $produk)
    {
        $obat = $produk->obat;
        if (!$obat) {
            return response()->json(['message' => 'Produk ini belum terhubung ke Master Obat.'], 422);
        }

        $produk->update(['obat_id' => null]);
        $this->writeProdukLog($produk->id, 'unreleased', "Tautan ke Master Obat dilepas ({$obat->kode_obat} - {$obat->nama}); data obat tidak dihapus.");

        return response()->json(['success' => true, 'message' => 'Tautan ke Master Obat dilepas. Data obat tetap ada.']);
    }

    /** Select2 search for "hubungkan ke obat yang sudah ada": obat not yet linked to another product. */
    public function obatSearch(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $results = Obat::withInactive()
            ->whereNotIn('id', RndProduk::query()->whereNotNull('obat_id')->select('obat_id'))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('kode_obat', 'like', "%{$q}%")))
            ->orderBy('nama')
            ->limit(30)
            ->get(['id', 'kode_obat', 'nama', 'kategori', 'status_aktif']);

        return response()->json(['results' => $results->map(fn (Obat $obat) => [
            'id' => $obat->id,
            'text' => trim(($obat->kode_obat ? $obat->kode_obat . ' - ' : '') . $obat->nama)
                . ($obat->kategori ? " ({$obat->kategori})" : '')
                . ((int) $obat->status_aktif === 1 ? '' : ' [nonaktif]'),
        ])]);
    }

    /** Same name the product list shows: brand + nama + netto (+ satuan) + sediaan. */
    public static function releaseNama(RndProduk $produk): string
    {
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            optional($produk->brand)->nama_brand,
            $produk->nama_produk,
            $produk->netto,
            $produk->netto_satuan,
            // Older products use a unit (mL, g) as sediaan: do not repeat it after the netto satuan
            strcasecmp((string) $produk->sediaan, (string) $produk->netto_satuan) === 0 ? null : $produk->sediaan,
        ]))));
    }

    /** Satuan dosis for the new obat: the netto satuan, else the sediaan when it is a dosis unit (older products). */
    private function releaseSatuan(RndProduk $produk): ?string
    {
        foreach ([$produk->netto_satuan, $produk->sediaan] as $satuan) {
            if ($satuan && in_array($satuan, Satuan::dosisOptions(), true)) {
                return $satuan;
            }
        }

        return null;
    }

    /** Current (longest valid) sertifikasi of one jenis, from the loaded relation. */
    private function currentSertifikasi(RndProduk $produk, string $jenis): ?RndProdukSertifikasi
    {
        return $produk->sertifikasi->where('jenis', $jenis)->sortByDesc(fn ($s) => [$s->tanggal_selesai, $s->id])->first();
    }

    /** Select2 search for the PIC field. */
    public function userSearch(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $users = User::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name']);

        return response()->json(['results' => $users->map(fn (User $user) => ['id' => $user->id, 'text' => $user->name])]);
    }

    public function export(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RndProdukExport($request), 'produk_rnd_' . now()->format('Ymd') . '.xlsx');
    }

    /** "100", "100 ml" or "100ml" -> "100"; anything else is left for the user to fill. */
    private function nettoNumber(?string $netto): ?string
    {
        return preg_match('/^\s*(\d+(?:[.,]\d+)?)/', (string) $netto, $m) ? str_replace(',', '.', $m[1]) : null;
    }

    /** Satuan stok from the primary packaging name when it is a known stok unit (Botol, Tube, Pot, Jar, ...). */
    private function guessSatuanStok(RndProduk $produk): ?string
    {
        $kemasan = strtolower((string) optional($produk->kemasanPremier)->nama_kemasan);
        if ($kemasan === '') {
            return null;
        }
        foreach (Satuan::stokOptions() as $satuan) {
            if (preg_match('/\b' . preg_quote(strtolower($satuan), '/') . '\b/', $kemasan)) {
                return $satuan;
            }
        }

        return null;
    }

    public function storeProdukNote(Request $request, RndProduk $produk)
    {
        $validated = $request->validate([
            'notes' => ['required', 'string'],
        ]);

        $this->writeProdukLog($produk->id, 'user-note', trim($validated['notes']));

        $produk->load([
            'productLogs' => fn ($query) => $query->latest('log_date_time'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catatan produk berhasil ditambahkan.',
            'data' => [
                'product_logs' => $produk->productLogs->values()->all(),
            ],
        ]);
    }

    public function updateSampleLog(Request $request, RndSampleLog $sampleLog)
    {
        $validated = $request->validate($this->sampleRules(false));

        $sampleLog->update([
            'tanggal_terima' => $validated['tanggal_terima'] ?? null,
            'status_sample' => $validated['status_sample'] ?? $sampleLog->status_sample,
            'evaluasi' => $this->evaluasiPayload($validated),
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->writeProdukLog($sampleLog->produk_id, 'sample-updated', "Sample {$sampleLog->no_produksi} diperbarui melalui riwayat sample.");

        return response()->json([
            'success' => true,
            'message' => 'Sample berhasil diperbarui.',
            'data' => $sampleLog->fresh(),
        ]);
    }

    private function sampleRules(bool $withNoProduksi): array
    {
        $rules = [
            'tanggal_terima' => ['nullable', 'date'],
            'status_sample' => ['nullable', Rule::in(self::STATUS_SAMPLE_OPTIONS)],
            'evaluasi' => ['nullable', 'array'],
            'evaluasi.*' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
        ];
        if ($withNoProduksi) {
            $rules['no_produksi'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    /** Only known evaluation aspects, empty ones dropped; null when nothing was filled. */
    private function evaluasiPayload(array $validated): ?array
    {
        $evaluasi = collect($validated['evaluasi'] ?? [])
            ->only(array_keys(RndSampleLog::EVALUASI))
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->all();

        return $evaluasi ?: null;
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

    public function destroyProdukDocument(\App\Models\Rnd\RndProdukDocument $document)
    {
        abort_unless($this->hasAdditionalDocumentsTable(), 404);

        $produk = $document->produk()->firstOrFail();

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $documentName = $document->original_name ?: basename((string) $document->file_path);
        $document->delete();

        $this->writeProdukLog($produk->id, 'document-deleted', 'Dokumen tambahan dihapus: ' . $documentName . '.');

        $produk->load(['additionalDocuments' => fn ($query) => $query->latest()]);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen tambahan berhasil dihapus.',
            'data' => [
                'additional_documents' => $produk->additionalDocuments->map(function (\App\Models\Rnd\RndProdukDocument $remainingDocument) {
                    return [
                        'id' => $remainingDocument->id,
                        'original_name' => $remainingDocument->original_name,
                        'url' => route('rnd.products.documents.show', $remainingDocument),
                        'size_bytes' => $remainingDocument->size_bytes,
                        'created_at' => optional($remainingDocument->created_at)?->toIso8601String(),
                    ];
                })->values()->all(),
            ],
        ]);
    }

    public function viewProdukDocument(\App\Models\Rnd\RndProdukDocument $document)
    {
        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        $path = Storage::disk('public')->path($document->file_path);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . ($document->original_name ?: basename($document->file_path)) . '"',
        ]);
    }

    /** exists rule for a kemasan of one tipe, so a sekunder kemasan cannot be saved as primer (and the other way). */
    private function kemasanRule(string $tipe)
    {
        return Rule::exists('rnd_master_kemasan', 'id')->where('tipe_kemasan', $tipe);
    }

    private function validatedPayload(Request $request, ?RndProduk $produk = null): array
    {
        $isUpdate = $produk !== null;
        $requiredRule = $isUpdate ? 'nullable' : 'required';

        $validated = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'status_produk' => ['nullable', Rule::in(RndProduk::STATUS_PRODUK)],
            'pic_user_id' => 'nullable|exists:users,id',
            'target_rilis' => 'nullable|date',
            'netto_satuan' => ['nullable', Rule::in(Satuan::dosisOptions())],
            'masa_simpan_bulan' => 'nullable|integer|min:1|max:240',
            'target_hpp' => 'nullable|numeric|min:0',
            'target_harga_jual' => 'nullable|numeric|min:0',
            'estimasi_biaya' => 'nullable|numeric|min:0',
            'brand_id' => $requiredRule . '|exists:rnd_master_brand,id',
            'produsen_vendor_id' => 'nullable|exists:rnd_master_vendor,id',
            'zat_aktif_ids' => 'nullable|array',
            'zat_aktif_ids.*' => 'exists:erm_zataktif,id',
            'additional_documents' => 'nullable|array',
            'additional_documents.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
            'kemasan_premier_id' => [$requiredRule, $this->kemasanRule('primer')],
            'kemasan_sekunder_id' => ['nullable', $this->kemasanRule('sekunder')],
            'kemasan_primer_vendor_id' => 'nullable|exists:rnd_master_vendor,id',
            'kemasan_sekunder_vendor_id' => 'nullable|exists:rnd_master_vendor,id',
            'desain_kemasan_primer_id' => 'nullable|exists:rnd_master_vendor,id',
            'desain_kemasan_sekunder_id' => 'nullable|exists:rnd_master_vendor,id',
            'sediaan' => [$requiredRule, 'string', Rule::in(Satuan::allOptions())],
            'netto' => 'nullable|string|max:255',
            'status_administrasi_fpp' => ['nullable', Rule::in(self::STATUS_ADMINISTRASI_FPP_OPTIONS)],
            'status_administrasi_spk' => ['nullable', Rule::in(self::STATUS_ADMINISTRASI_SPK_OPTIONS)],
            'status_administrasi_notif' => ['nullable', Rule::in(self::STATUS_ADMINISTRASI_NOTIF_OPTIONS)],
            'status_kemasan_primer' => ['nullable', Rule::in(self::STATUS_KEMASAN_OPTIONS)],
            'status_kemasan_sekunder' => ['nullable', Rule::in(self::STATUS_KEMASAN_OPTIONS)],
            'status_desain_kemasan_primer' => ['nullable', Rule::in(self::STATUS_DESAIN_OPTIONS)],
            'status_desain_kemasan_sekunder' => ['nullable', Rule::in(self::STATUS_DESAIN_OPTIONS)],
        ], [
            'kemasan_premier_id.exists' => 'Kemasan primer harus kemasan bertipe primer.',
            'kemasan_sekunder_id.exists' => 'Kemasan sekunder harus kemasan bertipe sekunder.',
        ]);

        // Notifikasi BPOM "done" needs the nomor, dates and PDF (a notif_bpom sertifikasi); the form has none of them,
        // so it may only keep an existing "done", never set it
        if (($validated['status_administrasi_notif'] ?? null) === 'done' && optional($produk)->status_administrasi_notif !== 'done') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status_administrasi_notif' => 'Status NOTIF "done" diisi lewat badge Notif BPOM di tabel atau tab Legalitas, karena perlu nomor, tanggal, dan dokumen notifikasi.',
            ]);
        }

        $validated['status_produk'] = $validated['status_produk'] ?? RndProduk::STATUS_AKTIF;

        $attributes = collect($validated)
            ->except(['zat_aktif_ids', 'additional_documents']);

        if ($isUpdate) {
            $attributes = $attributes->reject(function ($value, $key) {
                return in_array($key, ['brand_id', 'kemasan_premier_id', 'sediaan'], true) && $value === null;
            });
        }

        return [
            'attributes' => $attributes->all(),
            'zat_aktif_ids' => $validated['zat_aktif_ids'] ?? [],
        ];
    }

    private function storeAdditionalDocuments(Request $request, RndProduk $produk): int
    {
        if (!$this->hasAdditionalDocumentsTable()) {
            return 0;
        }

        $files = $request->file('additional_documents', []);

        if (!is_array($files) || $files === []) {
            return 0;
        }

        $storedCount = 0;

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $path = $file->store('rnd/produk-documents', 'public');

            $produk->additionalDocuments()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]);

            $storedCount++;
        }

        return $storedCount;
    }

    private function hasAdditionalDocumentsTable(): bool
    {
        static $hasTable;

        if ($hasTable === null) {
            $hasTable = Schema::hasTable('rnd_produk_document');
        }

        return $hasTable;
    }

    private function hasTimelineTable(): bool
    {
        static $hasTable;

        if ($hasTable === null) {
            $hasTable = Schema::hasTable('rnd_produk_timeline');
        }

        return $hasTable;
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
            // The notification itself is stored as a Notifikasi BPOM sertifikasi (also shown on the Legalitas tab)
            $notifValidated = $request->validate([
                'nomor' => ['required', 'string', 'max:255'],
                'tanggal_mulai' => ['required', 'date'],
                'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
                'notif_doc' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            ], [
                'nomor.required' => 'Nomor notifikasi BPOM wajib diisi.',
            ]);

            $file = $request->file('notif_doc');

            DB::transaction(function () use ($produk, $field, $file, $notifValidated) {
                $produk->sertifikasi()->create([
                    'jenis' => 'notif_bpom',
                    'nomor' => trim($notifValidated['nomor']),
                    'penerbit' => 'BPOM',
                    'tanggal_mulai' => $notifValidated['tanggal_mulai'],
                    'tanggal_selesai' => $notifValidated['tanggal_selesai'],
                    'doc_path' => $file->store('rnd/sertifikasi', 'public'),
                    'original_name' => $file->getClientOriginalName(),
                ]);
                $produk->update([$field => 'done']);
            });

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
            'kemasan_premier_id' => ['required', 'integer', $this->kemasanRule('primer')],
            'kemasan_sekunder_id' => ['nullable', 'integer', $this->kemasanRule('sekunder')],
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
            'kemasan_premier_id' => ['status_kemasan_primer', 'status_desain_kemasan_primer'],
            'kemasan_sekunder_id' => ['status_kemasan_sekunder', 'status_desain_kemasan_sekunder'],
        ];

        [$statusField, $desainStatusField] = $statusFieldMap[$field];
        $kemasanValue = $validated[$field] ?? null;
        $vendorFieldMap = [
            'kemasan_premier_id' => ['kemasan_primer_vendor_id', 'desain_kemasan_primer_id'],
            'kemasan_sekunder_id' => ['kemasan_sekunder_vendor_id', 'desain_kemasan_sekunder_id'],
        ];
        $relatedFields = $vendorFieldMap[$field];

        $attributes = [
            $field => $kemasanValue,
            $relatedFields[0] => $kemasanValue ? ($validated[$relatedFields[0]] ?? null) : null,
            $relatedFields[1] => $kemasanValue ? ($validated[$relatedFields[1]] ?? null) : null,
        ];

        if (!$kemasanValue) {
            // No sekunder kemasan: its statuses no longer mean anything
            $attributes[$statusField] = null;
            $attributes[$desainStatusField] = null;
        } elseif ((int) $produk->{$field} !== (int) $kemasanValue) {
            // Only another kemasan restarts the work; changing just the vendors keeps a "done" status
            $attributes[$statusField] = self::STATUS_KEMASAN_OPTIONS[0];
        }

        $produk->update($attributes);

        $this->writeProdukLog($produk->id, 'updated', 'Kemasan produk diperbarui melalui tabel Produk RND.');

        return response()->json([
            'success' => true,
            'message' => 'Kemasan berhasil diperbarui.',
            'data' => $produk->fresh()->load(['kemasanPremier', 'kemasanSekunder']),
        ]);
    }

    private function storeSampleLog(Request $request, RndProduk $produk)
    {
        $validated = $request->validate($this->sampleRules(true));

        $sampleLog = RndSampleLog::create([
            'produk_id' => $produk->id,
            'no_produksi' => $validated['no_produksi'],
            'tanggal_terima' => $validated['tanggal_terima'] ?? null,
            'status_sample' => $validated['status_sample'] ?? self::STATUS_SAMPLE_OPTIONS[0],
            'evaluasi' => $this->evaluasiPayload($validated),
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->writeProdukLog($produk->id, 'sample-added', "Sample {$sampleLog->no_produksi} ditambahkan.");

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
