<?php

namespace App\Services\ERM;

use App\Models\ERM\FakturBeliItem;
use App\Models\ERM\GudangMapping;
use App\Models\ERM\KartuStok;
use App\Models\ERM\ObatStokGudang;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Forecast kebutuhan obat, kebijakan (s, S) dengan pembulatan per box.
 * Semua input diambil otomatis dari data transaksi, tidak perlu setting per obat.
 *
 *   Pemakaian       = penjualan − retur penjualan, per hari, sejak obat mulai ada (maks. periode histori)
 *   Titik Pesan     = kebutuhan selama lead time yang tidak terlampaui pada {service level}% periode historis
 *                     (minimal rata-rata kebutuhan selama lead time; minimal 1 jika obat masih dipakai)
 *   Stok Maks       = idem untuk lead time + siklus pengadaan
 *   Safety Stock    = Titik Pesan − rata-rata kebutuhan selama lead time
 *   Posisi stok     = stok fisik (tanpa gudang expired) + pesanan yang belum datang
 *   Qty pesan       = (Stok Maks − posisi stok) dibulatkan ke atas per box, hanya jika posisi stok <= Titik Pesan
 *
 * Distribusi empiris (bukan asumsi normal) dipakai supaya obat yang pemakaiannya tidak teratur
 * tidak mendapat safety stock berlebihan, tanpa perlu klasifikasi manual.
 */
class ForecastService
{
    public const DAYS_PER_MONTH = 30.4;

    public const SERVICE_LEVELS = [90, 95, 98];

    public const REVIEW_PERIOD_DAYS = [
        'monthly' => 30,
        'weekly' => 7,
        'twice_weekly' => 3.5,
    ];

    public const LEAD_TIME_OPTIONS = [1, 2, 3, 5, 7, 14];

    // Metrics of an obat without demand (and not wajib); evaluate() adds the stock fields
    private const EMPTY_METRICS = [
        'obat_keluar' => 0,
        'active_months' => 0,
        'average_monthly_keluar' => 0,
        'std_monthly_keluar' => 0,
        'daily_demand' => 0,
        'safety_stock' => 0,
        'reorder_point' => 0,
        'max_stock' => 0,
        'limit_stok' => 0,
        'qty_pesan' => 0,
        'box_cover_days' => null,
        'days_of_cover' => null,
        'wajib_stok' => false,
        'warnings' => [],
    ];

    // Used when a supplier has too little purchase history
    public const DEFAULT_LEAD_TIME_DAYS = 3;

    // Minimum faktur count before a supplier / global median lead time is trusted
    public const MIN_LEAD_TIME_SAMPLES = 3;

    // Faktur with a longer request-to-receive gap are treated as data entry errors
    public const MAX_VALID_LEAD_TIME_DAYS = 60;

    // Stock covering more than this many days of demand is flagged as overstock;
    // a box covering more than this is flagged as an expiry risk
    public const OVERSTOCK_DAYS = 90;

    public function normalizeParams(array $input): array
    {
        $periodMonths = (int) ($input['period_months'] ?? 3);
        $periodMonths = in_array($periodMonths, [1, 3, 6, 12], true) ? $periodMonths : 3;

        $frequency = (string) ($input['pengadaan_frequency'] ?? 'twice_weekly');
        $frequency = array_key_exists($frequency, self::REVIEW_PERIOD_DAYS) ? $frequency : 'twice_weekly';

        // 'auto' = per supplier from faktur history; a number overrides it for every obat
        $leadTimeInput = $input['lead_time_days'] ?? 'auto';
        $leadTime = in_array((int) $leadTimeInput, self::LEAD_TIME_OPTIONS, true) ? (int) $leadTimeInput : 'auto';

        $serviceLevel = (int) ($input['service_level'] ?? 95);
        $serviceLevel = in_array($serviceLevel, self::SERVICE_LEVELS, true) ? $serviceLevel : 95;

        return [
            'period_months' => $periodMonths,
            'pengadaan_frequency' => $frequency,
            'review_days' => self::REVIEW_PERIOD_DAYS[$frequency],
            'lead_time_days' => $leadTime,
            'service_level' => $serviceLevel,
        ];
    }

