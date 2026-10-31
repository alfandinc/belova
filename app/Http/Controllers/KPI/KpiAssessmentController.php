<?php

namespace App\Http\Controllers\KPI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            $indicatorNotes = \App\Models\KPI\KpiIndicator::whereIn('id', $scores->keys())->pluck('notes', 'id');
            $indicators = $scores->map(function($s) use ($indicatorNotes){
                return (object)[
                    'indicator' => (object)[
                        'indicator_name' => $s->ss_indicator_name,
                        'id' => $s->indicators_id,
                        'notes' => $indicatorNotes->get($s->indicators_id),
                    ],
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

        // a specific-position evaluator assesses the employee once: offer the same scores for the employee's other positions
        $siblings = $this->pendingSpecificSiblings($assessment)->map(function ($sibling) {
            return [
                'id' => $sibling->id,
                'position' => optional($sibling->evaluateePosition)->name ?? '-',
            ];
        })->values();

        return response()->json([
            'message' => $canManageOverride ? 'Assessment updated.' : 'Assessment saved',
            'siblings' => $siblings,
            'apply_siblings_url' => $siblings->isNotEmpty() ? route('kpi.kpi_assessments.apply_siblings', $assessment) : null,
            'evaluatee' => optional($assessment->evaluateeEmployee)->nama ?? optional($assessment->evaluateeEmployee)->name,
        ]);
    }

    /**
     * Copy the scores of a done specific_position assessment to the same evaluator's pending
     * assessments on the employee's other positions. Indicators not scored here take the average raw score.
     */
    public function applyToSiblings(KpiAssessment $assessment)
    {
        abort_unless($this->canAccessAssessment($assessment), 403);

        if ($assessment->assessment_type !== 'specific_position' || $assessment->status !== 'done') {
            return response()->json(['message' => 'Penilaian ini belum selesai atau bukan penilai khusus.'], 422);
        }

        $pool = \App\Models\KPI\KpiScore::where('assessment_id', $assessment->id)->get()->keyBy('indicators_id');
        if ($pool->isEmpty()) {
            return response()->json(['message' => 'Penilaian ini belum punya nilai.'], 422);
        }
        $applied = DB::transaction(function () use ($assessment, $pool) {
            $applied = 0;
            foreach ($this->pendingSpecificSiblings($assessment) as $sibling) {
                if ($sibling->scores->isEmpty()) {
                    continue;
                }
                foreach ($sibling->scores as $s) {
                    $source = $pool->get($s->indicators_id);
                    $rawScore = $source ? (float) $source->score : \App\Models\KPI\KpiScore::fallbackRawScore($pool, $s);
                    $s->score = round($rawScore, 2);
                    $s->notes = $source?->notes;
                    $s->final_calculated_score = round(($rawScore / 5.0) * (((float) ($s->ss_indicator_weight_percentage ?? 0)) / 100.0) * ((float) ($s->ss_category_weight_percentage ?? 0)), 2);
                    $s->save();
                }
                $sibling->status = 'done';
                $sibling->save();
                $applied++;
            }
            return $applied;
        });

        return response()->json(['message' => $applied . ' posisi lain sudah diisi dengan penilaian yang sama.', 'applied' => $applied]);
    }

    private function pendingSpecificSiblings(KpiAssessment $assessment)
    {
        if ($assessment->assessment_type !== 'specific_position') {
            return collect();
        }

        return KpiAssessment::with(['evaluateePosition', 'scores'])
            ->where('period_id', $assessment->period_id)
            ->where('assessment_type', 'specific_position')
            ->where('evaluator_employee_id', $assessment->evaluator_employee_id)
            ->where('evaluator_position_id', $assessment->evaluator_position_id)
            ->where('evaluatee_employee_id', $assessment->evaluatee_employee_id)
            ->where('id', '!=', $assessment->id)
            ->where('status', 'pending')
            ->get();
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
