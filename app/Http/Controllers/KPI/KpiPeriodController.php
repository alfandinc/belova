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
    private function activeEmployees()
    {
        return HRDEmployee::query()
            ->with(['positions.divisions', 'positions.parentPositions.divisions'])
            ->whereRaw('LOWER(status) <> ?', ['tidak aktif'])
            ->get();
    }

    private function activeEmployeePositions(HRDEmployee $employee)
    {
        return $employee->positions()
            ->where('hrd_position.is_active', true)
            ->whereHas('divisions', function ($query) {
                $query->where('hrd_division.is_active', true);
            })
            ->get();
    }

    private function activeIndicatorMappingsForPosition(HRDPosition $position)
    {
        return \App\Models\KPI\KpiPositionIndicator::where('position_id', $position->id)
            ->with(['indicator.category'])
            ->get()
            ->filter(function ($mapping) {
                return $mapping->indicator
                    && $mapping->indicator->is_active
                    && $mapping->indicator->category
                    && $mapping->indicator->category->is_active;
            });
    }

    private function activeDirectParentPositions(HRDPosition $position)
    {
        return $position->directParentPositions()
            ->filter(function (HRDPosition $parentPosition) {
                return (bool) $parentPosition->is_active
                    && $parentPosition->divisions()->where('hrd_division.is_active', true)->exists();
            })
            ->values();
    }

    private function activeEvaluatorsForPosition(int $positionId)
    {
        return HRDEmployee::whereHas('positions', function ($query) use ($positionId) {
                $query->where('hrd_employee_position.position_id', $positionId)
                    ->where('hrd_position.is_active', true)
                    ->whereHas('divisions', function ($divisionQuery) {
                        $divisionQuery->where('hrd_division.is_active', true);
                    });
            })
            ->whereRaw('LOWER(status) <> ?', ['tidak aktif'])
            ->get();
    }

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
                // Aggregate assessments per employee. When an employee has multiple positions,
                // calculate each position separately and average the position totals into one row.
                $assessments = KpiAssessment::with(['evaluateeEmployee', 'evaluateePosition', 'scores.indicator', 'evaluatorEmployee'])
                    ->where('period_id', $period->id)
                    ->get()
                    ->groupBy('evaluatee_employee_id');

                $rows = [];
                foreach ($assessments as $evaluateeId => $group) {
                    $firstAssessment = $group->first();
                    $evaluatee = $firstAssessment->evaluateeEmployee;
                    $positionGroups = $group->groupBy('evaluatee_position_id');
                    $positionNames = $group->map(function ($assessment) {
                        return optional($assessment->evaluateePosition)->name;
                    })->filter()->unique()->values()->all();

                    $totalScore = 0.0;
                    $positionCount = 0;
                    $doneCount = 0;
                    $pendingCount = 0;
                    $evaluations = [];

                    foreach ($positionGroups as $positionGroup) {
                        $positionTotal = 0.0;
                        $uniqueTotals = [];
                        $filteredAssessments = collect();

                        foreach ($positionGroup as $assessment) {
                            $assessmentTotal = (float) $assessment->scores->sum('final_calculated_score');

                            if ($assessment->assessment_type === 'bottom_up') {
                                $positionTotal += $assessmentTotal;
                                $filteredAssessments->push($assessment);
                            } else {
                                $bucketKey = $assessment->assessment_type . ':' . ($assessment->evaluator_position_id ?? 0);
                                if (array_key_exists($bucketKey, $uniqueTotals)) {
                                    continue;
                                }

                                $uniqueTotals[$bucketKey] = $assessmentTotal;
                                $filteredAssessments->push($assessment);
                            }

                            if ($assessment->status === 'done') $doneCount++; else $pendingCount++;
                        }

                        foreach ($uniqueTotals as $bucketTotal) {
                            $positionTotal += $bucketTotal;
                        }

                        if ($filteredAssessments->isNotEmpty()) {
                            $totalScore += $positionTotal;
                            $positionCount++;
                        }

                        foreach ($filteredAssessments as $assessment) {
                            $scoresArr = [];
                            foreach ($assessment->scores as $s) {
                                $scoresArr[] = [
                                    'indicator_id' => $s->indicators_id,
                                    'indicator_name' => optional($s->indicator)->indicator_name ?? ($s->ss_indicator_name ?? null),
                                    'category_name' => optional(optional($s->indicator)->category)->category_name ?? ($s->ss_category_name ?? null) ?? 'Uncategorized',
                                    'category_weight' => $s->ss_category_weight_percentage ?? null,
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
                    }

                    $rows[] = [
                        'row_key' => (string) $evaluateeId,
                        'evaluatee_id' => $firstAssessment->evaluatee_employee_id,
                        'evaluatee_name' => optional($evaluatee)->nama ?? optional($evaluatee)->name ?? '-',
                        'evaluatee_position' => implode('<br>', $positionNames),
                        'total_score' => round($positionCount > 0 ? ($totalScore / $positionCount) : 0, 2),
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
                    $employees = $this->activeEmployees();

                    foreach ($employees as $employee) {
                        $evaluateePositions = $this->activeEmployeePositions($employee);
                        if ($evaluateePositions->isEmpty()) {
                            continue;
                        }

                        foreach ($evaluateePositions as $evaluateePosition) {
                            $mappings = $this->activeIndicatorMappingsForPosition($evaluateePosition);

                            if ($mappings->isEmpty()) {
                                continue;
                            }

                            foreach ($mappings as $map) {
                                $indicator = $map->indicator;
                                $category = $indicator->category;
                                $assessmentType = $category->evaluator_type;

                                    $evaluatorPositionTargets = collect();
                                if ($assessmentType === 'direct_parent') {
                    $evaluatorPositionTargets = $this->activeDirectParentPositions($evaluateePosition);
                                } elseif ($assessmentType === 'specific_position') {
                                        $specificPosition = HRDPosition::find($category->evaluator_position_id);
                    $evaluatorPositionTargets = ($specificPosition && $specificPosition->is_active) ? collect([$specificPosition]) : collect();
                                } elseif ($assessmentType === 'bottom_up') {
                                        $evaluatorPositionTargets = $this->bottomUpEvaluatorPositions($evaluateePosition);
                                }

                                    if ($evaluatorPositionTargets->isEmpty()) {
                                    continue;
                                }

                                    foreach ($evaluatorPositionTargets as $evPos) {
                                        $evaluatorPositionId = $evPos->id;
                                    $evaluatorsQuery = $this->activeEvaluatorsForPosition($evaluatorPositionId);

                                    if ($assessmentType === 'specific_position') {
                                        $selectedEvaluator = $evaluatorsQuery->first();
                                        $evaluators = $selectedEvaluator ? collect([$selectedEvaluator]) : collect();
                                    } else {
                                        $evaluators = $evaluatorsQuery
                                        ->filter(function ($evaluator) use ($evaluateePosition, $evaluatorPositionId) {
                                            return $this->shouldIncludeBottomUpEvaluator(
                                                $evaluator,
                                                $evaluateePosition->id,
                                                $evaluatorPositionId
                                            );
                                        })->values();
                                    }

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
        $employees = $this->activeEmployees();

        foreach ($employees as $employee) {
            $evaluateePositions = $this->activeEmployeePositions($employee);
            if ($evaluateePositions->isEmpty()) {
                continue;
            }

            foreach ($evaluateePositions as $evaluateePosition) {
                $mappings = $this->activeIndicatorMappingsForPosition($evaluateePosition);

                if ($mappings->isEmpty()) {
                    continue;
                }

                foreach ($mappings as $map) {
                    $indicator = $map->indicator;
                    $category = $indicator->category;
                    $assessmentType = $category->evaluator_type;

                    if ($assessmentType === 'bottom_up') {
                        foreach ($this->bottomUpEvaluatorPositions($evaluateePosition) as $evPos) {
                            $evaluators = $this->activeEvaluatorsForPosition($evPos->id)
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

                    if ($assessmentType === 'direct_parent') {
                        $evaluatorPositions = $this->activeDirectParentPositions($evaluateePosition);
                    } elseif ($assessmentType === 'specific_position') {
                        $specificPosition = HRDPosition::find($category->evaluator_position_id);
                        $evaluatorPositions = ($specificPosition && $specificPosition->is_active) ? collect([$specificPosition]) : collect();
                    } else {
                        $evaluatorPositions = collect();
                    }

                    if ($evaluatorPositions->isEmpty()) {
                        continue;
                    }

                    foreach ($evaluatorPositions as $evaluatorPosition) {
                        $evaluator = $this->activeEvaluatorsForPosition($evaluatorPosition->id)->first();

                        $proposals[] = [
                            'evaluatee_id' => $employee->id,
                            'evaluatee_name' => $employee->nama ?? ($employee->name ?? ''),
                            'evaluatee_position_id' => $evaluateePosition->id,
                            'evaluatee_position_name' => $evaluateePosition->name ?? '',
                            'evaluator_position_id' => $evaluatorPosition->id,
                            'evaluator_position_name' => $evaluatorPosition->name ?? '',
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
            ->where('hrd_position.is_active', true)
            ->whereHas('parentPositions', function ($parentQuery) use ($parentPositionId) {
                $parentQuery->where('hrd_position.id', $parentPositionId);
            })
            ->get();

        if ($positionsUnderParent->count() <= 1) {
            return true;
        }

        $primaryPosition = $evaluator->primaryPosition();

        return $primaryPosition && (int) $primaryPosition->id === $candidatePositionId;
    }

    private function bottomUpEvaluatorPositions(HRDPosition $evaluateePosition)
    {
        return $evaluateePosition->directChildPositions()
            ->filter(function (HRDPosition $childPos) {
                if (!$childPos->is_active || !$childPos->divisions()->where('hrd_division.is_active', true)->exists()) {
                    return false;
                }

                return HRDEmployee::whereHas('positions', function ($q) use ($childPos) {
                    $q->where('hrd_employee_position.position_id', $childPos->id)
                        ->where('hrd_position.is_active', true)
                        ->whereHas('divisions', function ($divisionQuery) {
                            $divisionQuery->where('hrd_division.is_active', true);
                        });
                })->whereRaw('LOWER(status) <> ?', ['tidak aktif'])->exists();
            })
            ->values();
    }
}
