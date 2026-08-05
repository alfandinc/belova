<?php

namespace App\Http\Controllers\KPI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HRD\Employee;
use App\Models\KPI\KpiAssessment;

class KpiAssessmentController extends Controller
{
    public function fill(KpiAssessment $assessment)
    {
        abort_unless($this->canAccessAssessment($assessment), 403);

        $scores = \App\Models\KPI\KpiScore::where('assessment_id', $assessment->id)->get()->keyBy('indicators_id');

        if ($scores->isNotEmpty()) {
            // Build indicators list from the saved score snapshot (ss_*) so modal matches generated assessment
            $indicators = $scores->map(function($s){
                return (object)[
                    'indicator' => (object)[ 'indicator_name' => $s->ss_indicator_name, 'id' => $s->indicators_id ],
                    'weight_percentage' => $s->ss_indicator_weight_percentage ?? 0,
                    'category_name' => $s->ss_category_name ?? null,
                ];
            })->values();
        } else {
            $indicators = \App\Models\KPI\KpiPositionIndicator::where('position_id', $assessment->evaluatee_position_id)
                ->with('indicator')
                ->get();
        }

        return view('kpi.assessments.fill', compact('assessment', 'indicators', 'scores'));
    }

    public function submit(Request $request, KpiAssessment $assessment)
    {
        abort_unless($this->canAccessAssessment($assessment), 403);

        $canManageOverride = $this->canManageAssessmentOverrides();

        // Prevent re-submission if already done
        if ($assessment->status === 'done' && !$canManageOverride) {
            return response()->json(['message' => 'Assessment already submitted and cannot be changed.'], 409);
        }

        $validated = $request->validate([
            'scores' => ['required', 'array', 'min:1'],
            'scores.*' => ['required', 'numeric', 'between:1,5'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $data = $validated['scores'];
        $notes = $validated['notes'] ?? [];

        foreach ($data as $indicatorId => $score) {
            // try to fetch existing snapshot weights (ss_*) saved when assessment was generated
            $existing = \App\Models\KPI\KpiScore::where('assessment_id', $assessment->id)->where('indicators_id', $indicatorId)->first();

            $ss_indicator_weight = $existing ? ($existing->ss_indicator_weight_percentage ?? 0) : 0;
            $ss_category_weight = $existing ? ($existing->ss_category_weight_percentage ?? 0) : 0;

            // Compute final_calculated_score using formula: (S/5) * (W_i/100) * W_c
            $s_norm = ((float)$score) / 5.0;
            $wi = ((float)$ss_indicator_weight) / 100.0;
            $wc = (float)$ss_category_weight;

            $final = round($s_norm * $wi * $wc, 2);

            \App\Models\KPI\KpiScore::updateOrCreate(
                ['assessment_id' => $assessment->id, 'indicators_id' => $indicatorId],
                [
                    'score' => $score,
                    'notes' => $notes[$indicatorId] ?? null,
                    'final_calculated_score' => $final,
                ]
            );
        }

        $assessment->status = 'done';
        if ($assessment->isDirty()) {
            $assessment->save();
        } else {
            $assessment->save();
        }

        return response()->json([
            'message' => $canManageOverride ? 'Assessment updated.' : 'Assessment saved',
        ]);
    }

    private function canAccessAssessment(KpiAssessment $assessment): bool
    {
        if ($this->canManageAssessmentOverrides()) {
            return true;
        }

        $employee = Employee::where('user_id', Auth::id())->first();

        return $employee && (int) $employee->id === (int) $assessment->evaluator_employee_id;
    }

    private function canManageAssessmentOverrides(): bool
    {
        return (bool) Auth::user()?->hasAnyRole(['Hrd', 'Admin']);
    }
}
