<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\ContentPlan;
use App\Models\Marketing\DesignRequest;
use App\Models\Marketing\DesignRequestFile;
use App\Models\Rnd\RndProduk;
use App\Support\Marketing\MarketingAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * "Request Desain": work queue of the graphic designer.
 * Anyone can request (and follow their own requests); Marketing takes, works on and approves.
 */
class DesignRequestController extends Controller
{
    /** Result files the designer may upload (large files: share a link instead). */
    private const RESULT_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'pdf', 'ai', 'psd', 'eps', 'cdr', 'zip', 'rar', 'mp4', 'mov'];
    private const MAX_FILE_KB = 20480;

    public function index(Request $request)
    {
        $user = Auth::user();

        // Prefill when opened from a Content Plan or an R&D product ("Ajukan desain kemasan")
        $prefill = null;
        if ($request->filled('rnd_produk_id')) {
            $produk = RndProduk::find($request->rnd_produk_id);
            $sisi = $request->input('sisi') === 'sekunder' ? 'sekunder' : 'primer';
            if ($produk) {
                $prefill = [
                    'jenis' => 'Kemasan',
                    'judul' => 'Desain kemasan ' . $sisi . ' - ' . $produk->nama_produk,
                    'rnd_produk_id' => $produk->id,
                    'rnd_kemasan_sisi' => $sisi,
                    'source_label' => 'Produk R&D: ' . $produk->nama_produk . ' (kemasan ' . $sisi . ')',
                ];
            }
        } elseif ($request->filled('content_plan_id')) {
            $plan = ContentPlan::find($request->content_plan_id);
            if ($plan) {
                $prefill = [
                    'jenis' => 'Konten Sosmed',
                    'judul' => 'Desain konten - ' . $plan->judul,
                    'content_plan_id' => $plan->id,
                    'deadline' => optional($plan->tanggal_produksi)->toDateString() ?: optional($plan->tanggal_publish)->toDateString(),
                    'source_label' => 'Content Plan: ' . $plan->judul,
                ];
            }
        }

        return view('design_request.index', [
            'isMarketing' => MarketingAccess::isMarketing($user),
            'designers' => MarketingAccess::users(),
            // "Catat approval / revisi": same picker as the Content Plan, a Marketing user defaults to themselves
            'reviewers' => ContentPlanController::reviewers(),
            'selfApprover' => $user->hasRole('Marketing') ? $user->name : null,
            'jenisOptions' => DesignRequest::JENIS,
            'prefill' => $prefill,
            'openId' => $request->integer('open') ?: null,
            'resultExtensions' => self::RESULT_EXTENSIONS,
            'maxFileMb' => (int) (self::MAX_FILE_KB / 1024),
        ]);
    }

    /** Workload of the designers (Marketing only). */
    public function capacity()
    {
        abort_unless(MarketingAccess::isMarketing(Auth::user()), 403);

        return response()->json(\App\Support\Marketing\DesignerCapacity::build());
    }

    /** Counts for the tab chips. */
    public function summary()
    {
        $user = Auth::user();
        $base = DesignRequest::query()->visibleTo($user);

        $out = [
            'saya' => (clone $base)->where('requester_id', $user->id)->whereIn('status', DesignRequest::ACTIVE_STATUSES)->count(),
        ];
        if (MarketingAccess::isMarketing($user)) {
            $out['aktif'] = (clone $base)->whereIn('status', DesignRequest::ACTIVE_STATUSES)->count();
            $out['tugas'] = (clone $base)->where('designer_id', $user->id)->whereIn('status', DesignRequest::ACTIVE_STATUSES)->count();
            $out['baru'] = (clone $base)->where('status', DesignRequest::STATUS_BARU)->count();
            $out['review'] = (clone $base)->where('status', DesignRequest::STATUS_REVIEW)->count();
        }

        return response()->json($out);
    }

    /**
     * Cards for one tab. scope: aktif (all active, Marketing) | tugas (assigned to me) |
     * saya (requested by me) | selesai (finished / cancelled, paged).
     */
    public function list(Request $request)
    {
        $user = Auth::user();
        $isMarketing = MarketingAccess::isMarketing($user);
        $scope = $request->input('scope', $isMarketing ? 'aktif' : 'saya');

        $query = DesignRequest::query()->visibleTo($user)
            ->with(['requester:id,name', 'designer:id,name', 'contentPlan:id,judul', 'rndProduk:id,nama_produk'])
            ->withCount([
                'files as hasil_count' => fn ($q) => $q->where('kind', DesignRequestFile::KIND_HASIL),
                'statusLogs as revisi_count' => fn ($q) => $q->where('action', 'revise'),
            ]);

        switch ($scope) {
            case 'tugas':
                $query->where('designer_id', $user->id)->whereIn('status', DesignRequest::ACTIVE_STATUSES);
                break;
            case 'saya':
                $query->where('requester_id', $user->id);
                if (!$request->boolean('include_done')) {
                    $query->whereIn('status', DesignRequest::ACTIVE_STATUSES);
                }
                break;
            case 'selesai':
                $query->whereIn('status', [DesignRequest::STATUS_SELESAI, DesignRequest::STATUS_DIBATALKAN]);
                break;
            default:
                $query->whereIn('status', DesignRequest::ACTIVE_STATUSES);
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('judul', 'like', '%' . $q . '%');
                if (ctype_digit(ltrim($q, '#'))) {
                    $w->orWhere('id', (int) ltrim($q, '#'));
                }
            });
        }

        if ($scope === 'selesai') {
            $query->orderByDesc('updated_at');
            $perPage = 20;
            $page = max(1, (int) $request->input('page', 1));
            $total = (clone $query)->count();
            $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();
            $hasMore = $page * $perPage < $total;
        } else {
            // nearest deadline first, no deadline last
            $items = $query->orderByRaw('deadline IS NULL')->orderBy('deadline')->orderBy('id')->get();
            $hasMore = false;
        }

        // content jobs can only be taken once the creator wrote the brief: one query for the whole list
        $planIds = $items->filter(fn (DesignRequest $dr) => $dr->isContentJob() && $dr->status === DesignRequest::STATUS_BARU)
            ->pluck('content_plan_id')->unique()->values();
        $briefReady = $planIds->isEmpty() ? collect() : \App\Models\Marketing\ContentBrief::whereIn('content_plan_id', $planIds)
            ->where(fn ($q) => ContentPlanController::filledBrief($q))->pluck('content_plan_id')->unique()->flip();
        $noPic = $planIds->isEmpty() ? collect() : ContentPlan::whereIn('id', $planIds)->whereNull('assigned_to')->pluck('id')->flip();

        return response()->json([
            'data' => $items->map(fn (DesignRequest $dr) => $this->card($dr) + [
                'actions' => $dr->availableActions($user),
                'is_content_job' => $dr->isContentJob(),
                'waiting_brief' => $dr->isContentJob() && $dr->status === DesignRequest::STATUS_BARU && !$briefReady->has($dr->content_plan_id),
                'waiting_pic' => $dr->isContentJob() && $dr->status === DesignRequest::STATUS_BARU && $noPic->has($dr->content_plan_id),
                'revisi_count' => (int) ($dr->revisi_count ?? 0),
            ])->values(),
            'has_more' => $hasMore,
            // tab counters + designer workload ride along: one request per refresh instead of three
            'summary' => $request->boolean('with_extra') ? $this->summary()->getData(true) : null,
            'capacity' => $request->boolean('with_extra') && $isMarketing ? \App\Support\Marketing\DesignerCapacity::build() : null,
        ]);
    }

    protected function sourceLabel(DesignRequest $dr): ?string
    {
        if ($dr->content_plan_id && $dr->contentPlan) {
            return 'Content Plan: ' . $dr->contentPlan->judul;
        }
        if ($dr->rnd_produk_id && $dr->rndProduk) {
            return 'Produk R&D: ' . $dr->rndProduk->nama_produk . ($dr->rnd_kemasan_sisi ? ' (kemasan ' . $dr->rnd_kemasan_sisi . ')' : '');
        }

        return null;
    }

    protected function card(DesignRequest $dr): array
    {
        return [
            'id' => $dr->id,
            'judul' => $dr->judul,
            'jenis' => $dr->jenis,
            'status' => $dr->status,
            'deadline' => optional($dr->deadline)->toDateString(),
            'requester' => optional($dr->requester)->name,
            'designer' => optional($dr->designer)->name,
            'designer_id' => $dr->designer_id,
            'content_plan_id' => $dr->content_plan_id,
            'rnd_produk_id' => $dr->rnd_produk_id,
            'source' => $this->sourceLabel($dr),
            'hasil_count' => (int) ($dr->hasil_count ?? 0),
            // H-1: still waiting while the deadline is tomorrow / today / past
            'urgency' => \App\Support\Marketing\DesignReminders::urgency($dr->deadline, $dr->status),
            'updated_at' => optional($dr->updated_at)->toIso8601String(),
            'completed_at' => optional($dr->completed_at)->toIso8601String(),
        ];
    }

    public function show($id)
    {
        $user = Auth::user();
        $dr = DesignRequest::with(['requester:id,name', 'designer:id,name', 'contentPlan:id,judul', 'rndProduk:id,nama_produk', 'files.user:id,name'])
            ->findOrFail($id);
        abort_unless($dr->canView($user), 403);

        $files = $dr->files->map(fn (DesignRequestFile $f) => $f->toCard());

        return response()->json($this->card($dr) + [
            'brief' => $dr->brief,
            'content_brief' => $this->contentBrief($dr),
            'spesifikasi' => $dr->spesifikasi ?: new \stdClass(),
            'rnd_kemasan_sisi' => $dr->rnd_kemasan_sisi,
            'is_content_job' => $dr->isContentJob(),
            'created_at' => optional($dr->created_at)->toIso8601String(),
            'referensi' => $files->where('kind', DesignRequestFile::KIND_REFERENSI)->values(),
            'hasil' => $files->where('kind', DesignRequestFile::KIND_HASIL)->sortByDesc('version')->values(),
            'timeline' => collect($dr->timeline())->map(function ($row) {
                $row['action_label'] = DesignRequest::ACTION_LABELS[$row['action']] ?? $row['action'];
                return $row;
            })->all(),
            'actions' => $dr->availableActions($user),
            'can' => [
                'edit' => $dr->canEdit($user),
                'upload' => $dr->canUpload($user),
                'is_marketing' => MarketingAccess::isMarketing($user),
            ],
        ]);
    }

    /**
     * One brief: a request made from a Content Plan shows that content's (latest) brief live,
     * so the designer reads exactly what the creator wrote. The request's own `brief` is then
     * only the extra design note.
     */
    protected function contentBrief(DesignRequest $dr): ?array
    {
        // full row: the eager-loaded relation only has id + judul
        $plan = $dr->content_plan_id ? ContentPlan::find($dr->content_plan_id) : null;
        if (!$plan) {
            return null;
        }
        $brief = $plan->briefs()->orderByDesc('created_at')->first();

        // isi_konten comes from a rich-text editor: send it as plain text with line breaks
        $isi = $brief ? trim(html_entity_decode(strip_tags(preg_replace('#<(br|/p|/li|/div)\s*/?>#i', "\n", (string) $brief->isi_konten)), ENT_QUOTES | ENT_HTML5, 'UTF-8')) : '';

        return [
            'headline' => optional($brief)->headline,
            'sub_headline' => optional($brief)->sub_headline,
            'isi' => preg_replace("/\n{3,}/", "\n\n", $isi),
            'images' => collect(optional($brief)->visual_references ?: [])->map(fn ($p) => \App\Support\Marketing\PublicFile::url($p))->filter()->values(),
            'caption' => $plan->caption,
            'platform' => array_values((array) ($plan->platform ?? [])),
            'jenis_konten' => array_values((array) ($plan->jenis_konten ?? [])),
            'tanggal_publish' => optional($plan->tanggal_publish)->toIso8601String(),
        ];
    }

    protected function rules(): array
    {
        return [
            'jenis' => ['required', Rule::in(DesignRequest::JENIS)],
            'judul' => 'required|string|max:255',
            'brief' => 'nullable|string|max:5000',
            'deadline' => 'nullable|date',
            'spesifikasi' => 'nullable|array',
            'spesifikasi.ukuran' => 'nullable|string|max:255',
            'spesifikasi.bahan' => 'nullable|string|max:255',
            'spesifikasi.jumlah' => 'nullable|string|max:100',
            'spesifikasi.catatan' => 'nullable|string|max:1000',
            'referensi' => 'nullable|array|max:10',
            'referensi.*' => 'file|mimes:jpg,jpeg,png,webp,gif,pdf|max:10240',
        ];
    }

    protected function cleanSpesifikasi(?array $spec): ?array
    {
        $spec = array_filter((array) $spec, fn ($v) => $v !== null && trim((string) $v) !== '');

        return $spec ?: null;
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate($this->rules() + [
            'content_plan_id' => 'nullable|integer|exists:marketing_content_plans,id',
            'rnd_produk_id' => 'nullable|integer|exists:rnd_produk,id',
            'rnd_kemasan_sisi' => 'nullable|required_with:rnd_produk_id|in:primer,sekunder',
            // Marketing may hand the job to a designer right away (it still starts with "Mulai kerjakan")
            'designer_id' => 'nullable|integer|exists:users,id',
        ]);
        $designerId = MarketingAccess::isMarketing($user) ? ($data['designer_id'] ?? null) : null;

        // one designer job per content: a second one would split the flow (the content follows only one)
        if (!empty($data['content_plan_id'])) {
            $existing = DesignRequest::where('content_plan_id', $data['content_plan_id'])
                ->whereIn('status', DesignRequest::ACTIVE_STATUSES)->first();
            if ($existing) {
                return response()->json(['message' => 'Konten ini sudah punya request desain aktif (#' . $existing->id . ').', 'existing_id' => $existing->id], 422);
            }
            // design of this content is already done: changes go through "Revisi masuk" on the content
            $done = DesignRequest::where('content_plan_id', $data['content_plan_id'])->where('status', DesignRequest::STATUS_SELESAI)->latest('id')->first();
            if ($done) {
                return response()->json(['message' => 'Desain konten ini sudah selesai (#' . $done->id . '). Untuk perubahan, catat "Revisi masuk" di Content Plan; untuk desain lain, buat request tanpa menautkan konten.', 'existing_id' => $done->id], 422);
            }
        }
        if (!empty($data['rnd_produk_id'])) {
            $existing = DesignRequest::where('rnd_produk_id', $data['rnd_produk_id'])
                ->where('rnd_kemasan_sisi', $data['rnd_kemasan_sisi'])
                ->whereIn('status', DesignRequest::ACTIVE_STATUSES)
                ->first();
            if ($existing) {
                return response()->json(['message' => 'Kemasan ini sudah punya request desain aktif (#' . $existing->id . ').', 'existing_id' => $existing->id], 422);
            }
        }

        $dr = DB::transaction(function () use ($data, $request, $user, $designerId) {
            $dr = DesignRequest::create([
                'jenis' => $data['jenis'],
                'judul' => $data['judul'],
                'brief' => $data['brief'] ?? null,
                'deadline' => $data['deadline'] ?? null,
                'spesifikasi' => $this->cleanSpesifikasi($data['spesifikasi'] ?? null),
                'status' => DesignRequest::STATUS_BARU,
                'requester_id' => $user->id,
                'designer_id' => $designerId,
                'content_plan_id' => $data['content_plan_id'] ?? null,
                'rnd_produk_id' => $data['rnd_produk_id'] ?? null,
                'rnd_kemasan_sisi' => !empty($data['rnd_produk_id']) ? $data['rnd_kemasan_sisi'] : null,
            ]);
            $this->storeReferences($dr, $request);
            $dr->logStatus('created', null, DesignRequest::STATUS_BARU);
            if ($designerId) {
                $dr->logStatus('assign', null, null, 'Desainer: ' . optional($dr->designer)->name);
            }

            return $dr;
        });
        $dr->syncRndStatus();

        return response()->json(['success' => true, 'id' => $dr->id]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $dr = DesignRequest::findOrFail($id);
        abort_unless($dr->canEdit($user), 403, 'Request ini tidak bisa diubah lagi.');

        $data = $request->validate($this->rules() + [
            'remove_files' => 'nullable|array',
            'remove_files.*' => 'integer',
        ]);

        DB::transaction(function () use ($dr, $data, $request) {
            $dr->update([
                // a job linked to a content / R&D product keeps its jenis (the link and its flow depend on it)
                'jenis' => $dr->content_plan_id || $dr->rnd_produk_id ? $dr->jenis : $data['jenis'],
                'judul' => $data['judul'],
                'brief' => $data['brief'] ?? null,
                'deadline' => $data['deadline'] ?? null,
                'spesifikasi' => $this->cleanSpesifikasi($data['spesifikasi'] ?? null),
            ]);
            foreach ($dr->files()->where('kind', DesignRequestFile::KIND_REFERENSI)->whereIn('id', $data['remove_files'] ?? [])->get() as $file) {
                $this->deleteStoredFile($file);
            }
            $this->storeReferences($dr, $request);
            $dr->logStatus('updated');
        });

        return response()->json(['success' => true]);
    }

    protected function storeReferences(DesignRequest $dr, Request $request): void
    {
        foreach ((array) $request->file('referensi', []) as $file) {
            $dr->files()->create([
                'kind' => DesignRequestFile::KIND_REFERENSI,
                'path' => $file->store('marketing/design_requests/' . $dr->id, 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'user_id' => Auth::id(),
            ]);
        }
    }

    /** One workflow step; allowed steps come from DesignRequest::availableActions(). */
    public function action(Request $request, $id)
    {
        $user = Auth::user();
        $dr = DesignRequest::findOrFail($id);
        abort_unless($dr->canView($user), 403);

        $payload = $request->validate([
            'action' => 'required|string',
            'notes' => 'nullable|string|max:2000',
            'designer_id' => 'nullable|integer|exists:users,id',
            // who approved / asked for the revision (usually said in the WA group)
            'actor' => 'nullable|string|max:100',
        ]);

        $allowed = collect($dr->availableActions($user))->keyBy('action');
        $action = $payload['action'];
        if (!$allowed->has($action)) {
            return response()->json(['message' => 'Aksi ini tidak tersedia untuk status ' . $dr->status . '.'], 422);
        }
        $notes = trim((string) ($payload['notes'] ?? ''));
        if ($allowed[$action]['note'] === 'required' && $notes === '') {
            return response()->json(['message' => 'Catatan wajib diisi.', 'errors' => ['notes' => ['Catatan wajib diisi.']]], 422);
        }
        $actor = trim((string) ($payload['actor'] ?? ''));
        if ($actor !== '' && in_array($action, ['approve', 'revise'], true)) {
            $notes = ($action === 'approve' ? 'Oleh: ' : 'Dari: ') . $actor . ($notes !== '' ? "\n" . $notes : '');
        }

        $from = $dr->status;
        $to = $from;
        $update = [];

        $contentJob = $dr->isContentJob();

        switch ($action) {
            case 'take':
                // a Content Plan job can only start once the creator wrote the brief (keeps the order of the flow)
                if ($contentJob && ($plan = \App\Models\Marketing\ContentPlan::find($dr->content_plan_id))) {
                    if (!$plan->briefs()->where(fn ($q) => ContentPlanController::filledBrief($q))->exists()) {
                        return response()->json(['message' => 'Brief konten belum diisi oleh content creator. Job bisa diambil setelah brief ada.'], 422);
                    }
                    // same rule as "Mulai produksi" on the content: someone answers for it
                    if (!$plan->assigned_to) {
                        return response()->json(['message' => 'PIC konten belum ditentukan di Content Plan. Job bisa diambil setelah ada PIC.'], 422);
                    }
                }
                $update['designer_id'] = $user->id;
                $to = DesignRequest::STATUS_DIKERJAKAN;
                break;
            case 'assign':
                if (empty($payload['designer_id'])) {
                    return response()->json(['message' => 'Pilih desainer.'], 422);
                }
                $update['designer_id'] = $payload['designer_id'];
                // the job stays Baru until the designer starts it ("Mulai kerjakan"), like a Content Plan job
                $designerName = optional(\App\Models\User::find($payload['designer_id']))->name;
                $notes = trim('Desainer: ' . $designerName . ($notes !== '' ? ' — ' . $notes : ''));
                break;
            case 'submit_review':
                // results are shared in the WA group; uploading final files here is optional
                $to = DesignRequest::STATUS_REVIEW;
                break;
            case 'approve':
                $to = DesignRequest::STATUS_SELESAI;
                $update['completed_at'] = now();
                break;
            case 'revise':
                $to = DesignRequest::STATUS_REVISI;
                $update['completed_at'] = null;
                break;
            case 'cancel':
                $to = DesignRequest::STATUS_DIBATALKAN;
                break;
            case 'reopen':
                $to = $dr->designer_id ? DesignRequest::STATUS_DIKERJAKAN : DesignRequest::STATUS_BARU;
                break;
            case 'comment':
                break;
        }

        $update['status'] = $to;
        // only when nobody moved the job meanwhile (e.g. two designers pressing "Ambil" at the same time)
        $changed = DB::transaction(function () use ($dr, $from, $update) {
            $current = DesignRequest::whereKey($dr->id)->lockForUpdate()->first();
            if (!$current || $current->status !== $from || (int) $current->designer_id !== (int) $dr->designer_id) {
                return false;
            }
            $current->update($update);

            return true;
        });
        if (!$changed) {
            return response()->json(['message' => 'Request ini baru saja diubah orang lain. Muat ulang untuk melihat status terbaru.'], 409);
        }
        $dr->refresh();
        $dr->logStatus($action, $from !== $to ? $from : null, $from !== $to ? $to : null, $notes);
        if ($from !== $to) {
            $dr->syncRndStatus();
            // taking / finishing a Content Plan job moves the content along (Produksi / Review)
            \App\Support\Marketing\DesignSync::fromDesign($dr->fresh('designer'), $action);
        }

        return response()->json(['success' => true, 'status' => $to]);
    }

    /** Add a result version: an uploaded file or a link (e.g. Google Drive for large files). */
    public function upload(Request $request, $id)
    {
        $user = Auth::user();
        $dr = DesignRequest::findOrFail($id);
        abort_unless($dr->canUpload($user), 403, 'Hasil hanya bisa diunggah saat request sedang dikerjakan.');

        $data = $request->validate([
            'file' => 'nullable|file|max:' . self::MAX_FILE_KB . '|required_without:url',
            'url' => 'nullable|url|max:1000|required_without:file',
            'notes' => 'nullable|string|max:1000',
        ], [
            'file.max' => 'File maksimal ' . (int) (self::MAX_FILE_KB / 1024) . ' MB. Untuk file besar, tempel link Google Drive.',
            'file.uploaded' => 'File gagal diunggah (kemungkinan terlalu besar). Untuk file besar, tempel link Google Drive.',
            'file.required_without' => 'Pilih file atau isi link hasil desain.',
            'url.required_without' => 'Pilih file atau isi link hasil desain.',
        ]);

        $attrs = [
            'kind' => DesignRequestFile::KIND_HASIL,
            'version' => (int) $dr->files()->where('kind', DesignRequestFile::KIND_HASIL)->max('version') + 1,
            'notes' => $data['notes'] ?? null,
            'user_id' => $user->id,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, self::RESULT_EXTENSIONS, true)) {
                return response()->json(['message' => 'Tipe file .' . $ext . ' tidak didukung. Gunakan: ' . implode(', ', self::RESULT_EXTENSIONS) . ', atau tempel link.'], 422);
            }
            $attrs += [
                'path' => $file->store('marketing/design_requests/' . $dr->id, 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        } else {
            $attrs['url'] = $data['url'];
        }

        $saved = $dr->files()->create($attrs);
        $dr->logStatus('upload', null, null, 'v' . $saved->version . ': ' . ($saved->original_name ?: $saved->url) . (!empty($data['notes']) ? ' — ' . $data['notes'] : ''));
        $dr->touch();

        return response()->json(['success' => true]);
    }

    public function destroyFile($fileId)
    {
        $user = Auth::user();
        $file = DesignRequestFile::with('designRequest')->findOrFail($fileId);
        $dr = $file->designRequest;
        abort_unless($dr && $dr->isActive(), 403, 'File pada request yang sudah selesai tidak bisa dihapus.');

        $allowed = $file->kind === DesignRequestFile::KIND_HASIL
            ? $dr->canUpload($user)
            : $dr->canEdit($user);
        abort_unless($allowed, 403);

        $this->deleteStoredFile($file);

        return response()->json(['success' => true]);
    }

    protected function deleteStoredFile(DesignRequestFile $file): void
    {
        if ($file->path) {
            try {
                Storage::disk('public')->delete($file->path);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        $file->delete();
    }
}
