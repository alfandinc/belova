<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Clinics;
use App\Support\Dashboard\Fmt;
use App\Support\Dashboard\Revenue;
use App\Support\Dashboard\RevenueTargets;
use Carbon\Carbon;

/**
 * Omset as of the end of the selected period (capped at today): month-to-date and year-to-date,
 * growth vs last month (same days) and last year (same dates), target achievement vs the pace the
 * month should be at, projected month-end, and the cumulative curve of this month vs last month.
 */
class TotalRevenueWidget implements Widget
{
    public function data(User $user, array $filter): array
    {
        $ref = $filter['period_end']->copy()->startOfDay();
        if ($ref->isFuture()) {
            $ref = Carbon::today();
        }

        $monthStart = $ref->copy()->startOfMonth();
        $day = $ref->day;
        $daysInMonth = $ref->daysInMonth;

        // Same day-of-month window last month (capped at that month's length)
        $prevMonthStart = $monthStart->copy()->subMonthNoOverflow();
        $prevMonthRef = $prevMonthStart->copy()->day(min($day, $prevMonthStart->daysInMonth));

        $mtd = Revenue::total($monthStart, $ref);
        $mtdPrevMonth = Revenue::total($prevMonthStart, $prevMonthRef);
        $mtdLastYear = Revenue::total($monthStart->copy()->subYearNoOverflow(), $ref->copy()->subYearNoOverflow());
        $ytd = Revenue::total($ref->copy()->startOfYear(), $ref);
        $ytdLastYear = Revenue::total($ref->copy()->subYearNoOverflow()->startOfYear(), $ref->copy()->subYearNoOverflow());

        $monthTarget = RevenueTargets::forPeriod($monthStart, $ref)['total'];
        $ytdTargets = RevenueTargets::forPeriod($ref->copy()->startOfYear(), $ref);

        $pace = $day / $daysInMonth * 100;
        $projection = $day > 0 ? $mtd / $day * $daysInMonth : 0;

        return [
            'refLabel' => $ref->translatedFormat('d M Y'),
            'monthLabel' => $ref->translatedFormat('F Y'),
            'mtd' => $mtd,
            'mtdClinics' => Clinics::split(Revenue::byKlinik($monthStart, $ref), fn ($v) => Fmt::rpShort($v)),
            'mtdMoM' => Fmt::trend($mtd, $mtdPrevMonth),
            'mtdMoMLabel' => '1–' . $prevMonthRef->day . ' ' . $prevMonthStart->translatedFormat('M'),
            'mtdYoY' => Fmt::trend($mtd, $mtdLastYear),
            'ytd' => $ytd,
            'ytdClinics' => Clinics::split(Revenue::byKlinik($ref->copy()->startOfYear(), $ref), fn ($v) => Fmt::rpShort($v)),
            'ytdYoY' => Fmt::trend($ytd, $ytdLastYear),
            'ytdLastYearLabel' => 's/d ' . $ref->copy()->subYearNoOverflow()->translatedFormat('d M Y'),
            'day' => $day,
            'daysInMonth' => $daysInMonth,
            'monthTarget' => $monthTarget,
            'monthTargetPct' => $monthTarget > 0 ? Fmt::share($mtd, $monthTarget) : null,
            'pace' => $pace,
            'projection' => $projection,
            'projectionPct' => $monthTarget > 0 ? Fmt::share($projection, $monthTarget) : null,
            'ytdTarget' => $ytdTargets['total'],
            'ytdTargetPct' => $ytdTargets['total'] > 0 ? Fmt::share($ytd, $ytdTargets['total']) : null,
            'ytdTargetLabel' => $ytdTargets['label'],
            'chart' => $this->chart($monthStart, $ref, $prevMonthStart, $monthTarget),
        ];
    }

    /** Cumulative omset per day of month: this month (up to ref), last month (whole), linear target. */
    private function chart(Carbon $monthStart, Carbon $ref, Carbon $prevMonthStart, float $target): array
    {
        $daysInMonth = $monthStart->daysInMonth;
        $current = Revenue::daily($monthStart, $ref);
        $previous = Revenue::daily($prevMonthStart, $prevMonthStart->copy()->endOfMonth());

        $sumCurrent = 0;
        $sumPrevious = 0;
        $categories = $thisMonth = $lastMonth = $targetLine = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $categories[] = (string) $d;

            $date = $monthStart->copy()->day($d);
            if ($date->lte($ref)) {
                $sumCurrent += $current[$date->toDateString()] ?? 0;
                $thisMonth[] = round($sumCurrent);
            } else {
                $thisMonth[] = null;
            }

            if ($d <= $prevMonthStart->daysInMonth) {
                $sumPrevious += $previous[$prevMonthStart->copy()->day($d)->toDateString()] ?? 0;
            }
            $lastMonth[] = round($sumPrevious);

            $targetLine[] = $target > 0 ? round($target / $daysInMonth * $d) : null;
        }

        $series = [
            ['name' => $monthStart->translatedFormat('F'), 'values' => $thisMonth, 'color' => '--dw-series-1'],
            ['name' => $prevMonthStart->translatedFormat('F'), 'values' => $lastMonth, 'color' => '--dw-other'],
        ];
        if ($target > 0) {
            $series[] = ['name' => 'Target', 'values' => $targetLine, 'color' => '--dsh-ink-2', 'dashed' => true];
        }

        return [
            'type' => 'line',
            'format' => 'rupiah',
            'categories' => $categories,
            'series' => $series,
            'hasData' => $sumCurrent > 0 || $sumPrevious > 0,
        ];
    }
}
