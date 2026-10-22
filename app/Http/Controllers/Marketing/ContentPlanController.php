<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\ContentBrief;
use App\Models\Marketing\ContentList;
use App\Models\Marketing\ContentPlan;
use App\Models\Marketing\DesignRequest;
use App\Support\Marketing\MarketingAccess;
use App\Support\Marketing\DesignSync;
use App\Support\Marketing\PublicFile;
use App\Support\Marketing\RoutineSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Content Plan = the social media publishing calendar.
 *
 * Flow: Terjadwal → Produksi → Review → Siap Publish → Published (+ Revisi loop, Batal).
 * Status values stay as stored before ("Scheduled", "Done", ...); labels are what users see.
 */
class ContentPlanController extends Controller
{
    /** Stored status => label shown to users. "Draft" only exists on old rows. */
    public const STATUS_LABELS = [
        'Draft' => 'Draft',
        'Scheduled' => 'Terjadwal',
        'Produksi' => 'Produksi',
        'Review' => 'Review',
        'Revisi' => 'Revisi',
        'Done' => 'Siap Publish',
        'Published' => 'Published',
        'Cancelled' => 'Batal',
    ];

    /**
     * Statuses that count as "terlewat" once the schedule date has passed.
     * Publish mode: anything not yet published. Production mode: anything not yet produced
     * (Review / Siap Publish mean production is finished).
     */
    public const OVERDUE_STATUSES = [
        'tanggal_publish' => ['Draft', 'Scheduled', 'Produksi', 'Review', 'Revisi', 'Done'],
        'tanggal_produksi' => ['Draft', 'Scheduled', 'Produksi', 'Revisi'],
    ];

    /**
     * Workflow buttons: action => [from statuses, to status, label, button style, note (null|optional|required)].
     * Every transition is written to the status log (who, when, note).
     */
    public const TRANSITIONS = [
        'start' => [['Draft', 'Scheduled'], 'Produksi', 'Mulai Produksi', 'primary', null],
        'submit_review' => [['Produksi', 'Revisi'], 'Review', 'Ajukan Review', 'primary', 'optional'],
        'approve' => [['Review'], 'Done', 'Approved', 'success', 'optional'],
        'revise' => [['Review', 'Done'], 'Revisi', 'Revisi masuk', 'warning', 'required'],
        'publish' => [['Done'], 'Published', 'Tandai Published', 'success', 'optional'],
        'cancel' => [['Draft', 'Scheduled', 'Produksi', 'Review', 'Revisi', 'Done'], 'Cancelled', 'Batalkan', 'outline-danger', 'required'],
        'reopen' => [['Cancelled'], 'Scheduled', 'Aktifkan Lagi', 'outline-primary', 'optional'],
    ];

    public const ACTION_LABELS = [
        'created' => 'Membuat jadwal',
        'reschedule' => 'Mengubah jadwal',
        'start' => 'Mulai produksi',
        'submit_review' => 'Mengajukan review',
        'approve' => 'Approved',
        'revise' => 'Revisi masuk',
        'publish' => 'Menandai published',
        'cancel' => 'Membatalkan',
        'reopen' => 'Mengaktifkan lagi',
        'design' => 'Menugaskan designer',
    ];

    /** Brand = a clinic (by keyword in erm_klinik.nama) + BCL; values stay the strings stored before. */
    private const CLINIC_BRANDS = [
        'premiere' => ['value' => 'Premiere Belova', 'color' => '#2563eb'],
        'skin' => ['value' => 'Belova Skin', 'color' => '#db2777'],
        'dental' => ['value' => 'Belova Dental Care', 'color' => '#16a34a'],
    ];
    public const PLATFORMS = ['Instagram', 'Facebook', 'TikTok', 'YouTube', 'Website', 'Other'];
    public const JENIS_KONTEN = ['Feed', 'Story', 'Reels', 'Artikel', 'Other'];
    public const KONTEN_PILAR = ['Edukasi', 'Awareness', 'Engagement/Interaktif', 'Promo/Testimoni', 'Lifestyle/Tips'];

