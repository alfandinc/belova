<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\Employee;
use App\Models\HRD\KpiAssessment;
use App\Models\HRD\KpiAssessmentPeriodIndicator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KpiAssessmentController extends Controller
{
    public function myAssessments(): View|RedirectResponse
    {
        $employee = $this->currentEmployee();
        if (!$employee) {
            return back()->with('error', 'Akun ini belum terhubung ke data pegawai.');
        }

        $assessments = KpiAssessment::with(['period', 'evaluatee.division', 'evaluatee.position'])
            ->where('evaluator_id', $employee->id)
            ->orderByDesc('id')
            ->get();

        return view('hrd.kpi-assessments.my-assessments', compact('assessments'));
    }

    public function fill(KpiAssessment $assessment): View|RedirectResponse
    {
        $employee = $this->currentEmployee();
        if (!$employee || $employee->id !== $assessment->evaluator_id) {
            abort(403);
        }

        $assessment->load(['period', 'evaluatee.division', 'evaluatee.position', 'evaluatee.user.roles', 'scores']);

        $indicators = $this->relevantIndicators($assessment)->get();
        $scores = $assessment->scores->keyBy('period_indicator_id');

        return view('hrd.kpi-assessments.fill', compact('assessment', 'indicators', 'scores'));
    }

    public function submit(Request $request, KpiAssessment $assessment): JsonResponse|RedirectResponse
    {
        $employee = $this->currentEmployee();
        if (!$employee || $employee->id !== $assessment->evaluator_id) {
            abort(403);
        }

        if ($assessment->status === 'submitted') {
            return redirect()->route('hrd.kpi_assessments.my')
                ->with('error', 'Assessment ini sudah disubmit.');
        }

        $assessment->load('period');
        if ($assessment->period->status === 'closed') {
            return redirect()->route('hrd.kpi_assessments.my')
                ->with('error', 'Periode sudah ditutup. Assessment tidak bisa diubah lagi.');
        }

        $indicators = $this->relevantIndicators($assessment)->get();
        if ($indicators->isEmpty()) {
            return redirect()->route('hrd.kpi_assessments.my')
                ->with('error', 'Tidak ada indikator yang relevan untuk assessment ini.');
        }

        $rules = [];
        foreach ($indicators as $indicator) {
            $rules['scores.' . $indicator->id] = $indicator->indicator_type === 'global'
                ? ['required', 'numeric', 'min:0', 'max:' . (float) $indicator->max_score]
                : ['required', 'integer', 'min:1', 'max:' . (int) $indicator->max_score];
            $rules['notes.' . $indicator->id] = ['nullable', 'string', 'max:1000'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($assessment, $indicators, $validated) {
            foreach ($indicators as $indicator) {
                $assessment->scores()->updateOrCreate(
                    ['period_indicator_id' => $indicator->id],
                    [
                        'score' => $validated['scores'][$indicator->id],
                        'note' => $validated['notes'][$indicator->id] ?? null,
                    ]
                );
            }

            $assessment->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'KPI Assessment berhasil disimpan.',
            ]);
        }

        return redirect()->route('hrd.kpi_assessments.my')
            ->with('success', 'KPI Assessment berhasil disimpan.');
    }

    private function relevantIndicators(KpiAssessment $assessment)
    {
        $assessment->loadMissing(['evaluatee.user.roles']);

        $applicableScopes = $this->resolveApplicableScopes($assessment);

        return KpiAssessmentPeriodIndicator::query()
            ->where('period_id', $assessment->period_id)
            ->whereIn('applicability_scope', $applicableScopes)
            ->where(function ($query) use ($assessment) {
                $query->whereNull('position_id')
                    ->orWhere('position_id', $assessment->position_id);
            })
            ->orderByRaw("CASE WHEN indicator_type = 'global' THEN 0 ELSE 1 END")
            ->orderBy('name');
    }

    private function resolveApplicableScopes(KpiAssessment $assessment): array
    {
        if ($assessment->evaluator_type === 'manager') {
            return ['manager_to_employee'];
        }

        if ($assessment->evaluator_type === 'head_manager') {
            return $this->isHrdTarget($assessment)
                ? ['head_manager_to_hrd']
                : ['head_manager_to_manager'];
        }

        if ($assessment->evaluator_type === 'hrd') {
            if ($this->isHeadManagerTarget($assessment)) {
                return ['hrd_to_all', 'hrd_to_head_manager'];
            }

            if ($this->isManagerTarget($assessment)) {
                return ['hrd_to_all', 'hrd_to_manager'];
            }

            return ['hrd_to_all', 'hrd_to_employee'];
        }

        return [];
    }

    private function isManagerTarget(KpiAssessment $assessment): bool
    {
        $user = $assessment->evaluatee?->user;
        return (bool) $user?->hasAnyRole(['Manager', 'manager']);
    }

    private function isHeadManagerTarget(KpiAssessment $assessment): bool
    {
        $user = $assessment->evaluatee?->user;
        return (bool) $user?->hasAnyRole(['Head Manager', 'HeadManager', 'HEAD MANAGER', 'head manager', 'Head_Manager']);
    }

    private function isHrdTarget(KpiAssessment $assessment): bool
    {
        $user = $assessment->evaluatee?->user;
        return (bool) $user?->hasAnyRole(['Hrd', 'HRD', 'hrd']);
    }

    private function currentEmployee(): ?Employee
    {
        return Employee::where('user_id', Auth::id())->first();
    }
}