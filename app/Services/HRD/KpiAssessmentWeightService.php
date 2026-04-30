<?php

namespace App\Services\HRD;

use App\Models\HRD\Employee;
use App\Models\HRD\KpiAssessment;
use App\Models\HRD\KpiAssessmentPeriodIndicator;
use Illuminate\Support\Collection;

class KpiAssessmentWeightService
{
    public function determineTargetRole(Employee $employee): string
    {
        $user = $employee->user;

        if ($user?->hasAnyRole(['Hrd', 'HRD', 'hrd'])) {
            return 'hrd';
        }

        if ($user?->hasAnyRole(['Head Manager', 'HeadManager', 'HEAD MANAGER', 'head manager', 'Head_Manager'])) {
            return 'head_manager';
        }

        if ($user?->hasAnyRole(['Manager', 'manager'])) {
            return 'manager';
        }

        return 'employee';
    }

    public function applicableScopesForTargetRole(string $targetRole): array
    {
        return match ($targetRole) {
            'hrd' => ['head_manager_to_hrd'],
            'head_manager' => ['hrd_to_all', 'hrd_to_head_manager', 'ceo_to_head_manager'],
            'manager' => ['hrd_to_all', 'hrd_to_manager', 'head_manager_to_manager'],
            default => ['hrd_to_all', 'hrd_to_employee', 'manager_to_employee'],
        };
    }

    public function indicatorsForEmployee(int $periodId, Employee $employee): Collection
    {
        $targetRole = $this->determineTargetRole($employee);
        $scopes = $this->applicableScopesForTargetRole($targetRole);

        return KpiAssessmentPeriodIndicator::query()
            ->where('period_id', $periodId)
            ->whereIn('applicability_scope', $scopes)
            ->where(function ($query) use ($employee) {
                $query->whereNull('position_id')
                    ->orWhere('position_id', $employee->position_id);
            })
            ->orderBy('indicator_type')
            ->orderBy('name')
            ->get();
    }

    public function effectiveWeights(Collection $indicators): Collection
    {
        $globalTotal = (float) $indicators
            ->where('indicator_type', 'global')
            ->sum(fn ($indicator) => (float) $indicator->weight_percentage);

        $technicalTotal = (float) $indicators
            ->where('indicator_type', 'technical')
            ->sum(fn ($indicator) => (float) $indicator->weight_percentage);

        $technicalShare = max(0.0, 100.0 - $globalTotal);

        return $indicators->mapWithKeys(function ($indicator) use ($technicalTotal, $technicalShare) {
            $rawWeight = (float) $indicator->weight_percentage;

            if ($indicator->indicator_type === 'global') {
                return [$indicator->id => round($rawWeight, 2)];
            }

            if ($technicalTotal <= 0) {
                return [$indicator->id => 0.0];
            }

            return [$indicator->id => round(($rawWeight / $technicalTotal) * $technicalShare, 2)];
        });
    }

    public function effectiveWeightsForAssessment(KpiAssessment $assessment): Collection
    {
        $assessment->loadMissing('evaluatee.user.roles');

        return $this->effectiveWeights(
            $this->indicatorsForEmployee($assessment->period_id, $assessment->evaluatee)
        );
    }
}