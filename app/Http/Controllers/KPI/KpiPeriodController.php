<?php

namespace App\Http\Controllers\KPI;

use App\Http\Controllers\Controller;
use App\Models\KPI\KpiPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Models\KPI\KpiAssessment;
use App\Models\KPI\KpiScore;
use App\Models\HRD\Position as HRDPosition;
use App\Models\HRD\Employee as HRDEmployee;

class KpiPeriodController extends Controller
{
    public function index()
    {
        return view('kpi.periods.index');
    }

    public function data(Request $request)
    {
        $query = KpiPeriod::query()->orderBy('year', 'desc')->orderBy('month', 'desc');
        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('name', function (KpiPeriod $p) {
                return $p->period_name ?? '-';
            })
            ->addColumn('period', function (KpiPeriod $p) {
                try {
                    $monthName = \DateTime::createFromFormat('!m', $p->month)->format('F');
                } catch (\Exception $e) {
                    $monthName = $p->month ?? '-';
                }
                $year = $p->year ?? '';
                return trim($monthName . ' ' . $year);
            })
            ->addColumn('status', function (KpiPeriod $p) {
                $map = [
                    'draft' => 'secondary',
                    'started' => 'info',
                    'open' => 'success',
                    'closed' => 'danger',
                ];
                $cls = isset($map[$p->status]) ? $map[$p->status] : 'secondary';
                return '<span class="badge badge-' . $cls . '">' . e($p->status) . '</span>';
            })
            ->addColumn('started_at', function (KpiPeriod $p) { return optional($p->started_at)->format('Y-m-d H:i') ?? '-'; })
            ->addColumn('open_at', function (KpiPeriod $p) { return optional($p->open_at)->format('Y-m-d H:i') ?? '-'; })
            ->addColumn('closed_at', function (KpiPeriod $p) { return optional($p->closed_at)->format('Y-m-d H:i') ?? '-'; })
            ->addColumn('action', function (KpiPeriod $p) {
                $startBtn = '';
                if ($p->status === 'draft') {
                    $startBtn = '<button class="btn btn-success btn-start-period" data-id="' . $p->id . '">Start</button>';
                } elseif ($p->status === 'started') {
                    $startBtn = '<button class="btn btn-primary btn-open-period" data-id="' . $p->id . '">Open</button>';
                } elseif ($p->status === 'open') {
                    $startBtn = '<button class="btn btn-warning btn-close-period" data-id="' . $p->id . '">Close</button>';
                }

                $scoresBtn = '<button class="btn btn-secondary btn-scores-period" data-id="' . $p->id . '"><i class="fa fa-list"></i> Scores</button>';
                $iconEdit = '<button class="btn btn-info btn-edit-period" data-id="' . $p->id . '" title="Edit"><i class="fa fa-edit"></i></button>';
                $iconDelete = '<button class="btn btn-danger btn-delete-period" data-id="' . $p->id . '" title="Delete"><i class="fa fa-trash"></i></button>';

                return '<div class="btn-group btn-group-sm" role="group">'
                    . $startBtn
                    . $scoresBtn
                    . '</div>'
                    . '<div class="btn-group btn-group-sm ml-1" role="group">'
                    . $iconEdit
                    . $iconDelete
                    . '</div>';
            })
                ->rawColumns(['action','status'])
            ->make(true);
    }

            public function show(KpiPeriod $period)
            {
            return response()->json(['success' => true, 'data' => $period]);
            }

