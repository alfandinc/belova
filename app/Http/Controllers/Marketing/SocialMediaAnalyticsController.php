<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marketing\ContentPlan;
use App\Models\Marketing\ContentReport;
use Illuminate\Support\Facades\DB;

class SocialMediaAnalyticsController extends Controller
{
    public function index()
    {
        return view('marketing.social_analytics.index');
    }

    /**
     * Return aggregated statistics per content plan for DataTable / charts
     */
    public function data(Request $request)
    {
        // Accept filters from request
        $brands = $request->input('brand', []);
        $platforms = $request->input('platform', []);
        $jenis = $request->input('jenis_konten', []);
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');

        // build base ContentPlan query applying filters on JSON columns where appropriate
        $q = ContentPlan::query();
        // brand filter (brand is stored as json array)
        if (!empty($brands)) {
            $q->where(function($sub) use ($brands) {
                foreach ((array)$brands as $b) {
                    if ($b === null || $b === '') continue;
                    $sub->orWhereJsonContains('brand', $b);
                }
            });
        }
        // platform filter (platform is stored as json array)
        if (!empty($platforms)) {
            $q->where(function($sub) use ($platforms) {
                foreach ((array)$platforms as $p) {
                    if ($p === null || $p === '') continue;
                    $sub->orWhereJsonContains('platform', $p);
                }
            });
        }
        // jenis_konten filter
        if (!empty($jenis)) {
            $q->where(function($sub) use ($jenis) {
                foreach ((array)$jenis as $j) {
                    if ($j === null || $j === '') continue;
                    $sub->orWhereJsonContains('jenis_konten', $j);
                }
            });
        }

        $plans = $q->orderBy('tanggal_publish', 'desc')->get();

        // parse date filters for recorded_at on reports
        $start = null; $end = null;
        if ($dateStart) {
            try { $start = \Carbon\Carbon::parse($dateStart)->startOfDay(); } catch (\Exception $e) { $start = null; }
        }
        if ($dateEnd) {
            try { $end = \Carbon\Carbon::parse($dateEnd)->endOfDay(); } catch (\Exception $e) { $end = null; }
        }

        $totals = [
            'likes' => 0,
            'comments' => 0,
            'saves' => 0,
            'shares' => 0,
            'reach' => 0,
            'impressions' => 0,
            // ad_reach not present in ContentReport table; keep 0 for now
            'ad_reach' => 0,
        ];

    $result = $plans->map(function($plan) use ($start, $end, &$totals) {
            $reportsQ = ContentReport::where('content_plan_id', $plan->id)->orderBy('recorded_at', 'desc');
            if ($start && $end) {
                $reportsQ->whereBetween('recorded_at', [$start, $end]);
            } elseif ($start) {
                $reportsQ->where('recorded_at', '>=', $start);
            } elseif ($end) {
                $reportsQ->where('recorded_at', '<=', $end);
            }
            $reports = $reportsQ->get();

            $totalLikes = $reports->sum('likes');
            $totalComments = $reports->sum('comments');
            $totalSaves = $reports->sum('saves');
            $totalShares = $reports->sum('shares');
            $totalReach = $reports->sum('reach');
            $totalImpressions = $reports->sum('impressions');

            // interactions = likes+comments+saves+shares
            $totalInteractions = $totalLikes + $totalComments + $totalSaves + $totalShares;

            // avg ERI/ERR across reports (simple mean)
            $avgEri = $reports->count() ? round($reports->avg('eri'), 4) : 0;
            $avgErr = $reports->count() ? round($reports->avg('err'), 4) : 0;

            // latest report (if any)
            $latest = $reports->first();

            return [
                'id' => $plan->id,
                'judul' => $plan->judul,
                'brand' => $plan->brand,
                'tanggal_publish' => $plan->tanggal_publish ? $plan->tanggal_publish->toDateTimeString() : null,
                'platform' => $plan->platform,
                'jenis_konten' => $plan->jenis_konten,
                'total_likes' => $totalLikes,
                'total_comments' => $totalComments,
                'total_saves' => $totalSaves,
                'total_shares' => $totalShares,
                'total_interactions' => $totalInteractions,
                'total_reach' => $totalReach,
                'total_impressions' => $totalImpressions,
                'avg_eri' => $avgEri,
                'avg_err' => $avgErr,
                'latest_report' => $latest,
                'reports_count' => $reports->count(),
            ];
        });

        // accumulate global totals
        foreach ($result as $r) {
            $totals['likes'] += $r['total_likes'] ?? 0;
            $totals['comments'] += $r['total_comments'] ?? 0;
            $totals['saves'] += $r['total_saves'] ?? 0;
            $totals['shares'] += $r['total_shares'] ?? 0;
            $totals['reach'] += $r['total_reach'] ?? 0;
            $totals['impressions'] += $r['total_impressions'] ?? 0;
            // ad_reach left as 0 unless source exists
        }

        // Month-by-month aggregates across the selected plans/reports
        $planIds = $plans->pluck('id')->toArray();
        $monthly = [];
        if (!empty($planIds)) {
            $monthlyQ = DB::table('marketing_content_reports')
                ->select(DB::raw("DATE_FORMAT(recorded_at, '%Y-%m') as month"),
                    DB::raw('SUM(likes) as likes'),
                    DB::raw('SUM(comments) as comments'),
                    DB::raw('SUM(saves) as saves'),
                    DB::raw('SUM(shares) as shares'),
                    DB::raw('SUM(reach) as reach'),
                    DB::raw('SUM(impressions) as impressions')
                )
                ->whereIn('content_plan_id', $planIds)
                ->groupBy('month')
                ->orderBy('month', 'asc');

            if ($start && $end) {
                $monthlyQ->whereBetween('recorded_at', [$start, $end]);
            } elseif ($start) {
                $monthlyQ->where('recorded_at', '>=', $start);
            } elseif ($end) {
                $monthlyQ->where('recorded_at', '<=', $end);
            }

            $monthly = $monthlyQ->get();
        }

        // normalize monthly into arrays for charting
        $months = [];
        $m_likes = []; $m_comments = []; $m_saves = []; $m_shares = []; $m_reach = []; $m_impressions = [];
        foreach ($monthly as $m) {
            $months[] = $m->month;
            $m_likes[] = (int) $m->likes;
            $m_comments[] = (int) $m->comments;
            $m_saves[] = (int) $m->saves;
            $m_shares[] = (int) $m->shares;
            $m_reach[] = (int) $m->reach;
            $m_impressions[] = (int) $m->impressions;
        }

        return response()->json([
            'data' => $result,
            'totals' => $totals,
            'by_month' => [
                'months' => $months,
                'likes' => $m_likes,
                'comments' => $m_comments,
                'saves' => $m_saves,
                'shares' => $m_shares,
                'reach' => $m_reach,
                'impressions' => $m_impressions,
            ],
        ]);
    }
}
