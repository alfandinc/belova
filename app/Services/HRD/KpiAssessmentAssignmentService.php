<?php

namespace App\Services\HRD;

use App\Models\HRD\Employee;
use App\Models\HRD\KpiAssessment;
use App\Models\HRD\KpiAssessmentIndicator;
use App\Models\HRD\KpiAssessmentPeriod;
use App\Models\HRD\KpiAssessmentPeriodIndicator;
use Illuminate\Database\Eloquent\Collection;

class KpiAssessmentAssignmentService
{
    private const ROLE_CEO = ['Ceo', 'CEO', 'ceo'];
    private const ROLE_HRD = ['Hrd', 'HRD', 'hrd'];
    private const ROLE_MANAGER = ['Manager', 'manager'];
    private const ROLE_HEAD_MANAGER = ['Head Manager', 'HeadManager', 'HEAD MANAGER', 'head manager', 'Head_Manager'];

    public function initializePeriod(KpiAssessmentPeriod $period): void
    {
        $this->snapshotIndicators($period);
        $this->generateAssignments($period);

        $period->update([
            'status' => 'active',
            'started_at' => now(),
            'snapshot_taken_at' => now(),
        ]);
    }

    private function snapshotIndicators(KpiAssessmentPeriod $period): void
    {
        $indicators = KpiAssessmentIndicator::query()
            ->where('is_active', true)
            ->orderBy('indicator_type')
            ->orderBy('position_id')
            ->orderBy('name')
            ->get();

        foreach ($indicators as $indicator) {
            KpiAssessmentPeriodIndicator::create([
                'period_id' => $period->id,
                'source_indicator_id' => $indicator->id,
                'name' => $indicator->name,
                'description' => $indicator->description,
                'indicator_type' => $indicator->indicator_type,
                'applicability_scope' => $indicator->applicability_scope,
                'position_id' => $indicator->position_id,
                'weight_percentage' => $indicator->weight_percentage,
                'max_score' => $indicator->max_score,
                'score_label_1' => $indicator->score_label_1,
                'score_label_2' => $indicator->score_label_2,
                'score_label_3' => $indicator->score_label_3,
                'score_label_4' => $indicator->score_label_4,
                'score_label_5' => $indicator->score_label_5,
            ]);
        }
    }

    private function generateAssignments(KpiAssessmentPeriod $period): void
    {
        $employees = Employee::with(['division', 'position', 'user.roles'])
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(status) <> ?', ['tidak aktif']);
            })
            ->get();

        $ceo = $this->firstByRole($employees, self::ROLE_CEO);
        $hrd = $this->firstByRole($employees, self::ROLE_HRD);
        $headManager = $this->firstHeadManager($employees);
        $divisionManagers = $employees
            ->filter(fn (Employee $employee) => $this->isManager($employee))
            ->groupBy('division_id')
            ->map(fn (Collection $group) => $group->first());

        foreach ($employees as $employee) {
            if ($this->isHeadManager($employee)) {
                if ($ceo) {
                    $this->createAssignment($period, $employee, $ceo, 'ceo');
                }
                if ($hrd) {
                    $this->createAssignment($period, $employee, $hrd, 'hrd');
                }
                continue;
            }

            if ($this->isHrd($employee)) {
                if ($headManager) {
                    $this->createAssignment($period, $employee, $headManager, 'head_manager');
                }
                continue;
            }

            if ($this->isManager($employee)) {
                if ($headManager) {
                    $this->createAssignment($period, $employee, $headManager, 'head_manager');
                }
                if ($hrd) {
                    $this->createAssignment($period, $employee, $hrd, 'hrd');
                }
                continue;
            }

            if ($employee->division_id && $divisionManagers->has($employee->division_id)) {
                $manager = $divisionManagers->get($employee->division_id);
                if ($manager && $manager->id !== $employee->id) {
                    $this->createAssignment($period, $employee, $manager, 'manager');
                }
            }

            if ($hrd) {
                $this->createAssignment($period, $employee, $hrd, 'hrd');
            }
        }
    }

    private function createAssignment(KpiAssessmentPeriod $period, Employee $evaluatee, Employee $evaluator, string $evaluatorType): void
    {
        KpiAssessment::firstOrCreate(
            [
                'period_id' => $period->id,
                'evaluatee_id' => $evaluatee->id,
                'evaluator_id' => $evaluator->id,
                'evaluator_type' => $evaluatorType,
            ],
            [
                'division_id' => $evaluatee->division_id,
                'position_id' => $evaluatee->position_id,
                'status' => 'pending',
            ]
        );
    }

    private function firstByRole(Collection $employees, array $roles): ?Employee
    {
        return $employees->first(fn (Employee $employee) => $this->hasAnyRole($employee, $roles));
    }

    private function firstHeadManager(Collection $employees): ?Employee
    {
        return $employees->first(fn (Employee $employee) => $this->isHeadManager($employee));
    }

    private function isHrd(Employee $employee): bool
    {
        return $this->hasAnyRole($employee, self::ROLE_HRD);
    }

    private function isHeadManager(Employee $employee): bool
    {
        return $this->hasAnyRole($employee, self::ROLE_HEAD_MANAGER);
    }

    private function isManager(Employee $employee): bool
    {
        return $this->hasAnyRole($employee, self::ROLE_MANAGER)
            && !$this->isHeadManager($employee)
            && !$this->isHrd($employee);
    }

    private function hasAnyRole(Employee $employee, array $roles): bool
    {
        if (!$employee->user) {
            return false;
        }

        foreach ($roles as $role) {
            if ($employee->user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }
}