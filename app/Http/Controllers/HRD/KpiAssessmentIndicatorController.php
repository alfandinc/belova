<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\KpiAssessmentIndicator;
use App\Models\HRD\Employee;
use App\Models\HRD\Position;
use App\Services\HRD\KpiAssessmentWeightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class KpiAssessmentIndicatorController extends Controller
{
    public function __construct(private readonly KpiAssessmentWeightService $weightService)
    {
    }

    public function index(): View
    {
        $this->authorizeManagement();

        $indicators = KpiAssessmentIndicator::with('position')
            ->orderBy('indicator_type')
            ->orderBy('applicability_scope')
            ->orderBy('position_id')
            ->orderBy('name')
            ->get();

        $positions = Position::with(['division', 'employees.user.roles'])
            ->orderBy('name')
            ->get();

        $activeIndicators = $indicators->where('is_active', true);
        $technicalWeightTotals = $activeIndicators
            ->where('indicator_type', 'technical')
            ->groupBy('position_id')
            ->map(fn ($group) => round($group->sum('weight_percentage'), 2));

        return view('hrd.kpi-assessments.indicators.index', [
            'indicators' => $indicators,
            'positions' => $positions,
            'applicabilityOptions' => KpiAssessmentIndicator::APPLICABILITY_OPTIONS,
            'globalWeightTotal' => round($activeIndicators->where('indicator_type', 'global')->sum('weight_percentage'), 2),
            'technicalWeightTotals' => $technicalWeightTotals,
        ]);
    }

    public function previewData()
    {
        $this->authorizeManagement();

        $previews = $this->buildAllPositionPreviews();

        return DataTables::of($previews)
            ->addColumn('position_name', fn (array $preview) => $preview['position']->name)
            ->addColumn('division_name', fn (array $preview) => $preview['position']->division->name ?? 'Tanpa divisi')
            ->addColumn('target_role_badge', function (array $preview) {
                return '<span class="badge badge-info text-uppercase">' . e(str_replace('_', ' ', $preview['target_role'])) . '</span>';
            })
            ->addColumn('formula_display', fn (array $preview) => $preview['formula'] ?: 'Belum ada formula aktif')
            ->addColumn('total_weight_display', fn (array $preview) => number_format($preview['total_weight'], 2) . '%')
            ->addColumn('action', function (array $preview) {
                return '<button type="button" class="btn btn-sm btn-outline-primary preview-detail-btn" data-url="' .
                    route('hrd.kpi_assessments.indicators.preview.show', $preview['position']->id) . '">Detail</button>';
            })
            ->rawColumns(['target_role_badge', 'action'])
            ->make(true);
    }

    public function previewShow(Position $position)
    {
        $this->authorizeManagement();

        $position->load(['division', 'employees.user.roles']);
        $preview = $this->buildPositionPreview($position, $this->activeIndicators());

        return response()->json([
            'position_name' => $position->name,
            'division_name' => $position->division->name ?? 'Tanpa divisi',
            'target_role' => str_replace('_', ' ', $preview['target_role']),
            'formula' => $preview['formula'] ?: 'Belum ada formula aktif untuk jabatan ini.',
            'total_weight' => number_format($preview['total_weight'], 2) . '%',
            'sections' => $preview['sections']->map(function (array $section) {
                return [
                    'title' => $section['title'],
                    'short_label' => $section['short_label'],
                    'total_weight' => number_format($section['effective_total_weight'], 2) . '%',
                    'raw_total_weight' => number_format($section['total_weight'], 2) . '%',
                    'indicators' => $section['indicators']->map(function (KpiAssessmentIndicator $indicator) {
                        return [
                            'name' => $indicator->name,
                            'type' => strtoupper($indicator->indicator_type),
                            'applicability' => $indicator->applicabilityLabel(),
                            'weight' => number_format($indicator->weight_percentage, 2) . '%',
                            'description' => $indicator->description ?: '-',
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeManagement();

        $validated = $this->validateIndicator($request);
        $validated['created_by'] = Auth::id();

        KpiAssessmentIndicator::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Indikator KPI Assessment berhasil ditambahkan.',
            ]);
        }

        return redirect()->route('hrd.kpi_assessments.indicators.index')
            ->with('success', 'Indikator KPI Assessment berhasil ditambahkan.');
    }

    public function update(Request $request, KpiAssessmentIndicator $indicator): JsonResponse|RedirectResponse
    {
        $this->authorizeManagement();

        $indicator->update($this->validateIndicator($request));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Indikator KPI Assessment berhasil diperbarui.',
            ]);
        }

        return redirect()->route('hrd.kpi_assessments.indicators.index')
            ->with('success', 'Indikator KPI Assessment berhasil diperbarui.');
    }

    public function destroy(Request $request, KpiAssessmentIndicator $indicator): JsonResponse|RedirectResponse
    {
        $this->authorizeManagement();

        $indicator->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Indikator KPI Assessment berhasil dihapus.',
            ]);
        }

        return redirect()->route('hrd.kpi_assessments.indicators.index')
            ->with('success', 'Indikator KPI Assessment berhasil dihapus.');
    }

    private function validateIndicator(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'indicator_type' => ['required', 'in:global,technical'],
            'applicability_scope' => ['required', 'in:' . implode(',', array_keys(KpiAssessmentIndicator::APPLICABILITY_OPTIONS))],
            'position_id' => ['nullable', 'exists:hrd_position,id'],
            'weight_percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'max_score' => ['required', 'integer', 'min:2', 'max:5'],
            'score_label_1' => ['nullable', 'string', 'max:255'],
            'score_label_2' => ['nullable', 'string', 'max:255'],
            'score_label_3' => ['nullable', 'string', 'max:255'],
            'score_label_4' => ['nullable', 'string', 'max:255'],
            'score_label_5' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validated['indicator_type'] === 'global' && $this->scopeRequiresGlobal($validated['applicability_scope']) === false) {
            throw ValidationException::withMessages([
                'indicator_type' => 'Scope ini hanya cocok untuk indikator technical.',
            ]);
        }

        if ($validated['indicator_type'] === 'technical' && $this->scopeRequiresTechnical($validated['applicability_scope']) === false) {
            throw ValidationException::withMessages([
                'indicator_type' => 'Scope ini hanya cocok untuk indikator global.',
            ]);
        }

        if ($this->positionIsRequired($validated['applicability_scope']) && empty($validated['position_id'])) {
            throw ValidationException::withMessages([
                'position_id' => 'Scope ini wajib memilih jabatan target.',
            ]);
        }

        if (!$this->positionIsAllowed($validated['applicability_scope'])) {
            $validated['position_id'] = null;
        }

        $validated['is_active'] = (bool) Arr::get($validated, 'is_active', false);

        return $validated;
    }

    private function scopeRequiresGlobal(string $scope): bool
    {
        return in_array($scope, ['hrd_to_all', 'hrd_to_employee', 'hrd_to_manager', 'hrd_to_head_manager', 'ceo_to_head_manager', 'head_manager_to_manager', 'head_manager_to_hrd'], true);
    }

    private function scopeRequiresTechnical(string $scope): bool
    {
        return in_array($scope, ['manager_to_employee', 'head_manager_to_manager', 'head_manager_to_hrd'], true);
    }

    private function positionIsRequired(string $scope): bool
    {
        return in_array($scope, ['manager_to_employee'], true);
    }

    private function positionIsAllowed(string $scope): bool
    {
        return in_array($scope, ['manager_to_employee', 'head_manager_to_manager', 'head_manager_to_hrd'], true);
    }

    private function authorizeManagement(): void
    {
        abort_unless(Auth::user()?->hasAnyRole(['Admin']), 403);
    }

    private function buildPositionPreview(Position $position, Collection $activeIndicators): array
    {
        $targetRole = $this->determinePositionTargetRole($position);
        $sections = collect();
        $effectiveWeights = $this->weightService->effectiveWeights(
            $this->indicatorsForTargetRoleAndPosition($activeIndicators, $targetRole, $position->id)
        );

        $hrdScopes = match ($targetRole) {
            'manager' => ['hrd_to_all', 'hrd_to_manager'],
            'head_manager' => ['hrd_to_all', 'hrd_to_head_manager'],
            'hrd' => ['head_manager_to_hrd'],
            default => ['hrd_to_all', 'hrd_to_employee'],
        };

        $hrdIndicators = $this->filterIndicatorsForPosition($activeIndicators, $position->id, $hrdScopes);
        if ($hrdIndicators->isNotEmpty()) {
            $sections->push($this->makePreviewSection('Dinilai HRD', $hrdIndicators, 'HRD', $effectiveWeights));
        }

        if ($targetRole === 'employee') {
            $managerIndicators = $this->filterIndicatorsForPosition($activeIndicators, $position->id, ['manager_to_employee']);
            if ($managerIndicators->isNotEmpty()) {
                $sections->push($this->makePreviewSection('Dinilai Manager', $managerIndicators, 'Manager Divisi', $effectiveWeights));
            }
        }

        if ($targetRole === 'manager') {
            $headManagerIndicators = $this->filterIndicatorsForPosition($activeIndicators, $position->id, ['head_manager_to_manager']);
            if ($headManagerIndicators->isNotEmpty()) {
                $sections->push($this->makePreviewSection('Dinilai Head Manager', $headManagerIndicators, 'Head Manager', $effectiveWeights));
            }
        }

        if ($targetRole === 'hrd') {
            $headManagerIndicators = $this->filterIndicatorsForPosition($activeIndicators, $position->id, ['head_manager_to_hrd']);
            if ($headManagerIndicators->isNotEmpty()) {
                $sections->push($this->makePreviewSection('Dinilai Head Manager', $headManagerIndicators, 'Head Manager', $effectiveWeights));
            }
        }

        if ($targetRole === 'head_manager') {
            $ceoIndicators = $this->filterIndicatorsForPosition($activeIndicators, $position->id, ['ceo_to_head_manager']);
            if ($ceoIndicators->isNotEmpty()) {
                $sections->push($this->makePreviewSection('Dinilai CEO', $ceoIndicators, 'CEO', $effectiveWeights));
            }
        }

        $totalWeight = round($sections->sum('effective_total_weight'), 2);
        $formula = $sections->map(fn (array $section) => $section['short_label'] . ' ' . rtrim(rtrim(number_format($section['effective_total_weight'], 2, '.', ''), '0'), '.') . '%')
            ->implode(' + ');

        return [
            'position' => $position,
            'target_role' => $targetRole,
            'sections' => $sections,
            'total_weight' => $totalWeight,
            'formula' => $formula ? $formula . ' = ' . rtrim(rtrim(number_format($totalWeight, 2, '.', ''), '0'), '.') . '%' : null,
        ];
    }

    private function filterIndicatorsForPosition(Collection $indicators, int $positionId, array $scopes): Collection
    {
        return $indicators
            ->filter(function (KpiAssessmentIndicator $indicator) use ($positionId, $scopes) {
                if (!in_array($indicator->applicability_scope, $scopes, true)) {
                    return false;
                }

                return !$indicator->position_id || (int) $indicator->position_id === $positionId;
            })
            ->sortBy([
                ['indicator_type', 'asc'],
                ['name', 'asc'],
            ])
            ->values();
    }

    private function makePreviewSection(string $title, Collection $indicators, string $shortLabel, Collection $effectiveWeights): array
    {
        return [
            'title' => $title,
            'short_label' => $shortLabel,
            'indicators' => $indicators,
            'total_weight' => round($indicators->sum('weight_percentage'), 2),
            'effective_total_weight' => round($indicators->sum(fn (KpiAssessmentIndicator $indicator) => (float) $effectiveWeights->get($indicator->id, 0)), 2),
        ];
    }

    private function determinePositionTargetRole(Position $position): string
    {
        $employees = $position->employees;

        if ($employees->contains(fn (Employee $employee) => $employee->user?->hasAnyRole(['Hrd', 'HRD', 'hrd']))) {
            return 'hrd';
        }

        if ($employees->contains(fn (Employee $employee) => $employee->user?->hasAnyRole(['Head Manager', 'HeadManager', 'HEAD MANAGER', 'head manager', 'Head_Manager']))) {
            return 'head_manager';
        }

        if ($employees->contains(fn (Employee $employee) => $employee->user?->hasAnyRole(['Manager', 'manager']))) {
            return 'manager';
        }

        return 'employee';
    }

    private function indicatorsForTargetRoleAndPosition(Collection $activeIndicators, string $targetRole, int $positionId): Collection
    {
        $scopes = $this->weightService->applicableScopesForTargetRole($targetRole);

        return $activeIndicators
            ->filter(function (KpiAssessmentIndicator $indicator) use ($scopes, $positionId) {
                if (!in_array($indicator->applicability_scope, $scopes, true)) {
                    return false;
                }

                return !$indicator->position_id || (int) $indicator->position_id === $positionId;
            })
            ->values();
    }

    private function activeIndicators(): Collection
    {
        return KpiAssessmentIndicator::with('position')
            ->where('is_active', true)
            ->orderBy('indicator_type')
            ->orderBy('applicability_scope')
            ->orderBy('position_id')
            ->orderBy('name')
            ->get();
    }

    private function buildAllPositionPreviews(): Collection
    {
        $activeIndicators = $this->activeIndicators();

        return Position::with(['division', 'employees.user.roles'])
            ->orderBy('name')
            ->get()
            ->map(fn (Position $position) => $this->buildPositionPreview($position, $activeIndicators))
            ->filter(fn (array $preview) => $preview['sections']->isNotEmpty())
            ->values();
    }
}