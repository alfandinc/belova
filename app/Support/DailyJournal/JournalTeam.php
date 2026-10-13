<?php

namespace App\Support\DailyJournal;

use App\Http\Controllers\HRD\Concerns\ResolvesDirectManagerApprovals;
use App\Models\HRD\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Who a user can see / give Daily Journal tasks to, and who receives their daily report.
 * Same position hierarchy as the HRD approvals (hrd_position_division).
 */
class JournalTeam
{
    use ResolvesDirectManagerApprovals;

    public const ALL_TASK_ROLES = ['Hrd', 'Admin', 'Ceo'];

    /** Per request memo: the journal endpoints ask several times per request. */
    private array $memo = [];

    /**
     * true = everyone, list of user ids = direct subordinates, null = no team.
     * Cached for 10 minutes: the position structure rarely changes and resolving it takes several queries.
     */
    public function memberUserIds(User $user): array|bool|null
    {
        if ($user->hasAnyRole(self::ALL_TASK_ROLES)) {
            return true;
        }

        if (!array_key_exists($user->id, $this->memo)) {
            $this->memo[$user->id] = Cache::remember(
                'daily_journal_team:' . $user->id,
                600,
                fn () => ['ids' => $this->resolveMemberUserIds($user)]
            )['ids'];
        }

        return $this->memo[$user->id];
    }

    private function resolveMemberUserIds(User $user): ?array
    {
        $employee = $user->employee;
        if (!$employee) {
            return null;
        }

        $employeeIds = $this->getSubordinateEmployeeIds($employee);
        if (empty($employeeIds)) {
            return null;
        }

        return Employee::query()
            ->whereIn('id', $employeeIds)
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $user->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function canManage(User $user, int $memberUserId): bool
    {
        if ($memberUserId === (int) $user->id) {
            return false;
        }

        $ids = $this->memberUserIds($user);

        return $ids === true || (is_array($ids) && in_array($memberUserId, $ids, true));
    }

    /** User ids of the direct manager(s), who receive the daily report. */
    public function managerUserIds(User $user): array
    {
        $employee = $user->employee;
        if (!$employee) {
            return [];
        }

        $employeeIds = $this->getDirectManagerEmployeeIds($employee);
        if (empty($employeeIds)) {
            return [];
        }

        return Employee::query()
            ->whereIn('id', $employeeIds)
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $user->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
