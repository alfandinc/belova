<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\KpiAssessment;
use App\Models\HRD\KpiAssessmentScore;
use App\Models\HRD\KpiAssessmentIndicator;
use App\Models\HRD\KpiAssessmentPeriod;
use App\Services\HRD\KpiAssessmentAssignmentService;
use App\Services\HRD\KpiAssessmentWeightService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KpiAssessmentPeriodController extends Controller
{
    public function __construct(
        private readonly KpiAssessmentAssignmentService $assignmentService,
        private readonly KpiAssessmentWeightService $weightService,
    )
    {
    }

    public function index(): View
    {
        $this->authorizeManagement();

        $periods = KpiAssessmentPeriod::withCount('assessments')
            ->orderByDesc('assessment_month')
            ->get();

        return view('hrd.kpi-assessments.periods.index', compact('periods'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'assessment_month' => ['required', 'date_format:Y-m'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        if (!KpiAssessmentIndicator::where('is_active', true)->exists()) {
            return back()->withInput()->with('error', 'Buat minimal satu indikator aktif sebelum membuat periode KPI Assessment.');
        }

        $assessmentMonth = Carbon::createFromFormat('Y-m', $validated['assessment_month'])->startOfMonth();

        if (KpiAssessmentPeriod::whereDate('assessment_month', $assessmentMonth)->exists()) {
            return back()->withInput()->with('error', 'Periode untuk bulan tersebut sudah ada.');
        }

        DB::transaction(function () use ($validated, $assessmentMonth) {
            $period = KpiAssessmentPeriod::create([
                'name' => $validated['name'] ?: 'KPI Assessment ' . $assessmentMonth->translatedFormat('F Y'),
                'assessment_month' => $assessmentMonth,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            $this->assignmentService->initializePeriod($period);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Periode KPI Assessment berhasil dibuat dan disnapshot.',
            ]);
        }

        return redirect()->route('hrd.kpi_assessments.periods.index')
            ->with('success', 'Periode KPI Assessment berhasil dibuat dan disnapshot.');
    }

    public function show(KpiAssessmentPeriod $period): View
    {
        $this->authorizeManagement();

        $assessments = $period->assessments()
            ->with(['evaluatee.division', 'evaluatee.position', 'scores.periodIndicator'])
            ->orderBy('evaluatee_id')
            ->get()
            ->groupBy('evaluatee_id');

        $rows = $assessments->map(function ($group) {
            $evaluatee = $group->first()->evaluatee;

            return [
                'employee' => $evaluatee,
                'submitted_count' => $group->where('status', 'submitted')->count(),
                'pending_count' => $group->where('status', 'pending')->count(),
                'total_score' => $this->calculateFinalScore($group),
                'ceo_score' => optional($group->firstWhere('evaluator_type', 'ceo'))->total_score,
                'manager_score' => optional($group->firstWhere('evaluator_type', 'manager'))->total_score,
                'hrd_score' => optional($group->firstWhere('evaluator_type', 'hrd'))->total_score,
                'head_manager_score' => optional($group->firstWhere('evaluator_type', 'head_manager'))->total_score,
            ];
        })->values();

        return view('hrd.kpi-assessments.periods.show', [
            'period' => $period,
            'rows' => $rows,
        ]);
    }

    public function close(Request $request, KpiAssessmentPeriod $period): JsonResponse|RedirectResponse
    {
        $this->authorizeManagement();

        if ($period->status === 'closed') {
            return back()->with('error', 'Periode ini sudah ditutup.');
        }

        $period->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Periode KPI Assessment ditutup. Data periode ini tetap immutable.',
            ]);
        }

        return redirect()->route('hrd.kpi_assessments.periods.show', $period)
            ->with('success', 'Periode KPI Assessment ditutup. Data periode ini tetap immutable.');
    }

    private function authorizeManagement(): void
    {
        abort_unless(Auth::user()?->hasAnyRole(['Hrd', 'Admin']), 403);
    }

    private function calculateFinalScore(Collection $group): float
    {
        /** @var KpiAssessment|null $firstAssessment */
        $firstAssessment = $group->first();

        if (!$firstAssessment?->evaluatee) {
            return 0.0;
        }

        $effectiveWeights = $this->weightService->effectiveWeights(
            $this->weightService->indicatorsForEmployee($firstAssessment->period_id, $firstAssessment->evaluatee)
        );

        $submittedScores = $group
            ->where('status', 'submitted')
            ->flatMap(fn (KpiAssessment $assessment) => $assessment->scores)
            ->filter(fn (KpiAssessmentScore $score) => (bool) $score->periodIndicator)
            ->keyBy('period_indicator_id');

        return round($submittedScores->sum(function (KpiAssessmentScore $score) use ($effectiveWeights) {
            $maxScore = max(1, (int) ($score->periodIndicator->max_score ?? 5));

            return ($score->score / $maxScore) * (float) $effectiveWeights->get($score->period_indicator_id, 0);
        }), 2);
    }
}