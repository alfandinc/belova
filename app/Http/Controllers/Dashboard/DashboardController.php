<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\Dashboard\Clinics;
use App\Support\Dashboard\DashboardWidgets;
use App\Support\Dashboard\QuickLinks;
use App\Support\Dashboard\Revenue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * The page itself only lists the user's widgets (config/dashboard_widgets.php, per role permission);
     * each widget's data is fetched afterwards from widget(), so a heavy widget never holds up the page
     * (or the redirect right after login).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        return view('dashboard.index', [
            'dashboardWidgets' => DashboardWidgets::forUser($user),
            'employeePosition' => $user?->employee?->primaryPosition(),
            'quickLinks' => QuickLinks::forUser($user),
            'dashboardFilter' => $this->filter($request),
            // Unread "jadwal diubah" notifications, shown on the header's "Jadwal Saya" button
            'jadwalUnread' => $user->unreadNotifications()
                ->where('type', \App\Notifications\JadwalBerubahNotification::class)
                ->count(),
        ]);
    }

    /** One widget's rendered HTML for the current period and widget options (AJAX). */
    public function widget(Request $request, string $key)
    {
        $user = Auth::user();
        $widget = DashboardWidgets::forUser($user)->firstWhere('key', $key);
        abort_unless($widget, 404);

        $dashboardFilter = $this->filter($request);
        DashboardWidgets::load(collect([$widget]), $user, $dashboardFilter);

        return response()->json([
            'html' => view('dashboard.partials.widget', compact('widget', 'dashboardFilter'))->render(),
        ]);
    }

    /**
     * Every sold item of one revenue category in the period, with its clinic split (Sumber Omset's
     * "Lihat semua"). Only for users who can see the Sumber Omset widget.
     */
    public function omsetItems(Request $request)
    {
        abort_unless(DashboardWidgets::forUser(Auth::user())->firstWhere('key', 'sumber_omset'), 403);

        $category = array_key_exists($request->input('category'), Revenue::CATEGORIES) ? $request->input('category') : 'tindakan';
        $filter = $this->filter($request);
        $items = Revenue::items($filter['period_start']->copy()->startOfDay(), $filter['period_end']->copy()->endOfDay(), $category, null);

        return response()->json([
            'html' => view('dashboard.partials.omset_items', [
                'items' => $items,
                'category' => $category,
                'clinics' => Clinics::all(),
                'total' => (float) $items->sum('total'),
                'periodLabel' => $filter['period_start']->translatedFormat('d M Y') . ' – ' . $filter['period_end']->translatedFormat('d M Y'),
            ])->render(),
        ]);
    }

    private function filter(Request $request): array
    {
        $now = Carbon::now();
        $start = $this->parseStartDate($request->input('start_date'), $now);
        $end = $this->parseEndDate($request->input('end_date'), $start, $now);

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [
            'year' => (int) $end->year,
            'month' => (int) $start->month,
            'period_start' => $start,
            'period_end' => $end,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'label' => $start->format('Y-m-d') . ' - ' . $end->format('Y-m-d'),
        ];
    }

    private function parseStartDate(?string $value, Carbon $now): Carbon
    {
        try {
            return $value ? Carbon::parse($value)->startOfDay() : $now->copy()->startOfMonth();
        } catch (\Throwable $e) {
            return $now->copy()->startOfMonth();
        }
    }

    private function parseEndDate(?string $value, Carbon $startDate, Carbon $now): Carbon
    {
        try {
            if (! $value) {
                return $now->copy();
            }

            $parsed = Carbon::parse($value)->endOfDay();

            if ($parsed->isSameMonth($now) && $parsed->year === $now->year && $parsed->greaterThan($now)) {
                return $now->copy();
            }

            return $parsed;
        } catch (\Throwable $e) {
            return $startDate->copy()->endOfMonth();
        }
    }
}
