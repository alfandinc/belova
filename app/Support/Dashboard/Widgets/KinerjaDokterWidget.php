<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Fmt;
use App\Support\Dashboard\Revenue;

/** Omset per doctor in the period (by the visit's doctor): patients, visits, average per visit, growth. */
class KinerjaDokterWidget implements Widget
{
    private const LIMIT = 8;

    public function data(User $user, array $filter): array
    {
        $start = $filter['period_start']->copy()->startOfDay();
        $end = $filter['period_end']->copy()->endOfDay();
        $days = (int) $start->diffInDays($end->copy()->startOfDay()) + 1;
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);

        $byDokter = fn ($from, $to) => Revenue::between($from, $to)
            ->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->leftJoin('erm_dokters as d', 'd.id', '=', 'v.dokter_id')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->leftJoin('erm_klinik as k', 'k.id', '=', 'd.klinik_id')
            ->groupBy('v.dokter_id', 'u.name', 'k.nama')
            ->selectRaw('v.dokter_id, u.name as nama, k.nama as klinik, SUM(fi.total_amount) as total,
                COUNT(DISTINCT v.id) as visits, COUNT(DISTINCT v.pasien_id) as patients')
            ->get()
            ->keyBy(fn ($r) => (string) ($r->dokter_id ?? 'none'));

        $current = $byDokter($start, $end);
        $previous = $byDokter($prevStart, $prevEnd);
        $total = (float) $current->sum('total');

        $rows = $current
            ->map(fn ($r, $key) => (object) [
                'nama' => $r->dokter_id ? ($r->nama ?? 'Dokter #' . $r->dokter_id) : 'Tanpa dokter (penjualan langsung)',
                'klinik' => $r->klinik,
                'total' => (float) $r->total,
                'share' => Fmt::share($r->total, $total),
                'visits' => (int) $r->visits,
                'patients' => (int) $r->patients,
                'average' => $r->visits > 0 ? $r->total / $r->visits : 0,
                'trend' => Fmt::trend((float) $r->total, (float) ($previous[$key]->total ?? 0)),
            ])
            ->sortByDesc('total')
            ->values();

        return [
            'periodLabel' => $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'),
            'previousLabel' => $prevStart->translatedFormat('d M') . ' – ' . $prevEnd->translatedFormat('d M Y'),
            'total' => $total,
            'rows' => $rows->take(self::LIMIT)->all(),
            'more' => max(0, $rows->count() - self::LIMIT),
        ];
    }
}
