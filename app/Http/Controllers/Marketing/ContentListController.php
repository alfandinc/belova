<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\ContentList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class ContentListController extends Controller
{
    public function stats()
    {
        $stats = ContentList::query()
            ->selectRaw("SUM(CASE WHEN approval_status = 'Pending' AND scheduled_plan_id IS NULL THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw("SUM(CASE WHEN approval_status = 'Approved' AND scheduled_plan_id IS NULL THEN 1 ELSE 0 END) as approved_count")
            ->selectRaw("SUM(CASE WHEN approval_status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count")
            ->selectRaw("SUM(CASE WHEN scheduled_plan_id IS NOT NULL THEN 1 ELSE 0 END) as scheduled_count")
            ->first();

        return response()->json([
            'pending' => (int) ($stats->pending_count ?? 0),
            'approved' => (int) ($stats->approved_count ?? 0),
            'rejected' => (int) ($stats->rejected_count ?? 0),
            'scheduled' => (int) ($stats->scheduled_count ?? 0),
        ]);
    }

    public function datatable(Request $request)
    {
        $query = ContentList::with(['assignedTo', 'approvedBy', 'scheduledPlan']);
        $isManager = Auth::check() && Auth::user()->hasAnyRole(['Manager', 'manager']);

        if ($request->filled('filter_status')) {
            $status = strtolower((string) $request->filter_status);

            if ($status === 'scheduled') {
                $query->whereNotNull('scheduled_plan_id');
            } elseif ($status === 'approved') {
                $query->where('approval_status', 'Approved')
                    ->whereNull('scheduled_plan_id');
            } elseif ($status === 'pending') {
                $query->where('approval_status', 'Pending')
                    ->whereNull('scheduled_plan_id');
            } elseif ($status === 'rejected') {
                $query->where('approval_status', 'Rejected');
            }
        } elseif ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($isManager) {
            $query->orderByRaw("CASE
                WHEN approval_status = 'Pending' AND scheduled_plan_id IS NULL THEN 0
                WHEN approval_status = 'Approved' AND scheduled_plan_id IS NULL THEN 1
                WHEN approval_status = 'Rejected' THEN 2
                WHEN scheduled_plan_id IS NOT NULL THEN 3
                ELSE 4
            END");
        } else {
            $query->orderByRaw("CASE
                WHEN approval_status = 'Approved' AND scheduled_plan_id IS NULL THEN 0
                WHEN approval_status = 'Rejected' THEN 1
                WHEN approval_status = 'Pending' AND scheduled_plan_id IS NULL THEN 2
                WHEN scheduled_plan_id IS NOT NULL THEN 3
                ELSE 4
            END");
        }

        return DataTables::of($query)
            ->editColumn('judul', function ($row) {
                $title = '<strong>' . e($row->judul ?? '') . '</strong>';
                if (!$row->konten_pilar) {
                    return $title;
                }

                $map = [
                    'Edukasi' => ['cls' => 'primary', 'style' => ''],
                    'Awareness' => ['cls' => 'warning', 'style' => ''],
                    'Engagement/Interaktif' => ['cls' => 'success', 'style' => ''],
                    'Promo/Testimoni' => ['cls' => 'danger', 'style' => ''],
                    'Lifestyle/Tips' => ['cls' => 'info', 'style' => ''],
                ];

                $item = $map[$row->konten_pilar] ?? ['cls' => 'secondary', 'style' => ''];
                $badge = '<div class="mt-1"><span class="badge badge-' . $item['cls'] . '" style="' . $item['style'] . '">' . e($row->konten_pilar) . '</span></div>';

                return $title . $badge;
            })
            ->addColumn('assigned_to_name', function ($row) {
                return $row->assignedTo ? $row->assignedTo->name : '';
            })
            ->addColumn('approved_by_name', function ($row) {
                return $row->approvedBy ? $row->approvedBy->name : '';
            })
            ->addColumn('scheduled_plan_title', function ($row) {
                return $row->scheduledPlan ? $row->scheduledPlan->judul : '';
            })
            ->addColumn('status_badge', function ($row) {
                $status = strtolower($row->approval_status ?? 'pending');

                if ($row->scheduled_plan_id && $row->scheduledPlan) {
                    $status = 'scheduled';
                }

                $map = [
                    'pending' => ['bg' => '#ffc107', 'color' => '#212529', 'label' => 'Pending'],
                    'approved' => ['bg' => '#28a745', 'color' => '#ffffff', 'label' => 'Approved'],
                    'scheduled' => ['bg' => '#17a2b8', 'color' => '#ffffff', 'label' => 'Scheduled'],
                    'rejected' => ['bg' => '#dc3545', 'color' => '#ffffff', 'label' => 'Rejected'],
                ];

                $item = $map[$status] ?? ['bg' => '#6c757d', 'color' => '#ffffff', 'label' => ucfirst($row->approval_status ?? 'Unknown')];

                $meta = [];
                if ($row->approved_at) {
                    $meta[] = '<div class="small text-muted mt-1">Approved At: ' . e($row->approved_at->format('d/m/Y H:i')) . '</div>';
                }
                if ($row->approvedBy) {
                    $meta[] = '<div class="small text-muted">Approved By: ' . e($row->approvedBy->name) . '</div>';
                }

                return '<div><span class="badge" style="background:' . $item['bg'] . ';color:' . $item['color'] . ';">' . e($item['label']) . '</span>' . implode('', $meta) . '</div>';
            })
            ->addColumn('action', function ($row) {
                $buttons = [];
                $canApprove = Auth::check() && Auth::user()->hasAnyRole(['Manager', 'manager']);

                $status = strtolower($row->approval_status ?? 'pending');
                $isScheduled = (bool) ($row->scheduled_plan_id && $row->scheduledPlan);

                if ($canApprove && $status !== 'approved' && !$isScheduled) {
                    $buttons[] = '<button type="button" class="btn btn-sm btn-success btn-content-list-approve" data-id="' . $row->id . '"><i class="fas fa-check"></i></button>';
                }

                if ($canApprove && $status !== 'rejected' && !$isScheduled) {
                    $buttons[] = '<button type="button" class="btn btn-sm btn-warning btn-content-list-reject" data-id="' . $row->id . '"><i class="fas fa-times"></i></button>';
                }

                if ($status === 'approved' && !$isScheduled) {
                    $buttons[] = '<button type="button" class="btn btn-sm btn-primary btn-content-list-schedule" data-id="' . $row->id . '"><i class="fas fa-calendar-plus"></i></button>';
                }

                $buttons[] = '<button type="button" class="btn btn-sm btn-info btn-content-list-edit" data-id="' . $row->id . '"><i class="fas fa-edit"></i></button>';
                $buttons[] = '<button type="button" class="btn btn-sm btn-danger btn-content-list-delete" data-id="' . $row->id . '"><i class="fas fa-trash"></i></button>';

                return '<div class="btn-group btn-group-sm" role="group">' . implode('', $buttons) . '</div>';
            })
            ->editColumn('brand', function ($row) {
                if (!$row->brand || !is_array($row->brand)) {
                    return '';
                }

                return collect($row->brand)->map(function ($brand) {
                    return '<span class="badge badge-light border mr-1">' . e($brand) . '</span>';
                })->implode(' ');
            })
            ->editColumn('platform', function ($row) {
                if (!$row->platform || !is_array($row->platform)) {
                    return '';
                }

                return collect($row->platform)->map(function ($platform) {
                    return '<span class="badge badge-dark mr-1">' . e($platform) . '</span>';
                })->implode(' ');
            })
            ->editColumn('jenis_konten', function ($row) {
                if (!$row->jenis_konten || !is_array($row->jenis_konten)) {
                    return '';
                }

                return collect($row->jenis_konten)->map(function ($jenisKonten) {
                    return '<span class="badge badge-primary mr-1">' . e($jenisKonten) . '</span>';
                })->implode(' ');
            })
                ->rawColumns(['judul', 'brand', 'platform', 'jenis_konten', 'status_badge', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $list = ContentList::create($data);

        return response()->json(['success' => true, 'data' => $list]);
    }

    public function show($id)
    {
        return response()->json(ContentList::with(['assignedTo', 'approvedBy', 'scheduledPlan'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $list = ContentList::findOrFail($id);
        $data = $this->validatePayload($request, $list->approval_status);
        $list->update($data);

        return response()->json(['success' => true, 'data' => $list->fresh(['assignedTo', 'approvedBy', 'scheduledPlan'])]);
    }

    public function approve(Request $request, $id)
    {
        abort_unless(Auth::check() && Auth::user()->hasAnyRole(['Manager', 'manager']), 403);

        $list = ContentList::findOrFail($id);
        $payload = $request->validate([
            'approval_status' => 'required|string|in:Pending,Approved,Rejected',
        ]);

        $data = [
            'approval_status' => $payload['approval_status'],
            'approval_notes' => null,
        ];

        if ($payload['approval_status'] === 'Approved') {
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now();
        } else {
            $data['approved_by'] = null;
            $data['approved_at'] = null;
        }

        $list->update($data);

        return response()->json(['success' => true, 'data' => $list->fresh(['assignedTo', 'approvedBy', 'scheduledPlan'])]);
    }

    public function schedulePayload($id)
    {
        $list = ContentList::findOrFail($id);

        if (strtolower($list->approval_status ?? 'pending') !== 'approved') {
            return response()->json(['message' => 'Content list belum di-approve.'], 422);
        }

        if ($list->scheduled_plan_id) {
            return response()->json(['message' => 'Content list ini sudah dijadwalkan.'], 422);
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
            'catatan' => $list->catatan,
        ]);
    }

    public function destroy($id)
    {
        $list = ContentList::findOrFail($id);
        $list->delete();

        return response()->json(['success' => true]);
    }

    protected function validatePayload(Request $request, $defaultApprovalStatus = 'Pending')
    {
        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'brand' => 'nullable|array',
            'platform' => 'required|array',
            'jenis_konten' => 'required|array',
            'konten_pilar' => 'nullable|string|in:Edukasi,Awareness,Engagement/Interaktif,Promo/Testimoni,Lifestyle/Tips',
            'link_referensi' => 'nullable|string',
            'catatan' => 'nullable|string',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $data['brand'] = isset($data['brand']) ? array_values($data['brand']) : null;
        $data['platform'] = array_values($data['platform']);
        $data['jenis_konten'] = array_values($data['jenis_konten']);
        $data['approval_status'] = $defaultApprovalStatus ?: 'Pending';

        return $data;
    }
}