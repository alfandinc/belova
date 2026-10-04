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
        $positionMappings = \App\Models\KPI\KpiPositionIndicator::where('position_id', $position->id)
            ->with(['indicator.category'])
            ->get()
            ->filter(function ($mapping) {
                return $mapping->indicator
                    && $mapping->indicator->is_active
                    && $mapping->indicator->category
                    && $mapping->indicator->category->is_active
                    && !$mapping->indicator->category->is_shared;
            });

        // shared categories: the same indicators (with their shared weight) apply to every position
        $sharedMappings = $this->sharedIndicators()->map(function ($indicator) use ($position) {
            $mapping = new \App\Models\KPI\KpiPositionIndicator([
                'position_id' => $position->id,
                'indicator_id' => $indicator->id,
                'weight_percentage' => $indicator->shared_weight_percentage,
            ]);

            return $mapping->setRelation('indicator', $indicator);
        });

        return $positionMappings->values()->concat($sharedMappings);
    }

    private $sharedIndicatorsCache = null;

    private function sharedIndicators()
    {
        if ($this->sharedIndicatorsCache === null) {
            $this->sharedIndicatorsCache = \App\Models\KPI\KpiIndicator::query()
                ->with('category')
                ->where('is_active', true)
                ->whereNotNull('shared_weight_percentage')
                ->whereHas('category', function ($query) {
                    $query->where('is_active', true)->where('is_shared', true);
                })
                ->get();
        }

        return $this->sharedIndicatorsCache;
    }

    /**
     * Work out every assessment a period start creates: for each active employee and each of their active
     * positions, the position's indicators grouped by category, and the evaluators of each category.
     * Both the preview and the real start use this, so the preview is exactly what gets generated.
     *
     * Returns ['assignments' => [...], 'warnings' => [...]]. One assignment = one kpi_assessments row
     * (evaluator + evaluator position + evaluatee + evaluatee position + type) with its indicators.
     */
    private function buildDistribution(): array
    {
        $assignments = [];
        $warnings = [];
        $positionPlans = [];

        foreach ($this->activeEmployees() as $employee) {
            foreach ($this->activeEmployeePositions($employee) as $evaluateePosition) {
                // evaluators and indicators depend only on the position, so work them out once per position
                $positionPlans[$evaluateePosition->id] ??= $this->positionPlan($evaluateePosition, $warnings);

                foreach ($positionPlans[$evaluateePosition->id] as $plan) {
                    $category = $plan['category'];
                    // the evaluator of a specific position (e.g. HRD) also assesses themselves;
                    // nobody is their own atasan or their own bawahan
                    $evaluators = $category->evaluator_type === 'specific_position'
                        ? $plan['evaluators']
                        : $plan['evaluators']->reject(function ($evaluator) use ($employee) {
                            return (int) $evaluator['employee']->id === (int) $employee->id;
                        });

                    if ($evaluators->isEmpty()) {
                        if ($plan['reason'] !== null || $plan['evaluators']->isNotEmpty()) {
                            $this->addWarning($warnings, 'no_evaluator:' . $evaluateePosition->id . ':' . $category->id,
                                'Posisi ' . $evaluateePosition->name . ' - kategori ' . $category->category_name . ': tidak ada penilai ('
                                . ($plan['reason'] ?? 'satu-satunya penilai adalah karyawan itu sendiri') . '). Indikator kategori ini tidak dibuat.');
                        }
                        continue;
                    }

                    foreach ($evaluators as $evaluator) {
                        $key = implode(':', [
                            $evaluator['employee']->id,
                            $evaluator['position']->id,
                            $employee->id,
                            $evaluateePosition->id,
                            $category->evaluator_type,
                        ]);

                        $assignments[$key] ??= [
                            'evaluator_employee' => $evaluator['employee'],
                            'evaluator_position' => $evaluator['position'],
                            'evaluatee_employee' => $employee,
                            'evaluatee_position' => $evaluateePosition,
                            'assessment_type' => $category->evaluator_type,
                            'indicators' => [],
                        ];

                        foreach ($plan['mappings'] as $mapping) {
                            $assignments[$key]['indicators'][$mapping->indicator->id] = [
                                'indicator' => $mapping->indicator,
                                'category' => $category,
                                'weight' => (float) ($mapping->weight_percentage ?? 0),
                            ];
                        }
                    }
                }
            }
        }

        $categoryTotal = (float) \App\Models\KPI\KpiIndicatorCategory::where('is_active', true)->sum('weight_percentage');
        if (abs($categoryTotal - 100.0) > 0.001) {
            $this->addWarning($warnings, 'category_total',
                'Total bobot semua kategori aktif ' . number_format($categoryTotal, 2) . '% (seharusnya 100%). Nilai akhir tidak akan berskala 0-100.');
        }

        return ['assignments' => array_values($assignments), 'warnings' => array_values($warnings)];
    }

    /**
     * Indicators of one position grouped by category, with the evaluators of each category.
     */
    private function positionPlan(HRDPosition $position, array &$warnings): array
    {
        $plans = [];
        $byCategory = $this->activeIndicatorMappingsForPosition($position)
            ->groupBy(fn ($mapping) => $mapping->indicator->category_id);

        foreach ($byCategory as $mappings) {
            $category = $mappings->first()->indicator->category;

            $total = (float) $mappings->sum(fn ($mapping) => (float) $mapping->weight_percentage);
            if (abs($total - 100.0) > 0.001) {
                $this->addWarning($warnings, 'weight:' . $position->id . ':' . $category->id,
                    'Posisi ' . $position->name . ' - kategori ' . $category->category_name . ': total bobot indikator '
                    . number_format($total, 2) . '% (seharusnya 100%).');
            }

            [$evaluators, $reason] = $this->evaluatorsForCategory($position, $category);

            $plans[] = [
                'category' => $category,
                'mappings' => $mappings->values(),
                'evaluators' => $evaluators,
                'reason' => $reason,
            ];
        }

        return $plans;
    }

    /**
     * Evaluators of a category for an evaluatee position: [collection of ['employee', 'position'], reason or null].
     * The reason explains an empty list; it stays null when an empty list is expected (bottom-up evaluation
     * of a position that simply has no subordinates).
     */
    private function evaluatorsForCategory(HRDPosition $evaluateePosition, $category): array
    {
        $evaluators = collect();

        if ($category->evaluator_type === 'direct_parent') {
            $parentPositions = $this->activeDirectParentPositions($evaluateePosition);
            if ($parentPositions->isEmpty()) {
                return [$evaluators, 'tidak punya posisi atasan langsung yang aktif'];
            }

            foreach ($parentPositions as $parentPosition) {
                foreach ($this->activeEvaluatorsForPosition($parentPosition->id) as $employee) {
                    $evaluators->push(['employee' => $employee, 'position' => $parentPosition]);
                }
            }

            // someone holding two parent positions of the same evaluatee assesses them once
            $evaluators = $evaluators->unique(fn ($evaluator) => $evaluator['employee']->id)->values();

            return [$evaluators, $evaluators->isEmpty() ? 'posisi atasan tidak punya karyawan aktif' : null];
        }

        if ($category->evaluator_type === 'specific_position') {
            $specificPosition = $category->evaluator_position_id ? HRDPosition::find($category->evaluator_position_id) : null;
            if (!$specificPosition || !$specificPosition->is_active) {
                return [$evaluators, 'posisi penilai kategori tidak ditemukan atau nonaktif'];
            }

            // one evaluator for a specific position (the earliest registered active employee)
            $employee = $this->activeEvaluatorsForPosition($specificPosition->id)->sortBy('id')->first();
            if (!$employee) {
                return [$evaluators, 'posisi penilai ' . $specificPosition->name . ' tidak punya karyawan aktif'];
            }

            return [collect([['employee' => $employee, 'position' => $specificPosition]]), null];
        }

        if ($category->evaluator_type === 'bottom_up') {
            foreach ($this->bottomUpEvaluatorPositions($evaluateePosition) as $childPosition) {
                $employees = $this->activeEvaluatorsForPosition($childPosition->id)
                    ->filter(function ($employee) use ($evaluateePosition, $childPosition) {
                        return $this->shouldIncludeBottomUpEvaluator($employee, $evaluateePosition->id, $childPosition->id);
                    });

                foreach ($employees as $employee) {
                    $evaluators->push(['employee' => $employee, 'position' => $childPosition]);
                }
            }

            return [$evaluators, null];
        }

        return [$evaluators, 'tipe evaluator tidak dikenal: ' . $category->evaluator_type];
    }

    private function addWarning(array &$warnings, string $key, string $message): void
    {
        if (isset($warnings[$key])) {
            $warnings[$key]['count']++;
            return;
        }

        $warnings[$key] = ['message' => $message, 'count' => 1];
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
                // names and weights come from the ss_* snapshot on each score, so indicators are not loaded
                $assessments = KpiAssessment::with(['evaluateeEmployee', 'evaluateePosition', 'evaluatorEmployee', 'evaluatorPosition', 'scores'])
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

                    // Specific-position evaluators assess the employee once, not once per position.
                    // Reuse a done specific_position assessment for the same evaluator's pending
                    // assessments on the employee's other positions (in memory only).
                    $specificScores = [];
                    foreach ($group as $assessment) {
                        if ($assessment->assessment_type !== 'specific_position' || $assessment->status !== 'done') {
                            continue;
                        }
                        foreach ($assessment->scores as $s) {
                            $specificScores[$assessment->evaluator_position_id][$s->indicators_id] ??= $s;
                        }
                    }
                    foreach ($group as $assessment) {
                        if ($assessment->assessment_type !== 'specific_position' || $assessment->status === 'done') {
                            continue;
                        }
                        $pool = $specificScores[$assessment->evaluator_position_id] ?? [];
                        if (empty($pool) || $assessment->scores->isEmpty()) {
                            continue;
                        }
                        // Indicators the evaluator did not score elsewhere take their average raw score
                        $averageScore = array_sum(array_map(fn($s) => (float) $s->score, $pool)) / count($pool);
                        foreach ($assessment->scores as $s) {
                            $source = $pool[$s->indicators_id] ?? null;
                            $rawScore = $source ? (float) $source->score : $averageScore;
                            $s->score = round($rawScore, 2);
                            $s->notes = $source?->notes;
                            $s->final_calculated_score = round(($rawScore / 5.0) * (((float) ($s->ss_indicator_weight_percentage ?? 0)) / 100.0) * ((float) ($s->ss_category_weight_percentage ?? 0)), 2);
                        }
                        $assessment->status = 'done';
                    }

                    foreach ($positionGroups as $positionGroup) {
                        // An indicator may be scored by several evaluators (e.g. multiple atasan or
                        // bottom-up peers). Average each indicator across the evaluators who submitted,
                        // then sum the indicators so the position total stays on the 0-100 scale.
                        $indicatorScores = [];

                        foreach ($positionGroup as $assessment) {
                            if ($assessment->status === 'done') {
                                $doneCount++;
                                foreach ($assessment->scores as $s) {
                                    $indicatorScores[$s->indicators_id][] = (float) $s->final_calculated_score;
                                }
                            } else {
                                $pendingCount++;
                            }
                        }

                        $positionTotal = 0.0;
                        foreach ($indicatorScores as $values) {
                            $positionTotal += array_sum($values) / count($values);
                        }

                        $totalScore += $positionTotal;
                        $positionCount++;

                        foreach ($positionGroup as $assessment) {
                            $scoresArr = [];
                            foreach ($assessment->scores as $s) {
                                $scoresArr[] = [
                                    'indicator_id' => $s->indicators_id,
                                    'indicator_name' => $s->ss_indicator_name ?? '-',
                                    'category_name' => $s->ss_category_name ?? 'Uncategorized',
                                    'category_weight' => $s->ss_category_weight_percentage ?? null,
                                    'indicator_weight' => $s->ss_indicator_weight_percentage,
                                    'score' => $s->score,
                                    'final_calculated_score' => $s->final_calculated_score,
                                    'notes' => $s->notes,
                                ];
                            }

                            $evaluations[] = [
                                'assessment_id' => $assessment->id,
                                'evaluator_id' => $assessment->evaluator_employee_id,
                                'evaluator_name' => optional($assessment->evaluatorEmployee)->nama ?? optional($assessment->evaluatorEmployee)->name ?? ('Position ' . ($assessment->evaluator_position_id ?? '')),
                                'evaluator_position' => optional($assessment->evaluatorPosition)->name,
                                'evaluatee_position' => optional($assessment->evaluateePosition)->name,
                                'assessment_type' => $assessment->assessment_type,
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
                        'evaluatee_positions' => $positionNames,
                        'total_score' => round($positionCount > 0 ? ($totalScore / $positionCount) : 0, 2),
                        'done_count' => $doneCount,
                        'pending_count' => $pendingCount,
                        'total_count' => $doneCount + $pendingCount,
                        'evaluations' => $evaluations,
                    ];
                }

                usort($rows, fn ($a, $b) => strcasecmp($a['evaluatee_name'], $b['evaluatee_name']));

                return response()->json([
                    'success' => true,
                    'period' => [
                        'id' => $period->id,
                        'name' => $period->period_name,
                        'label' => trim(($period->month ? \DateTime::createFromFormat('!m', $period->month)->format('F') : '') . ' ' . $period->year),
                        'status' => $period->status,
                    ],
                    'data' => $rows,
                ]);
            }

    public function startAssessment(Request $request, KpiPeriod $period)
    {
        try {
            $created = DB::transaction(function () use ($period) {
                // lock the period so a double click or a second user cannot generate the assessments twice
                $lockedPeriod = KpiPeriod::whereKey($period->id)->lockForUpdate()->first();
                if ($lockedPeriod->status !== 'draft') {
                    throw new \RuntimeException('Periode ini sudah dimulai (status: ' . $lockedPeriod->status . '). Assessment tidak dibuat ulang.');
                }

                $assignments = $this->buildDistribution()['assignments'];
                if (empty($assignments)) {
                    throw new \RuntimeException('Tidak ada assessment yang bisa dibuat. Periksa indikator posisi dan penilai di halaman Master Indicators.');
                }

                $assessmentCount = 0;
                $scoreCount = 0;

                foreach ($assignments as $assignment) {
                    $assessment = KpiAssessment::firstOrCreate([
                        'period_id' => $lockedPeriod->id,
                        'evaluator_employee_id' => $assignment['evaluator_employee']->id,
                        'evaluator_position_id' => $assignment['evaluator_position']->id,
                        'evaluatee_employee_id' => $assignment['evaluatee_employee']->id,
                        'evaluatee_position_id' => $assignment['evaluatee_position']->id,
                        'assessment_type' => $assignment['assessment_type'],
                    ], [
                        'status' => 'pending',
                    ]);
                    $assessmentCount++;

                    foreach ($assignment['indicators'] as $item) {
                        // ss_* is the snapshot old assessments keep, whatever changes in the indicators later
                        KpiScore::firstOrCreate([
                            'assessment_id' => $assessment->id,
                            'indicators_id' => $item['indicator']->id,
                        ], [
                            'ss_category_name' => $item['category']->category_name,
                            'ss_category_weight_percentage' => $item['category']->weight_percentage,
                            'ss_indicator_name' => $item['indicator']->indicator_name,
                            'ss_indicator_weight_percentage' => $item['weight'],
                            'score' => 0,
                        ]);
                        $scoreCount++;
                    }
                }

                $lockedPeriod->status = 'started';
                $lockedPeriod->started_at = now();
                $lockedPeriod->save();

                return ['assessments' => $assessmentCount, 'scores' => $scoreCount];
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Gagal memulai periode: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Periode dimulai: ' . $created['assessments'] . ' assessment dan ' . $created['scores'] . ' indikator penilaian dibuat.',
            'counts' => $created,
        ]);
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
        $distribution = $this->buildDistribution();
        $proposals = [];
        $evaluatees = [];

        foreach ($distribution['assignments'] as $assignment) {
            $evaluatee = $assignment['evaluatee_employee'];
            $evaluator = $assignment['evaluator_employee'];
            $evaluatees[$evaluatee->id] = true;

            foreach ($assignment['indicators'] as $item) {
                $proposals[] = [
                    'evaluatee_id' => $evaluatee->id,
                    'evaluatee_name' => $evaluatee->nama ?? ($evaluatee->name ?? ''),
                    'evaluatee_position_id' => $assignment['evaluatee_position']->id,
                    'evaluatee_position_name' => $assignment['evaluatee_position']->name ?? '',
                    'evaluator_position_id' => $assignment['evaluator_position']->id,
                    'evaluator_position_name' => $assignment['evaluator_position']->name ?? '',
                    'evaluator_employee_id' => $evaluator->id,
                    'evaluator_employee_name' => $evaluator->nama ?? ($evaluator->name ?? null),
                    'indicator_id' => $item['indicator']->id,
                    'indicator_name' => $item['indicator']->indicator_name,
                    'category_name' => $item['category']->category_name,
                    'category_weight' => (float) ($item['category']->weight_percentage ?? 0),
                    'indicator_weight' => $item['weight'],
                    'assessment_type' => $assignment['assessment_type'],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'status' => $period->status,
            'counts' => [
                'proposals' => count($proposals),
                'assessments' => count($distribution['assignments']),
                'evaluatees' => count($evaluatees),
            ],
            'warnings' => $distribution['warnings'],
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

    /**
     * An employee holding several positions under the same parent assesses that parent only once:
     * through their primary position when it is one of them, otherwise through the lowest position id.
     */
    private function shouldIncludeBottomUpEvaluator(HRDEmployee $evaluator, int $parentPositionId, int $candidatePositionId): bool
    {
        // read the hierarchy pivot directly: a whereHas on the self-referencing parentPositions relation
        // aliases the joined table, so a hrd_position.id condition never matches the parent
        $positionIdsUnderParent = DB::table('hrd_employee_position as ep')
            ->join('hrd_position as p', 'p.id', '=', 'ep.position_id')
            ->join('hrd_position_division as pd', 'pd.position_id', '=', 'ep.position_id')
            ->where('ep.employee_id', $evaluator->id)
            ->where('p.is_active', true)
            ->where('pd.parent_position_id', $parentPositionId)
            ->distinct()
            ->pluck('ep.position_id')
            ->map(fn ($id) => (int) $id);

        if ($positionIdsUnderParent->count() <= 1) {
            return true;
        }

        $primaryPosition = $evaluator->primaryPosition();
        $chosenPositionId = ($primaryPosition && $positionIdsUnderParent->contains((int) $primaryPosition->id))
            ? (int) $primaryPosition->id
            : $positionIdsUnderParent->min();

        return $chosenPositionId === $candidatePositionId;
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
