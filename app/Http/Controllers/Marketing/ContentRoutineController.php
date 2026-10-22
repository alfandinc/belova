<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\ContentRoutine;
use App\Models\Marketing\ContentRoutinePost;
use App\Support\Marketing\MarketingAccess;
use App\Support\Marketing\RoutineSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * "Konten rutin" on the Content Plan: made once, uploaded on fixed days (e.g. "We Are Open" every day 09:00).
 * Only the daily upload is recorded (Sudah upload / Lewati); there is no production or review per day.
 */
class ContentRoutineController extends Controller
{
    protected function authorizeManage(): void
    {
        abort_unless(MarketingAccess::isMarketing(Auth::user()), 403, 'Hanya tim Marketing yang dapat mengubah konten rutin.');
    }

    /** All routines (active first) with this month's upload score. */
    public function index()
    {
        $routines = ContentRoutine::with('assignedTo:id,name')->orderByDesc('is_active')->orderBy('jam')->orderBy('judul')->get();

        return response()->json(['data' => $routines->map(fn (ContentRoutine $r) => $this->summary($r) + $this->monthScore($r))->values()]);
    }

    public function show($id)
    {
        $routine = ContentRoutine::with('assignedTo:id,name')->findOrFail($id);

        // last 30 scheduled days up to today, newest first
        $from = Carbon::today()->subDays(29);
        if ($routine->start_date->gt($from)) {
            $from = $routine->start_date->copy();
        }
        $history = [];
        if ($from->lte(Carbon::today())) {
            foreach (array_reverse(RoutineSchedule::between($from, Carbon::today()), true) as $day) {
                foreach ($day as $occ) {
                    if ((int) $occ['routine_id'] === (int) $routine->id) {
                        $history[] = $occ;
                    }
                }
            }
        }

        return response()->json($this->summary($routine) + $this->monthScore($routine) + [
            'brand' => array_values((array) ($routine->brand ?? [])),
            'platform' => array_values((array) ($routine->platform ?? [])),
            'jenis_konten' => array_values((array) ($routine->jenis_konten ?? [])),
            'days' => array_map('intval', (array) $routine->days),
            'start_date' => $routine->start_date->toDateString(),
            'end_date' => optional($routine->end_date)->toDateString(),
            'asset_link' => $routine->asset_link,
            'asset_url' => RoutineSchedule::safeUrl($routine->asset_link),
            'caption' => $routine->caption,
            'catatan' => $routine->catatan,
            'history' => $history,
            'timeline' => collect($routine->timeline())->map(function ($row) {
                $row['action_label'] = ContentRoutine::ACTION_LABELS[$row['action']] ?? $row['action'];
                return $row;
            })->all(),
        ]);
    }

    protected function summary(ContentRoutine $r): array
    {
        return [
            'id' => $r->id,
            'judul' => $r->judul,
            'jam' => $r->jamLabel(),
            'days_label' => $r->daysLabel(),
            'brand' => array_values((array) ($r->brand ?? [])),
            'platform' => array_values((array) ($r->platform ?? [])),
            'assigned_to' => $r->assigned_to,
            'assigned_to_name' => optional($r->assignedTo)->name,
            'is_active' => (bool) $r->is_active,
            'ended' => $r->end_date && $r->end_date->lt(Carbon::today()),
        ];
    }

    /** This month so far: uploaded / scheduled days (skipped days are not counted). */
    protected function monthScore(ContentRoutine $r): array
    {
        $from = Carbon::today()->startOfMonth();
        $posted = 0;
        $onTime = 0;
        $total = 0;
        foreach (RoutineSchedule::between($from, Carbon::today()) as $day) {
            foreach ($day as $occ) {
                if ((int) $occ['routine_id'] !== (int) $r->id || in_array($occ['state'], ['skipped', 'due', 'upcoming'], true)) {
                    continue;
                }
                $total++;
                $posted += $occ['state'] === 'posted' ? 1 : 0;
                $onTime += $occ['on_time'] ? 1 : 0;
            }
        }

        return ['month_posted' => $posted, 'month_on_time' => $onTime, 'month_total' => $total];
    }

    protected function rules(): array
    {
        return [
            'judul' => 'required|string|max:255',
            'brand' => 'nullable|array',
            'brand.*' => 'string|max:100',
            'platform' => 'required|array|min:1',
            'platform.*' => 'string|max:50',
            'jenis_konten' => 'nullable|array',
            'jenis_konten.*' => 'string|max:50',
            'jam' => 'required|date_format:H:i',
            'days' => 'required|array|min:1',
            'days.*' => 'integer|between:1,7',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'asset_link' => 'nullable|string|max:1000',
            'caption' => 'nullable|string|max:5000',
            'catatan' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ];
    }

    protected function payload(array $data): array
    {
        $days = array_values(array_unique(array_map('intval', $data['days'])));
        sort($days);

        return [
            'judul' => $data['judul'],
            'brand' => array_values($data['brand'] ?? []),
            'platform' => array_values($data['platform']),
            'jenis_konten' => array_values($data['jenis_konten'] ?? []),
            'jam' => $data['jam'] . ':00',
            'days' => $days,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'asset_link' => $data['asset_link'] ?? null,
            'caption' => $data['caption'] ?? null,
            'catatan' => $data['catatan'] ?? null,
        ];
    }

