<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\HRD\Employee;
use App\Models\User;
use App\Support\Dashboard\Fmt;
use Carbon\Carbon;

/** Active workforce by employment status, new joiners in the period, and contracts ending within 30 days. */
class EmployeeWidget implements Widget
{
    private const CONTRACT_WARNING_DAYS = 30;

    public function data(User $user, array $filter): array
    {
        $start = $filter['period_start']->copy()->startOfDay();
        $end = $filter['period_end']->copy()->endOfDay();
        $today = Carbon::today();

        // Employee's global scope already hides "tidak aktif"
        $active = Employee::query()->get(['id', 'nama', 'status', 'jenis_kelamin', 'tanggal_lahir', 'tanggal_masuk', 'kontrak_berakhir']);
        $count = $active->count();

        $statusCounts = $active->countBy(fn ($e) => strtolower((string) $e->status));
        $statuses = collect([
            ['Tetap', 'tetap', Fmt::SERIES[0]],
            ['Kontrak', 'kontrak', Fmt::SERIES[1]],
            ['Freelance', 'freelance', Fmt::SERIES[2]],
        ])->map(fn ($s) => [
            'label' => $s[0],
            'value' => Fmt::num($statusCounts[$s[1]] ?? 0),
            'pct' => Fmt::share($statusCounts[$s[1]] ?? 0, $count),
            'color' => $s[2],
        ]);
        $otherStatus = $count - $statusCounts->only(['tetap', 'kontrak', 'freelance'])->sum();
        if ($otherStatus > 0) {
            $statuses->push(['label' => 'Lainnya', 'value' => Fmt::num($otherStatus), 'pct' => Fmt::share($otherStatus, $count), 'color' => Fmt::OTHER]);
        }

        $female = $active->filter(fn ($e) => strtolower((string) $e->jenis_kelamin) === 'p')->count();
        $male = $active->filter(fn ($e) => strtolower((string) $e->jenis_kelamin) === 'l')->count();

        $joiners = $active
            ->filter(fn ($e) => $e->tanggal_masuk && $e->tanggal_masuk->between($start, $end))
            ->sortByDesc('tanggal_masuk')
            ->values();

        $contracts = $active
            ->filter(fn ($e) => strtolower((string) $e->status) === 'kontrak' && $e->kontrak_berakhir)
            ->map(function ($e) use ($today) {
                $endDate = Carbon::parse($e->kontrak_berakhir)->startOfDay();

                return (object) ['id' => $e->id, 'nama' => $e->nama, 'end' => $endDate, 'days' => (int) $today->diffInDays($endDate, false)];
            })
            ->filter(fn ($c) => $c->days <= self::CONTRACT_WARNING_DAYS)
            ->sortBy('days')
            ->values();

        return [
            'periodLabel' => $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'),
            'count' => $count,
            'statuses' => $statuses->all(),
            'female' => $female,
            'male' => $male,
            'averageAge' => $active->filter(fn ($e) => $e->tanggal_lahir)->avg(fn ($e) => $e->tanggal_lahir->diffInYears($today)),
            'joiners' => $joiners->take(5)->all(),
            'joinerCount' => $joiners->count(),
            'contracts' => $contracts->take(6)->all(),
            'contractCount' => $contracts->count(),
            'contractDays' => self::CONTRACT_WARNING_DAYS,
        ];
    }
}