    /**
     * Previous full months (current month excluded).
     * Example: today in July, 3 months => Apr 1 - Jun 30.
     */
    public function period(int $periodMonths): array
    {
        $end = Carbon::now()->startOfMonth()->subDay()->endOfDay();
        $start = (clone $end)->subMonthsNoOverflow($periodMonths - 1)->startOfMonth()->startOfDay();

        return [$start, $end];
    }

    public function formulaLabel(array $params): string
    {
        $leadTime = $params['lead_time_days'] === 'auto'
            ? 'lead time otomatis per supplier (median riwayat faktur, default ' . self::DEFAULT_LEAD_TIME_DAYS . ' hari)'
            : 'lead time ' . $params['lead_time_days'] . ' hari';

        return sprintf(
            'Pemakaian = penjualan − retur, %d bulan terakhir. %s, siklus pengadaan %s hari, service level %d%%. '
            . 'Titik Pesan = kebutuhan selama lead time yang tidak terlampaui pada %d%% periode historis. '
            . 'Stok Maks = idem untuk lead time + siklus pengadaan. '
            . 'Pesan jika Stok + Pending ≤ Titik Pesan, sebanyak Stok Maks − (Stok + Pending), dibulatkan ke atas per box.',
            $params['period_months'],
            ucfirst($leadTime),
            $this->formatNumber($params['review_days']),
            $params['service_level'],
            $params['service_level']
        );
    }

    /**
     * Usable stock per obat: all gudang except the expired gudang.
     */
    public function stockPerObat($obatIds = null)
    {
        $expiredGudangId = GudangMapping::getDefaultGudangId('expired');

        return ObatStokGudang::query()
            ->when($expiredGudangId, function ($q) use ($expiredGudangId) {
                $q->where('gudang_id', '!=', $expiredGudangId);
            }, function ($q) {
                // Fallback when no expired mapping is configured
                $q->whereHas('gudang', function ($g) {
                    $g->where('nama', '!=', 'Gudang ED');
                });
            })
            ->when($obatIds !== null, function ($q) use ($obatIds) {
                $q->whereIn('obat_id', $obatIds);
            })
            ->select('obat_id', DB::raw('SUM(stok) as total_stock'))
            ->groupBy('obat_id')
            ->pluck('total_stock', 'obat_id');
    }

    /**
     * Quantity ordered through permintaan (faktur diminta/diterima) that has not arrived yet.
     */
    public function outstandingPerObat($obatIds = null)
    {
        return FakturBeliItem::query()
            ->join('erm_fakturbeli', 'erm_fakturbeli.id', '=', 'erm_fakturbeli_items.fakturbeli_id')
            ->whereNotNull('erm_fakturbeli.permintaan_id')
            ->whereIn('erm_fakturbeli.status', ['diminta', 'diterima'])
            ->when($obatIds !== null, function ($q) use ($obatIds) {
                $q->whereIn('erm_fakturbeli_items.obat_id', $obatIds);
            })
            ->select(
                'erm_fakturbeli_items.obat_id',
                DB::raw('SUM(CASE WHEN COALESCE(erm_fakturbeli_items.sisa, 0) > 0 THEN COALESCE(erm_fakturbeli_items.sisa, 0) ELSE GREATEST(COALESCE(erm_fakturbeli_items.diminta, 0) - COALESCE(erm_fakturbeli_items.qty, 0), 0) END) as total_outstanding')
            )
            ->groupBy('erm_fakturbeli_items.obat_id')
            ->pluck('total_outstanding', 'erm_fakturbeli_items.obat_id');
    }