    /**
     * Brand choices: every clinic (with its logo and the clinic color used by the sidebar/dashboard)
     * plus BCL (kos). [{value, label, title, color, logo, logo_bg}]
     */
    public static function brandOptions(): array
    {
        $out = [];
        try {
            $kliniks = DB::table('erm_klinik')->orderBy('id')->get(['nama', 'logo']);
        } catch (\Throwable $e) {
            $kliniks = collect();
        }
        foreach ($kliniks as $klinik) {
            $key = collect(array_keys(self::CLINIC_BRANDS))->first(fn ($w) => str_contains(strtolower($klinik->nama), $w));
            $known = $key ? self::CLINIC_BRANDS[$key] : null;
            $out[] = [
                'value' => $known['value'] ?? $klinik->nama,
                'label' => $known['value'] ?? $klinik->nama,
                'title' => $klinik->nama,
                'color' => $known['color'] ?? '#6b7280',
                'logo' => PublicFile::url($klinik->logo),
                'logo_bg' => null,
            ];
        }
        $out[] = [
            'value' => 'BCL',
            'label' => 'BCL (Kos)',
            'title' => 'Belova Center Living (Kos)',
            'color' => '#a21caf',
            'logo' => '/img/logobcl-white.png',
            'logo_bg' => '#a21caf',
        ];

        return $out;
    }

    /** People who usually approve / ask for revisions (picked when the creator records a WA decision). */
    public static function reviewers(): array
    {
        try {
            return \App\Models\User::role(['Marketing', 'Manager', 'Head Manager', 'Ceo'])
                ->where('is_active', true)->orderBy('name')->pluck('name')->unique()->values()->all();
        } catch (\Throwable $e) {
            return MarketingAccess::users()->pluck('name')->all();
        }
    }

    public function index()
    {
        return view('marketing.content_plan.index', [
            'users' => MarketingAccess::users(),
            'statusLabels' => self::STATUS_LABELS,
            'overdueStatuses' => self::OVERDUE_STATUSES,
            'brands' => self::brandOptions(),
            'platforms' => self::PLATFORMS,
            'jenisKonten' => self::JENIS_KONTEN,
            'kontenPilar' => self::KONTEN_PILAR,
            'canManage' => MarketingAccess::isMarketing(Auth::user()),
            'reviewers' => self::reviewers(),
            // Marketing users approving the content themselves get their own name pre-filled
            'selfApprover' => optional(Auth::user())->hasRole('Marketing') ? Auth::user()->name : null,
        ]);
    }

    protected function authorizeManage(): void
    {
        abort_unless(MarketingAccess::isMarketing(Auth::user()), 403, 'Hanya tim Marketing yang dapat mengubah content plan.');
    }

    protected function planRules(): array
    {
        return [
            'judul' => 'required|string|max:255',
            'brand' => 'nullable|array',
            'deskripsi' => 'nullable|string',
            'caption' => 'nullable|string',
            'mention' => 'nullable|string',
            'tanggal_produksi' => 'nullable|date',
            'tanggal_publish' => 'required|date',
            'platform' => 'required|array',
            'konten_pilar' => 'nullable|string|in:' . implode(',', self::KONTEN_PILAR),
            'jenis_konten' => 'required|array',
            'target_audience' => 'nullable|string',
            'link_asset' => 'nullable|string',
            'link_referensi' => 'nullable|string',
            'link_publikasi' => 'nullable|array',
            'link_publikasi.*' => 'nullable|string|max:1000',
            'catatan' => 'nullable|string',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ];
    }

    protected function normalizePlanData(array $data): array
    {
        $data['platform'] = array_values($data['platform']);
        $data['jenis_konten'] = array_values($data['jenis_konten']);
        if (isset($data['brand'])) {
            $data['brand'] = array_values($data['brand']);
        }
        if (isset($data['link_publikasi']) && is_array($data['link_publikasi'])) {
            $data['link_publikasi'] = array_filter($data['link_publikasi'], fn ($v) => $v !== null && trim($v) !== '');
        } else {
            unset($data['link_publikasi']);
        }

        return $data;
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate($this->planRules() + self::designerRules() + [
            'content_list_id' => 'nullable|integer|exists:marketing_content_lists,id',
        ]);
        $contentListId = $data['content_list_id'] ?? null;
        $designer = $this->pullDesignerChoice($data);
        unset($data['content_list_id']);
        $data = $this->normalizePlanData($data);
        // New plans always start the workflow at "Terjadwal"
        $data['status'] = 'Scheduled';

        // Scheduling from a content list: check the list first and lock it, so a list that is
        // not approved / already scheduled never produces a (duplicate) plan.
        $result = DB::transaction(function () use ($data, $contentListId) {
            $contentList = null;
            if ($contentListId) {
                $contentList = ContentList::whereKey($contentListId)->lockForUpdate()->first();
                if (!$contentList || strtolower($contentList->approval_status ?? '') !== 'approved') {
                    return 'Ide konten ini belum di-approve.';
                }
                if ($contentList->scheduled_plan_id && ContentPlan::whereKey($contentList->scheduled_plan_id)->exists()) {
                    return 'Ide konten ini sudah dijadwalkan.';
                }
            }

            $plan = ContentPlan::create($data);
            $plan->logStatus('created', null, 'Scheduled', $contentList ? 'Dari ide konten: ' . $contentList->judul : null);
            $brief = null;

            if ($contentList) {
                $contentList->update(['scheduled_plan_id' => $plan->id]);
                $contentList->logStatus('scheduled', $contentList->approval_status, 'Scheduled', 'Publish ' . $plan->tanggal_publish->format('d/m/Y H:i'));

                if ($contentList->gambar_referensi) {
                    $brief = ContentBrief::create([
                        'content_plan_id' => $plan->id,
                        'user_id' => Auth::id(),
                        'visual_references' => [$contentList->gambar_referensi],
                    ]);
                }
            }

            return [$plan, $brief];
        });

        if (is_string($result)) {
            return response()->json(['message' => $result, 'errors' => ['content_list_id' => [$result]]], 422);
        }

        [$plan, $brief] = $result;
        if ($designer) {
            DesignSync::applyChoice($plan, ...$designer);
        }

        // brief_id lets the modal update this brief instead of creating a second one
        return response()->json(['success' => true, 'data' => $plan, 'brief_id' => $brief ? $brief->id : null]);
    }

