<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\ContentList;
use App\Support\Marketing\MarketingAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\Marketing\PublicFile;

/**
 * Content List = "Ide Konten": ideas are proposed here, approved by any Marketing user
 * (each decision is kept in the history), then scheduled into the Content Plan.
 *
 * Display status: Pending / Approved / Rejected, or Scheduled once a plan exists.
 */
class ContentListController extends Controller
{
    public const ACTION_LABELS = [
        'created' => 'Mengajukan ide',
        'resubmitted' => 'Mengubah & mengajukan ulang',
        'updated' => 'Mengubah ide',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'scheduled' => 'Dijadwalkan',
    ];

    protected function authorizeManage(): void
    {
        abort_unless(MarketingAccess::isMarketing(Auth::user()), 403, 'Hanya tim Marketing yang dapat mengubah ide konten.');
    }

    protected function filtered(Request $request)
    {
        $query = ContentList::query();

        if ($request->filled('filter_brand')) {
            $brands = (array) $request->filter_brand;
            $query->where(function ($builder) use ($brands) {
                foreach ($brands as $brand) {
                    $builder->orWhereJsonContains('brand', $brand);
                }
            });
        }
        if ($request->filled('filter_assigned_to')) {
            $query->where('assigned_to', $request->filter_assigned_to);
        }
        if ($request->filled('q')) {
            $query->where('judul', 'like', '%' . $request->q . '%');
        }

        return $query;
    }