    /**
     * Purchase pack size per obat, keyed by obat_id: ['qty' => int, 'source' => string].
     * Taken from the latest master faktur, otherwise from the latest permintaan item (qty_total / jumlah_box),
     * so it fills itself in as permintaan are made.
     */
    public function boxSizes($obatIds = null): array
    {
        $boxes = [];

        $permintaanRows = DB::table('erm_permintaan_items')
            ->where('jumlah_box', '>', 0)
            ->where('qty_total', '>', 0)
            ->when($obatIds !== null, function ($q) use ($obatIds) {
                $q->whereIn('obat_id', $obatIds);
            })
            ->orderBy('id')
            ->get(['obat_id', 'qty_total', 'jumlah_box']);
        foreach ($permintaanRows as $row) {
            $perBox = (float) $row->qty_total / (float) $row->jumlah_box;
            // Only whole pack sizes are meaningful
            if ($perBox >= 1 && abs($perBox - round($perBox)) < 0.0001) {
                $boxes[$row->obat_id] = ['qty' => (int) round($perBox), 'source' => 'permintaan'];
            }
        }

        // Master faktur wins over permintaan; later rows overwrite earlier ones
        $masterRows = DB::table('erm_master_faktur')
            ->where('qty_per_box', '>', 0)
            ->when($obatIds !== null, function ($q) use ($obatIds) {
                $q->whereIn('obat_id', $obatIds);
            })
            ->orderBy('id')
            ->get(['obat_id', 'qty_per_box']);
        foreach ($masterRows as $row) {
            $boxes[$row->obat_id] = ['qty' => (int) $row->qty_per_box, 'source' => 'master_faktur'];
        }

        return $boxes;
    }

    /**
     * Lead time per obat, keyed by obat_id: ['days' => float, 'source' => string].
     * Median request-to-receive days of the obat's supplier (latest master faktur, otherwise latest faktur),
     * otherwise the median over all suppliers, otherwise DEFAULT_LEAD_TIME_DAYS.
     */
    public function leadTimes(array $params, array $obatIds): array
    {
        if ($params['lead_time_days'] !== 'auto') {
            return array_fill_keys($obatIds, ['days' => (float) $params['lead_time_days'], 'source' => 'manual']);
        }

        // Order date: permintaan approval when the faktur came from a permintaan, otherwise faktur requested_date.
        // 0-day gaps are faktur entered on arrival (no real order date), so they are not lead time samples.
        $samples = DB::table('erm_fakturbeli as f')
            ->leftJoin('erm_permintaan as p', 'p.id', '=', 'f.permintaan_id')
            ->whereNotNull('f.received_date')
            ->whereNotNull('f.pemasok_id')
            ->select(
                'f.pemasok_id',
                DB::raw('DATEDIFF(f.received_date, COALESCE(DATE(p.approved_date), p.request_date, f.requested_date)) as days')
            )
            ->get()
            ->filter(function ($r) {
                return $r->days !== null && $r->days >= 1 && $r->days <= self::MAX_VALID_LEAD_TIME_DAYS;
            });

        $perPemasok = [];
        foreach ($samples->groupBy('pemasok_id') as $pemasokId => $rows) {
            if ($rows->count() >= self::MIN_LEAD_TIME_SAMPLES) {
                $perPemasok[$pemasokId] = max($this->median($rows->pluck('days')->all()), 1);
            }
        }
        $global = $samples->count() >= self::MIN_LEAD_TIME_SAMPLES
            ? max($this->median($samples->pluck('days')->all()), 1)
            : null;

        // Supplier per obat: latest master faktur, otherwise latest faktur beli
        $pemasokPerObat = DB::table('erm_fakturbeli_items as fi')
            ->join('erm_fakturbeli as f', 'f.id', '=', 'fi.fakturbeli_id')
            ->whereNotNull('f.pemasok_id')
            ->whereIn('fi.obat_id', $obatIds)
            ->orderBy('fi.id')
            ->pluck('f.pemasok_id', 'fi.obat_id')
            ->all();
        $masterPemasok = DB::table('erm_master_faktur')
            ->whereNotNull('pemasok_id')
            ->whereIn('obat_id', $obatIds)
            ->orderBy('id')
            ->pluck('pemasok_id', 'obat_id')
            ->all();
        $pemasokPerObat = array_replace($pemasokPerObat, $masterPemasok);

        $result = [];
        foreach ($obatIds as $obatId) {
            $pemasokId = $pemasokPerObat[$obatId] ?? null;
            if ($pemasokId !== null && isset($perPemasok[$pemasokId])) {
                $result[$obatId] = ['days' => (float) $perPemasok[$pemasokId], 'source' => 'supplier'];
            } elseif ($global !== null) {
                $result[$obatId] = ['days' => (float) $global, 'source' => 'global'];
            } else {
                $result[$obatId] = ['days' => (float) self::DEFAULT_LEAD_TIME_DAYS, 'source' => 'default'];
            }
        }

        return $result;
    }