    public function show($id)
    {
        $plan = ContentPlan::with('assignedTo:id,name')->findOrFail($id);
        $latestBrief = $plan->briefs()->orderBy('created_at', 'desc')->first();

        $out = $plan->toArray();
        $out['tanggal_produksi'] = optional($plan->tanggal_produksi)->toDateString();
        $out['tanggal_publish'] = optional($plan->tanggal_publish)->toIso8601String();
        $out['status_label'] = self::STATUS_LABELS[$plan->status] ?? $plan->status;
        $out['transitions'] = MarketingAccess::isMarketing(Auth::user()) ? $this->transitionsFor($plan) : [];
        $out['timeline'] = $plan->timeline(self::STATUS_LABELS);
        $out['design_requests'] = $this->designRequestsOf($plan);
        $job = DesignSync::activeJob($plan);
        $out['design_job'] = $job ? [
            'id' => $job->id,
            'status' => $job->status,
            'designer_id' => $job->designer_id,
            'designer' => optional($job->designer)->name,
            'note' => $job->brief,
        ] : null;
        $out['has_brief'] = $plan->briefs()->where(fn ($q) => self::filledBrief($q))->exists();
        $out['brief'] = $latestBrief ? [
            'id' => $latestBrief->id,
            'headline' => $latestBrief->headline,
            'sub_headline' => $latestBrief->sub_headline,
            'isi_konten' => $latestBrief->isi_konten,
            'visual_references' => $latestBrief->visual_references,
        ] : null;

        return response()->json($out);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeManage();

        $plan = ContentPlan::findOrFail($id);
        // status is only changed through transition()
        $data = $request->validate($this->planRules() + self::designerRules());
        $designer = $this->pullDesignerChoice($data);
        $data = $this->normalizePlanData($data);

        $before = $this->scheduleSnapshot($plan);
        $plan->update($data);
        $this->logReschedule($plan, $before);
        if ($designer) {
            DesignSync::applyChoice($plan->fresh(), ...$designer);
        }

        return response()->json(['success' => true, 'data' => $plan]);
    }

    public function destroy($id)
    {
        $this->authorizeManage();

        $plan = ContentPlan::findOrFail($id);
        // Release the source content list so it can be scheduled again
        // (the FK is nullOnDelete, but prod schema may lack it)
        ContentList::where('scheduled_plan_id', $plan->id)->update(['scheduled_plan_id' => null]);
        DesignSync::planDeleted($plan);
        $plan->delete();

        return response()->json(['success' => true]);
    }

    /** Drag & drop on the board: move the date of the active mode only. */
    public function inlineUpdate(Request $request, $id)
    {
        $this->authorizeManage();

        $plan = ContentPlan::findOrFail($id);
        $data = $request->validate([
            'tanggal_publish' => 'sometimes|required|date',
            'tanggal_produksi' => 'sometimes|required|date',
        ]);

        $before = $this->scheduleSnapshot($plan);
        $plan->update($data);
        $this->logReschedule($plan, $before);
        DesignSync::syncDeadline($plan->fresh());

        return response()->json(['success' => true, 'data' => $plan]);
    }

