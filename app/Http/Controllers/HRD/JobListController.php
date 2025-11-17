<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\JobList;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class JobListController extends Controller
{
    public function index()
    {
        $divisions = \App\Models\HRD\Division::all();
        return view('hrd.joblist.index', compact('divisions'));
    }

    public function data(Request $request)
    {
        $query = JobList::with(['division', 'creator'])->select('hrd_joblists.*');
        // apply status filter if provided and valid
        $status = $request->get('status');
        $validStatuses = ['progress','done','canceled'];
        if ($status && in_array($status, $validStatuses)) {
            $query->where('status', $status);
        }
        return DataTables::of($query)
            ->addColumn('division_name', function ($row) {
                return $row->division?->name;
            })
            ->addColumn('creator_name', function ($row) {
                return $row->creator?->name;
            })
            ->addColumn('status_badge', function ($row) {
                $status = $row->status;
                $label = ucfirst(str_replace('_', ' ', $status));
                switch ($status) {
                    case 'done':
                        $class = 'badge-success';
                        break;
                    case 'canceled':
                        $class = 'badge-danger';
                        break;
                    case 'progress':
                    default:
                        $class = 'badge-info';
                }
                return '<span class="badge ' . $class . '">' . $label . '</span>';
            })
            ->addColumn('priority_badge', function ($row) {
                $p = $row->priority;
                $label = ucfirst(str_replace('_', ' ', $p));
                switch ($p) {
                    case 'very_important':
                        $class = 'badge-danger';
                        break;
                    case 'important':
                        $class = 'badge-warning';
                        break;
                    case 'normal':
                        $class = 'badge-info';
                        break;
                    case 'low':
                    default:
                        $class = 'badge-secondary';
                }
                return '<span class="badge ' . $class . '">' . $label . '</span>';
            })
            ->addColumn('due_date_display', function ($row) {
                if (empty($row->due_date)) return '';
                // format date as: 1 Januari 2025 (Indonesian month names)
                try {
                    $dt = Carbon::parse($row->due_date);
                } catch (\Exception $e) {
                    return $row->due_date;
                }
                $months = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                $formatted = $dt->day . ' ' . ($months[(int)$dt->format('n')] ?? $dt->format('F')) . ' ' . $dt->year;
                $today = Carbon::today();
                // if due date is before today AND status is 'progress' (on going), show blinking warning
                if ($dt->lt($today) && ($row->status ?? '') === 'progress') {
                    $warning = ' <span class="text-danger blink" title="Terlewat hari ini">&#9888; Terlewat</span>';
                    return $formatted . $warning;
                }
                return $formatted;
            })
            ->addColumn('actions', function ($row) {
                return view('hrd.joblist._actions', compact('row'))->render();
            })
            ->rawColumns(['actions','status_badge','priority_badge','due_date_display'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:progress,done,canceled',
            'priority' => 'nullable|string|in:low,normal,important,very_important',
            'division_id' => 'nullable|integer',
            'due_date' => 'nullable|date',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $data = $v->validated();
        // apply defaults if not present
        if (empty($data['status'])) $data['status'] = 'progress';
        if (empty($data['priority'])) $data['priority'] = 'low';
        $data['created_by'] = Auth::id();
        $job = JobList::create($data);
        return response()->json(['success' => true, 'data' => $job]);
    }

    public function show($id)
    {
        $job = JobList::with(['division','creator'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $job]);
    }

    public function update(Request $request, $id)
    {
        $job = JobList::findOrFail($id);
        $v = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:progress,done,canceled',
            'priority' => 'nullable|string|in:low,normal,important,very_important',
            'division_id' => 'nullable|integer',
            'due_date' => 'nullable|date',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        $data = $v->validated();
        if (empty($data['status'])) $data['status'] = 'progress';
        if (empty($data['priority'])) $data['priority'] = 'low';
        $job->update($data);
        return response()->json(['success' => true, 'data' => $job]);
    }

    public function destroy($id)
    {
        $job = JobList::findOrFail($id);
        $job->delete();
        return response()->json(['success' => true]);
    }
}