    /**
     * Demand history per obat over the period, keyed by obat_id.
     * Days before the obat first appeared in kartu stok are not counted, so new obat are not diluted.
     */
    public function demandStats(Carbon $start, Carbon $end, int $periodMonths, $obatIds = null): array
    {
        $dailyRows = KartuStok::query()
            ->whereBetween('tanggal', [$start, $end])
            ->where(function ($q) {
                $q->where(function ($s) {
                    $s->where('tipe', 'keluar')->where('ref_type', 'invoice_penjualan');
                })->orWhere(function ($s) {
                    // Sales returns: billing edits (invoice_return) and the Finance retur menu (retur_pembelian)
                    $s->where('tipe', 'masuk')->whereIn('ref_type', ['invoice_return', 'retur_pembelian']);
                });
            })
            ->when($obatIds !== null, function ($q) use ($obatIds) {
                $q->whereIn('obat_id', $obatIds);
            })
            ->select(
                'obat_id',
                DB::raw('DATE(tanggal) as tgl'),
                DB::raw("SUM(CASE WHEN tipe = 'keluar' THEN qty ELSE -qty END) as net_qty")
            )
            ->groupBy('obat_id', DB::raw('DATE(tanggal)'))
            ->get()
            ->groupBy('obat_id');

        $firstSeen = KartuStok::query()
            ->when($obatIds !== null, function ($q) use ($obatIds) {
                $q->whereIn('obat_id', $obatIds);
            })
            ->select('obat_id', DB::raw('MIN(tanggal) as first_tanggal'))
            ->groupBy('obat_id')
            ->pluck('first_tanggal', 'obat_id');

        $periodStartDay = $start->copy()->startOfDay();
        $periodEndDay = $end->copy()->startOfDay();

        $stats = [];
        foreach ($dailyRows as $obatId => $rows) {
            $activeStart = $periodStartDay->copy();
            if (isset($firstSeen[$obatId])) {
                $first = Carbon::parse($firstSeen[$obatId])->startOfDay();
                if ($first->gt($activeStart)) {
                    $activeStart = $first->min($periodEndDay);
                }
            }

            $days = (int) $activeStart->diffInDays($periodEndDay) + 1;
            $daily = array_fill(0, $days, 0.0);
            foreach ($rows as $row) {
                $offset = (int) $activeStart->diffInDays(Carbon::parse($row->tgl)->startOfDay(), false);
                if ($offset >= 0 && $offset < $days) {
                    // Returns can only cancel out sales, never create negative demand
                    $daily[$offset] = max($daily[$offset] + (float) $row->net_qty, 0);
                }
            }

            // One-off bulk sales are capped so a single event does not set the stock level.
            // Tukey far-out rule on days with demand: cap = Q3 + 3 × IQR.
            $nonZero = array_values(array_filter($daily, function ($v) {
                return $v > 0;
            }));
            $cappedDays = 0;
            $planningDaily = $daily;
            if (count($nonZero) >= 4) {
                sort($nonZero);
                $q1 = $this->percentile($nonZero, 0.25);
                $q3 = $this->percentile($nonZero, 0.75);
                $cap = $q3 + 3 * ($q3 - $q1);
                foreach ($planningDaily as $i => $v) {
                    if ($v > $cap) {
                        $planningDaily[$i] = $cap;
                        $cappedDays++;
                    }
                }
            }

            $total = array_sum($daily);
            $activeMonths = max(1, min($periodMonths, (int) ceil($days / self::DAYS_PER_MONTH)));

            // Monthly totals for the variability shown in the table
            $monthly = [];
            $startTs = $activeStart->copy()->setTime(12, 0)->timestamp;
            for ($i = 0; $i < $days; $i++) {
                $key = date('Y-m', $startTs + $i * 86400);
                $monthly[$key] = ($monthly[$key] ?? 0) + $daily[$i];
            }
            $monthlyValues = array_values($monthly);
            $meanMonthly = $total / max(count($monthlyValues), 1);
            $stdMonthly = count($monthlyValues) >= 2
                ? sqrt(array_sum(array_map(function ($v) use ($meanMonthly) {
                    return ($v - $meanMonthly) ** 2;
                }, $monthlyValues)) / (count($monthlyValues) - 1))
                : 0;

            $stats[$obatId] = [
                'total' => $total,
                'active_days' => $days,
                'active_months' => $activeMonths,
                'mean_daily' => $total / $days,
                'mean_monthly' => $meanMonthly,
                'std_monthly' => $stdMonthly,
                // Planning uses the capped series; reported totals use real sales
                'daily' => $planningDaily,
                'planning_mean_daily' => array_sum($planningDaily) / $days,
                'demand_days' => count($nonZero),
                'capped_days' => $cappedDays,
            ];
        }

        return $stats;
    }