    /** Run one workflow step (see TRANSITIONS). */
    public function transition(Request $request, $id)
    {
        $this->authorizeManage();

        $plan = ContentPlan::findOrFail($id);
        $payload = $request->validate([
            'action' => 'required|string|in:' . implode(',', array_keys(self::TRANSITIONS)),
            'notes' => 'nullable|string|max:2000',
            // who approved / asked for the revision (usually said in the WA group, recorded by the creator)
            'actor' => 'nullable|string|max:100',
            'link_publikasi' => 'nullable|array',
            'link_publikasi.*' => 'nullable|string|max:1000',
            // when it really went live (the button is often pressed later): "Y-m-d H:i"
            'tayang_at' => 'nullable|date_format:Y-m-d H:i',
        ]);

        [$from, $to, $label, , $note] = self::TRANSITIONS[$payload['action']];
        if (!in_array($plan->status, $from, true)) {
            return response()->json([
                'message' => '"' . $label . '" tidak bisa dilakukan saat status ' . (self::STATUS_LABELS[$plan->status] ?? $plan->status) . '.',
            ], 422);
        }
        if (in_array($payload['action'], DesignSync::hiddenPlanActions($plan), true)) {
            return response()->json(['message' => 'Konten ini dikerjakan designer: produksi dimulai saat designer mengambil job, dan review saat designer menandai selesai.'], 422);
        }
        if ($payload['action'] === 'start' && ($missing = $this->missingPreparation($plan))) {
            return response()->json(['message' => 'Lengkapi dulu sebelum mulai produksi: ' . implode(' dan ', $missing) . '.', 'missing' => $missing], 422);
        }
        $notes = trim((string) ($payload['notes'] ?? ''));
        if ($note === 'required' && $notes === '') {
            return response()->json(['message' => 'Catatan wajib diisi untuk "' . $label . '".', 'errors' => ['notes' => ['Catatan wajib diisi.']]], 422);
        }

        $update = ['status' => $to];

        if ($payload['action'] === 'publish') {
            // Published needs the post link(s); links typed in the dialog are merged per platform
            $links = is_array($plan->link_publikasi) ? $plan->link_publikasi : [];
            foreach ((array) ($payload['link_publikasi'] ?? []) as $platform => $url) {
                if ($url !== null && trim($url) !== '') {
                    $links[$platform] = trim($url);
                }
            }
            if (!array_filter($links, fn ($v) => trim((string) $v) !== '')) {
                return response()->json(['message' => 'Isi minimal satu link publikasi sebelum menandai Published.', 'errors' => ['link_publikasi' => ['Link publikasi wajib diisi.']]], 422);
            }
            $update['link_publikasi'] = $links;
        }

        $oldStatus = $plan->status;
        $plan->update($update);
        $actor = trim((string) ($payload['actor'] ?? ''));
        if ($actor !== '' && in_array($payload['action'], ['approve', 'revise'], true)) {
            $notes = ($payload['action'] === 'approve' ? 'Oleh: ' : 'Dari: ') . $actor . ($notes !== '' ? "\n" . $notes : '');
        }
        if ($payload['action'] === 'publish' && !empty($payload['tayang_at'])) {
            $notes = self::tayangNote(Carbon::parse($payload['tayang_at']), $plan->tanggal_publish) . ($notes !== '' ? "\n" . $notes : '');
        }
        $plan->logStatus($payload['action'], $oldStatus, $to, $notes);
        DesignSync::fromPlan($plan, $payload['action'], $notes);

        return response()->json(['success' => true, 'status' => $to, 'status_label' => self::STATUS_LABELS[$to] ?? $to]);
    }

    /** Minutes after the scheduled time a post still counts as on time. */
    public const ON_TIME_MINUTES = 30;

    /** "Tayang: 21/10/2026 09.00 (tepat waktu)" / "(terlambat 1 jam 15 menit)" for the history. */
    public static function tayangNote(Carbon $tayang, $jadwal): string
    {
        $note = 'Tayang: ' . $tayang->format('d/m/Y H.i');
        if ($jadwal) {
            $late = (int) Carbon::parse($jadwal)->diffInMinutes($tayang, false);
            $note .= $late <= self::ON_TIME_MINUTES ? ' (tepat waktu)' : ' (terlambat ' . self::duration($late) . ')';
        }

        return $note;
    }

    public static function duration(int $minutes): string
    {
        if ($minutes >= 1440) {
            return intdiv($minutes, 1440) . ' hari';
        }

        return trim((intdiv($minutes, 60) ? intdiv($minutes, 60) . ' jam ' : '') . ($minutes % 60 ? ($minutes % 60) . ' menit' : ''));
    }

    /**
     * A brief counts as written when it has a headline or a script; reference images alone
     * (e.g. copied from the idea) are not a brief yet.
     */
    public static function filledBrief($query)
    {
        return $query->where(function ($q) {
            $q->where(fn ($w) => $w->whereNotNull('headline')->where('headline', '!=', ''))
                ->orWhere(fn ($w) => $w->whereNotNull('isi_konten')->where('isi_konten', '!=', ''));
        });
    }