    public function store(Request $request)
    {
        $this->authorizeManage();
        $data = $request->validate($this->rules(), [], $this->attributeNames());

        $routine = ContentRoutine::create($this->payload($data) + ['is_active' => true, 'created_by' => Auth::id()]);
        $routine->logStatus('created', null, null, $routine->daysLabel() . ' jam ' . $routine->jamLabel());

        return response()->json(['success' => true, 'id' => $routine->id]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeManage();
        $routine = ContentRoutine::findOrFail($id);
        $data = $request->validate($this->rules(), [], $this->attributeNames());

        $wasActive = (bool) $routine->is_active;
        $active = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $wasActive;
        $routine->update($this->payload($data) + ['is_active' => $active]);
        $routine->logStatus('updated', null, null, $routine->daysLabel() . ' jam ' . $routine->jamLabel());
        if ($wasActive !== $active) {
            $routine->logStatus($active ? 'resumed' : 'paused');
        }

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $this->authorizeManage();
        ContentRoutine::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * "Sudah upload" for one day, with optional post links per platform. Future days are allowed:
     * posts are often scheduled ahead in the platform's own scheduler.
     */
    public function post(Request $request, $id)
    {
        $this->authorizeManage();
        $routine = ContentRoutine::findOrFail($id);
        $data = $request->validate([
            'tanggal' => 'required|date',
            'link_publikasi' => 'nullable|array',
            'link_publikasi.*' => 'nullable|string|max:1000',
            // when it really went live (often checked off later); the moment of recording is kept apart
            'jam_tayang' => 'nullable|date_format:H:i',
        ]);
        $day = $this->checkDay($routine, $data['tanggal']);
        $links = array_filter(array_map(fn ($v) => trim((string) $v), $data['link_publikasi'] ?? []));
        $tayang = !empty($data['jam_tayang']) ? Carbon::parse($day->toDateString() . ' ' . $data['jam_tayang']) : now();

        $this->record($routine, $day, ContentRoutinePost::POSTED, $links ?: null, null, $tayang);

        return response()->json(['success' => true]);
    }

    /** "Lewati" a day (clinic closed, holiday, …): the reason is required. */
    public function skip(Request $request, $id)
    {
        $this->authorizeManage();
        $routine = ContentRoutine::findOrFail($id);
        $data = $request->validate(['tanggal' => 'required|date', 'notes' => 'required|string|max:1000'], [], ['notes' => 'alasan']);
        $day = $this->checkDay($routine, $data['tanggal']);

        $this->record($routine, $day, ContentRoutinePost::SKIPPED, null, $data['notes']);

        return response()->json(['success' => true]);
    }

    /** Remove the record of a day (tapped by mistake): the day is open again. */
    public function undo(Request $request, $id)
    {
        $this->authorizeManage();
        $routine = ContentRoutine::findOrFail($id);
        $data = $request->validate(['tanggal' => 'required|date']);
        $post = $routine->posts()->whereDate('tanggal', $data['tanggal'])->first();
        abort_unless($post, 404, 'Tidak ada catatan pada tanggal ini.');

        $post->delete();
        $routine->logStatus('undo', $post->status, null, Carbon::parse($data['tanggal'])->format('d/m/Y'));

        return response()->json(['success' => true]);
    }

    protected function checkDay(ContentRoutine $routine, string $date): Carbon
    {
        $day = Carbon::parse($date)->startOfDay();
        abort_unless($routine->scheduledOn($day), 422, 'Konten ini tidak dijadwalkan pada tanggal tersebut.');

        return $day;
    }

    /** done_at = when it went live (posted) or was skipped; updated_at = when it was recorded. */
    protected function record(ContentRoutine $routine, Carbon $day, string $status, ?array $links, ?string $notes, ?Carbon $doneAt = null): void
    {
        DB::transaction(function () use ($routine, $day, $status, $links, $notes, $doneAt) {
            $post = ContentRoutinePost::firstOrNew(['routine_id' => $routine->id, 'tanggal' => $day->toDateString()]);
            $post->fill([
                'status' => $status,
                // the schedule of that day is kept (first record wins; an edit of the routine does not move it)
                'jam_jadwal' => $post->jam_jadwal ?: $routine->jamLabel() . ':00',
                'link_publikasi' => $links,
                'notes' => $notes,
                'user_id' => Auth::id(),
                'done_at' => $doneAt ?: now(),
            ])->save();
            $log = $status === ContentRoutinePost::POSTED && $doneAt
                ? ContentPlanController::tayangNote($doneAt, Carbon::parse($day->toDateString() . ' ' . substr((string) $post->jam_jadwal, 0, 5)))
                : $day->format('d/m/Y') . ($notes ? ' — ' . $notes : '');
            $routine->logStatus($status, null, null, $log);
        });
    }

    protected function attributeNames(): array
    {
        return ['judul' => 'judul', 'platform' => 'platform', 'jam' => 'jam upload', 'days' => 'hari upload', 'start_date' => 'tanggal mulai', 'end_date' => 'tanggal selesai'];
    }
}