            public function details(Request $request, KpiPeriod $period)
            {
                // Aggregate assessments grouped by evaluatee: compute total score and counts
                $assessments = KpiAssessment::with(['evaluateeEmployee', 'evaluateePosition', 'scores.indicator', 'evaluatorEmployee'])
                    ->where('period_id', $period->id)
                    ->get()
                    ->groupBy('evaluatee_employee_id');

                $rows = [];
                foreach ($assessments as $evaluateeId => $group) {
                    $evaluatee = $group->first()->evaluateeEmployee;

                    $totalScore = 0.0;
                    $doneCount = 0;
                    $pendingCount = 0;
                    $directParentTotals = [];

                    foreach ($group as $assessment) {
                        $assessmentTotal = (float) $assessment->scores->sum('final_calculated_score');
                        if ($assessment->assessment_type === 'direct_parent') {
                            $directParentTotals[] = $assessmentTotal;
                        } else {
                            $totalScore += $assessmentTotal;
                        }
                        if ($assessment->status === 'done') $doneCount++; else $pendingCount++;
                    }

                    if (!empty($directParentTotals)) {
                        $totalScore += array_sum($directParentTotals) / count($directParentTotals);
                    }

                    // build evaluations details per evaluator
                    $evaluations = [];
                    foreach ($group as $assessment) {
                        $scoresArr = [];
                        foreach ($assessment->scores as $s) {
                            $scoresArr[] = [
                                'indicator_id' => $s->indicators_id,
                                'indicator_name' => optional($s->indicator)->indicator_name ?? ($s->ss_indicator_name ?? null),
                                'category_name' => optional(optional($s->indicator)->category)->category_name ?? ($s->ss_category_name ?? null) ?? 'Uncategorized',
                                'indicator_weight' => $s->indicator?->weight_percentage ?? $s->ss_indicator_weight_percentage ?? $s->indicator?->indicator_weight ?? null,
                                'score' => $s->score,
                                'final_calculated_score' => $s->final_calculated_score,
                                'notes' => $s->notes,
                            ];
                        }

                        $evaluations[] = [
                            'assessment_id' => $assessment->id,
                            'evaluator_id' => $assessment->evaluator_employee_id,
                            'evaluator_name' => optional($assessment->evaluatorEmployee)->nama ?? optional($assessment->evaluatorEmployee)->name ?? ('Position ' . ($assessment->evaluator_position_id ?? '')),
                            'status' => $assessment->status,
                            'total_score' => round((float) array_sum(array_map(fn($x) => (float) ($x['final_calculated_score'] ?? 0), $scoresArr)), 2),
                            'scores' => $scoresArr,
                        ];
                    }

                    $positionNames = $group->map(function ($assessment) {
                        return optional($assessment->evaluateePosition)->name;
                    })->filter()->unique()->values()->all();

                    $rows[] = [
                        'row_key' => (string) $evaluateeId,
                        'evaluatee_id' => $evaluateeId,
                        'evaluatee_name' => optional($evaluatee)->nama ?? optional($evaluatee)->name ?? '-',
                        'evaluatee_position' => implode('<br>', $positionNames),
                        'total_score' => round($totalScore, 2),
                        'done_count' => $doneCount,
                        'pending_count' => $pendingCount,
                        'total_count' => $doneCount + $pendingCount,
                        'evaluations' => $evaluations,
                    ];
                }

                return response()->json(['success' => true, 'data' => $rows]);
            }

