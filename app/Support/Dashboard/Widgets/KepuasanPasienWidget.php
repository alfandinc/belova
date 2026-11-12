<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\Finance\KepuasanPasien;
use App\Models\User;
use App\Support\Dashboard\Clinics;
use App\Support\Dashboard\Fmt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Satisfaction surveys filled on the kasir customer display (finance_kepuasan_pasien) in the period:
 * average rating vs the previous period, share of satisfied patients (rating 4–5), rating distribution,
 * per clinic, and the latest complaints / low ratings. "Puas" = 4–5, the same rule as the report page.
 */
class KepuasanPasienWidget implements Widget
{
    /** Roles of the report page (routes/web.php); only they get the link and see patient names. */
    private const REPORT_ROLES = ['Admin', 'Finance', 'Kasir', 'Manager', 'Head Manager', 'Ceo', 'Marketing'];

    private const KELUHAN_LIMIT = 5;

    public function data(User $user, array $filter): array
    {
        $start = $filter['period_start']->copy()->startOfDay();
        $end = $filter['period_end']->copy()->endOfDay();
        $days = (int) $start->diffInDays($end->copy()->startOfDay()) + 1;
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();

        $rows = $this->perKlinikRating($start, $end);
        $prevRows = $this->perKlinikRating($prevStart, $prevEnd);

        $current = $this->summarize($rows);
        $previous = $this->summarize($prevRows);
        $canOpenReport = $user->hasAnyRole(self::REPORT_ROLES);

        $distribution = [];
        foreach ([5, 4, 3, 2, 1] as $rating) {
            $count = (int) $rows->where('rating', $rating)->sum('total');
            $distribution[] = [
                'label' => KepuasanPasien::EMOJI[$rating] . ' ' . KepuasanPasien::LABELS[$rating],
                'count' => $count,
                'pct' => Fmt::share($count, $current['total']),
            ];
        }

        return [
            'periodLabel' => $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'),
            'previousLabel' => $prevStart->translatedFormat('d M') . ' – ' . $prevEnd->translatedFormat('d M Y'),
            'total' => $current['total'],
            'avg' => $current['avg'],
            'avgTrend' => $previous['total'] ? Fmt::trend($current['avg'], $previous['avg']) : null,
            'puasPct' => $current['puasPct'],
            'tidakPuas' => $current['tidakPuas'],
            'keluhanCount' => $this->between($start, $end)->whereNotNull('keluhan')->count(),
            'distribution' => $distribution,
            'clinics' => Clinics::sections($rows, fn ($klinikRows) => $this->summarize($klinikRows), true),
            'keluhan' => $this->keluhan($start, $end, $canOpenReport),
            'reportUrl' => $canOpenReport ? route('finance.kepuasan-pasien.index') : null,
        ];
    }

    private function between(Carbon $start, Carbon $end)
    {
        return DB::table('finance_kepuasan_pasien')->whereBetween('created_at', [$start, $end]);
    }

    /** One row per clinic + rating: klinik_id, rating, total. */
    private function perKlinikRating(Carbon $start, Carbon $end)
    {
        return $this->between($start, $end)
            ->selectRaw('klinik_id, rating, COUNT(*) as total')
            ->groupBy('klinik_id', 'rating')
            ->get();
    }

    private function summarize($rows): array
    {
        $total = (int) $rows->sum('total');
        $puas = (int) $rows->where('rating', '>=', 4)->sum('total');

        return [
            'total' => $total,
            'avg' => $total ? $rows->sum(fn ($r) => $r->rating * $r->total) / $total : 0.0,
            'puasPct' => Fmt::share($puas, $total),
            'tidakPuas' => (int) $rows->where('rating', '<=', 2)->sum('total'),
        ];
    }

    /** Latest surveys that need attention: a written complaint or a rating of 1–2. */
    private function keluhan(Carbon $start, Carbon $end, bool $withPasien): array
    {
        return KepuasanPasien::query()
            ->whereBetween('created_at', [$start, $end])
            ->where(fn ($q) => $q->whereNotNull('keluhan')->orWhere('rating', '<=', 2))
            ->with($withPasien ? ['pasien:id,nama'] : [])
            ->latest()
            ->limit(self::KELUHAN_LIMIT)
            ->get()
            ->map(fn (KepuasanPasien $row) => [
                'rating' => $row->rating,
                'label' => (KepuasanPasien::EMOJI[$row->rating] ?? '') . ' ' . (KepuasanPasien::LABELS[$row->rating] ?? $row->rating),
                'tone' => $row->rating <= 2 ? 'critical' : ($row->rating === 3 ? 'warning' : 'neutral'),
                'text' => $row->keluhan,
                'meta' => collect([
                    $row->created_at?->translatedFormat('d M H:i'),
                    $row->klinik_id ? Clinics::find($row->klinik_id)->short : null,
                    $withPasien ? optional($row->pasien)->nama : null,
                ])->filter()->implode(' · '),
            ])
            ->all();
    }
}
