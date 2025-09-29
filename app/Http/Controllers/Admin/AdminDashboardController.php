<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    // Returns JSON data for last 7 days of user registrations
    public function activityData(Request $request)
    {
        if (!class_exists('App\\Models\\User')) {
            return response()->json(['labels' => [], 'data' => []]);
        }

        // Build date range (last 7 days)
        $end = Carbon::today()->endOfDay();
        $start = Carbon::today()->subDays(6)->startOfDay();

        // Query DB grouped by date to avoid timezone/counting issues
        $rows = DB::table('users')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $labels = [];
        $counts = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $dateKey = $cursor->toDateString();
            $labels[] = $cursor->format('D');
            $counts[] = isset($rows[$dateKey]) ? (int) $rows[$dateKey] : 0;
            $cursor->addDay();
        }

        return response()->json(['labels' => $labels, 'data' => $counts]);
    }

    // Admin dashboard page
    public function index()
    {
        $usersCount = class_exists('App\\Models\\User') ? forward_static_call(['App\\Models\\User', 'count']) : null;

        // Try several fallbacks to count roles: Spatie Role model, 'roles' table, model_has_roles pivot, or role_user pivot
        $rolesCount = null;
        if (class_exists('Spatie\\Permission\\Models\\Role')) {
            $rolesCount = forward_static_call(['Spatie\\Permission\\Models\\Role', 'count']);
        } elseif (\Illuminate\Support\Facades\Schema::hasTable('roles')) {
            $rolesCount = \Illuminate\Support\Facades\DB::table('roles')->count();
        } elseif (\Illuminate\Support\Facades\Schema::hasTable('model_has_roles')) {
            $rolesCount = \Illuminate\Support\Facades\DB::table('model_has_roles')->distinct('role_id')->count('role_id');
        } elseif (\Illuminate\Support\Facades\Schema::hasTable('role_user')) {
            $rolesCount = \Illuminate\Support\Facades\DB::table('role_user')->distinct('role_id')->count('role_id');
        } else {
            $rolesCount = 0;
        }

        return view('admin.dashboard', compact('usersCount','rolesCount'));
    }
}
