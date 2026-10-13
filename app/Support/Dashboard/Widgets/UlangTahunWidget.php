<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\HRD\Employee;
use App\Models\User;
use Carbon\Carbon;

/** Active employees whose birthday falls today or within the next week. */
class UlangTahunWidget implements Widget
{
    private const DAYS_AHEAD = 7;

    public function data(User $user, array $filter): array
    {
        $today = Carbon::today();

        $people = Employee::query()
            ->whereNotNull('tanggal_lahir')
            ->get(['id', 'nama', 'tanggal_lahir', 'photo'])
            ->map(function ($employee) use ($today) {
                $birth = Carbon::parse($employee->tanggal_lahir);
                // Feb 29 birthdays fall on Feb 28 in non-leap years
                $next = Carbon::create($today->year, $birth->month, min($birth->day, Carbon::create($today->year, $birth->month, 1)->daysInMonth));
                if ($next->lt($today)) {
                    $next = Carbon::create($today->year + 1, $birth->month, min($birth->day, Carbon::create($today->year + 1, $birth->month, 1)->daysInMonth));
                }

                return (object) [
                    'nama' => $employee->nama,
                    'date' => $next,
                    'days' => (int) $today->diffInDays($next),
                    'age' => $next->year - $birth->year,
                ];
            })
            ->filter(fn ($p) => $p->days <= self::DAYS_AHEAD)
            ->sortBy('days')
            ->values();

        return [
            'people' => $people->all(),
            'todayCount' => $people->where('days', 0)->count(),
            'daysAhead' => self::DAYS_AHEAD,
        ];
    }
}