    /** "Siapa yang mengerjakan?" in Penugasan (only sent by the form when that section is shown). */
    protected static function designerRules(): array
    {
        return [
            'needs_designer' => 'nullable|boolean',
            'designer_id' => 'nullable|integer|exists:users,id',
        ];
    }

    /** Take the designer fields out of the plan data: [needsDesigner, designerId] or null when not sent. */
    protected function pullDesignerChoice(array &$data): ?array
    {
        $sent = array_key_exists('needs_designer', $data);
        $choice = $sent ? [(bool) $data['needs_designer'], $data['designer_id'] ?? null] : null;
        unset($data['needs_designer'], $data['designer_id']);

        return $choice;
    }

    /** Steps that must be done before "Mulai produksi": [] when ready. */
    protected function missingPreparation(ContentPlan $plan): array
    {
        $missing = [];
        if (!$plan->assigned_to) {
            $missing[] = 'PIC';
        }
        if (!$plan->briefs()->where(fn ($q) => self::filledBrief($q))->exists()) {
            $missing[] = 'brief';
        }

        return $missing;
    }

    protected function transitionsFor(ContentPlan $plan, ?bool $hasActiveJob = null): array
    {
        $hidden = ($hasActiveJob ?? (bool) DesignSync::activeJob($plan)) ? ['start', 'submit_review'] : [];
        $out = [];
        foreach (self::TRANSITIONS as $action => [$from, $to, $label, $style, $note]) {
            if (in_array($plan->status, $from, true) && !in_array($action, $hidden, true)) {
                $out[] = compact('action', 'label', 'style', 'note') + ['to' => $to];
            }
        }

        return $out;
    }

    protected function designRequestsOf(ContentPlan $plan): array
    {
        if (!Schema::hasTable('marketing_design_requests')) {
            return [];
        }

        return $plan->designRequests()->with('designer:id,name')->orderByDesc('id')->get()
            ->map(fn (DesignRequest $dr) => [
                'id' => $dr->id,
                'judul' => $dr->judul,
                'status' => $dr->status,
                'designer' => optional($dr->designer)->name,
                'deadline' => optional($dr->deadline)->toDateString(),
            ])->all();
    }

    protected function scheduleSnapshot(ContentPlan $plan): array
    {
        return [
            'tanggal_publish' => optional($plan->tanggal_publish)->format('d/m/Y H:i'),
            'tanggal_produksi' => optional($plan->tanggal_produksi)->format('d/m/Y'),
        ];
    }

    protected function logReschedule(ContentPlan $plan, array $before): void
    {
        $after = $this->scheduleSnapshot($plan->fresh());
        $changes = [];
        foreach (['tanggal_publish' => 'Publish', 'tanggal_produksi' => 'Produksi'] as $field => $label) {
            if ($before[$field] !== $after[$field]) {
                $changes[] = $label . ': ' . ($before[$field] ?: '-') . ' → ' . ($after[$field] ?: '-');
            }
        }
        if ($changes) {
            $plan->logStatus('reschedule', null, null, implode('; ', $changes));
        }
    }

    protected function scheduleBy(Request $request)
    {
        return $request->input('schedule_by') === 'tanggal_produksi' ? 'tanggal_produksi' : 'tanggal_publish';
    }

    /**
     * Filters shared by week() and statusList(): date range on the schedule column,
     * brand / platform (JSON arrays), status, konten pilar and "Tugas Saya" (mine=1).
     */
    protected function applyFilters($query, Request $request, $scheduleBy)
    {
        if ($request->filled('date_start') && $request->filled('date_end')) {
            $start = Carbon::createFromFormat('d/m/Y', $request->date_start)->startOfDay();
            $end = Carbon::createFromFormat('d/m/Y', $request->date_end)->endOfDay();
            $query->whereBetween($scheduleBy, [$start, $end]);
        }
        foreach (['filter_brand' => 'brand', 'filter_platform' => 'platform'] as $param => $column) {
            if ($request->filled($param)) {
                $values = (array) $request->input($param);
                $query->where(function ($q) use ($values, $column) {
                    foreach ($values as $value) {
                        $q->orWhereJsonContains($column, $value);
                    }
                });
            }
        }
        if ($request->filled('filter_status')) {
            $query->where('status', $request->filter_status);
        }
        if ($request->filled('filter_konten_pilar')) {
            $query->where('konten_pilar', $request->filter_konten_pilar);
        }
        if ($request->boolean('mine')) {
            $query->where('assigned_to', Auth::id());
        }

        return $query;
    }

    protected function applyOverdue($query, $scheduleBy)
    {
        $query->whereIn('status', self::OVERDUE_STATUSES[$scheduleBy]);
        if ($scheduleBy === 'tanggal_produksi') {
            $query->whereDate('tanggal_produksi', '<', Carbon::today()->toDateString());
        } else {
            $query->where('tanggal_publish', '<', Carbon::now());
        }

        return $query;
    }

