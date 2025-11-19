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

    /**
     * Dashboard index showing per-division stats (separate page)
     */
    public function dashboard()
    {
        return view('hrd.joblist.dashboard');
    }

    public function data(Request $request)
    {
        $query = JobList::with(['division', 'creator'])->select('hrd_joblists.*');
        // Restrict visibility based on user role:
        // - Users with roles Hrd, Admin, Manager see all records
        // - Users with role Employee only see records from their division
        $user = Auth::user();
        if ($user) {
            if ($user->hasAnyRole(['Hrd','Admin','Manager'])) {
                // no restriction
            } elseif ($user->hasAnyRole('Employee')) {
                $divisionId = optional($user->employee)->division_id;
                if ($divisionId) {
                    $query->where('division_id', $divisionId);
                } else {
                    // If employee has no division, return no rows
                    $query->whereRaw('1 = 0');
                }
            }
        }
        // Apply due_date range filter when provided (start_date, end_date expected as YYYY-MM-DD)
        $start = $request->get('start_date');
        $end = $request->get('end_date');
        if ($start && $end) {
            try {
                $query->whereBetween('due_date', [$start, $end]);
            } catch (\Exception $e) {
                // ignore malformed dates
            }
        }
        // apply status filter if provided and valid
        $status = $request->get('status');
        $validStatuses = ['progress','done','canceled'];
        if ($status && in_array($status, $validStatuses)) {
            $query->where('status', $status);
        }
        // apply division filter if provided
        $division = $request->get('division_id');
        if ($division && is_numeric($division)) {
            $query->where('division_id', $division);
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
            ->addColumn('status_control', function ($row) {
                $status = $row->status;
                $opts = ['progress' => 'Progress', 'done' => 'Done', 'canceled' => 'Canceled'];
                // badge class mapping
                switch ($status) {
                    case 'done':
                        $badgeClass = 'badge-success';
                        break;
                    case 'canceled':
                        $badgeClass = 'badge-danger';
                        break;
                    case 'progress':
                    default:
                        $badgeClass = 'badge-info';
                }
                $label = ucfirst(str_replace('_', ' ', $status));

                $html = '<div class="d-flex align-items-center">';
                $html .= '<span class="badge ' . $badgeClass . ' mr-2 status-inline-badge">' . $label . '</span>';
                // hide select initially; badge is shown. Clicking badge will reveal select.
                $html .= '<select style="display:none; min-width:120px;" class="form-control form-control-sm job-status-select" data-id="' . $row->id . '">';
                foreach ($opts as $k => $v) {
                    $sel = ($k === $status) ? ' selected' : '';
                    $html .= '<option value="' . $k . '"' . $sel . '>' . $v . '</option>';
                }
                $html .= '</select>';
                $html .= '</div>';
                return $html;
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
            ->setRowAttr([
                'class' => function ($row) {
                    try {
                        if ($row->creator && method_exists($row->creator, 'hasRole') && $row->creator->hasRole(['ceo','Ceo','CEO'])) {
                            return 'table-warning';
                        }
                    } catch (\Throwable $e) {
                        // ignore role check failures
                    }
                    return '';
                }
            ])
            ->rawColumns(['actions','status_badge','status_control','priority_badge','due_date_display'])
            ->make(true);
    }

    /**
     * Inline update for single fields (used by DataTable inline controls)
     */
    public function inlineUpdate(Request $request, $id)
    {
        $job = JobList::findOrFail($id);
        $v = Validator::make($request->all(), [
            'status' => 'required|string|in:progress,done,canceled',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        $job->status = $request->input('status');
        $job->save();
        return response()->json(['success' => true, 'data' => $job]);
    }

    /**
     * Return per-division summary counts (ongoing/done) filtered by due_date range.
     */
    public function summary(Request $request)
    {
        $start = $request->get('start_date');
        $end = $request->get('end_date');
        $divisions = \App\Models\HRD\Division::all();
        $result = [];
        foreach ($divisions as $d) {
            $base = JobList::where('division_id', $d->id);
            if ($start && $end) {
                try {
                    $base->whereBetween('due_date', [$start, $end]);
                } catch (\Exception $e) {
                    // ignore
                }
            }
            $ongoing = (clone $base)->where('status', 'progress')->count();
            $done = (clone $base)->where('status', 'done')->count();
            $canceled = (clone $base)->where('status', 'canceled')->count();
            $result[] = [
                'division_id' => $d->id,
                'division_name' => $d->name,
                'ongoing' => $ongoing,
                'done' => $done,
                'canceled' => $canceled,
            ];
        }
        return response()->json(['success' => true, 'data' => $result]);
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
        if (empty($data['priority'])) $data['priority'] = 'normal';
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
        if (empty($data['priority'])) $data['priority'] = 'normal';
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
