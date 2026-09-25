<?php

namespace App\Http\Controllers\HRD\Concerns;

use App\Models\HRD\Employee;

trait ResolvesDirectManagerApprovals
{
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
        $managerPositionIds = $employee->positions()->pluck('hrd_position.id');

        if ($managerPositionIds->isEmpty()) {
            return [];
        }

        return Employee::query()
            ->where('id', '!=', $employee->id)
            ->whereHas('positions.parentPositions', function ($query) use ($managerPositionIds) {
                $query->whereIn('hrd_position.id', $managerPositionIds);
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