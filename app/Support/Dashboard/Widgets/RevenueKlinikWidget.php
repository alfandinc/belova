<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Clinics;
use App\Support\Dashboard\Fmt;
use App\Support\Dashboard\Revenue;
use App\Support\Dashboard\RevenueTargets;
use Carbon\Carbon;

/**
 * Omset per clinic as of the end of the selected period: MTD with growth vs last month (same days),
 * month target and pace, YTD with growth vs last year, and the YTD target.
 */
class RevenueKlinikWidget implements Widget
{
    public function data(User $user, array $filter): array
    {
        $ref = $filter['period_end']->copy()->startOfDay();
        if ($ref->isFuture()) {
            $ref = Carbon::today();
        }

        $monthStart = $ref->copy()->startOfMonth();
        $prevMonthStart = $monthStart->copy()->subMonthNoOverflow();
        $prevMonthRef = $prevMonthStart->copy()->day(min($ref->day, $prevMonthStart->daysInMonth));
        $yearStart = $ref->copy()->startOfYear();
        $lastYearRef = $ref->copy()->subYearNoOverflow();

        $mtd = Revenue::byKlinik($monthStart, $ref);
        $mtdPrev = Revenue::byKlinik($prevMonthStart, $prevMonthRef);
        $ytd = Revenue::byKlinik($yearStart, $ref);
        $ytdPrev = Revenue::byKlinik($lastYearRef->copy()->startOfYear(), $lastYearRef);
        $monthTargets = RevenueTargets::forPeriod($monthStart, $ref)['byKlinik'];
        $ytdTargets = RevenueTargets::forPeriod($yearStart, $ref);

        $pace = $ref->day / $ref->daysInMonth * 100;
        $totalMtd = (float) $mtd->sum();

        // Fixed clinic order (Premiere, Skin, Dental) so each clinic's card keeps its place
        $rows = Clinics::all()
            ->map(function ($klinik) use ($mtd, $mtdPrev, $ytd, $ytdPrev, $monthTargets, $ytdTargets, $totalMtd) {
                $m = (float) ($mtd[$klinik->id] ?? 0);
                $y = (float) ($ytd[$klinik->id] ?? 0);
                $mt = (float) ($monthTargets[$klinik->id] ?? 0);
                $yt = (float) ($ytdTargets['byKlinik'][$klinik->id] ?? 0);

                return (object) [
                    'klinik' => $klinik,
                    'mtd' => $m,
                    'share' => Fmt::share($m, $totalMtd),
                    'mom' => Fmt::trend($m, (float) ($mtdPrev[$klinik->id] ?? 0)),
                    'monthTarget' => $mt,
                    'monthPct' => $mt > 0 ? Fmt::share($m, $mt) : null,
                    'ytd' => $y,
                    'yoy' => Fmt::trend($y, (float) ($ytdPrev[$klinik->id] ?? 0)),
                    'ytdTarget' => $yt,
                    'ytdPct' => $yt > 0 ? Fmt::share($y, $yt) : null,
                ];
            })
            ->values();

        $sumMonthTarget = (float) $monthTargets->sum();

        return [
            'refLabel' => $ref->translatedFormat('d M Y'),
            'monthLabel' => $ref->translatedFormat('F'),
            'ytdTargetLabel' => $ytdTargets['label'],
            'pace' => $pace,
            'rows' => $rows->all(),
            'total' => (object) [
                'mtd' => $totalMtd,
                'mom' => Fmt::trend($totalMtd, (float) $mtdPrev->sum()),
                'monthTarget' => $sumMonthTarget,
                'monthPct' => $sumMonthTarget > 0 ? Fmt::share($totalMtd, $sumMonthTarget) : null,
                'ytd' => (float) $ytd->sum(),
                'yoy' => Fmt::trend((float) $ytd->sum(), (float) $ytdPrev->sum()),
                'ytdTarget' => (float) $ytdTargets['total'],
                'ytdPct' => $ytdTargets['total'] > 0 ? Fmt::share($ytd->sum(), $ytdTargets['total']) : null,
            ],
        ];
    }
}
