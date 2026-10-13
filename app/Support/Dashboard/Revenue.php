<?php

namespace App\Support\Dashboard;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Omset definitions shared by the executive widgets, so every number agrees:
 * omset = finance_invoices.total_amount of paid / partial invoices, dated by payment_date.
 *
 * Breakdowns by category split each invoice's total over its items in proportion to the items'
 * final_amount, so invoice-level discount / tax is spread fairly and the categories add up to omset.
 */
class Revenue
{
    /** Display order and labels of the revenue categories. */
    public const CATEGORIES = [
        'tindakan' => 'Tindakan',
        'obat' => 'Obat & Produk',
        'konsultasi' => 'Konsultasi',
        'lab' => 'Laboratorium',
        'lain' => 'Lain-lain',
    ];

    /** Categorical slots for the categories (fixed, validated as adjacent pairs); "lain" is the neutral gray. */
    public const CATEGORY_COLORS = [
        'tindakan' => 'var(--dw-series-1)',
        'obat' => 'var(--dw-series-2)',
        'konsultasi' => 'var(--dw-series-3)',
        'lab' => 'var(--dw-series-4)',
        'lain' => 'var(--dw-other)',
    ];

    public static function paid(): Builder
    {
        return DB::table('finance_invoices as fi')
            ->whereNotNull('fi.payment_date')
            ->whereIn('fi.status', ['paid', 'partial']);
    }

    public static function between(Carbon $from, Carbon $to): Builder
    {
        return self::paid()->whereBetween('fi.payment_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }

    public static function total(Carbon $from, Carbon $to): float
    {
        return (float) self::between($from, $to)->sum('fi.total_amount');
    }

    /** klinik_id => omset */
    public static function byKlinik(Carbon $from, Carbon $to): Collection
    {
        return self::between($from, $to)
            ->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->groupBy('v.klinik_id')
            ->selectRaw('v.klinik_id, SUM(fi.total_amount) as total')
            ->pluck('total', 'klinik_id')
            ->map(fn ($v) => (float) $v);
    }

    /** Y-m-d => omset */
    public static function daily(Carbon $from, Carbon $to): Collection
    {
        return self::between($from, $to)
            ->selectRaw('DATE(fi.payment_date) as day, SUM(fi.total_amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($v) => (float) $v);
    }

    /** SQL CASE that maps an invoice item to a category key. */
    public static function categorySql(string $alias = 'fii'): string
    {
        $type = "COALESCE({$alias}.billable_type, '')";

        return "CASE
            WHEN {$type} LIKE '%Konsultasi' THEN 'konsultasi'
            WHEN {$type} LIKE '%Tindakan' THEN 'tindakan'
            WHEN {$type} LIKE '%ResepFarmasi' OR {$type} LIKE '%Obat' THEN 'obat'
            WHEN {$type} LIKE '%Lab%' THEN 'lab'
            ELSE 'lain' END";
    }

    /**
     * category => omset (allocated). Whatever cannot be allocated (invoice without items) goes to "lain",
     * so the categories always sum to total(). Optional klinik filter.
     */
    public static function byCategory(Carbon $from, Carbon $to, ?int $klinikId = null): Collection
    {
        $category = self::categorySql();

        $rows = self::itemShares($from, $to, $klinikId)
            ->selectRaw("{$category} as category, SUM(fi.total_amount * fii.final_amount / it.items_total) as total")
            ->groupBy('category')
            ->pluck('total', 'category');

        $total = (float) self::between($from, $to)
            ->when($klinikId, fn ($q) => $q->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')->where('v.klinik_id', $klinikId))
            ->sum('fi.total_amount');

        $result = collect(self::CATEGORIES)->map(fn ($label, $key) => (float) ($rows[$key] ?? 0));
        $result['lain'] += max(0, $total - $result->sum());

        return $result;
    }

    /**
     * Top items of one category by allocated omset: name, omset, qty.
     */
    public static function topItems(Carbon $from, Carbon $to, string $categoryKey, int $limit = 5): Collection
    {
        $category = self::categorySql();

        return self::itemShares($from, $to)
            ->whereRaw("{$category} = ?", [$categoryKey])
            ->groupBy('fii.name')
            ->selectRaw('fii.name, SUM(fi.total_amount * fii.final_amount / it.items_total) as total, SUM(fii.quantity) as qty')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /** Invoice items joined to their paid invoice and the invoice's item total (allocation base). */
    private static function itemShares(Carbon $from, Carbon $to, ?int $klinikId = null): Builder
    {
        $itemTotals = DB::table('finance_invoice_items')
            ->selectRaw('invoice_id, SUM(final_amount) as items_total')
            ->groupBy('invoice_id')
            ->havingRaw('SUM(final_amount) > 0');

        return self::between($from, $to)
            ->join('finance_invoice_items as fii', 'fii.invoice_id', '=', 'fi.id')
            ->joinSub($itemTotals, 'it', 'it.invoice_id', '=', 'fi.id')
            ->when($klinikId, fn ($q) => $q->join('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')->where('v.klinik_id', $klinikId));
    }
}
