<?php

namespace App\Support\Dashboard;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Omset definitions shared by the executive widgets, so every number agrees:
 * omset = finance_invoices.total_amount of transacted invoices (payment first processed: paid, piutang or
 * asuransi), dated by transaction_date shifted by the clinic cut off (reportDateSql).
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

    public static function transacted(): Builder
    {
        return DB::table('finance_invoices as fi')
            ->whereNotNull('fi.transaction_date');
    }

    /**
     * Invoices whose omset date (reportDateSql) falls in [$from, $to]. The plain transaction_date range
     * (widened by the largest clinic cutoff) keeps the index usable; the omset date does the exact cut.
     */
    public static function between(Carbon $from, Carbon $to): Builder
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->addDay()->startOfDay()->addSeconds(self::maxCutoffSeconds());

        return self::transacted()
            ->where('fi.transaction_date', '>=', $start)
            ->where('fi.transaction_date', '<', $end)
            ->whereRaw(self::reportDateSql() . ' BETWEEN ? AND ?', [$from->toDateString(), $to->toDateString()]);
    }

    /**
     * SQL for the day an invoice's omset counts on: the transaction date shifted back by its clinic's
     * cut off (erm_klinik.report_cutoff_time, e.g. 05:00 = service after midnight still counts for the day
     * before). Same rule as the finance report (FinanceReportController::buildReportDateExpression).
     */
    public static function reportDateSql(string $alias = 'fi'): string
    {
        return "DATE(SUBTIME({$alias}.transaction_date, COALESCE((
            SELECT rk.report_cutoff_time FROM erm_visitations rv
            JOIN erm_klinik rk ON rk.id = rv.klinik_id
            WHERE rv.id = {$alias}.visitation_id
        ), '00:00:00')))";
    }

    private static ?int $maxCutoffSeconds = null;

    /** Largest clinic cut off in seconds (0 when none / the column is missing). */
    private static function maxCutoffSeconds(): int
    {
        if (self::$maxCutoffSeconds === null) {
            try {
                self::$maxCutoffSeconds = (int) DB::table('erm_klinik')->max(DB::raw('TIME_TO_SEC(report_cutoff_time)'));
            } catch (\Throwable $e) {
                self::$maxCutoffSeconds = 0;
            }
        }

        return self::$maxCutoffSeconds;
    }

    public static function total(Carbon $from, Carbon $to): float
    {
        return (float) self::between($from, $to)->sum('fi.total_amount');
    }

    /** klinik_id => omset ('' = invoice without a visit) */
    public static function byKlinik(Carbon $from, Carbon $to): Collection
    {
        return self::between($from, $to)
            ->leftJoin('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->groupBy('v.klinik_id')
            ->selectRaw('v.klinik_id, SUM(fi.total_amount) as total')
            ->pluck('total', 'klinik_id')
            ->map(fn ($v) => (float) $v);
    }

    /** Y-m-d => omset */
    public static function daily(Carbon $from, Carbon $to): Collection
    {
        return self::between($from, $to)
            ->selectRaw(self::reportDateSql() . ' as day, SUM(fi.total_amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($v) => (float) $v);
    }

    /**
     * Adds the visit's metode bayar (erm_metode_bayar: Umum or an insurer) to an invoice query that
     * already joins erm_visitations as v; select / group by mb_nama and mb_asuransi.
     */
    public static function withMetodeBayar(Builder $query): Builder
    {
        return $query->leftJoin('erm_metode_bayar as mb', 'mb.id', '=', 'v.metode_bayar_id')
            ->addSelect(DB::raw("COALESCE(mb.nama, 'Tanpa metode bayar') as mb_nama, COALESCE(mb.is_asuransi, 0) as mb_asuransi"))
            ->groupBy('mb_nama', 'mb_asuransi');
    }

    /**
     * Rows with mb_nama, mb_asuransi, total, invoices => Umum vs Asuransi (nominal, percent of $total)
     * and every insurer, largest first.
     *
     * @return array{umum: array, asuransi: array, insurers: list<array>}
     */
    public static function metodeBayarSplit(iterable $rows, float $total): array
    {
        $rows = collect($rows);
        $part = fn ($rows) => [
            'total' => (float) $rows->sum('total'),
            'pct' => Fmt::share($rows->sum('total'), $total),
            'invoices' => (int) $rows->sum('invoices'),
        ];
        $insurance = $rows->where('mb_asuransi', 1);

        return [
            'umum' => $part($rows->where('mb_asuransi', '!=', 1)),
            'asuransi' => $part($insurance),
            'insurers' => $insurance->groupBy('mb_nama')
                ->map(fn ($r, $nama) => ['label' => $nama] + $part($r))
                ->sortByDesc('total')
                ->values()
                ->all(),
        ];
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
     * Items of one category by allocated omset, best first: (object) [name, total, qty, byKlinik
     * (klinik_id => omset), qtyByKlinik]. $limit null = all items.
     */
    public static function items(Carbon $from, Carbon $to, string $categoryKey, ?int $limit = 5): Collection
    {
        $category = self::categorySql();

        $rows = self::itemShares($from, $to)
            ->leftJoin('erm_visitations as vk', 'vk.id', '=', 'fi.visitation_id')
            ->whereRaw("{$category} = ?", [$categoryKey])
            ->groupBy('fii.name', 'vk.klinik_id')
            ->selectRaw('fii.name, vk.klinik_id, SUM(fi.total_amount * fii.final_amount / it.items_total) as total, SUM(fii.quantity) as qty')
            ->get();

        return $rows->groupBy('name')
            ->map(fn ($perKlinik, $name) => (object) [
                'name' => $name,
                'total' => (float) $perKlinik->sum('total'),
                'qty' => (float) $perKlinik->sum('qty'),
                'byKlinik' => $perKlinik->mapWithKeys(fn ($r) => [(string) $r->klinik_id => (float) $r->total]),
                'qtyByKlinik' => $perKlinik->mapWithKeys(fn ($r) => [(string) $r->klinik_id => (float) $r->qty]),
            ])
            ->sortByDesc('total')
            ->when($limit, fn ($c) => $c->take($limit))
            ->values();
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
