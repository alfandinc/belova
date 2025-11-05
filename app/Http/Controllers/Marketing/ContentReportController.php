<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marketing\ContentReport;
use App\Models\Marketing\ContentPlan;
use Yajra\DataTables\DataTables;

class ContentReportController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ContentReport::with('contentPlan')->select('marketing_content_reports.*');
            return DataTables::of($data)
                ->editColumn('content_plan_id', function ($row) {
                    return $row->contentPlan ? e($row->contentPlan->judul) . ' (id:' . $row->content_plan_id . ')' : $row->content_plan_id;
                })
                ->editColumn('recorded_at', function ($row) {
                    return $row->recorded_at ? $row->recorded_at->format('Y-m-d H:i') : '';
                })
                ->make(true);
        }
        $plans = ContentPlan::orderBy('judul')->get();
        return view('marketing.content_report.index', compact('plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'content_plan_id' => 'nullable|exists:marketing_content_plans,id',
            'likes' => 'nullable|integer|min:0',
            'comments' => 'nullable|integer|min:0',
            'saves' => 'nullable|integer|min:0',
            'shares' => 'nullable|integer|min:0',
            'reach' => 'nullable|integer|min:0',
            'impressions' => 'nullable|integer|min:0',
            'err' => 'nullable|numeric',
            'eri' => 'nullable|numeric',
            'recorded_at' => 'nullable|date',
        ]);

    // ensure numeric values
        $likes = intval($data['likes'] ?? 0);
        $comments = intval($data['comments'] ?? 0);
        $saves = intval($data['saves'] ?? 0);
        $shares = intval($data['shares'] ?? 0);
        $impressions = intval($data['impressions'] ?? 0);
    $reach = intval($data['reach'] ?? 0);

        // compute ERR and ERI (as percentage) based on reach and impressions respectively
        $interactions = $likes + $comments + $saves + $shares;
        // ERI: interactions / impressions
        $data['eri'] = ($impressions > 0) ? round(($interactions / $impressions) * 100, 4) : 0;
        // ERR: interactions / reach
        $data['err'] = ($reach > 0) ? round(($interactions / $reach) * 100, 4) : 0;

    // recorded_at should be set to now when saving from the modal
    $data['recorded_at'] = \Carbon\Carbon::now();

    // legacy engagement_rate column removed; we store only eri and err

    $report = ContentReport::create($data);

        return response()->json(['success' => true, 'data' => $report]);
    }

    /**
     * Return latest report for a given content plan
     */
    public function byPlan($id)
    {
        $reports = ContentReport::where('content_plan_id', $id)->orderBy('recorded_at', 'desc')->get();
        return response()->json($reports);
    }

    /**
     * Update an existing report
     */
    public function update(Request $request, $id)
    {
        $report = ContentReport::findOrFail($id);
        $data = $request->validate([
            'likes' => 'nullable|integer|min:0',
            'comments' => 'nullable|integer|min:0',
            'saves' => 'nullable|integer|min:0',
            'shares' => 'nullable|integer|min:0',
            'reach' => 'nullable|integer|min:0',
            'impressions' => 'nullable|integer|min:0',
            'err' => 'nullable|numeric',
            'eri' => 'nullable|numeric',
            'recorded_at' => 'nullable|date',
        ]);
        // merge with existing values for calculation
        $likes = intval($data['likes'] ?? $report->likes);
        $comments = intval($data['comments'] ?? $report->comments);
        $saves = intval($data['saves'] ?? $report->saves);
        $shares = intval($data['shares'] ?? $report->shares);
        $impressions = intval($data['impressions'] ?? $report->impressions);
        $reach = intval($data['reach'] ?? $report->reach);

        $interactions = $likes + $comments + $saves + $shares;
        $data['eri'] = ($impressions > 0) ? round(($interactions / $impressions) * 100, 4) : 0;
        $data['err'] = ($reach > 0) ? round(($interactions / $reach) * 100, 4) : 0;

        // Always update recorded_at to now when user clicks save
        $data['recorded_at'] = \Carbon\Carbon::now();

    // legacy engagement_rate column removed; we store only eri and err

        $report->update($data);

        return response()->json(['success' => true, 'data' => $report]);
    }

    /**
     * Return a single report by id
     */
    public function show($id)
    {
        $report = ContentReport::findOrFail($id);
        return response()->json($report);
    }
}
