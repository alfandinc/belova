<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Clinics;
use App\Support\Dashboard\Fmt;
use App\Support\Dashboard\Revenue;
use Illuminate\Support\Facades\Route;

/**
 * Where the period's omset comes from (invoice items): the category split over all clinics with growth vs
 * the previous period of the same length, then one section per clinic with its own category split,
 * best-selling tindakan / products ("Lihat semua" lists every item) and payment methods.
 */
class SumberOmsetWidget implements Widget
{
    private const TOP = 5;

    private const PAYMENT_METHODS = [
        'cash' => 'Tunai',
        'qris' => 'QRIS',
        'transfer' => 'Transfer',
        'piutang' => 'Piutang',
        'lainnya' => 'Lainnya',
    ];

    public function data(User $user, array $filter): array
    {
        $start = $filter['period_start']->copy()->startOfDay();
        $end = $filter['period_end']->copy()->endOfDay();
        $days = (int) $start->diffInDays($end->copy()->startOfDay()) + 1;
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);

        $current = Revenue::byCategory($start, $end);
        $previous = Revenue::byCategory($prevStart, $prevEnd);
        $total = (float) $current->sum();

        $categories = collect(Revenue::CATEGORIES)->map(fn ($label, $key) => [
            'label' => $label,
            'amount' => $current[$key],
            'pct' => Fmt::share($current[$key], $total),
            'trend' => Fmt::trend($current[$key], $previous[$key]),
            'color' => Revenue::CATEGORY_COLORS[$key],
        ])->values();

        // All items once; each clinic's best sellers are ranked by that clinic's own omset
        $items = [
            'tindakan' => Revenue::items($start, $end, 'tindakan', null),
            'obat' => Revenue::items($start, $end, 'obat', null),
        ];

        $methods = Revenue::between($start, $end)
            ->leftJoin('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->selectRaw("v.klinik_id, COALESCE(NULLIF(fi.payment_method, ''), 'lainnya') as method, SUM(fi.total_amount) as total, COUNT(*) as invoices")
            ->groupBy('v.klinik_id', 'method')
            ->get();

        // null (no "Lihat semua") while a stale route cache does not know the route yet
        $itemsUrl = Route::has('dashboard.omset-items')
            ? route('dashboard.omset-items', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()])
            : null;

        $clinics = Clinics::all()->map(function ($klinik) use ($start, $end, $items, $methods) {
            $split = Revenue::byCategory($start, $end, $klinik->id);
            $sum = (float) $split->sum();
            $key = (string) $klinik->id;

            $top = fn (string $category) => $items[$category]
                ->filter(fn ($item) => ($item->byKlinik[$key] ?? 0) > 0)
                ->sortByDesc(fn ($item) => $item->byKlinik[$key])
                ->take(self::TOP)
                ->map(fn ($item) => (object) [
                    'name' => $item->name,
                    'total' => (float) $item->byKlinik[$key],
                    'qty' => (float) ($item->qtyByKlinik[$key] ?? 0),
                ])
                ->values()
                ->all();

            return [
                'klinik' => $klinik,
                'total' => $sum,
                'categories' => collect(Revenue::CATEGORIES)->map(fn ($label, $cat) => [
                    'label' => $label,
                    'value' => Fmt::rp($split[$cat]),
                    'pct' => Fmt::share($split[$cat], $sum),
                    'color' => Revenue::CATEGORY_COLORS[$cat],
                ])->values()->all(),
                'topTindakan' => $top('tindakan'),
                'topObat' => $top('obat'),
                'methods' => $methods->filter(fn ($m) => (string) $m->klinik_id === $key)
                    ->map(fn ($m) => [
                        'label' => self::PAYMENT_METHODS[$m->method] ?? ucwords(str_replace('_', ' ', $m->method)),
                        'total' => (float) $m->total,
                        'pct' => Fmt::share($m->total, $sum),
                        'invoices' => (int) $m->invoices,
                    ])
                    ->sortByDesc('total')
                    ->values()
                    ->all(),
            ];
        })->values();

        return [
            'periodLabel' => $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'),
            'previousLabel' => $prevStart->translatedFormat('d M') . ' – ' . $prevEnd->translatedFormat('d M Y'),
            'total' => $total,
            'categories' => $categories->all(),
            'clinics' => $clinics->all(),
            'itemsUrl' => $itemsUrl,
        ];
    }
}
