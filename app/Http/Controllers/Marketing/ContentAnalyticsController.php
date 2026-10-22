<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Support\Marketing\ContentAnalytics;
use App\Support\Marketing\InstagramInsights;
use App\Support\Marketing\MarketingAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** "Analisa Konten": performance of the published Instagram content per period (see ContentAnalytics). */
class ContentAnalyticsController extends Controller
{
    public function index()
    {
        return view('marketing.content_analytics.index', [
            'brands' => ContentPlanController::brandOptions(),
            'jenisKonten' => ContentPlanController::JENIS_KONTEN,
            'kontenPilar' => ContentPlanController::KONTEN_PILAR,
            'canManage' => MarketingAccess::isMarketing(Auth::user()),
            'enabled' => InstagramInsights::enabled(),
            'igAccounts' => InstagramInsights::enabled()
                ? \App\Models\Marketing\SocialAccount::where('platform', 'instagram')->orderBy('username')->get(['id', 'username', 'brand'])
                : collect(),
        ]);
    }

    public function data(Request $request)
    {
        abort_unless(InstagramInsights::enabled(), 422, 'Tabel insight belum ada: jalankan php artisan migrate.');
        $data = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
            'brand' => 'nullable|string|max:100',
            'jenis' => 'nullable|string|max:50',
            'pilar' => 'nullable|string|max:100',
            'account' => 'nullable|integer',
        ], [], ['start' => 'tanggal awal', 'end' => 'tanggal akhir']);

        $start = Carbon::parse($data['start']);
        $end = Carbon::parse($data['end']);
        abort_if($start->diffInDays($end) > 366, 422, 'Periode maksimal 1 tahun.');

        return response()->json(ContentAnalytics::build($start, $end, $data));
    }

    /** Best posting time for the schedule form (optionally for one jenis / klinik). */
    public function bestTime(Request $request)
    {
        $filters = $request->validate(['jenis' => 'nullable|string|max:50', 'brand' => 'nullable|string|max:100']);

        return response()->json(['hint' => \App\Support\Marketing\PostingTimeAnalytics::hint($filters)]);
    }
}
