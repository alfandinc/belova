<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HRD\{
    PerformanceEvaluation,
    PerformanceQuestion,
    PerformanceScore,
    Employee
};
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PerformanceScoreController extends Controller
{
    public function submitScores(Request $request, PerformanceEvaluation $evaluation)
    {
        // Check if user is authorized to submit this evaluation
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if ($evaluation->evaluator_id != $employee->id) {
            return redirect()->back()->with('error', 'You are not authorized to submit this evaluation.');
        }

        // Determine evaluation type
        $evaluationType = $this->determineEvaluationType($evaluation);

        // Get active questions for this evaluation type
        $questionIds = PerformanceQuestion::where('evaluation_type', $evaluationType)
            ->where('is_active', true)
            ->pluck('id');

        // Validate request
        $request->validate([
            'scores' => 'required|array',
            'scores.*' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|array',
        ]);

        // Process each score
        foreach ($request->scores as $questionId => $score) {
            // Check if question ID is valid
            if (!$questionIds->contains($questionId)) {
                continue;
            }

            PerformanceScore::updateOrCreate(
                [
                    'evaluation_id' => $evaluation->id,
                    'question_id' => $questionId,
                ],
                [
                    'score' => $score,
                    'comment' => $request->comments[$questionId] ?? null,
                ]
            );
        }

        // Mark evaluation as completed
        $evaluation->status = 'completed';
        $evaluation->completed_at = Carbon::now();
        $evaluation->save();

        return redirect()->route('hrd.performance.my-evaluations')
            ->with('success', 'Evaluation submitted successfully.');
    }

    private function determineEvaluationType(PerformanceEvaluation $evaluation)
    {
        $evaluator = $evaluation->evaluator;
        $evaluatee = $evaluation->evaluatee;

        $isEvaluatorHRD = $evaluator->division->name == 'HRD';
        $isEvaluatorManager = $evaluator->isManager();
        $isEvaluateeHRD = $evaluatee->division->name == 'HRD';
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
}
