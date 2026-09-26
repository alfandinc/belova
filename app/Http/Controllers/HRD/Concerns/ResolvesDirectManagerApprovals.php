<?php

namespace App\Http\Controllers\HRD\Concerns;

use App\Models\HRD\Employee;

trait ResolvesDirectManagerApprovals
{
    protected function hasDirectSubordinates(?Employee $employee): bool
    {
        return $employee ? !empty($this->getSubordinateEmployeeIds($employee)) : false;
    }

    protected function canAccessDirectApprovalTeam($user): bool
    {
        return $user && $this->hasDirectSubordinates($user->employee ?? null);
    }

    protected function getDirectParentPositionIds(Employee $employee): array
    {
        return $employee->positions()
            ->with('parentPositions:id')
            ->get()
            ->flatMap(function ($position) {
                return $position->directParentPositions()->pluck('id');
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function getSubordinateEmployeeIds(Employee $employee): array
    {
        $childPositionIds = $employee->positions()
            ->with('childPositions:id')
            ->get()
            ->flatMap(function ($position) {
                return $position->directChildPositions()->pluck('id');
            })
            ->filter()
            ->unique()
            ->values();

        if ($childPositionIds->isEmpty()) {
            return [];
        }

        return Employee::query()
            ->where('id', '!=', $employee->id)
            ->whereHas('positions', function ($query) use ($childPositionIds) {
                $query->whereIn('hrd_position.id', $childPositionIds->all());
            })
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }

    protected function getDirectManagerEmployeeIds(Employee $employee): array
    {
        $parentPositionIds = $this->getDirectParentPositionIds($employee);

        if (empty($parentPositionIds)) {
            return [];
        }

        return Employee::query()
            ->where('id', '!=', $employee->id)
            ->whereHas('positions', function ($query) use ($parentPositionIds) {
                $query->whereIn('hrd_position.id', $parentPositionIds);
            })
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }

    protected function canApproveAsDirectManager(?Employee $approver, ?Employee $requester): bool
    {
        if (!$approver || !$requester) {
            return false;
        }

        return in_array($approver->id, $this->getDirectManagerEmployeeIds($requester), true);
    }
}