    /**
     * Default settings used outside the Forecast page (Manajemen Stok status, alerts, summary).
     */
    public function defaultParams(): array
    {
        return $this->normalizeParams([]);
    }

    /**
     * Forecast for many obat at once, keyed by obat_id. Stock is always live; the demand plan is cached per day.
     * Without $obatIds every active obat is returned.
     */
    public function computeAll(array $params, ?array $obatIds = null): array
    {
        // Several callers in one request (table, summary, alerts) need the same result
        $memoKey = md5(json_encode([$params, $obatIds]));
        if (isset(self::$memo[$memoKey])) {
            return self::$memo[$memoKey];
        }

        $bundle = $this->planBundle($params);

        // Plain query: hydrating ~1000 Obat models costs more than the forecast itself
        $obats = DB::table('erm_obat')
            ->when($obatIds === null, function ($q) {
                $q->where('status_aktif', 1);
            }, function ($q) use ($obatIds) {
                $q->whereIn('id', $obatIds);
            })
            ->get(['id', 'wajib_stok', 'stop_pengadaan']);

        $stockPerObat = $this->stockPerObat($obatIds);
        $outstandingPerObat = $this->outstandingPerObat($obatIds);

        // Lead time is only part of the cached plan for obat with demand; wajib obat without demand need one too
        $missingLeadTime = $obats->filter(function ($o) use ($bundle) {
            return $o->wajib_stok && !isset($bundle['plans'][$o->id]);
        })->pluck('id')->all();
        $extraLeadTimes = $missingLeadTime ? $this->leadTimes($params, $missingLeadTime) : [];

        $result = [];
        foreach ($obats as $obat) {
            $plan = $bundle['plans'][$obat->id] ?? null;
            $result[$obat->id] = $this->evaluate(
                $params,
                $plan,
                (float) ($stockPerObat[$obat->id] ?? 0),
                (float) ($outstandingPerObat[$obat->id] ?? 0),
                $bundle['boxes'][$obat->id] ?? null,
                (bool) $obat->wajib_stok,
                $plan['lead_time'] ?? ($extraLeadTimes[$obat->id] ?? ['days' => (float) self::DEFAULT_LEAD_TIME_DAYS, 'source' => 'default'])
            );
            $result[$obat->id]['stop_pengadaan'] = (bool) $obat->stop_pengadaan;
            if ($obat->stop_pengadaan) {
                $result[$obat->id] = $this->stopPengadaan($result[$obat->id]);
            }
        }

        return self::$memo[$memoKey] = $result;
    }