            public function startAssessment(Request $request, KpiPeriod $period)
            {
                DB::beginTransaction();
                try {
                    $employees = HRDEmployee::whereRaw('LOWER(status) <> ?', ['tidak aktif'])->get();

                    foreach ($employees as $employee) {
                        $primaryPosition = $employee->primaryPosition();
                        $evaluateePositions = $employee->positions()->get();
                        if ($evaluateePositions->isEmpty()) {
                            continue;
                        }

                        foreach ($evaluateePositions as $evaluateePosition) {
                            $mappings = \App\Models\KPI\KpiPositionIndicator::where('position_id', $evaluateePosition->id)
                                ->with(['indicator.category'])
                                ->get()
                                ->filter(function ($mapping) {
                                    return $mapping->indicator
                                        && $mapping->indicator->is_active
                                        && $mapping->indicator->category
                                        && $mapping->indicator->category->is_active;
                                });

                            if ($mappings->isEmpty()) {
                                continue;
                            }

                            foreach ($mappings as $map) {
                                $indicator = $map->indicator;
                                $category = $indicator->category;
                                $assessmentType = $category->evaluator_type;

                                if (in_array($assessmentType, ['specific_position'], true)
                                    && (!$primaryPosition || (int) $evaluateePosition->id !== (int) $primaryPosition->id)) {
                                    continue;
                                }

                                $evaluatorPositionTargets = null;
                                if ($assessmentType === 'direct_parent') {
                                    $evaluatorPositionTargets = $evaluateePosition->parent_id ?: null;
                                } elseif ($assessmentType === 'specific_position') {
                                    $evaluatorPositionTargets = $category->evaluator_position_id;
                                } elseif ($assessmentType === 'bottom_up') {
                                    $children = HRDPosition::where('parent_id', $evaluateePosition->id)->get();
                                    $childWithEmp = [];
                                    foreach ($children as $childPos) {
                                        $hasEmp = HRDEmployee::whereHas('positions', function ($q) use ($childPos) {
                                            $q->where('hrd_employee_position.position_id', $childPos->id);
                                        })->whereRaw('LOWER(status) <> ?', ['tidak aktif'])->exists();
                                        if ($hasEmp) {
                                            $childWithEmp[] = $childPos;
                                        }
                                    }
                                    $evaluatorPositionTargets = empty($childWithEmp) ? null : $childWithEmp;
                                }

                                if (!$evaluatorPositionTargets) {
                                    continue;
                                }

                                $evaluatorPositions = is_array($evaluatorPositionTargets)
                                    ? $evaluatorPositionTargets
                                    : [$evaluatorPositionTargets];

                                foreach ($evaluatorPositions as $evPos) {
                                    $evaluatorPositionId = $evPos->id ?? $evPos;
                                    $evaluators = HRDEmployee::whereHas('positions', function ($q) use ($evaluatorPositionId) {
                                        $q->where('hrd_employee_position.position_id', $evaluatorPositionId);
                                    })->whereRaw('LOWER(status) <> ?', ['tidak aktif'])->get()
                                        ->filter(function ($evaluator) use ($evaluateePosition, $evaluatorPositionId) {
                                            return $this->shouldIncludeBottomUpEvaluator(
                                                $evaluator,
                                                $evaluateePosition->id,
                                                $evaluatorPositionId
                                            );
                                        })->values();

                                    foreach ($evaluators as $evaluator) {
                                        $assessment = KpiAssessment::firstOrCreate([
                                            'period_id' => $period->id,
                                            'evaluator_employee_id' => $evaluator->id,
                                            'evaluator_position_id' => $evaluatorPositionId,
                                            'evaluatee_employee_id' => $employee->id,
                                            'evaluatee_position_id' => $evaluateePosition->id,
                                            'assessment_type' => $assessmentType,
                                        ], [
                                            'status' => 'pending',
                                        ]);

                                        KpiScore::firstOrCreate([
                                            'assessment_id' => $assessment->id,
                                            'indicators_id' => $indicator->id,
                                        ], [
                                            'ss_category_name' => $category->category_name,
                                            'ss_category_weight_percentage' => $category->weight_percentage,
                                            'ss_indicator_name' => $indicator->indicator_name,
                                            'ss_indicator_weight_percentage' => $map->weight_percentage ?? 0,
                                            'score' => 0,
                                        ]);
                                    }
                                }
                            }
                        }
                    }

                    $period->status = 'started';
                    $period->started_at = now();
                    $period->save();

                    DB::commit();
                    return response()->json(['success' => true, 'message' => 'Assessments generated and period started.']);
                } catch (\Throwable $e) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
                }
            }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'status' => 'nullable|in:draft,started,open,closed',
            'period_name' => 'required|string|max:255',
            'started_at' => 'nullable|date',
            'open_at' => 'nullable|date',
            'closed_at' => 'nullable|date',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $data = $v->validated();
        if (empty($data['status'])) $data['status'] = 'draft';

        $period = KpiPeriod::create($data);

        return response()->json(['success' => true, 'message' => 'KPI period created.', 'data' => $period]);
    }

    public function previewStart(Request $request, KpiPeriod $period)
    {
        $proposals = [];
        $employees = HRDEmployee::whereRaw('LOWER(status) <> ?', ['tidak aktif'])->get();

        foreach ($employees as $employee) {
            $primaryPosition = $employee->primaryPosition();
            $evaluateePositions = $employee->positions()->get();
            if ($evaluateePositions->isEmpty()) {
                continue;
            }

            foreach ($evaluateePositions as $evaluateePosition) {
                $mappings = \App\Models\KPI\KpiPositionIndicator::where('position_id', $evaluateePosition->id)
                    ->with(['indicator.category'])
                    ->get()
                    ->filter(function ($mapping) {
                        return $mapping->indicator
                            && $mapping->indicator->is_active
                            && $mapping->indicator->category
                            && $mapping->indicator->category->is_active;
                    });

                if ($mappings->isEmpty()) {
                    continue;
                }

                foreach ($mappings as $map) {
                    $indicator = $map->indicator;
                    $category = $indicator->category;
                    $assessmentType = $category->evaluator_type;

                    if (in_array($assessmentType, ['specific_position'], true)
                        && (!$primaryPosition || (int) $evaluateePosition->id !== (int) $primaryPosition->id)) {
                        continue;
                    }

                    if ($assessmentType === 'bottom_up') {
                        $children = HRDPosition::where('parent_id', $evaluateePosition->id)->get();
                        $childWithEmp = [];
                        foreach ($children as $childPos) {
                            $hasEmp = HRDEmployee::whereHas('positions', function ($q) use ($childPos) {
                                $q->where('hrd_employee_position.position_id', $childPos->id);
                            })->whereRaw('LOWER(status) <> ?', ['tidak aktif'])->exists();
                            if ($hasEmp) {
                                $childWithEmp[] = $childPos;
                            }
                        }

                        foreach ($childWithEmp as $evPos) {
                            $evaluators = HRDEmployee::whereHas('positions', function ($q) use ($evPos) {
                                $q->where('hrd_employee_position.position_id', $evPos->id);
                            })->whereRaw('LOWER(status) <> ?', ['tidak aktif'])->get()
                                ->filter(function ($evaluator) use ($evaluateePosition, $evPos) {
                                    return $this->shouldIncludeBottomUpEvaluator(
                                        $evaluator,
                                        $evaluateePosition->id,
                                        $evPos->id
                                    );
                                })->values();

                            foreach ($evaluators as $evaluator) {
                                $proposals[] = [
                                    'evaluatee_id' => $employee->id,
                                    'evaluatee_name' => $employee->nama ?? ($employee->name ?? ''),
                                    'evaluatee_position_id' => $evaluateePosition->id,
                                    'evaluatee_position_name' => $evaluateePosition->name ?? '',
                                    'evaluator_position_id' => $evPos->id,
                                    'evaluator_position_name' => $evPos->name ?? '',
                                    'evaluator_employee_id' => $evaluator?->id,
                                    'evaluator_employee_name' => $evaluator?->nama ?? ($evaluator?->name ?? null),
                                    'indicator_id' => $indicator->id,
                                    'indicator_name' => $indicator->indicator_name,
                                    'category_name' => $category->category_name,
                                    'category_weight' => $category->weight_percentage ?? 0,
                                    'indicator_weight' => $map->weight_percentage ?? 0,
                                    'assessment_type' => $assessmentType,
                                ];
                            }
                        }

                        continue;
                    }

                    $evaluatorPositionId = null;
                    if ($assessmentType === 'direct_parent') {
                        $evaluatorPositionId = $evaluateePosition->parent_id ?: null;
                    } elseif ($assessmentType === 'specific_position') {
                        $evaluatorPositionId = $category->evaluator_position_id;
                    }

                    if (!$evaluatorPositionId) {
                        continue;
                    }

                    $evaluator = HRDEmployee::whereHas('positions', function ($q) use ($evaluatorPositionId) {
                        $q->where('hrd_employee_position.position_id', $evaluatorPositionId);
                    })->whereRaw('LOWER(status) <> ?', ['tidak aktif'])->first();

                    $proposals[] = [
                        'evaluatee_id' => $employee->id,
                        'evaluatee_name' => $employee->nama ?? ($employee->name ?? ''),
                        'evaluatee_position_id' => $evaluateePosition->id,
                        'evaluatee_position_name' => $evaluateePosition->name ?? '',
                        'evaluator_position_id' => $evaluatorPositionId,
                        'evaluator_position_name' => HRDPosition::find($evaluatorPositionId)?->name ?? '',
                        'evaluator_employee_id' => $evaluator?->id,
                        'evaluator_employee_name' => $evaluator?->nama ?? ($evaluator?->name ?? null),
                        'indicator_id' => $indicator->id,
                        'indicator_name' => $indicator->indicator_name,
                        'category_name' => $category->category_name,
                        'category_weight' => $category->weight_percentage ?? 0,
                        'indicator_weight' => $map->weight_percentage ?? 0,
                        'assessment_type' => $assessmentType,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'counts' => [
                'proposals' => count($proposals),
            ],
            'data' => $proposals,
        ]);
    }

    public function update(Request $request, KpiPeriod $period)
    {
        $v = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'status' => 'nullable|in:draft,started,open,closed',
            'period_name' => 'required|string|max:255',
            'started_at' => 'nullable|date',
            'open_at' => 'nullable|date',
            'closed_at' => 'nullable|date',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $data = $v->validated();
        if (empty($data['status'])) $data['status'] = 'draft';

        $period->update($data);

        return response()->json(['success' => true, 'message' => 'KPI period updated.', 'data' => $period]);
    }

    public function destroy(KpiPeriod $period)
    {
        $period->delete();
        return response()->json(['success' => true, 'message' => 'KPI period deleted.']);
    }

    public function openPeriod(Request $request, KpiPeriod $period)
    {
        $period->status = 'open';
        $period->open_at = now();
        $period->save();
        return response()->json(['success' => true, 'message' => 'Period opened.', 'data' => $period]);
    }

    public function closePeriod(Request $request, KpiPeriod $period)
    {
        $period->status = 'closed';
        $period->closed_at = now();
        $period->save();
        return response()->json(['success' => true, 'message' => 'Period closed.', 'data' => $period]);
    }

    private function shouldIncludeBottomUpEvaluator(HRDEmployee $evaluator, int $parentPositionId, int $candidatePositionId): bool
    {
        $positionsUnderParent = $evaluator->positions()
            ->where('parent_id', $parentPositionId)
            ->get();

        if ($positionsUnderParent->count() <= 1) {
            return true;
        }

        $primaryPosition = $evaluator->primaryPosition();

        return $primaryPosition && (int) $primaryPosition->id === $candidatePositionId;
    }
}
