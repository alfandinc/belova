<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;
use App\Support\Dashboard\Clinics;
use App\Support\Dashboard\Fmt;
use App\Support\Dashboard\Revenue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The cashier's day: totals over all clinics (received today, invoices not paid yet, open receivables),
 * then one section per clinic (the invoice's visit clinic) with its omset by the visit metode bayar
 * (Umum / Asuransi) and unpaid invoices.
 */
class KasirWidget implements Widget
{
    /** Latest unpaid invoices listed per clinic */
    private const LIST_LIMIT = 3;

    public function data(User $user, array $filter): array
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();

        $paid = fn (Carbon $day) => DB::table('finance_invoices as fi')
            ->leftJoin('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->whereNotNull('fi.payment_date')
            ->whereIn('fi.status', ['paid', 'partial'])
            ->whereBetween('fi.payment_date', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]);

        // klinik_id × the visit's metode bayar (Umum / insurer)
        $todayRows = Revenue::withMetodeBayar($paid($today)
            ->selectRaw('v.klinik_id, SUM(fi.total_amount) as total, COUNT(*) as invoices')
            ->groupBy('v.klinik_id'))
            ->get();
        $yesterdayByKlinik = $paid($yesterday)
            ->groupBy('v.klinik_id')
            ->selectRaw('v.klinik_id, SUM(fi.total_amount) as total')
            ->get()
            ->groupBy(fn ($r) => Clinics::find($r->klinik_id)->id ?? 'none')
            ->map(fn ($rows) => (float) $rows->sum('total'));

        // Rp 0 invoices (fully discounted / empty) have nothing to collect
        $unpaidQuery = DB::table('finance_invoices as fi')
            ->leftJoin('erm_visitations as v', 'v.id', '=', 'fi.visitation_id')
            ->where('fi.status', 'issued')
            ->where('fi.total_amount', '>', 0);
        $unpaidRows = (clone $unpaidQuery)
            ->groupBy('v.klinik_id')
            ->selectRaw('v.klinik_id, COUNT(*) as invoices, SUM(fi.total_amount) as total')
            ->get();
        $unpaidLatest = (clone $unpaidQuery)
            ->leftJoin('erm_pasiens as p', 'p.id', '=', 'v.pasien_id')
            ->orderByDesc('fi.created_at')
            ->limit(60)
            ->get(['v.klinik_id', 'fi.invoice_number', 'fi.total_amount', 'fi.created_at', 'p.nama as pasien'])
            ->groupBy(fn ($r) => Clinics::find($r->klinik_id)->id ?? 'none');

        $piutang = DB::table('finance_piutangs')
            ->where('payment_status', '!=', 'paid')
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(amount - COALESCE(paid_amount, 0)), 0) as sisa')
            ->first();

        $total = (float) $todayRows->sum('total');

        // Every row source of the sections carries klinik_id; tag them so one section gets all three
        $rows = $todayRows->map(fn ($r) => (object) ['klinik_id' => $r->klinik_id, 'kind' => 'paid', 'row' => $r])
            ->concat($unpaidRows->map(fn ($r) => (object) ['klinik_id' => $r->klinik_id, 'kind' => 'unpaid', 'row' => $r]));

        return [
            'todayLabel' => $today->translatedFormat('l, d M Y'),
            'total' => $total,
            'invoices' => (int) $todayRows->sum('invoices'),
            'metodeBayar' => $total > 0 ? Revenue::metodeBayarSplit($todayRows, $total) : null,
            'trend' => Fmt::trend($total, (float) $yesterdayByKlinik->sum()),
            'unpaidCount' => (int) $unpaidRows->sum('invoices'),
            'unpaidAmount' => (float) $unpaidRows->sum('total'),
            'piutangCount' => (int) $piutang->total,
            'piutangSisa' => (float) $piutang->sisa,
            'clinics' => Clinics::sections($rows, function ($rows, $klinik) use ($yesterdayByKlinik, $unpaidLatest) {
                $paid = $rows->where('kind', 'paid')->pluck('row');
                $unpaid = $rows->where('kind', 'unpaid')->pluck('row');
                $key = $klinik->id ?? 'none';
                $received = (float) $paid->sum('total');

                return [
                    'total' => $received,
                    'invoices' => (int) $paid->sum('invoices'),
                    'trend' => Fmt::trend($received, $yesterdayByKlinik->get($key, 0)),
                    'metodeBayar' => $received > 0 ? Revenue::metodeBayarSplit($paid, $received) : null,
                    'unpaidCount' => (int) $unpaid->sum('invoices'),
                    'unpaidAmount' => (float) $unpaid->sum('total'),
                    'unpaid' => $unpaidLatest->get($key, collect())->take(self::LIST_LIMIT)->all(),
                ];
            }),
        ];
    }
}