    /** @var array<string, array> computeAll results for the current request */
    private static array $memo = [];

    /**
     * Drop memoized results, e.g. after stok changed within the same request.
     */
    public static function forgetComputed(): void
    {
        self::$memo = [];
    }

    /**
     * Demand plan (no stock involved) for every obat with demand, plus box sizes. Cached for the day:
     * it only depends on past full months and faktur / permintaan history.
     */
    public function planBundle(array $params): array
    {
        $key = 'erm_forecast_plan:' . md5(json_encode($params)) . ':' . now()->format('Y-m-d');

        return Cache::remember($key, 3600, function () use ($params) {
            [$start, $end] = $this->period($params['period_months']);
            $demand = $this->demandStats($start, $end, $params['period_months']);
            $leadTimes = $demand ? $this->leadTimes($params, array_keys($demand)) : [];

            $plans = [];
            foreach ($demand as $obatId => $stats) {
                $plans[$obatId] = $this->plan($params, $stats, $leadTimes[$obatId]);
            }

            return [
                'period_start' => $start->format('Y-m-d'),
                'period_end' => $end->format('Y-m-d'),
                'plans' => $plans,
                'boxes' => $this->boxSizes(),
            ];
        });
    }

    /**
     * Reorder point and maximum stock from the demand history (independent of current stock).
     */
    public function plan(array $params, array $demand, array $leadTime): array
    {
        $L = (float) $leadTime['days'];
        $R = (float) $params['review_days'];
        $p = $params['service_level'] / 100;

        $total = $demand['total'];
        $planDaily = $demand['planning_mean_daily'];
        // Too few sale days for a meaningful distribution: plan on the average only
        $useQuantile = $demand['demand_days'] >= 3;

        $reorderPoint = 0;
        $maxStock = 0;
        if ($total > 0) {
            $reorderPoint = $planDaily * $L;
            $maxStock = $planDaily * ($L + $R);
            if ($useQuantile) {
                $reorderPoint = max($this->windowQuantile($demand['daily'], (int) ceil($L), $p), $reorderPoint);
                $maxStock = max($this->windowQuantile($demand['daily'], (int) ceil($L + $R), $p), $maxStock);
            }

            // Whole units; an obat that is still used keeps at least 1 in stock
            $reorderPoint = max(ceil(round($reorderPoint, 4)), 1);
            $maxStock = max(ceil(round($maxStock, 4)), $reorderPoint + 1);
        }

        return [
            'total' => $total,
            'active_months' => $demand['active_months'],
            'mean_monthly' => $demand['mean_monthly'],
            'std_monthly' => $demand['std_monthly'],
            'mean_daily' => $demand['mean_daily'],
            'planning_mean_daily' => $planDaily,
            'demand_days' => $demand['demand_days'],
            'capped_days' => $demand['capped_days'],
            'use_quantile' => $useQuantile,
            'lead_time' => $leadTime,
            'reorder_point' => $reorderPoint,
            'max_stock' => $maxStock,
            'safety_stock' => max($reorderPoint - ceil(round($planDaily * $L, 4)), 0),
        ];
    }