    public function stats(Request $request)
    {
        $stats = $this->filtered($request)
            ->selectRaw("SUM(CASE WHEN approval_status = 'Pending' AND scheduled_plan_id IS NULL THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw("SUM(CASE WHEN approval_status = 'Approved' AND scheduled_plan_id IS NULL THEN 1 ELSE 0 END) as approved_count")
            ->selectRaw("SUM(CASE WHEN approval_status = 'Rejected' AND scheduled_plan_id IS NULL THEN 1 ELSE 0 END) as rejected_count")
            ->selectRaw("SUM(CASE WHEN scheduled_plan_id IS NOT NULL THEN 1 ELSE 0 END) as scheduled_count")
            ->first();

        return response()->json([
            'pending' => (int) ($stats->pending_count ?? 0),
            'approved' => (int) ($stats->approved_count ?? 0),
            'rejected' => (int) ($stats->rejected_count ?? 0),
            'scheduled' => (int) ($stats->scheduled_count ?? 0),
        ]);
    }

    /** Card list for the "Ide Konten" tab (paged with "Muat lagi"). */
    public function list(Request $request)
    {
        $query = $this->filtered($request)->with(['assignedTo:id,name', 'approvedBy:id,name', 'scheduledPlan:id,judul,tanggal_publish,status']);

        $status = strtolower((string) $request->input('status', 'pending'));
        switch ($status) {
            case 'scheduled':
                $query->whereNotNull('scheduled_plan_id')->orderByDesc('updated_at');
                break;
            case 'approved':
            case 'rejected':
            case 'pending':
                $query->where('approval_status', ucfirst($status))->whereNull('scheduled_plan_id')->orderByDesc('updated_at');
                break;
            default: // all
                $query->orderByDesc('updated_at');
        }

        $perPage = 20;
        $page = max(1, (int) $request->input('page', 1));
        $total = (clone $query)->count();
        $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $user = Auth::user();
        $canManage = MarketingAccess::isMarketing($user);

        return response()->json([
            'data' => $items->map(fn (ContentList $row) => $this->card($row, $canManage))->values(),
            'has_more' => $page * $perPage < $total,
            'total' => $total,
        ]);
    }

    protected function displayStatus(ContentList $row): string
    {
        if ($row->scheduled_plan_id && $row->scheduledPlan) {
            return 'Scheduled';
        }

        return ucfirst(strtolower($row->approval_status ?: 'Pending'));
    }

    protected function card(ContentList $row, bool $canManage): array
    {
        $status = $this->displayStatus($row);

        return [
            'id' => $row->id,
            'judul' => $row->judul,
            'brand' => array_values((array) ($row->brand ?? [])),
            'platform' => array_values((array) ($row->platform ?? [])),
            'jenis_konten' => array_values((array) ($row->jenis_konten ?? [])),
            'konten_pilar' => $row->konten_pilar,
            'catatan' => $row->catatan,
            'link_referensi' => $row->link_referensi,
            'assigned_to_name' => optional($row->assignedTo)->name,
            'status' => $status,
            'approval_notes' => $row->approval_notes,
            'approved_by_name' => optional($row->approvedBy)->name,
            'approved_at' => optional($row->approved_at)->toIso8601String(),
            'gambar_url' => PublicFile::url($row->gambar_referensi),
            'scheduled_plan' => $row->scheduledPlan ? [
                'id' => $row->scheduledPlan->id,
                'tanggal_publish' => optional($row->scheduledPlan->tanggal_publish)->toIso8601String(),
                'status' => $row->scheduledPlan->status,
            ] : null,
            'can' => [
                'approve' => $canManage && in_array($status, ['Pending', 'Rejected'], true),
                'reject' => $canManage && in_array($status, ['Pending', 'Approved'], true),
                'schedule' => $canManage && $status === 'Approved',
                'edit' => $canManage && $status !== 'Scheduled',
                'delete' => $canManage,
            ],
        ];
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $data = $this->validatePayload($request);
        $data['approval_status'] = 'Pending';
        $list = ContentList::create($data);
        $list->logStatus('created', null, 'Pending');

        return response()->json(['success' => true, 'data' => $list]);
    }

    public function show($id)
    {
        $list = ContentList::with(['assignedTo:id,name', 'approvedBy:id,name', 'scheduledPlan'])->findOrFail($id);

        return response()->json($list->toArray() + [
            'gambar_url' => PublicFile::url($list->gambar_referensi),
            'display_status' => $this->displayStatus($list),
            'timeline' => $list->timeline(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeManage();

        $list = ContentList::findOrFail($id);
        $data = $this->validatePayload($request);

        // Any edit before scheduling goes back to Pending: an approved idea must be re-approved
        // after changes, and editing a rejected idea is how it gets resubmitted.
        // The last decision note is kept so the approver sees what had to be fixed.
        $isScheduled = $list->scheduled_plan_id && $list->scheduledPlan;
        $oldStatus = $list->approval_status ?: 'Pending';
        $resubmitted = !$isScheduled && strtolower($oldStatus) !== 'pending';
        if ($resubmitted) {
            $data['approval_status'] = 'Pending';
            $data['approved_by'] = null;
            $data['approved_at'] = null;
        }
        $list->update($data);
        $list->logStatus($resubmitted ? 'resubmitted' : 'updated', $resubmitted ? $oldStatus : null, $resubmitted ? 'Pending' : null);

        return response()->json([
            'success' => true,
            'resubmitted' => $resubmitted,
            'data' => $list->fresh(['assignedTo', 'approvedBy', 'scheduledPlan']),
        ]);
    }

    /** Approve (note optional) or reject (note required); any Marketing user, kept in the history. */
    public function approve(Request $request, $id)
    {
        $this->authorizeManage();

        $list = ContentList::findOrFail($id);
        $payload = $request->validate([
            'approval_status' => 'required|string|in:Approved,Rejected',
            'approval_notes' => 'nullable|string|max:2000|required_if:approval_status,Rejected',
        ], [
            'approval_notes.required_if' => 'Alasan reject wajib diisi.',
        ]);

        if ($list->scheduled_plan_id && $list->scheduledPlan) {
            return response()->json(['message' => 'Ide ini sudah dijadwalkan; ubah lewat Content Plan.'], 422);
        }

        $approved = $payload['approval_status'] === 'Approved';
        $oldStatus = $list->approval_status ?: 'Pending';
        $list->update([
            'approval_status' => $payload['approval_status'],
            // latest decision note, shown on the card
            'approval_notes' => $payload['approval_notes'] ?? null,
            'approved_by' => $approved ? Auth::id() : null,
            'approved_at' => $approved ? now() : null,
        ]);
        $list->logStatus($approved ? 'approve' : 'reject', $oldStatus, $payload['approval_status'], $payload['approval_notes'] ?? null);

        return response()->json(['success' => true]);
    }

    public function schedulePayload($id)
    {
        $list = ContentList::findOrFail($id);

        if (strtolower($list->approval_status ?? 'pending') !== 'approved') {
            return response()->json(['message' => 'Ide konten ini belum di-approve.'], 422);
        }

        if ($list->scheduled_plan_id && $list->scheduledPlan) {
            return response()->json(['message' => 'Ide konten ini sudah dijadwalkan.'], 422);
        }

        return response()->json([
            'id' => $list->id,
            'judul' => $list->judul,
            'brand' => $list->brand,
            'platform' => $list->platform,
            'assigned_to' => $list->assigned_to,
            'jenis_konten' => $list->jenis_konten,
            'konten_pilar' => $list->konten_pilar,
            'link_referensi' => $list->link_referensi,
            'gambar_referensi' => $list->gambar_referensi,
            'gambar_url' => PublicFile::url($list->gambar_referensi),
            'catatan' => $list->catatan,
        ]);
    }

    public function destroy($id)
    {
        $this->authorizeManage();

        ContentList::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    protected function validatePayload(Request $request)
    {
        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'brand' => 'nullable|array',
            'platform' => 'required|array',
            'jenis_konten' => 'required|array',
            'konten_pilar' => 'nullable|string|in:' . implode(',', ContentPlanController::KONTEN_PILAR),
            'link_referensi' => 'nullable|string',
            'gambar_referensi' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'remove_gambar' => 'nullable|boolean',
            'catatan' => 'nullable|string',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $data['brand'] = isset($data['brand']) ? array_values($data['brand']) : null;
        $data['platform'] = array_values($data['platform']);
        $data['jenis_konten'] = array_values($data['jenis_konten']);

        if ($request->hasFile('gambar_referensi')) {
            $data['gambar_referensi'] = $request->file('gambar_referensi')->store('uploads/content_list_visual_references', 'public');
        } elseif ($request->boolean('remove_gambar')) {
            $data['gambar_referensi'] = null;
        } else {
            unset($data['gambar_referensi']);
        }
        unset($data['remove_gambar']);

        return $data;
    }
}