    /** Only http(s) links are rendered; a bare "instagram.com/..." gets https:// */
    protected function safeUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '') return null;
        if (preg_match('#^https?://#i', $url)) return $url;
        if (!preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url)) return 'https://' . ltrim($url, '/');

        return null;
    }

    /**
     * All content plans of a week range as plain data grouped by date (YYYY-MM-DD);
     * the board builds the cards client-side.
     */
    public function week(Request $request)
    {
        $scheduleBy = $this->scheduleBy($request);
        $hasDesign = Schema::hasTable('marketing_design_requests');
        $canManage = MarketingAccess::isMarketing(Auth::user());

        $query = ContentPlan::with('assignedTo:id,name')
            ->withCount(['briefs as brief_filled' => fn ($q) => self::filledBrief($q)]);
        // revision count + when the content last went into Review (history table arrives with a migration)
        if (Schema::hasTable('marketing_status_logs')) {
            $query->withCount(['statusLogs as revisi_count' => fn ($q) => $q->where('action', 'revise')])
                ->withMax(['statusLogs as review_since' => fn ($q) => $q->where('to_status', 'Review')], 'created_at');
        }
        if ($hasDesign) {
            $query->with(['designRequests' => fn ($q) => $q->select('id', 'content_plan_id', 'status', 'designer_id', 'deadline')->with('designer:id,name')->orderByDesc('id')]);
        }
        $this->applyFilters($query, $request, $scheduleBy);
        $items = $query->orderBy($scheduleBy)->orderBy('tanggal_publish')->get();
        $publishedIds = $items->where('status', 'Published')->pluck('id')->all();
        $published = self::publishInfo($publishedIds);
        $insights = $this->insightInfo($publishedIds);
        $thumbs = $this->thumbnails($items->pluck('id')->all());

        $rows = $items->map(function (ContentPlan $row) use ($scheduleBy, $hasDesign, $canManage, $published, $insights, $thumbs) {
            $platforms = is_array($row->platform)
                ? $row->platform
                : array_filter(array_map('trim', explode(',', (string) $row->platform)));
            // links are stored keyed by platform; older rows used a plain list in platform order
            $links = is_array($row->link_publikasi) ? $row->link_publikasi : [];
            $platformItems = [];
            foreach (array_values($platforms) as $i => $p) {
                $platformItems[] = ['name' => $p, 'url' => $this->safeUrl($links[$p] ?? ($links[$i] ?? null))];
            }

            // newest design request decides the badge on the card
            $design = null;
            $activeJob = null;
            if ($hasDesign && $row->designRequests->isNotEmpty()) {
                $activeJob = $row->designRequests->first(fn ($dr) => $dr->isActive());
                $design = ($activeJob ?: $row->designRequests->first())->status;
            }

            $scheduleDate = $scheduleBy === 'tanggal_produksi'
                ? optional($row->tanggal_produksi)->toDateString()
                : optional($row->tanggal_publish)->toIso8601String();

            return [
                'id' => $row->id,
                'judul' => $row->judul,
                'status' => $row->status,
                'status_label' => self::STATUS_LABELS[$row->status] ?? $row->status,
                'schedule_date' => $scheduleDate,
                'tanggal_produksi' => optional($row->tanggal_produksi)->toDateString(),
                'tanggal_publish' => optional($row->tanggal_publish)->toIso8601String(),
                'assigned_to' => $row->assigned_to,
                'assigned_to_name' => $row->assignedTo ? $row->assignedTo->name : '',
                'brand' => array_values((array) ($row->brand ?? [])),
                'jenis_konten' => array_values((array) ($row->jenis_konten ?? [])),
                'konten_pilar' => $row->konten_pilar,
                'platforms' => $platformItems,
                'design_status' => $design,
                // workflow buttons for the card (Marketing only)
                'transitions' => $canManage ? $this->transitionsFor($row, (bool) $activeJob) : [],
                'needs_designer' => (bool) $activeJob,
                'designer_name' => $activeJob ? optional($activeJob->designer)->name : null,
                // H-1: the designer job is still waiting while its deadline is close / past
                'design_urgency' => $activeJob ? \App\Support\Marketing\DesignReminders::urgency($activeJob->deadline, $activeJob->status) : null,
                'link_publikasi' => is_array($row->link_publikasi) ? $row->link_publikasi : (object) [],
                'revisi_count' => (int) ($row->revisi_count ?? 0),
                'has_brief' => ($row->brief_filled ?? 0) > 0,
                'review_since' => $row->review_since ? Carbon::parse($row->review_since)->toIso8601String() : null,
                // Published: when it went live (time typed in "Tandai published"), by whom, on time or not
                'published' => $published[$row->id] ?? null,
                // latest Instagram numbers (from "Update insight") and the views gained since the snapshot before
                'insight' => $insights[$row->id] ?? null,
                // preview on the card: the post itself once published (copy kept by "Update insight"),
                // before that the first reference picture of the brief
                'thumb' => $thumbs[$row->id] ?? null,
            ];
        });

        $grouped = $rows->groupBy(function ($r) {
            return $r['schedule_date'] ? Carbon::parse($r['schedule_date'])->format('Y-m-d') : 'no_date';
        })->toArray();

        // konten rutin of the week (upload days only exist on the publish calendar)
        $routines = [];
        if ($scheduleBy === 'tanggal_publish' && $request->filled('date_start') && $request->filled('date_end')) {
            $routines = RoutineSchedule::between(
                Carbon::createFromFormat('d/m/Y', $request->date_start),
                Carbon::createFromFormat('d/m/Y', $request->date_end),
                $this->routineFilters($request)
            );
        }

        return response()->json(['data' => $grouped, 'routines' => (object) $routines]);
    }

    /**
     * Latest "publish" of each plan: go-live time (the "Tayang: d/m/Y H.i" note written by transition(),
     * otherwise the moment the button was pressed), who recorded it and how late against the schedule.
     *
     * @return array<int, array{at: string, by: ?string, on_time: ?bool, late_by: ?string}>
     */
    public static function publishInfo(array $planIds): array
    {
        if (!$planIds || !Schema::hasTable('marketing_status_logs')) {
            return [];
        }
        $plans = ContentPlan::whereIn('id', $planIds)->pluck('tanggal_publish', 'id');
        $logs = \App\Models\Marketing\StatusLog::with('user:id,name')
            ->where('loggable_type', ContentPlan::class)->whereIn('loggable_id', $planIds)
            ->where('action', 'publish')->orderBy('id')->get()
            ->keyBy('loggable_id'); // last one wins

        $out = [];
        foreach ($logs as $planId => $log) {
            $at = preg_match('/Tayang: (\d{2}\/\d{2}\/\d{4} \d{2}\.\d{2})/', (string) $log->notes, $m)
                ? Carbon::createFromFormat('d/m/Y H.i', $m[1])
                : Carbon::parse($log->created_at);
            $late = $plans[$planId] ? (int) Carbon::parse($plans[$planId])->diffInMinutes($at, false) : null;
            $out[$planId] = [
                'at' => $at->toIso8601String(),
                'by' => optional($log->user)->name,
                'on_time' => $late === null ? null : $late <= self::ON_TIME_MINUTES,
                'late_by' => $late !== null && $late > self::ON_TIME_MINUTES ? self::duration($late) : null,
            ];
        }

        return $out;
    }

    /** @return array<int, array{url: string, kind: string}> kind: post (Instagram picture) | ref (brief reference) */
    protected function thumbnails(array $planIds): array
    {
        $out = [];
        foreach ($planIds as $id) {
            if ($path = \App\Support\Marketing\InstagramInsights::thumbnailPath($id)) {
                $out[$id] = ['url' => PublicFile::url($path) . '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($path), 'kind' => 'post'];
            }
        }
        $rest = array_values(array_diff($planIds, array_keys($out)));
        if ($rest) {
            $briefs = ContentBrief::whereIn('content_plan_id', $rest)->orderBy('created_at')->get(['content_plan_id', 'visual_references'])
                ->keyBy('content_plan_id'); // latest brief per content wins
            foreach ($briefs as $planId => $brief) {
                $first = collect($brief->visual_references ?: [])->first();
                if ($first && ($url = PublicFile::url($first))) {
                    $out[$planId] = ['url' => $url, 'kind' => 'ref'];
                }
            }
        }

        return $out;
    }

    /** @return array<int, array{tanggal: string, views: ?int, likes: ?int, comments: ?int, reach: ?int, views_delta: ?int}> */
    protected function insightInfo(array $planIds): array
    {
        if (!$planIds || !Schema::hasTable('marketing_content_insights')) {
            return [];
        }
        $out = [];
        $rows = \App\Models\Marketing\ContentInsight::whereIn('content_plan_id', $planIds)->where('platform', 'instagram')
            ->orderBy('content_plan_id')->orderByDesc('tanggal')->get()->groupBy('content_plan_id');
        foreach ($rows as $planId => $list) {
            [$last, $before] = [$list->get(0), $list->get(1)];
            $out[$planId] = [
                'tanggal' => $last->tanggal->toDateString(),
                'views' => $last->views,
                'likes' => $last->likes,
                'comments' => $last->comments,
                'reach' => $last->reach,
                'views_delta' => $before && $last->views !== null && $before->views !== null ? $last->views - $before->views : null,
            ];
        }

        return $out;
    }

    /** Board filters that also apply to konten rutin. */
    protected function routineFilters(Request $request): array
    {
        return [
            'mine' => $request->boolean('mine') ? Auth::id() : null,
            'brand' => $request->filled('filter_brand') ? (array) $request->input('filter_brand') : null,
            'platform' => $request->filled('filter_platform') ? (array) $request->input('filter_platform') : null,
        ];
    }

    /**
     * Flat list for the "Konten Terlewat" modal. `status` is a plan status, 'all',
     * or 'terlewat' (see OVERDUE_STATUSES). With `count_only=1` only the count is returned.
     */
    public function statusList(Request $request)
    {
        $scheduleBy = $this->scheduleBy($request);

        $query = ContentPlan::with('assignedTo:id,name');
        $this->applyFilters($query, $request, $scheduleBy);

        $status = strtolower((string) $request->input('status', ''));

        // Published on Instagram without a post link: such content never shows up in the insights
        if ($status === 'tanpa_link') {
            $rows = ContentPlan::with('assignedTo:id,name')->where('status', 'Published')
                ->where('tanggal_publish', '>=', now()->subDays(\App\Support\Marketing\InstagramInsights::SYNC_DAYS)->startOfDay())
                ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_to', Auth::id()))
                ->orderByDesc('tanggal_publish')->get()
                ->filter(fn (ContentPlan $p) => in_array('Instagram', (array) $p->platform, true)
                    && !\App\Support\Marketing\InstagramInsights::shortcode(\App\Support\Marketing\InstagramInsights::instagramLink($p)))
                ->values();
            if ($request->boolean('count_only')) {
                return response()->json(['count' => $rows->count()]);
            }

            return response()->json(['data' => $rows->map(fn (ContentPlan $row) => [
                'id' => $row->id,
                'day' => Carbon::parse($row->tanggal_publish)->locale('id')->translatedFormat('l j F Y'),
                'time' => Carbon::parse($row->tanggal_publish)->format('H.i'),
                'judul' => $row->judul ?? '',
                'brand' => is_array($row->brand) ? implode(', ', $row->brand) : (string) ($row->brand ?? ''),
                'status' => $row->status,
                'status_label' => self::STATUS_LABELS[$row->status] ?? $row->status,
                'assigned' => $row->assignedTo ? $row->assignedTo->name : '',
                'link' => \App\Support\Marketing\InstagramInsights::instagramLink($row),
            ])->values()]);
        }

        $overdue = in_array($status, ['terlewat', 'overdue'], true);
        if ($overdue) {
            $this->applyOverdue($query, $scheduleBy);
        } elseif ($status !== '' && $status !== 'all') {
            $query->where('status', $request->status);
        }

        // missed uploads of konten rutin (last days) count as "terlewat" on the publish calendar
        $lateRoutines = $overdue && $scheduleBy === 'tanggal_publish' ? RoutineSchedule::late($this->routineFilters($request)) : [];

        if ($request->boolean('count_only')) {
            return response()->json(['count' => $query->count() + count($lateRoutines)]);
        }

        $rows = $query->orderBy($scheduleBy)->orderBy('tanggal_publish')->get()->map(function ($row) use ($scheduleBy) {
            $scheduleValue = $row->{$scheduleBy};

            return [
                'id' => $row->id,
                'day' => $scheduleValue ? Carbon::parse($scheduleValue)->locale('id')->translatedFormat('l j F Y') : '',
                'time' => $scheduleBy === 'tanggal_publish' && $scheduleValue ? Carbon::parse($scheduleValue)->format('H.i') : '',
                'judul' => $row->judul ?? '',
                'brand' => is_array($row->brand) ? implode(', ', $row->brand) : (string) ($row->brand ?? ''),
                'status' => $row->status,
                'status_label' => self::STATUS_LABELS[$row->status] ?? $row->status,
                'assigned' => $row->assignedTo ? $row->assignedTo->name : '',
            ];
        })->values();

        foreach ($lateRoutines as $occ) {
            $rows->push([
                'type' => 'routine',
                'routine_id' => $occ['routine_id'],
                'date' => $occ['date'],
                'day' => Carbon::parse($occ['date'])->locale('id')->translatedFormat('l j F Y'),
                'time' => str_replace(':', '.', $occ['time']),
                'judul' => $occ['judul'],
                'brand' => implode(', ', $occ['brand']),
                'assigned' => $occ['assigned_to_name'] ?: '',
            ]);
        }

        return response()->json(['data' => $rows]);
    }
}
