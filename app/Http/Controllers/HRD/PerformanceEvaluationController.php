<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Log; (removed duplicate)
use App\Models\HRD\{
    PerformanceEvaluationPeriod,
    PerformanceEvaluation,
    PerformanceQuestion,
    PerformanceScore,
    Employee,
    Division
};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PerformanceScoreExport;
use Carbon\Carbon;

class PerformanceEvaluationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if ($request->has('draw')) {
                // This is a DataTables request
                $query = PerformanceEvaluationPeriod::query();
                
                // Total records count
                $totalRecords = $query->count();
                
                // Apply search
                if ($request->has('search') && !empty($request->search['value'])) {
                    $searchValue = $request->search['value'];
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%")
                          ->orWhere('status', 'like', "%{$searchValue}%");
                    });
                }
                
                // Total records after filtering
                $totalRecordsFiltered = $query->count();
                
                // Apply ordering
                if ($request->has('order')) {
                    $columns = ['name', 'start_date', 'end_date', 'status', null];
                    $orderColumn = $request->order[0]['column'];
                    $orderDir = $request->order[0]['dir'];
                    
                    if (isset($columns[$orderColumn]) && $columns[$orderColumn] !== null) {
                        $query->orderBy($columns[$orderColumn], $orderDir);
                    }
                } else {
                    $query->orderBy('created_at', 'desc');
                }
                
                // Apply pagination
                if ($request->has('start') && $request->has('length')) {
                    $query->skip($request->start)->take($request->length);
                }
                
                $periods = $query->get();
                
                $data = [];
                foreach ($periods as $period) {
                    $actions = '<a href="' . route('hrd.performance.periods.show', $period) . '" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> Details</a>';
                    if ($period->status == 'pending') {
                        $actions .= ' <button type="button" class="btn btn-sm btn-primary edit-period" 
                                        data-id="' . $period->id . '"
                                        data-name="' . $period->name . '"
                                        data-start="' . $period->start_date->format('Y-m-d') . '"
                                        data-end="' . $period->end_date->format('Y-m-d') . '"
                                        data-status="' . $period->status . '"
                                        data-toggle="modal" data-target="#editPeriodModal">
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success btn-initiate" data-id="' . $period->id . '">
                                        <i class="fa fa-play"></i> Initiate
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $period->id . '">
                                        <i class="fa fa-trash"></i> Delete
                                    </button>';
                    }
                    if (empty($actions)) {
                        $actions = '<span></span>';
                    }
                    $status = '<span class="badge badge-' . ($period->status == 'pending' ? 'warning' : ($period->status == 'active' ? 'primary' : 'success')) . '">' . ucfirst($period->status) . '</span>';
                    $data[] = [
                        $period->name,
                        $period->start_date->format('d M Y'),
                        $period->end_date->format('d M Y'),
                        $status,
                        $actions
                    ];
                }
                // Debug: log outgoing data array
                Log::info('PerformanceEvaluation DataTables response', ['data' => $data]);
                
                return response()->json([
                    'draw' => intval($request->draw),
                    'recordsTotal' => $totalRecords,
                    'recordsFiltered' => $totalRecordsFiltered,
                    'data' => $data
                ]);
            } else {
                // Return the full rendered view for other AJAX requests (backward compatibility)
                $periods = PerformanceEvaluationPeriod::orderBy('created_at', 'desc')->paginate(10);
                return view('hrd.performance.periods.index', compact('periods'))->render();
            }
        }
        
        // Initial page load
        return view('hrd.performance.periods.index');
    }

    public function create()
    {
        return view('hrd.performance.periods.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'mode' => 'required|in:360,satu arah',
        ]);

        // Always set status to pending for new periods
        $validated['status'] = 'pending';

        $period = PerformanceEvaluationPeriod::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Evaluation period created successfully.',
                'period' => $period
            ]);
        }

        return redirect()->route('hrd.performance.periods.index')
            ->with('success', 'Evaluation period created successfully.');
    }

    public function show(PerformanceEvaluationPeriod $period)
    {
        $evaluations = PerformanceEvaluation::with(['evaluator.division', 'evaluatee.division', 'evaluator.user.roles', 'evaluatee.user.roles'])
            ->where('period_id', $period->id)
            ->get();

        // Attach evaluation_type to each evaluation
        foreach ($evaluations as $eval) {
            $type = $this->determineEvaluationType($eval);
            $labels = [
                'hrd_to_manager' => 'HRD to Manager',
                'manager_to_employee' => 'Manager to Employee',
                'employee_to_manager' => 'Employee to Manager',
                'manager_to_hrd' => 'Manager to HRD',
                'employee_to_hrd' => 'Employee to HRD',
            ];
            $eval->evaluation_type = $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
        }

        $pendingCount = $evaluations->where('status', 'pending')->count();
        $completedCount = $evaluations->where('status', 'completed')->count();

        return view('hrd.performance.periods.show', compact('period', 'evaluations', 'pendingCount', 'completedCount'));
    }

    public function edit(PerformanceEvaluationPeriod $period)
    {
        return view('hrd.performance.periods.edit', compact('period'));
    }

    public function update(Request $request, PerformanceEvaluationPeriod $period)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'mode' => 'required|in:360,satu arah',
        ]);

        // For edit modal, maintain the existing status (don't reset to pending)
        if (!$request->has('status')) {
            $validated['status'] = $period->status;
        } else {
            $validated['status'] = $request->status;
        }

        $period->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Evaluation period updated successfully.',
                'period' => $period
            ]);
        }

        return redirect()->route('hrd.performance.periods.index')
            ->with('success', 'Evaluation period updated successfully.');
    }

    public function destroy(Request $request, PerformanceEvaluationPeriod $period)
    {
        // Check if evaluations exist
        $hasEvaluations = PerformanceEvaluation::where('period_id', $period->id)->exists();

        if ($hasEvaluations) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete period with existing evaluations.'
                ], 422);
            }
            
            return redirect()->route('hrd.performance.periods.index')
                ->with('error', 'Cannot delete period with existing evaluations.');
        }

        $period->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Evaluation period deleted successfully.'
            ]);
        }

        return redirect()->route('hrd.performance.periods.index')
            ->with('success', 'Evaluation period deleted successfully.');
    }


    public function initiate(PerformanceEvaluationPeriod $period)
    {
        // Get all employees with their divisions eager loaded
        $employees = Employee::with(['division', 'user.roles'])->get();

        // Get HRD employees
        $hrdEmployees = $employees->filter(function ($employee) {
            return $employee->division && stripos($employee->division->name, 'Human Resource') !== false;
        });

        // Get managers
        $managers = $employees->filter(function ($employee) {
            return $employee->isManager();
        });

        // Get regular employees (non-managers and non-HRD)
        $regularEmployees = $employees->filter(function ($employee) {
            return !$employee->isManager() &&
                $employee->division &&
                stripos($employee->division->name, 'Human Resource') === false;
        });

        // Normalize mode value for comparison
        $mode = strtolower(trim($period->mode));
        // Initiate evaluations based on mode
        if ($mode === '360') {
            // 1. HRD to Managers
            foreach ($hrdEmployees as $hrd) {
                foreach ($managers as $manager) {
                    PerformanceEvaluation::firstOrCreate(
                        [
                            'period_id' => $period->id,
                            'evaluator_id' => $hrd->id,
                            'evaluatee_id' => $manager->id
                        ],
                        [
                            'status' => 'pending'
                        ]
                    );
                }
            }

            // 2. Managers to Employees in their division
            foreach ($managers as $manager) {
                if (!$manager->division) continue;
                $divisionEmployees = $employees->filter(function ($employee) use ($manager) {
                    return $employee->division && $employee->division->id === $manager->division->id && $employee->id !== $manager->id;
                });
                foreach ($divisionEmployees as $employee) {
                    PerformanceEvaluation::firstOrCreate(
                        [
                            'period_id' => $period->id,
                            'evaluator_id' => $manager->id,
                            'evaluatee_id' => $employee->id
                        ],
                        [
                            'status' => 'pending'
                        ]
                    );
                }
            }

            // 3. Employees to their Managers
            foreach ($regularEmployees as $employee) {
                if (!$employee->division) continue;
                $divisionManager = $managers->first(function ($manager) use ($employee) {
                    return $manager->division && $manager->division->id === $employee->division->id;
                });
                if ($divisionManager) {
                    PerformanceEvaluation::firstOrCreate(
                        [
                            'period_id' => $period->id,
                            'evaluator_id' => $employee->id,
                            'evaluatee_id' => $divisionManager->id
                        ],
                        [
                            'status' => 'pending'
                        ]
                    );
                }
            }

            // 4. Managers to HRD
            foreach ($managers as $manager) {
                foreach ($hrdEmployees as $hrd) {
                    PerformanceEvaluation::firstOrCreate(
                        [
                            'period_id' => $period->id,
                            'evaluator_id' => $manager->id,
                            'evaluatee_id' => $hrd->id
                        ],
                        [
                            'status' => 'pending'
                        ]
                    );
                }
            }

            // 5. Employee to HRD
            foreach ($regularEmployees as $employee) {
                foreach ($hrdEmployees as $hrd) {
                    PerformanceEvaluation::firstOrCreate(
                        [
                            'period_id' => $period->id,
                            'evaluator_id' => $employee->id,
                            'evaluatee_id' => $hrd->id
                        ],
                        [
                            'status' => 'pending'
                        ]
                    );
                }
            }
        } else if ($mode === 'satu arah') {
            // Only HRD to Managers and Managers to Employees
            foreach ($hrdEmployees as $hrd) {
                foreach ($managers as $manager) {
                    PerformanceEvaluation::firstOrCreate(
                        [
                            'period_id' => $period->id,
                            'evaluator_id' => $hrd->id,
                            'evaluatee_id' => $manager->id
                        ],
                        [
                            'status' => 'pending'
                        ]
                    );
                }
            }
            foreach ($managers as $manager) {
                if (!$manager->division) continue;
                $divisionEmployees = $employees->filter(function ($employee) use ($manager) {
                    // Only allow manager to employee, not employee to manager
                    return $employee->division && $employee->division->id === $manager->division->id && $employee->id !== $manager->id && !$employee->isManager();
                });
                foreach ($divisionEmployees as $employee) {
                    PerformanceEvaluation::firstOrCreate(
                        [
                            'period_id' => $period->id,
                            'evaluator_id' => $manager->id,
                            'evaluatee_id' => $employee->id
                        ],
                        [
                            'status' => 'pending'
                        ]
                    );
                }
            }
            // No Employee to Manager or Employee to HRD in satu arah mode
        }

        $period->status = 'active';
        $period->save();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Evaluation period initiated successfully.'
            ]);
        }

        return redirect()->route('hrd.performance.periods.show', $period)
            ->with('success', 'Evaluation period initiated successfully.');
    }

    public function myEvaluations(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return redirect()->back()->with('error', 'No employee record found for your account.');
        }

        if ($request->ajax()) {
            $type = $request->input('type', 'pending');
            
            // Use table alias to avoid ambiguous column names
            $query = PerformanceEvaluation::select('performance_evaluations.*')
                ->with(['evaluatee', 'evaluatee.position', 'evaluatee.division', 'period'])
                ->where('performance_evaluations.evaluator_id', $employee->id)
                ->where('performance_evaluations.status', $type);

            // Only show evaluations from active periods for pending and completed evaluations
            if (in_array($type, ['pending', 'completed'])) {
                $query->whereHas('period', function ($q) {
                    $q->where('performance_evaluation_periods.status', 'active');
                });
            }

            // Datatable server-side processing
            return datatables()->of($query)
                ->addColumn('period_name', function ($evaluation) {
                    return $evaluation->period ? $evaluation->period->name : 'N/A';
                })
                ->addColumn('evaluatee_name', function ($evaluation) {
                    return $evaluation->evaluatee ? $evaluation->evaluatee->nama : 'N/A';
                })
                ->addColumn('position', function ($evaluation) {
                    if (!$evaluation->evaluatee) {
                        return 'N/A';
                    }
                    $position = (is_object($evaluation->evaluatee->position) && isset($evaluation->evaluatee->position->name))
                        ? $evaluation->evaluatee->position->name
                        : 'N/A';
                    $division = (is_object($evaluation->evaluatee->division) && isset($evaluation->evaluatee->division->name))
                        ? $evaluation->evaluatee->division->name
                        : 'N/A';
                    return $position . '<span class="text-muted d-block small">' . $division . '</span>';
                })
                ->addColumn('evaluation_type', function ($evaluation) {
                    $type = (new \App\Http\Controllers\HRD\PerformanceEvaluationController)->determineEvaluationType($evaluation);
                    $labels = [
                        'hrd_to_manager' => 'HRD to Manager',
                        'manager_to_employee' => 'Manager to Employee',
                        'employee_to_manager' => 'Employee to Manager',
                        'manager_to_hrd' => 'Manager to HRD',
                        'employee_to_hrd' => 'Employee to HRD',
                    ];
                    return $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
                })
                ->addColumn('completed_at', function ($evaluation) {
                    return $evaluation->completed_at ? $evaluation->completed_at->format('d M Y') : 'N/A';
                })
                ->addColumn('action', function ($evaluation) {
                    if ($evaluation->status === 'pending') {
                        return '<a href="' . route('hrd.performance.evaluations.fill', $evaluation) . '" class="btn btn-primary btn-sm">
                                    <i class="fa fa-edit"></i> Fill Evaluation
                                </a>';
                    }
                    return '';
                })
                ->rawColumns(['position', 'action'])
                ->make(true);
        }

        // Initial view load
        return view('hrd.performance.my-evaluations');
    }

    public function fillEvaluation(PerformanceEvaluation $evaluation)
    {
        // Check if user is authorized to fill this evaluation
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if ($evaluation->evaluator_id != $employee->id) {
            return redirect()->back()->with('error', 'You are not authorized to fill this evaluation.');
        }

        // Determine evaluation type
        $evaluationType = $this->determineEvaluationType($evaluation);

        // Get questions for this evaluation type
        $questions = PerformanceQuestion::where('evaluation_type', $evaluationType)
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->groupBy('category_id');

        $categories = collect();
        foreach ($questions as $categoryId => $questionGroup) {
            $category = $questionGroup->first()->category;
            $categories->push([
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'questions' => $questionGroup
            ]);
        }

        return view('hrd.performance.fill-evaluation', compact('evaluation', 'categories'));
    }

    // Determine which type of evaluation this is based on evaluator and evaluatee positions
    private function determineEvaluationType(PerformanceEvaluation $evaluation)
    {
        $evaluator = $evaluation->evaluator;
        $evaluatee = $evaluation->evaluatee;


        $hrdNames = ['human resources', 'hrd', 'human resource'];
        $isEvaluatorHRD = $evaluator->division instanceof Division && in_array(strtolower($evaluator->division->name), $hrdNames);
        $isEvaluatorManager = $evaluator->isManager();
        $isEvaluateeHRD = $evaluatee->division instanceof Division && in_array(strtolower($evaluatee->division->name), $hrdNames);
        $isEvaluateeManager = $evaluatee->isManager();

        if ($isEvaluatorHRD && $isEvaluateeManager) {
            return 'hrd_to_manager';
        } elseif ($isEvaluatorManager && !$isEvaluateeManager && !$isEvaluateeHRD) {
            return 'manager_to_employee';
        } elseif (!$isEvaluatorManager && !$isEvaluatorHRD && $isEvaluateeManager) {
            return 'employee_to_manager';
        } elseif ($isEvaluatorManager && $isEvaluateeHRD) {
            return 'manager_to_hrd';
        } elseif (!$isEvaluatorManager && !$isEvaluatorHRD && $isEvaluateeHRD) {
            return 'employee_to_hrd';
        }

        // Default fallback
        return 'other';
    }

    public function results()
    {
        return view('hrd.performance.results.index');
    }
    
    public function resultsData()
    {
        $periods = PerformanceEvaluationPeriod::where('status', 'completed')->get();
        
        return \Yajra\DataTables\Facades\DataTables::of($periods)
            ->addColumn('date_range', function ($period) {
                return $period->start_date->format('d M Y') . ' - ' . $period->end_date->format('d M Y');
            })
            ->addColumn('evaluations', function ($period) {
                $totalEvals = $period->evaluations->count();
                $completedEvals = $period->evaluations->where('status', 'completed')->count();
                $completionRate = $totalEvals > 0 ? round(($completedEvals / $totalEvals) * 100) : 0;
                return $completedEvals . ' / ' . $totalEvals . ' (' . $completionRate . '% completed)';
            })
            ->addColumn('action', function ($period) {
                return '<a href="' . route('hrd.performance.results.period', $period) . '" class="btn btn-info btn-sm">
                            <i class="fa fa-chart-bar"></i> View Results
                        </a>';
            })
            ->addColumn('download', function($period) {
                return '<button class="btn btn-sm btn-success btn-download-score" data-period-id="' . $period->id . '">Tarik Data</button>';
            })
            ->rawColumns(['action', 'download'])
            ->make(true);
    }

    // Download score data for a period as Excel/CSV
    public function downloadScore(PerformanceEvaluationPeriod $period)
    {
        Log::info('Export XLSX triggered', ['period_id' => $period->id]);
        // Get all evaluations for this period with related data
        $evaluations = \App\Models\HRD\PerformanceEvaluation::with(['evaluator.division', 'evaluatee.division', 'scores.question'])
            ->where('period_id', $period->id)
            ->get();

        // Get all questions for this period (from all evaluations)
        $questionIds = $evaluations->flatMap(function($eval) {
            return $eval->scores->pluck('question_id');
        })->unique()->values();
        $questions = \App\Models\HRD\PerformanceQuestion::whereIn('id', $questionIds)->get();

        // Prepare header
        $headers = [
            'nama_penilai', 'posisi_penilai', 'nama_dinilai', 'posisi_dinilai', 'divisi_dinilai'
        ];
        foreach ($questions as $q) {
            $headers[] = $q->question_text;
        }

        $rows = [];
        foreach ($evaluations as $eval) {
            $evaluator = $eval->evaluator;
            $evaluatee = $eval->evaluatee;
            $nama_penilai = $evaluator ? ($evaluator->nama ?? $evaluator->name ?? $evaluator->nama_lengkap ?? '') : '';
            $nama_dinilai = $evaluatee ? ($evaluatee->nama ?? $evaluatee->name ?? $evaluatee->nama_lengkap ?? '') : '';
            $row = [
                $nama_penilai,
                optional(optional($eval->evaluator)->division)->name,
                $nama_dinilai,
                optional(optional($eval->evaluatee)->division)->name,
                optional(optional($eval->evaluatee)->division)->name,
            ];
            // Map question_id => score for this evaluation
            $scoreMap = $eval->scores->pluck('score', 'question_id');
            foreach ($questions as $q) {
                $row[] = $scoreMap[$q->id] ?? '';
            }
            $rows[] = $row;
        }

        $export = new PerformanceScoreExport($headers, $rows);
        $periodName = $period->name ?? ('periode_' . $period->id);
        $periodNameSlug = preg_replace('/[^A-Za-z0-9_\-]/', '_', str_replace(' ', '_', $periodName));
        $filename = 'score_' . $periodNameSlug . '.xlsx';
        return Excel::download($export, $filename);
    }



    public function periodResults(PerformanceEvaluationPeriod $period)
    {
        return view('hrd.performance.results.period', compact('period'));
    }
    
    public function periodResultsData(PerformanceEvaluationPeriod $period)
    {
        $employees = Employee::all();
        $averageScoresCollection = collect();

        foreach ($employees as $employee) {
            $evaluations = PerformanceEvaluation::with('scores.question.category')
                ->where('period_id', $period->id)
                ->where('evaluatee_id', $employee->id)
                ->where('status', 'completed')
                ->get();

            if ($evaluations->isNotEmpty()) {
                $scores = collect();

                foreach ($evaluations as $evaluation) {
                    $scores = $scores->concat($evaluation->scores);
                }
                
                // Filter out text-type questions (which have score=0) for average calculation
                $scoreTypeScores = $scores->filter(function ($score) {
                    return $score->question && $score->question->question_type === 'score';
                });
                
                $categoryScores = $scoreTypeScores->groupBy(function ($score) {
                    return optional($score->question->category)->name ?? 'Unknown';
                });

                $categoryAverages = [];
                foreach ($categoryScores as $category => $catScores) {
                    $categoryAverages[$category] = round($catScores->avg('score'), 2);
                }

                // Only use score-type questions for the overall average
                $overallAverage = $scoreTypeScores->isEmpty() ? 0 : round($scoreTypeScores->avg('score'), 2);

                $averageScoresCollection->push([
                    'employee' => $employee,
                    'categoryAverages' => $categoryAverages,
                    'overallAverage' => $overallAverage
                ]);
            }
        }

        return \Yajra\DataTables\Facades\DataTables::of($averageScoresCollection)
            ->addColumn('name', function ($score) {
                return $score['employee']->name ?? $score['employee']->nama;
            })
            ->addColumn('position', function ($score) {
                return $score['employee']->position->name ?? 'N/A';
            })
            ->addColumn('division', function ($score) {
                return $score['employee']->division->name ?? 'N/A';
            })
            ->addColumn('score', function ($score) {
                $badgeClass = $score['overallAverage'] >= 4 ? 'success' : 
                             ($score['overallAverage'] >= 3 ? 'info' : 
                             ($score['overallAverage'] >= 2 ? 'warning' : 'danger'));
                             
                return '<span class="badge badge-' . $badgeClass . ' badge-pill">' . 
                       number_format($score['overallAverage'], 2) . '</span>';
            })
            ->addColumn('action', function ($score) use ($period) {
                return '<a href="' . route('hrd.performance.results.employee', ['period' => $period, 'employee' => $score['employee']]) . '" class="btn btn-info btn-sm">
                            <i class="fa fa-eye"></i> View Details
                        </a>';
            })
            ->rawColumns(['score', 'action'])
            ->make(true);
    }

    public function employeeResults(PerformanceEvaluationPeriod $period, Employee $employee)
    {
        $evaluations = PerformanceEvaluation::with(['scores.question.category', 'evaluator'])
            ->where('period_id', $period->id)
            ->where('evaluatee_id', $employee->id)
            ->get();

        if ($evaluations->isEmpty()) {
            return redirect()->back()->with('error', 'No completed evaluations found.');
        }

        $scores = collect();
        foreach ($evaluations as $evaluation) {
            $scores = $scores->concat($evaluation->scores);
        }

        $categoryScores = $scores->groupBy(function ($score) {
            return optional(optional($score->question)->category)->name ?? 'Unknown';
        });

        $categoryResults = [];
        foreach ($categoryScores as $category => $catScores) {
            $questionScores = $catScores->groupBy('question_id')->map(function ($item) {
                $firstItem = $item->first();
                $question = $firstItem->question;
                
                if ($question->question_type === 'score') {
                    return [
                        'question' => $question->question_text,
                        'question_type' => 'score',
                        'average_score' => round($item->avg('score'), 2),
                        'comments' => $item->pluck('comment')->filter()->values()->all()
                    ];
                } else {
                    return [
                        'question' => $question->question_text,
                        'question_type' => 'text',
                        'text_answers' => $item->pluck('text_answer')->filter()->values()->all(),
                        'comments' => $item->pluck('comment')->filter()->values()->all()
                    ];
                }
            });
            
            // Only calculate average for categories with score-type questions
            $scoreTypeQuestions = $catScores->filter(function ($score) {
                return $score->question->question_type === 'score';
            });
            
            $categoryAverage = $scoreTypeQuestions->isEmpty() ? 
                null : round($scoreTypeQuestions->avg('score'), 2);

            $categoryResults[] = [
                'name' => $category,
                'average' => $categoryAverage,
                'questions' => $questionScores
            ];
        }

        // Only calculate overall average for score-type questions
        $scoreTypeScores = $scores->filter(function ($score) {
            return $score->question->question_type === 'score';
        });
        
        $overallAverage = $scoreTypeScores->isEmpty() ? 
            null : round($scoreTypeScores->avg('score'), 2);

        return view('hrd.performance.results.employee', compact('period', 'employee', 'categoryResults', 'overallAverage'));
    }

    // Handle evaluation form submission
    public function submitEvaluation(Request $request, PerformanceEvaluation $evaluation)
    {
        // Debug information
        \Illuminate\Support\Facades\Log::info('Submit Evaluation Request', [
            'evaluation_id' => $evaluation->id,
            'request_data' => $request->all(),
            'scores' => $request->scores,
            'text_answers' => $request->text_answers
        ]);
        
        // Log DB connection status
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            \Illuminate\Support\Facades\Log::info("Database is connected");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Database connection failed: " . $e->getMessage());
        }
        
        // Validate the request
        $request->validate([
            'scores.*' => 'nullable|integer|min:1|max:5',
            'text_answers.*' => 'nullable|string|max:1000',
            'comments.*' => 'nullable|string|max:500',
        ]);

        // Check if the evaluation is already completed
        if ($evaluation->status === 'completed') {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This evaluation has already been submitted.'
                ], 422);
            }
            
            return redirect()->back()->with('error', 'This evaluation has already been submitted.');
        }

        // Get the authenticated user
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        // Check if user is authorized to submit this evaluation
        if ($evaluation->evaluator_id != $employee->id) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to submit this evaluation.'
                ], 403);
            }
            
            return redirect()->back()->with('error', 'You are not authorized to submit this evaluation.');
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // Get all questions being evaluated
            $questionIds = array_unique(
                array_merge(
                    array_keys($request->scores ?? []), 
                    array_keys($request->text_answers ?? [])
                )
            );
            
            // Save scores, text answers, and comments
            foreach ($questionIds as $questionId) {
                $question = PerformanceQuestion::find($questionId);
                if (!$question) continue;
                $score = 0; // Default to 0 instead of null to satisfy NOT NULL constraint
                $textAnswer = null;
                $comment = $request->comments[$questionId] ?? null;
                if ($question->question_type === 'score') {
                    // Use the score from the request, or default to 0 if missing
                    $score = $request->scores[$questionId] ?? 0;
                } else {
                    $textAnswer = $request->text_answers[$questionId] ?? '';
                }
                
                // Log each score being processed
                \Illuminate\Support\Facades\Log::info("Processing question", [
                    'question_id' => $questionId,
                    'score' => $score,
                    'text_answer' => $textAnswer,
                    'comment' => $comment
                ]);
                
                try {
                    // Ensure the score is never null (0 for non-score questions or if missing)
                    $finalScore = $question->question_type === 'score' ? ($score ?: 0) : 0;
                    
                    $evaluation->scores()->updateOrCreate(
                        ['question_id' => $questionId],
                        [
                            'score' => $finalScore,
                            'text_answer' => $textAnswer ?: '',
                            'comment' => $comment
                        ]
                    );
                    \Illuminate\Support\Facades\Log::info("Score saved successfully for question " . $questionId);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error saving score: " . $e->getMessage(), [
                        'question_id' => $questionId,
                        'evaluation_id' => $evaluation->id,
                        'final_score' => $finalScore ?? 0,
                        'text_answer' => $textAnswer ?: ''
                    ]);
                    throw $e; // Re-throw to trigger the transaction rollback
                }
            }

            // Mark evaluation as completed
            $evaluation->status = 'completed';
            $evaluation->completed_at = now();
            $evaluation->save();

            // Check if period is complete
            $this->checkPeriodCompletion($evaluation->period);                // No need for fallback direct SQL now that we understand the issue

            DB::commit();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Evaluation submitted successfully.'
                ]);
            }
            
            return redirect()->route('hrd.performance.my-evaluations')
                ->with('success', 'Evaluation submitted successfully.');
                
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while submitting the evaluation: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'An error occurred while submitting the evaluation.')
                ->withInput();
        }
    }

    // Check if all evaluations for a period are completed and mark the period as completed if so
    private function checkPeriodCompletion(PerformanceEvaluationPeriod $period)
    {
        $totalEvaluations = PerformanceEvaluation::where('period_id', $period->id)->count();
        $completedEvaluations = PerformanceEvaluation::where('period_id', $period->id)
            ->where('status', 'completed')
            ->count();

        if ($totalEvaluations > 0 && $totalEvaluations === $completedEvaluations) {
            $period->status = 'completed';
            $period->completed_at = now();
            $period->save();
        }
    }
}
