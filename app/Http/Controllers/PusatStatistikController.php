<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PusatStatistikController extends Controller
{
    /**
     * Display the pusat statistik dashboard (blank placeholder).
     */
    public function index(Request $request)
    {
        return view('pusatstatistik.dashboard');
    }

    /**
     * Show statistik dokter page. If id provided, show that dokter, otherwise first available.
     */
    public function dokter(Request $request, $id = null)
    {
        $dokter = null;
        try {
            if ($id) {
                $dokter = \App\Models\ERM\Dokter::with(['user','spesialisasi','klinik','mapping'])->find($id);
            } else {
                // Prefer the authenticated user's dokter record if available
                $user = \Illuminate\Support\Facades\Auth::user();
                if ($user && method_exists($user, 'dokter') && $user->dokter) {
                    $dokter = \App\Models\ERM\Dokter::with(['user','spesialisasi','klinik','mapping'])->find($user->dokter->id);
                }
                // Fallback to first dokter if none found yet
                if (!$dokter) {
                    $dokter = \App\Models\ERM\Dokter::with(['user','spesialisasi','klinik','mapping'])->first();
                }
            }
            // also load list of doctors for filter/select
            $dokterList = \App\Models\ERM\Dokter::with(['user','spesialisasi','klinik'])->orderBy('id')->get();

            // prepare initial visitation stats for the selected dokter (for immediate render)
            $initialLabels = [];
            $initialSeries = [];
            $now = \Illuminate\Support\Carbon::now();
            for ($i = 11; $i >= 0; $i--) {
                $m = $now->copy()->subMonths($i);
                $initialLabels[] = $m->format('Y-m');
            }
            $start = $now->copy()->subMonths(11)->startOfMonth()->toDateString();
            $end = $now->copy()->endOfMonth()->toDateString();
                        if ($dokter) {
                        $results = \App\Models\ERM\Visitation::selectRaw("DATE_FORMAT(tanggal_visitation, '%Y-%m') as ym, count(*) as total")
                            ->where('dokter_id', $dokter->id)
                            ->where('status_kunjungan', 2)
                            ->whereBetween('tanggal_visitation', [$start, $end])
                            ->groupBy('ym')
                            ->pluck('total', 'ym')
                            ->toArray();
                foreach ($initialLabels as $m) {
                    $initialSeries[] = isset($results[$m]) ? (int)$results[$m] : 0;
                }
            }
        } catch (\Exception $e) {
            $dokter = null;
            $dokterList = collect();
        }

        $initialVisits = ['labels' => $initialLabels ?? [], 'series' => $initialSeries ?? []];
        return view('statistik.dokter', compact('dokter','dokterList','initialVisits'));
    }

    /**
     * Return dokter data as JSON for AJAX requests.
     */
    public function dokterData(Request $request, $id)
    {
        $dokter = \App\Models\ERM\Dokter::with(['user','spesialisasi','klinik','mapping'])->find($id);
        if (!$dokter) {
            return response()->json(['ok' => false, 'message' => 'Dokter tidak ditemukan'], 404);
        }

        $photo = $dokter->photo ? asset('storage/' . ltrim($dokter->photo, '/')) : asset('img/avatar.png');

        $data = [
            'id' => $dokter->id,
            'name' => $dokter->user->name ?? null,
            'spesialisasi' => $dokter->spesialisasi->nama ?? null,
            'klinik' => $dokter->klinik->nama ?? null,
            'nik' => $dokter->nik ?? null,
            'sip' => $dokter->sip ?? null,
            'str' => $dokter->str ?? null,
            'no_hp' => $dokter->no_hp ?? null,
            'photo' => $photo,
        ];

        return response()->json(['ok' => true, 'data' => $data]);
    }

    /**
     * Return visitation statistics (visits per month) for the last 12 months for a dokter.
     */
    public function dokterVisitationStats(Request $request, $id)
    {
        // accept optional start/end query params (YYYY-MM-DD). If provided, use them; otherwise default to last 12 months
        $now = \Illuminate\Support\Carbon::now();
        if ($request->has('all')) {
            // all time requested: try to determine earliest visitation date for this dokter
                $minDate = \App\Models\ERM\Visitation::where('dokter_id', $id)->where('status_kunjungan', 2)->min('tanggal_visitation');
            if ($minDate) {
                $startDt = \Illuminate\Support\Carbon::parse($minDate)->startOfMonth();
                $endDt = $now->copy()->endOfMonth();
            } else {
                // fallback to last 12 months
                $startDt = $now->copy()->subMonths(11)->startOfMonth();
                $endDt = $now->copy()->endOfMonth();
            }
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                // keep exact start/end days (do not expand to month) so we can detect same-month ranges
                $rawStart = $request->input('start');
                $rawEnd = $request->input('end');
                $startExact = \Illuminate\Support\Carbon::parse($rawStart)->startOfDay();
                $endExact = \Illuminate\Support\Carbon::parse($rawEnd)->endOfDay();
                // For building month-period if needed, keep month boundaries as well
                $startDt = $startExact->copy();
                $endDt = $endExact->copy();
            } catch (\Exception $e) {
                $startDt = $now->copy()->subMonths(11)->startOfMonth();
                $endDt = $now->copy()->endOfMonth();
            }
        } else {
            $startDt = $now->copy()->subMonths(11)->startOfMonth();
            $endDt = $now->copy()->endOfMonth();
        }

        // Determine whether we should aggregate by day or by month.
        // If user supplied exact start/end and they fall within the same calendar month, return daily buckets.
        $useDaily = false;
        if ($request->has('start') && $request->has('end')) {
            try {
                $startCheck = \Illuminate\Support\Carbon::parse($request->input('start'));
                $endCheck = \Illuminate\Support\Carbon::parse($request->input('end'));
                if ($startCheck->format('Y-m') === $endCheck->format('Y-m')) {
                    $useDaily = true;
                }
            } catch (\Exception $e) {
                $useDaily = false;
            }
        }

        // Build period format and labels
        if ($useDaily) {
            $periodFmt = "DATE(v.tanggal_visitation)";
            $start = \Illuminate\Support\Carbon::parse($request->input('start'))->startOfDay()->toDateString();
            $end = \Illuminate\Support\Carbon::parse($request->input('end'))->endOfDay()->toDateString();
            $period = new \Carbon\CarbonPeriod($start, '1 day', $end);
            $labels = array_map(function($d){ return $d->format('Y-m-d'); }, iterator_to_array($period));
        } else {
            $periodFmt = "DATE_FORMAT(v.tanggal_visitation, '%Y-%m')";
            $start = $startDt->toDateString();
            $end = $endDt->toDateString();
            $periodRange = new \Carbon\CarbonPeriod($startDt->copy()->startOfMonth(), '1 month', $endDt->copy()->endOfMonth());
            $labels = array_map(function($d){ return $d->format('Y-m'); }, iterator_to_array($periodRange));
        }

        // Query visit counts grouped by period and jenis_kunjungan
        $visQ = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
            ->selectRaw("{$periodFmt} as period, v.jenis_kunjungan as jenis, count(*) as total")
            ->where('v.dokter_id', $id)
            ->where('v.status_kunjungan', 2);
        if ($start && $end) $visQ->whereBetween('v.tanggal_visitation', [$start, $end]);
        $visRows = $visQ->groupBy('period','jenis')->get();

        // Query konsultasi-with-lab counts grouped by period
        $labQ = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
            ->join('erm_lab_permintaan as l', 'l.visitation_id', '=', 'v.id')
            ->selectRaw("{$periodFmt} as period, count(distinct v.id) as total")
            ->where('v.dokter_id', $id)
            ->where('v.status_kunjungan', 2)
            ->where('v.jenis_kunjungan', 1);
        if ($start && $end) $labQ->whereBetween('v.tanggal_visitation', [$start, $end]);
        $labRows = $labQ->groupBy('period')->get();

        // Build maps
        $mapJenis = [];
        foreach ($visRows as $r) {
            $p = $r->period;
            $j = (int)$r->jenis;
            if (!isset($mapJenis[$p])) $mapJenis[$p] = [];
            $mapJenis[$p][$j] = (int)$r->total;
        }
        $mapLab = [];
        foreach ($labRows as $r) { $mapLab[$r->period] = (int)$r->total; }

        // Prepare series arrays
        $seriesMap = [
            'Total' => [],
            'Konsultasi' => [],
            'Konsultasi (Tanpa Lab)' => [],
            'Konsultasi (Dengan Lab)' => [],
            'Beli Produk' => [],
            'Lab' => [],
        ];

        foreach ($labels as $labl) {
            $counts = isset($mapJenis[$labl]) ? $mapJenis[$labl] : [];
            $kons = isset($counts[1]) ? (int)$counts[1] : 0;
            $beli = isset($counts[2]) ? (int)$counts[2] : 0;
            $lab = isset($counts[3]) ? (int)$counts[3] : 0;
            $konsWithLab = isset($mapLab[$labl]) ? (int)$mapLab[$labl] : 0;
            $konsNoLab = max(0, $kons - $konsWithLab);
            $total = $kons + $beli + $lab;

            $seriesMap['Total'][] = $total;
            $seriesMap['Konsultasi'][] = $kons;
            $seriesMap['Konsultasi (Tanpa Lab)'][] = $konsNoLab;
            $seriesMap['Konsultasi (Dengan Lab)'][] = $konsWithLab;
            $seriesMap['Beli Produk'][] = $beli;
            $seriesMap['Lab'][] = $lab;
        }

        // Convert to ApexCharts series format
        $seriesOut = [];
        foreach ($seriesMap as $name => $arr) {
            $seriesOut[] = ['name' => $name, 'data' => $arr];
        }

        return response()->json(['ok' => true, 'labels' => $labels, 'series' => $seriesOut]);
    }

    /**
     * Return visitation breakdown by jenis_kunjungan for a dokter (all time).
     */
    public function dokterVisitationBreakdown(Request $request, $id)
    {
        // aggregated counts by jenis_kunjungan; allow optional start/end filter (YYYY-MM-DD)
            $query = \App\Models\ERM\Visitation::selectRaw("jenis_kunjungan, count(*) as total")->where('dokter_id', $id)->where('status_kunjungan', 2);
        if ($request->has('start') && $request->has('end')) {
            try {
                $s = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $e = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
                $query->whereBetween('tanggal_visitation', [$s, $e]);
            } catch (\Exception $e) {
                // ignore parsing errors and use all-time
            }
        }

        $counts = $query->groupBy('jenis_kunjungan')->pluck('total', 'jenis_kunjungan')->toArray();

        $mapping = [1 => 'Konsultasi', 2 => 'Beli Produk', 3 => 'Lab'];

        $breakdown = [];
        foreach ($mapping as $k => $label) {
            $breakdown[$k] = isset($counts[$k]) ? (int)$counts[$k] : 0;
        }

        // Further split Konsultasi into with/without LabPermintaan
        $konsultasiWithLab = 0;
        $konsultasiNoLab = 0;
        try {
            $visQ = \Illuminate\Support\Facades\DB::table('erm_visitations as v')
                ->where('v.dokter_id', $id)
                ->where('v.status_kunjungan', 2)
                ->where('v.jenis_kunjungan', 1);
            if ($request->has('start') && $request->has('end')) {
                $s = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $e = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
                $visQ->whereBetween('v.tanggal_visitation', [$s, $e]);
            }

            // count with lab (exists in erm_lab_permintaan)
            $withLab = (clone $visQ)->join('erm_lab_permintaan as l', 'l.visitation_id', '=', 'v.id')
                ->selectRaw('count(distinct v.id) as cnt')
                ->value('cnt');
            $konsultasiWithLab = (int)($withLab ?: 0);

            // total konsultasi from earlier breakdown for jenis_kunjungan=1
            $totalKons = $breakdown[1] ?? 0;
            $konsultasiNoLab = max(0, $totalKons - $konsultasiWithLab);
        } catch (\Exception $e) {
            // ignore DB errors and leave zeros
        }

        // expose both legacy numeric keys and new detailed keys
        $breakdown['konsultasi_with_lab'] = $konsultasiWithLab;
        $breakdown['konsultasi_no_lab'] = $konsultasiNoLab;

        $total = array_sum([($breakdown[1] ?? 0), ($breakdown[2] ?? 0), ($breakdown[3] ?? 0)]);

        return response()->json(['ok' => true, 'breakdown' => $breakdown, 'total' => (int)$total]);
    }

    /**
     * Return retention-like stats for a dokter: number of new patients (first visit in period),
     * returning patients (had earlier visits before period and also visited in period), and retention rate.
     */
    public function dokterRetentionStats(Request $request, $id)
    {
        $now = \Illuminate\Support\Carbon::now();
        // Determine date range: accept start/end (YYYY-MM-DD) or all=1, otherwise default to current month
        if ($request->has('all')) {
            $start = null; $end = null;
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                $start = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $end = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
            } catch (\Exception $e) {
                $start = $now->copy()->startOfMonth()->toDateString();
                $end = $now->copy()->endOfMonth()->toDateString();
            }
        } else {
            $start = $now->copy()->startOfMonth()->toDateString();
            $end = $now->copy()->endOfMonth()->toDateString();
        }

        // collect pasien_ids that visited in the period (filter status_kunjungan = 2)
        $visQ = \App\Models\ERM\Visitation::where('dokter_id', $id)->where('status_kunjungan', 2);
        if ($start && $end) {
            $visQ->whereBetween('tanggal_visitation', [$start, $end]);
        }
        $pasienIds = $visQ->pluck('pasien_id')->unique()->filter()->values()->all();
        $total = count($pasienIds);

        $newCount = 0;
        $returningCount = 0;

        if ($total > 0) {
            // fetch first-ever visitation date per pasien for this dokter (across all time)
            $firstDates = \App\Models\ERM\Visitation::selectRaw('pasien_id, MIN(tanggal_visitation) as first_date')
                ->where('dokter_id', $id)
                ->where('status_kunjungan', 2)
                ->whereIn('pasien_id', $pasienIds)
                ->groupBy('pasien_id')
                ->pluck('first_date', 'pasien_id')
                ->toArray();

            foreach ($pasienIds as $pid) {
                $fd = isset($firstDates[$pid]) ? $firstDates[$pid] : null;
                if (!$fd) {
                    // defensively treat as new
                    $newCount++;
                    continue;
                }
                if ($start) {
                    // if first_date is on/after period start -> new, otherwise returning
                    try {
                        $firstDt = \Illuminate\Support\Carbon::parse($fd)->toDateString();
                        if ($firstDt >= $start) $newCount++; else $returningCount++;
                    } catch (\Exception $e) {
                        $newCount++;
                    }
                } else {
                    // no start (all time): by definition first visit falls inside period (all time) -> treat as new
                    $newCount++;
                }
            }
        }

        $retention = 0.0;
        if ($total > 0) {
            $retention = ($returningCount / $total) * 100.0;
            $retention = round($retention, 1);
        }

        return response()->json([
            'ok' => true,
            'total' => (int)$total,
            'new' => (int)$newCount,
            'returning' => (int)$returningCount,
            'retention_rate' => $retention,
        ]);
    }

    /**
     * Return tindakan statistics for a dokter: top tindakan by occurrence in the selected period.
     */
    public function dokterTindakanStats(Request $request, $id)
    {
        $now = \Illuminate\Support\Carbon::now();
        if ($request->has('all')) {
            $start = null; $end = null;
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                $start = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $end = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
            } catch (\Exception $e) {
                $start = $now->copy()->startOfMonth()->toDateString();
                $end = $now->copy()->endOfMonth()->toDateString();
            }
        } else {
            $start = $now->copy()->startOfMonth()->toDateString();
            $end = $now->copy()->endOfMonth()->toDateString();
        }

        // Build query: join riwayat tindakan to visitations and tindakan metadata
        $q = \Illuminate\Support\Facades\DB::table('erm_riwayat_tindakan as r')
            ->join('erm_visitations as v', 'r.visitation_id', '=', 'v.id')
            ->join('erm_tindakan as t', 'r.tindakan_id', '=', 't.id')
            ->where('v.dokter_id', $id)
            ->where('v.status_kunjungan', 2);

        if ($start && $end) {
            $q->whereBetween('v.tanggal_visitation', [$start, $end]);
        }

        $rows = $q->selectRaw('r.tindakan_id as tindakan_id, t.nama as name, count(*) as total')
            ->groupBy('r.tindakan_id', 't.nama')
            ->orderByRaw('count(*) desc')
            ->limit(10)
            ->get();

        $tops = [];
        foreach ($rows as $r) {
            $tops[] = [
                'tindakan_id' => $r->tindakan_id,
                'name' => $r->name,
                'count' => (int)$r->total,
            ];
        }

        return response()->json(['ok' => true, 'tops' => $tops]);
    }

    /**
     * Return obat statistics for a dokter: top obat by total jumlah in resep farmasi for the selected period.
     */
    public function dokterObatStats(Request $request, $id)
    {
        $now = \Illuminate\Support\Carbon::now();
        if ($request->has('all')) {
            $start = null; $end = null;
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                $start = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $end = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
            } catch (\Exception $e) {
                $start = $now->copy()->startOfMonth()->toDateString();
                $end = $now->copy()->endOfMonth()->toDateString();
            }
        } else {
            $start = $now->copy()->startOfMonth()->toDateString();
            $end = $now->copy()->endOfMonth()->toDateString();
        }

        // Build query: join resep farmasi to visitations and obat metadata
        // Filter obat stats by the visitation's dokter_id to match other statistik endpoints
        $q = \Illuminate\Support\Facades\DB::table('erm_resepfarmasi as r')
            ->join('erm_visitations as v', 'r.visitation_id', '=', 'v.id')
            ->leftJoin('erm_obat as o', 'r.obat_id', '=', 'o.id')
            ->where('v.dokter_id', $id)
            ->where('v.status_kunjungan', 2);

        if ($start && $end) {
            $q->whereBetween('v.tanggal_visitation', [$start, $end]);
        }

        $rows = $q->selectRaw('r.obat_id as obat_id, o.nama as name, SUM(COALESCE(r.jumlah,0)) as total')
            ->groupBy('r.obat_id', 'o.nama')
            ->orderByRaw('SUM(COALESCE(r.jumlah,0)) desc')
            ->limit(20)
            ->get();

        $tops = [];
        foreach ($rows as $r) {
            $tops[] = [
                'obat_id' => $r->obat_id,
                'name' => $r->name ?: ('Obat ' . $r->obat_id),
                'jumlah' => (int)$r->total,
            ];
        }

        return response()->json(['ok' => true, 'tops' => $tops]);
    }

    /**
     * Return patient-level statistics for a dokter: total unique patients (in date range),
     * gender distribution, age buckets and pasien status counts.
     */
    public function dokterPatientStats(Request $request, $id)
    {
        // Determine date range: accept start/end (YYYY-MM-DD) or all=1, otherwise default to current month
        $now = \Illuminate\Support\Carbon::now();
        if ($request->has('all')) {
            $start = null; $end = null;
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                $start = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $end = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
            } catch (\Exception $e) {
                $start = $now->copy()->startOfMonth()->toDateString();
                $end = $now->copy()->endOfMonth()->toDateString();
            }
        } else {
            $start = $now->copy()->startOfMonth()->toDateString();
            $end = $now->copy()->endOfMonth()->toDateString();
        }

        // Build visitation query to collect pasien_ids (filter by status_kunjungan = 2)
        $visQ = \App\Models\ERM\Visitation::where('dokter_id', $id)->where('status_kunjungan', 2);
        if ($start && $end) {
            $visQ->whereBetween('tanggal_visitation', [$start, $end]);
        }

        $pasienIds = $visQ->pluck('pasien_id')->unique()->filter()->values()->all();

        $totalPatients = count($pasienIds);

        $genderCounts = ['male' => 0, 'female' => 0, 'other' => 0];
        $ageBuckets = [
            '0-17' => 0,
            '18-30' => 0,
            '31-45' => 0,
            '46-60' => 0,
            '61+' => 0,
        ];
        $ages = [];
        $statusCounts = [];

        if (!empty($pasienIds)) {
            $pasiens = \App\Models\ERM\Pasien::whereIn('id', $pasienIds)->get(['id','tanggal_lahir','gender','status_pasien']);
            foreach ($pasiens as $p) {
                // gender normalization (support Indonesian labels like 'Laki-laki' / 'Perempuan')
                $g = strtolower(trim((string)$p->gender));
                $maleValues = ['l','m','male','man','laki-laki','laki laki','laki','pria'];
                $femaleValues = ['p','f','female','woman','perempuan','wanita'];
                if (in_array($g, $maleValues, true)) $genderCounts['male']++;
                else if (in_array($g, $femaleValues, true)) $genderCounts['female']++;
                else $genderCounts['other']++;

                // age calculation
                if ($p->tanggal_lahir) {
                    try {
                        $age = \Illuminate\Support\Carbon::parse($p->tanggal_lahir)->age;
                        $ages[] = $age;
                        if ($age <= 17) $ageBuckets['0-17']++;
                        else if ($age <= 30) $ageBuckets['18-30']++;
                        else if ($age <= 45) $ageBuckets['31-45']++;
                        else if ($age <= 60) $ageBuckets['46-60']++;
                        else $ageBuckets['61+']++;
                    } catch (\Exception $e) {
                        // ignore invalid dates
                    }
                }

                // status_pasien counts
                $st = (string)($p->status_pasien ?? 'unknown');
                if (!isset($statusCounts[$st])) $statusCounts[$st] = 0;
                $statusCounts[$st]++;
            }
        }

        $avgAge = null;
        if (!empty($ages)) {
            $avgAge = round(array_sum($ages) / count($ages), 1);
        }

        return response()->json([
            'ok' => true,
            'totalPatients' => (int)$totalPatients,
            'gender' => $genderCounts,
            'age' => [
                'buckets' => $ageBuckets,
                'average' => $avgAge,
            ],
            'statusCounts' => $statusCounts,
        ]);
    }

    /**
     * Return top patients (by visit count) for a dokter within optional date range.
     */
    public function dokterTopPatients(Request $request, $id)
    {
        $now = \Illuminate\Support\Carbon::now();
        if ($request->has('all')) {
            $start = null; $end = null;
        } elseif ($request->has('start') && $request->has('end')) {
            try {
                $start = \Illuminate\Support\Carbon::parse($request->input('start'))->toDateString();
                $end = \Illuminate\Support\Carbon::parse($request->input('end'))->toDateString();
            } catch (\Exception $e) {
                $start = $now->copy()->startOfMonth()->toDateString();
                $end = $now->copy()->endOfMonth()->toDateString();
            }
        } else {
            $start = $now->copy()->startOfMonth()->toDateString();
            $end = $now->copy()->endOfMonth()->toDateString();
        }

        // Build visitation query and include a left join to invoices to calculate spend (only count paid invoices)
        $visQ = \App\Models\ERM\Visitation::from('erm_visitations as v')
            ->where('v.dokter_id', $id)
            ->where('v.status_kunjungan', 2)
            ->leftJoin('finance_invoices as inv', 'v.id', '=', 'inv.visitation_id');
        if ($start && $end) {
            $visQ->whereBetween('v.tanggal_visitation', [$start, $end]);
        }

        // Select pasien_id, visit count and sum of paid invoice amounts
        $select = "v.pasien_id, count(*) as total, SUM(CASE WHEN inv.amount_paid IS NOT NULL THEN inv.total_amount ELSE 0 END) as spend";
        $rowsQ = $visQ->selectRaw($select)
            ->groupBy('v.pasien_id');

        // sorting: support 'spend' or 'visits'
        $sort = $request->input('sort', 'visits');
        $dir = strtolower($request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if ($sort === 'spend') {
            // order by the computed spend (use the same expression to be safe)
            $rowsQ->orderByRaw("SUM(CASE WHEN inv.amount_paid IS NOT NULL THEN inv.total_amount ELSE 0 END) $dir");
        } else {
            // default: order by visits (total)
            $rowsQ->orderByRaw("count(*) $dir");
        }

        $rows = $rowsQ->limit(10)->get()->toArray();

        $patientIds = array_map(function($r){ return $r['pasien_id']; }, $rows);
        $patients = [];
        if (!empty($patientIds)) {
            $pasiens = \App\Models\ERM\Pasien::whereIn('id', $patientIds)->get();
            foreach ($pasiens as $p) {
                $patients[$p->id] = $p;
            }
        }

        $tops = [];
        // convert rows into tops preserving visits and spend
        foreach ($rows as $r) {
            $pid = $r['pasien_id'];
            $p = isset($patients[$pid]) ? $patients[$pid] : null;
            $name = $p ? ($p->nama ?? $p->name ?? ($p->nama_lengkap ?? null)) : null;
            if (!$name) $name = 'Pasien ' . $pid;
            $tops[] = [
                'pasien_id' => $pid,
                'name' => $name,
                'visits' => (int)($r['total'] ?? 0),
                'spend' => (float)($r['spend'] ?? 0),
            ];
        }

        return response()->json(['ok' => true, 'tops' => $tops]);
    }
}