    /**
     * Full forecast metrics for one obat from its plan and current stock.
     */
    public function evaluate(array $params, ?array $plan, float $stock, float $outstanding, ?array $box, bool $wajib, array $leadTime): array
    {
        $L = (float) $leadTime['days'];
        $total = $plan['total'] ?? 0;
        $daily = $plan['mean_daily'] ?? 0;
        $reorderPoint = $plan['reorder_point'] ?? 0;
        $maxStock = $plan['max_stock'] ?? 0;
        $boxQty = $box['qty'] ?? null;
        $orderUnit = $boxQty ?: 1;

        // Wajib Ada: always keep stock, even with little or no demand
        if ($wajib) {
            $reorderPoint = max($reorderPoint, 1);
            $maxStock = max($maxStock, $reorderPoint + 1, $boxQty ?: 0);
        }
        $planned = $total > 0 || $wajib;

        // Most obat have no demand and are not wajib: nothing to plan, skip the work below
        if (!$planned) {
            return self::EMPTY_METRICS + [
                'lead_time_days' => round($L, 1),
                'lead_time_source' => $leadTime['source'],
                'total_stock' => round($stock, 2),
                'approved_outstanding_permintaan' => round($outstanding, 2),
                'total_stock_with_approved_permintaan' => round($stock + $outstanding, 2),
                'inventory_position' => round($stock + $outstanding, 2),
                'isi_per_box' => $boxQty,
                'isi_per_box_source' => $box['source'] ?? null,
                'jumlah_pesan_box' => $boxQty ? 0 : null,
                'status' => $stock > 0 ? 'tidak_bergerak' : 'tidak_ada_pemakaian',
            ];
        }

        $position = $stock + $outstanding;

        $qtyPesan = 0;
        if ($planned && $position <= $reorderPoint) {
            $need = max($maxStock - $position, 0);
            $qtyPesan = (int) (ceil(round($need / $orderUnit, 4)) * $orderUnit);
        }

        $daysOfCover = $daily > 0 ? $stock / $daily : null;
        $boxCoverDays = ($boxQty && $daily > 0) ? $boxQty / $daily : null;
        // Stock that runs out before an order placed today can arrive
        $runsOutBeforeDelivery = $daily > 0 ? $stock < $daily * $L : $stock <= 0;

        if (!$planned) {
            $status = $stock > 0 ? 'tidak_bergerak' : 'tidak_ada_pemakaian';
        } elseif ($runsOutBeforeDelivery && $position > $reorderPoint) {
            // Physical stock is low but enough is already on order
            $status = 'menunggu';
        } elseif ($runsOutBeforeDelivery) {
            $status = 'kritis';
        } elseif ($position <= $reorderPoint) {
            $status = 'pesan';
        } elseif ($daysOfCover !== null && $daysOfCover > self::OVERSTOCK_DAYS) {
            $status = 'overstock';
        } else {
            $status = 'aman';
        }

        $warnings = [];
        if ($planned && !$boxQty) {
            $warnings[] = 'Isi box belum diketahui, qty dihitung per satuan';
        }
        if ($boxCoverDays !== null && $boxCoverDays > self::OVERSTOCK_DAYS) {
            $warnings[] = '1 box = ' . number_format(floor($boxCoverDays), 0, ',', '.') . ' hari pemakaian, cek risiko ED';
        }
        if ($planned && $leadTime['source'] === 'default') {
            $warnings[] = 'Lead time default (riwayat faktur belum cukup)';
        }
        if (($plan['capped_days'] ?? 0) > 0) {
            $warnings[] = 'Ada penjualan borongan tidak biasa, tidak dijadikan patokan stok';
        }
        if ($total > 0 && !($plan['use_quantile'] ?? false) && !$wajib) {
            $warnings[] = 'Pemakaian sangat jarang (' . ($plan['demand_days'] ?? 0) . ' hari transaksi), pertimbangkan pesan saat ada resep';
        }

        return [
            'obat_keluar' => round($total, 2),
            'active_months' => $plan['active_months'] ?? 0,
            'average_monthly_keluar' => round($plan['mean_monthly'] ?? 0, 2),
            'std_monthly_keluar' => round($plan['std_monthly'] ?? 0, 2),
            'daily_demand' => round($daily, 4),
            'lead_time_days' => round($L, 1),
            'lead_time_source' => $leadTime['source'],
            'safety_stock' => $plan['safety_stock'] ?? 0,
            'reorder_point' => $reorderPoint,
            'max_stock' => $maxStock,
            // Kept for older consumers: limit stok = titik pesan
            'limit_stok' => $reorderPoint,
            'total_stock' => round($stock, 2),
            'approved_outstanding_permintaan' => round($outstanding, 2),
            'total_stock_with_approved_permintaan' => round($position, 2),
            'inventory_position' => round($position, 2),
            'isi_per_box' => $boxQty,
            'isi_per_box_source' => $box['source'] ?? null,
            'qty_pesan' => $qtyPesan,
            'jumlah_pesan_box' => $boxQty ? (int) ($qtyPesan / $boxQty) : null,
            'box_cover_days' => $boxCoverDays !== null ? round($boxCoverDays, 1) : null,
            'days_of_cover' => $daysOfCover !== null ? round($daysOfCover, 1) : null,
            'wajib_stok' => $wajib,
            'status' => $status,
            'warnings' => $warnings,
        ];
    }

