<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\JobList;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $query = JobList::with(['division', 'divisions', 'creator', 'updater', 'dibacaBy'])->select('hrd_joblists.*');
        // Restrict visibility based on user role:
        // - Users with roles Hrd, Admin, Manager see all records
        // - Users with role Employee only see records from their division
        $user = Auth::user();
        if ($user) {
            if ($user->hasAnyRole(['Hrd','Admin','Manager'])) {
                // no restriction (admins/hrd/managers keep original full access)
            } elseif ($user->hasAnyRole('Employee')) {
                $divisionId = optional($user->employee)->division_id;
                $isManager = $user->hasAnyRole(['Manager','manager']);
                if ($divisionId) {
                    $query->where(function($q) use ($divisionId, $isManager) {
                        // Jobs visible to all employees in the division (for_manager = false)
                        $q->where(function($qa) use ($divisionId) {
                            $qa->where('for_manager', false)
                               ->where(function($qb) use ($divisionId) {
                                   $qb->where('all_divisions', true)
                                      ->orWhere('division_id', $divisionId)
                                      ->orWhereHas('divisions', function($qq) use ($divisionId) {
                                          $qq->where('hrd_division.id', $divisionId);
                                      });
                               });
                        });

                        // If the user is a manager, also include jobs marked for_manager in their division
                        if ($isManager) {
                            $q->orWhere(function($qc) use ($divisionId) {
                                $qc->where('for_manager', true)
                                   ->where(function($qd) use ($divisionId) {
                                       $qd->where('all_divisions', true)
                                          ->orWhere('division_id', $divisionId)
                                          ->orWhereHas('divisions', function($qq) use ($divisionId) {
                                              $qq->where('hrd_division.id', $divisionId);
                                          });
                                   });
                            });
                        }
                    });
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
        // apply status filter if provided and valid. If no explicit status is chosen,
        // honor the `hide_done` flag to show only delegated/progress when requested.
        $status = $request->get('status');
        $hideDone = $request->get('hide_done');
        $validStatuses = ['progress','done','canceled','delegated'];
        if ($status && in_array($status, $validStatuses)) {
            $query->where('status', $status);
        } elseif ($hideDone) {
            // when hide_done is truthy and no explicit status filter set, show delegated + progress
            $query->whereIn('status', ['delegated', 'progress']);
        }
        // apply division filter if provided (should include all_divisions and pivot)
        $division = $request->get('division_id');
        if ($division && is_numeric($division)) {
            $query->where(function($q) use ($division) {
                $q->where('all_divisions', true)
                  ->orWhere('division_id', $division)
                  ->orWhereHas('divisions', function($qq) use ($division) {
                      $qq->where('hrd_division.id', $division);
                  });
            });
        }
        // apply for_manager filter if explicitly provided
        // expected values: '' (all) | '1' (manager only) | '0' (non-manager only)
        $forManager = $request->get('for_manager');
        if ($forManager !== null && $forManager !== '') {
            // cast to integer (0 or 1)
            $val = (int) $forManager;
            $query->where('for_manager', $val);
        }

        // Order by priority weight so that 'very_important' items appear first.
        // Within each priority group, place overdue items (due_date < today) first
        // and sort overdue items by most-recently-overdue (yesterday = top) using DATEDIFF(CURDATE(), due_date).
        // Upcoming items are sorted by nearest future date. Null due_date values are pushed to the end.
        $query->orderByRaw(
            "CASE priority WHEN 'very_important' THEN 3 WHEN 'important' THEN 2 WHEN 'normal' THEN 1 ELSE 0 END DESC, " .
            "CASE WHEN due_date IS NULL THEN 2 WHEN due_date < CURDATE() THEN 0 ELSE 1 END ASC, " .
            "CASE WHEN due_date IS NULL THEN 999999 WHEN due_date < CURDATE() THEN DATEDIFF(CURDATE(), due_date) ELSE DATEDIFF(due_date, CURDATE()) END ASC, " .
            "due_date ASC"
        );
        return DataTables::of($query)
            ->addColumn('division_name', function ($row) {
                if (!empty($row->all_divisions)) return 'All Divisions';
                // prefer pivoted divisions if present
                $names = $row->divisions->pluck('name')->toArray();
                if (!empty($names)) return implode(', ', $names);
                return $row->division?->name;
            })
            ->addColumn('creator_name', function ($row) {
                return $row->creator?->name;
            })
            ->addColumn('updater_name', function ($row) {
                return $row->updater?->name;
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
                    // delegated should be info (blue)
                    case 'delegated':
                        $class = 'badge-info';
                        break;
                    // progress should be warning (yellow)
                    case 'progress':
                    default:
                        $class = 'badge-warning';
                }
                return '<span class="badge ' . $class . '">' . $label . '</span>';
            })
            ->addColumn('status_control', function ($row) {
                $status = $row->status;
                $opts = ['delegated' => 'Delegated', 'progress' => 'Progress', 'done' => 'Done', 'canceled' => 'Canceled'];
                // badge class mapping
                switch ($status) {
                    case 'done':
                        $badgeClass = 'badge-success';
                        break;
                    case 'canceled':
                        $badgeClass = 'badge-danger';
                        break;
                    // delegated should be info (blue)
                    case 'delegated':
                        $badgeClass = 'badge-info';
                        break;
                    // progress should be warning (yellow)
                    case 'progress':
                    default:
                        $badgeClass = 'badge-warning';
                }
                $label = ucfirst(str_replace('_', ' ', $status));

                // container - keep the badge and select in a horizontal row,
                // and place the "dibaca" info in a block below the row
                $html = '<div>';
                $html .= '<div class="d-flex align-items-center">';
                $html .= '<span class="badge ' . $badgeClass . ' mr-2 status-inline-badge">' . $label . '</span>';
                // hide select initially; badge is shown. Clicking badge will reveal select.
                $html .= '<select style="display:none; min-width:120px;" class="form-control form-control-sm job-status-select" data-id="' . $row->id . '">';
                foreach ($opts as $k => $v) {
                    $sel = ($k === $status) ? ' selected' : '';
                    $html .= '<option value="' . $k . '"' . $sel . '>' . $v . '</option>';
                }
                $html .= '</select>';
                $html .= '</div>'; // end d-flex
                // show contextual small text below the badge
                if (($row->status ?? '') === 'done') {
                    // show when the job was completed using updated_at
                    $when = '';
                    try {
                        if ($row->updated_at) {
                            $when = $row->updated_at->format('d-m-Y H:i');
                        }
                    } catch (\Throwable $e) {
                        $when = (string) ($row->updated_at ?? '');
                    }
                    if (!empty($when)) {
                        $html .= '<div class="mt-1"><small class="text-muted">Selesai pada</small><div><small class="text-muted">' . htmlspecialchars($when) . '</small></div></div>';
                    }
                } else {
                    // default: show who marked it read (dibaca)
                    if (!empty($row->dibacaBy)) {
                        $when = '';
                        try {
                            if ($row->dibaca_at) {
                                $when = $row->dibaca_at->format('d-m-Y H:i');
                            }
                        } catch (\Throwable $e) {
                            $when = (string) ($row->dibaca_at ?? '');
                        }
                        $html .= '<div class="mt-1">';
                        $html .= '<small class="text-muted">Dibaca oleh ' . htmlspecialchars($row->dibacaBy->name) . '</small>';
                        if (!empty($when)) {
                            $html .= '<div><small class="text-muted">' . htmlspecialchars($when) . '</small></div>';
                        }
                        $html .= '</div>';
                    }
                }
                $html .= '</div>'; // wrapper
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
                // Show blinking warning when overdue for ongoing assignments
                // Applies to 'progress' and 'delegated' statuses
                if ($dt->lt($today) && in_array(($row->status ?? ''), ['progress', 'delegated'])) {
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
            'status' => 'required|string|in:progress,done,canceled,delegated',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        $job->status = $request->input('status');
        $job->save();
        return response()->json(['success' => true, 'data' => $job]);
    }

    /**
     * Mark a joblist item as read by the current user
     */
    public function markRead(Request $request, $id)
    {
        $job = JobList::findOrFail($id);
        $job->dibaca_by = Auth::id();
        $job->dibaca_at = now();
        // When a user marks as read, advance status to 'progress'
        $job->status = 'progress';
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
            // include jobs targeted to all divisions, jobs with division_id, and jobs linked via pivot
            $baseSelector = function() use ($d) {
                return JobList::where(function($q) use ($d) {
                    $q->where('all_divisions', true)
                      ->orWhere('division_id', $d->id)
                      ->orWhereHas('divisions', function($qq) use ($d) { $qq->where('hrd_division.id', $d->id); });
                });
            };

            // For 'ongoing' (progress) items we filter by due_date range when provided
            $baseDue = $baseSelector();
            if ($start && $end) {
                try {
                    $baseDue->whereBetween('due_date', [$start, $end]);
                } catch (\Exception $e) {
                    // ignore malformed dates
                }
            }
            $ongoing = (clone $baseDue)->where('status', 'progress')->count();

            // For 'done' and 'canceled' items, it's more accurate to consider when the record was updated
            // (i.e. when status changed). Use updated_at range if provided.
            $baseStatus = $baseSelector();
            if ($start && $end) {
                try {
                    $baseStatus->whereBetween('updated_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
                } catch (\Exception $e) {
                    // ignore malformed dates
                }
            }
            $done = (clone $baseStatus)->where('status', 'done')->count();
            $canceled = (clone $baseStatus)->where('status', 'canceled')->count();
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
            'status' => 'nullable|string|in:progress,done,canceled,delegated',
            'priority' => 'nullable|string|in:low,normal,important,very_important',
            'division_id' => 'nullable|integer', // legacy single-division support
            'divisions' => 'nullable|array',
            'divisions.*' => 'nullable|integer|exists:hrd_division,id',
            'all_divisions' => 'sometimes|boolean',
            'for_manager' => 'sometimes|boolean',
            'due_date' => 'nullable|date',
            'dokumen.*' => 'sometimes|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx|max:10240',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $data = $v->validated();
        // apply defaults if not present
        if (empty($data['status'])) $data['status'] = 'delegated';
        if (empty($data['priority'])) $data['priority'] = 'normal';
        $data['created_by'] = Auth::id();
        // handle all_divisions and for_manager flag
        $data['all_divisions'] = isset($data['all_divisions']) ? (bool)$data['all_divisions'] : false;
        $data['for_manager'] = isset($data['for_manager']) ? (bool)$data['for_manager'] : false;
        $job = JobList::create($data);

        // assign divisions if provided (or legacy division_id)
        if (!empty($data['all_divisions'])) {
            $job->assignDivisions('all');
        } else {
            if (!empty($data['divisions'])) {
                $job->assignDivisions($data['divisions']);
            } elseif (!empty($data['division_id'])) {
                $job->assignDivisions([$data['division_id']]);
            }
        }
        // Handle uploaded documents on create
        if ($request->hasFile('dokumen')) {
            $uploaded = $request->file('dokumen');
            $stored = $job->documents ?? [];
            foreach ($uploaded as $f) {
                if (!$f->isValid()) continue;
                $ext = $f->getClientOriginalExtension();
                $name = pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = Str::slug(substr($name, 0, 50));
                $filename = $safe . '-' . time() . '-' . Str::random(6) . '.' . $ext;
                $path = 'joblist_documents/' . $job->id . '/';
                $f->storeAs('public/' . $path, $filename);
                $stored[] = $path . $filename;
            }
            $job->documents = $stored;
            $job->save();
        }
        return response()->json(['success' => true, 'data' => $job]);
    }

    public function show($id)
    {
        $job = JobList::with(['division','divisions','creator'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $job]);
    }

    /**
     * Save notes for a joblist entry via AJAX
     */
    public function saveNotes(Request $request, $id)
    {
        $job = JobList::findOrFail($id);
        $v = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:4000',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        $job->notes = $request->input('notes');
        $job->updated_by = Auth::id();
        $job->save();
        return response()->json(['success' => true, 'data' => $job]);
    }

    public function update(Request $request, $id)
    {
        $job = JobList::findOrFail($id);
        $v = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:progress,done,canceled,delegated',
            'priority' => 'nullable|string|in:low,normal,important,very_important',
            'division_id' => 'nullable|integer', // legacy
            'divisions' => 'nullable|array',
            'divisions.*' => 'nullable|integer|exists:hrd_division,id',
            'all_divisions' => 'sometimes|boolean',
            'for_manager' => 'sometimes|boolean',
            'due_date' => 'nullable|date',
            'dokumen.*' => 'sometimes|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx|max:10240',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        $data = $v->validated();
        if (empty($data['status'])) $data['status'] = 'delegated';
        if (empty($data['priority'])) $data['priority'] = 'normal';
        // handle all_divisions and for_manager
        $data['all_divisions'] = isset($data['all_divisions']) ? (bool)$data['all_divisions'] : false;
        $data['for_manager'] = isset($data['for_manager']) ? (bool)$data['for_manager'] : false;
        // set updated_by
        $data['updated_by'] = Auth::id();
        $job->update($data);

        if (!empty($data['all_divisions'])) {
            $job->assignDivisions('all');
        } else {
            if (!empty($data['divisions'])) {
                $job->assignDivisions($data['divisions']);
            } elseif (!empty($data['division_id'])) {
                $job->assignDivisions([$data['division_id']]);
            } else {
                // if no divisions provided, detach existing and clear flag
                $job->assignDivisions(null);
            }
        }
        // Handle uploaded documents (if any) when updating
        if ($request->hasFile('dokumen')) {
            $uploaded = $request->file('dokumen');
            $stored = $job->documents ?? [];
            foreach ($uploaded as $f) {
                if (!$f->isValid()) continue;
                $ext = $f->getClientOriginalExtension();
                $name = pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = Str::slug(substr($name, 0, 50));
                $filename = $safe . '-' . time() . '-' . Str::random(6) . '.' . $ext;
                $path = 'joblist_documents/' . $job->id . '/';
                $f->storeAs('public/' . $path, $filename);
                $stored[] = $path . $filename;
            }
            $job->documents = $stored;
            $job->save();
        }
        return response()->json(['success' => true, 'data' => $job]);
    }

    public function destroy($id)
    {
        $job = JobList::findOrFail($id);
        $job->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Upload additional documents for a joblist (used after marking Done inline)
     */
    public function uploadDocuments(Request $request, $id)
    {
        $job = JobList::findOrFail($id);
        $v = Validator::make($request->all(), [
            'dokumen.*' => 'required|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx|max:10240',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        $stored = $job->documents ?? [];
        if ($request->hasFile('dokumen')) {
            foreach ($request->file('dokumen') as $f) {
                if (!$f->isValid()) continue;
                $ext = $f->getClientOriginalExtension();
                $name = pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = Str::slug(substr($name, 0, 50));
                $filename = $safe . '-' . time() . '-' . Str::random(6) . '.' . $ext;
                $path = 'joblist_documents/' . $job->id . '/';
                $f->storeAs('public/' . $path, $filename);
                $stored[] = $path . $filename;
            }
            $job->documents = $stored;
            $job->save();
        }
        return response()->json(['success' => true, 'documents' => $job->documents]);
    }

    /**
     * Serve a stored document for a joblist entry.
     * Uses the public disk storage path and streams or downloads the file via Laravel.
     */
    public function downloadDocument(Request $request, $id, $index)
    {
        $job = JobList::findOrFail($id);
        $docs = $job->documents ?? [];
        if (!is_numeric($index)) {
            abort(404);
        }
        $i = (int) $index;
        if (!isset($docs[$i])) {
            abort(404);
        }
        $raw = $docs[$i];
        // Normalize stored path which may be stored as:
        // - "joblist_documents/4/file.png"
        // - "/storage/joblist_documents/4/file.png"
        // - "http://host/storage/joblist_documents/4/file.png"
        // - or even a full public path
        $path = $raw;
        // strip URL if present
        if (preg_match('#^https?://#i', $path)) {
            $u = parse_url($path);
            $path = $u['path'] ?? $path;
        }
        // remove leading /storage/ if present
        if (strpos($path, '/storage/') === 0) {
            $path = substr($path, strlen('/storage/'));
            $path = ltrim($path, '/');
        }
        // remove any leading slash
        $path = ltrim($path, '/');

        // first try storage/app/public
        if (Storage::disk('public')->exists($path)) {
            $full = storage_path('app/public/' . $path);
            $mime = @mime_content_type($full) ?: 'application/octet-stream';
            if (strpos($mime, 'image/') === 0 || $mime === 'application/pdf') {
                return response()->file($full, ['Content-Type' => $mime]);
            }
            return response()->download($full);
        }

        // next try direct public path (in case files were saved to public/...)
        $publicCandidate = public_path($path);
        if (file_exists($publicCandidate)) {
            $mime = @mime_content_type($publicCandidate) ?: 'application/octet-stream';
            if (strpos($mime, 'image/') === 0 || $mime === 'application/pdf') {
                return response()->file($publicCandidate, ['Content-Type' => $mime]);
            }
            return response()->download($publicCandidate);
        }

        // additional fallback: some installs write to storage/app/private/public/... (observed in this environment)
        $privateCandidate = storage_path('app/private/public/' . $path);
        if (file_exists($privateCandidate)) {
            $mime = @mime_content_type($privateCandidate) ?: 'application/octet-stream';
            if (strpos($mime, 'image/') === 0 || $mime === 'application/pdf') {
                return response()->file($privateCandidate, ['Content-Type' => $mime]);
            }
            return response()->download($privateCandidate);
        }

        // not found
        abort(404);
    }
}
