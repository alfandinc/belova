<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\{
    PerformanceEvaluationPeriod,
    PerformanceEvaluation,
    PerformanceQuestion,
    Employee,
    Division
};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PerformanceEvaluationController extends Controller
{
    public function index(Request $request)
    {
        $periods = PerformanceEvaluationPeriod::orderBy('created_at', 'desc')->paginate(10);
        
        if ($request->ajax()) {
            // Return the full rendered view for AJAX requests
            return view('hrd.performance.periods.index', compact('periods'))->render();
        }
        
        return view('hrd.performance.periods.index', compact('periods'));
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
        $evaluations = PerformanceEvaluation::where('period_id', $period->id)->get();
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
            // Now we can safely use division relationship
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
            // Safety check for manager's division
            if (!$manager->division) continue;

            // Find all employees in this manager's division
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
            // Safety check for employee's division
            if (!$employee->division) continue;

            // Find the manager of this division
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

    public function myEvaluations()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return redirect()->back()->with('error', 'No employee record found for your account.');
        }

        $pendingEvaluations = PerformanceEvaluation::with(['evaluatee', 'period'])
            ->where('evaluator_id', $employee->id)
            ->where('status', 'pending')
            ->get();

        $completedEvaluations = PerformanceEvaluation::with(['evaluatee', 'period'])
            ->where('evaluator_id', $employee->id)
            ->where('status', 'completed')
            ->get();

        return view('hrd.performance.my-evaluations', compact('pendingEvaluations', 'completedEvaluations'));
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

        $isEvaluatorHRD = $evaluator->division instanceof Division && strtolower($evaluator->division->name) === 'human resources';
        $isEvaluatorManager = $evaluator->isManager();
        $isEvaluateeHRD = $evaluatee->division instanceof Division && strtolower($evaluatee->division->name) === 'human resources';
        $isEvaluateeManager = $evaluatee->isManager();

        if ($isEvaluatorHRD && $isEvaluateeManager) {
            return 'hrd_to_manager';
        } elseif ($isEvaluatorManager && !$isEvaluateeManager && !$isEvaluateeHRD) {
            return 'manager_to_employee';
        } elseif (!$isEvaluatorManager && !$isEvaluatorHRD && $isEvaluateeManager) {
            return 'employee_to_manager';
        } elseif ($isEvaluatorManager && $isEvaluateeHRD) {
            return 'manager_to_hrd';
        }

        // Default fallback
        return 'manager_to_employee';
    }

    public function results()
    {
        $periods = PerformanceEvaluationPeriod::where('status', 'completed')->get();
        return view('hrd.performance.results.index', compact('periods'));
    }

    public function periodResults(PerformanceEvaluationPeriod $period)
    {
        $employees = Employee::all();
        $averageScores = [];

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

                $categoryScores = $scores->groupBy(function ($score) {
                    return $score->question->category->name;
                });

                $categoryAverages = [];
                foreach ($categoryScores as $category => $catScores) {
                    $categoryAverages[$category] = round($catScores->avg('score'), 2);
                }

                $overallAverage = round($scores->avg('score'), 2);

                $averageScores[] = [
                    'employee' => $employee,
                    'categoryAverages' => $categoryAverages,
                    'overallAverage' => $overallAverage
                ];
            }
        }

        return view('hrd.performance.results.period', compact('period', 'averageScores'));
    }

    public function employeeResults(PerformanceEvaluationPeriod $period, Employee $employee)
    {
        $evaluations = PerformanceEvaluation::with(['scores.question.category', 'evaluator'])
            ->where('period_id', $period->id)
            ->where('evaluatee_id', $employee->id)
            ->where('status', 'completed')
            ->get();

        if ($evaluations->isEmpty()) {
            return redirect()->back()->with('error', 'No completed evaluations found.');
        }

        $scores = collect();
        foreach ($evaluations as $evaluation) {
            $scores = $scores->concat($evaluation->scores);
        }

        $categoryScores = $scores->groupBy(function ($score) {
            return $score->question->category->name;
        });

        $categoryResults = [];
        foreach ($categoryScores as $category => $catScores) {
            $questionScores = $catScores->groupBy('question_id')->map(function ($item) {
                return [
                    'question' => $item->first()->question->question_text,
                    'average_score' => round($item->avg('score'), 2),
                    'comments' => $item->pluck('comment')->filter()->values()->all()
                ];
            });

            $categoryResults[] = [
                'name' => $category,
                'average' => round($catScores->avg('score'), 2),
                'questions' => $questionScores
            ];
        }

        $overallAverage = round($scores->avg('score'), 2);

        return view('hrd.performance.results.employee', compact('period', 'employee', 'categoryResults', 'overallAverage'));
    }

    // Handle evaluation form submission
    public function submitEvaluation(Request $request, PerformanceEvaluation $evaluation)
    {
        // Validate the request
        $request->validate([
            'scores.*' => 'required|integer|min:1|max:5',
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
            // Save scores and comments
            foreach ($request->scores as $questionId => $score) {
                $comment = $request->comments[$questionId] ?? null;
                
                $evaluation->scores()->create([
                    'question_id' => $questionId,
                    'score' => $score,
                    'comment' => $comment
                ]);
            }

            // Mark evaluation as completed
            $evaluation->status = 'completed';
            $evaluation->completed_at = now();
            $evaluation->save();

            // Check if period is complete
            $this->checkPeriodCompletion($evaluation->period);

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