    /**
     * Stop Pengadaan: the obat is no longer purchased. Usage and stock stay visible (to sell off the
     * remaining stock), but nothing is ever suggested for ordering.
     */
    private function stopPengadaan(array $metrics): array
    {
        $metrics['qty_pesan'] = 0;
        $metrics['jumlah_pesan_box'] = $metrics['isi_per_box'] ? 0 : null;
        $metrics['status'] = 'stop_pengadaan';
        $metrics['warnings'] = array_values(array_filter($metrics['warnings'], function ($w) {
            // Purchase-related hints no longer apply
            return !str_starts_with($w, 'Isi box') && !str_starts_with($w, '1 box') && !str_starts_with($w, 'Lead time') && !str_starts_with($w, 'Pemakaian sangat jarang');
        }));

        return $metrics;
    }

    /**
     * Forecast for one obat from raw demand stats (no cache).
     */
    public function metrics(array $params, ?array $demand, float $stock, float $outstanding, ?array $box, array $leadTime, bool $wajib = false): array
    {
        $plan = $demand ? $this->plan($params, $demand, $leadTime) : null;

        return $this->evaluate($params, $plan, $stock, $outstanding, $box, $wajib, $leadTime);
    }

    /**
     * The p-quantile of demand over every $window consecutive days in the history.
     * With less history than the window, the total is scaled up to the window length.
     */
    private function windowQuantile(array $daily, int $window, float $p): float
    {
        $n = count($daily);
        if ($n === 0 || $window <= 0) {
            return 0;
        }
        if ($window > $n) {
            return array_sum($daily) * $window / $n;
        }

        $prefix = [0.0];
        foreach ($daily as $i => $v) {
            $prefix[$i + 1] = $prefix[$i] + $v;
        }

        $sums = [];
        for ($i = 0; $i + $window <= $n; $i++) {
            $sums[] = $prefix[$i + $window] - $prefix[$i];
        }
        sort($sums);

        $index = max((int) ceil($p * count($sums)) - 1, 0);
        return $sums[$index];
    }

    /**
     * Linear-interpolated percentile of an already sorted list.
     */
    private function percentile(array $sorted, float $p): float
    {
        $n = count($sorted);
        if ($n === 0) {
            return 0;
        }
        $pos = ($n - 1) * $p;
        $lower = (int) floor($pos);
        $upper = (int) ceil($pos);
        return $sorted[$lower] + ($sorted[$upper] - $sorted[$lower]) * ($pos - $lower);
    }

    private function median(array $values): float
    {
        sort($values);
        $n = count($values);
        if ($n === 0) {
            return 0;
        }
        $mid = intdiv($n, 2);
        return $n % 2 ? (float) $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }

    private function formatNumber($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    }
}
