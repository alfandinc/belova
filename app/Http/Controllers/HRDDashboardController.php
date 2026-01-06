<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HRD\Employee;
use App\Models\HRD\PengajuanLibur;
use App\Models\HRD\PengajuanLembur;
use App\Models\HRD\PengajuanGantiShift;
use Carbon\Carbon;

class HRDDashboardController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::check() || !Auth::user()->hasAnyRole('Hrd', 'Ceo', 'Manager', 'Employee')) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        // Date range filter: default to this month through end of next month
        $defaultStart = Carbon::now()->startOfMonth();
        $defaultEnd = Carbon::now()->copy()->addMonthNoOverflow()->endOfMonth();
        $filterStart = $request->query('start') ? Carbon::parse($request->query('start'))->startOfDay() : $defaultStart->copy();
        $filterEnd = $request->query('end') ? Carbon::parse($request->query('end'))->endOfDay() : $defaultEnd->copy();

        // Counts (filtered by date range for pending items)
        $counts = [
            'employees' => Employee::count(),
            'pending_leaves' => PengajuanLibur::where(function($q){
                    $q->where('status_manager', 'pending')->orWhere('status_manager', 'menunggu')->orWhereNull('status_manager')
                      ->orWhere('status_hrd', 'pending')->orWhere('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                })
                ->whereDate('tanggal_mulai', '<=', $filterEnd)
                ->where(function($q) use ($filterStart){
                    $q->whereDate('tanggal_selesai', '>=', $filterStart)->orWhereNull('tanggal_selesai');
                })
                ->count(),
            'pending_overtime' => PengajuanLembur::where(function($q){
                    $q->where('status_manager', 'pending')->orWhere('status_manager', 'menunggu')->orWhereNull('status_manager')
                      ->orWhere('status_hrd', 'pending')->orWhere('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                })
                ->whereBetween('tanggal', [$filterStart, $filterEnd])
                ->count(),
            'pending_shifts' => PengajuanGantiShift::where(function($q){
                    $q->where('status_manager', 'pending')->orWhere('status_manager', 'menunggu')->orWhereNull('status_manager')
                      ->orWhere('status_hrd', 'pending')->orWhere('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                })
                ->whereBetween('tanggal_shift', [$filterStart, $filterEnd])
                ->count(),
        ];

        // Pending rows (short list)
        $lembur = PengajuanLembur::where(function($q){
                $q->where('status_manager', 'pending')->orWhere('status_manager', 'menunggu')->orWhereNull('status_manager')
                    ->orWhere('status_hrd', 'pending')->orWhere('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                })
                ->whereBetween('tanggal', [$filterStart, $filterEnd])
                ->orderByDesc('tanggal')
                ->take(5)->get();
        $libur = PengajuanLibur::where(function($q){
                $q->where('status_manager', 'pending')->orWhere('status_manager', 'menunggu')->orWhereNull('status_manager')
                    ->orWhere('status_hrd', 'pending')->orWhere('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                })
                // overlap with selected range: mulai <= end AND (selesai >= start OR selesai IS NULL)
                ->whereDate('tanggal_mulai', '<=', $filterEnd)
                ->where(function($q) use ($filterStart){
                    $q->whereDate('tanggal_selesai', '>=', $filterStart)->orWhereNull('tanggal_selesai');
                })
                ->orderByDesc('tanggal_mulai')
                ->take(5)->get();
        $ganti = PengajuanGantiShift::where(function($q){
                $q->where('status_manager', 'pending')->orWhere('status_manager', 'menunggu')->orWhereNull('status_manager')
                    ->orWhere('status_hrd', 'pending')->orWhere('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                })
                ->whereBetween('tanggal_shift', [$filterStart, $filterEnd])
                ->orderByDesc('tanggal_shift')
                ->take(5)->get();
        $pendingRows = collect()->concat([$lembur, $libur, $ganti])->flatten(1);

    // Upcoming birthdays (next 5)
    // Use today (midnight) so time-of-day won't make a birthday that occurs today appear as already passed
    $today = Carbon::today();
        $birthdays = Employee::whereNotNull('tanggal_lahir')
            ->get()
            ->map(function($e) use ($today) {
                $dob = Carbon::parse($e->tanggal_lahir);
                $next = $dob->copy()->year($today->year);
                if ($next->lt($today)) $next->addYear();
                $e->upcoming_days = (int) $today->diffInDays($next);
                $e->upcoming_date = $next;
                return $e;
            })
            ->sortBy('upcoming_days')
            ->take(5);

        // Full upcoming birthdays list (all employees with dob), sorted by next occurrence
        $allBirthdays = Employee::whereNotNull('tanggal_lahir')
            ->get()
            ->map(function($e) use ($today) {
                $dob = Carbon::parse($e->tanggal_lahir);
                $next = $dob->copy()->year($today->year);
                if ($next->lt($today)) $next->addYear();
                $e->upcoming_days = (int) $today->diffInDays($next);
                $e->upcoming_date = $next;
                return $e;
            })
            ->sortBy('upcoming_days')
            ->values();

        return view('hrd.dashboard', compact('counts', 'pendingRows', 'birthdays', 'allBirthdays', 'filterStart', 'filterEnd'));
    }

    // AJAX: pending approvals list filtered by date range, no page reload
    public function pendingApprovals(Request $request)
    {
        if (!Auth::check() || !Auth::user()->hasAnyRole('Hrd', 'Ceo', 'Manager', 'Employee', 'Admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $defaultStart = Carbon::now()->startOfMonth();
        $defaultEnd = Carbon::now()->copy()->addMonthNoOverflow()->endOfMonth();
        try {
            $filterStart = $request->query('start') ? Carbon::parse($request->query('start'))->startOfDay() : $defaultStart->copy();
        } catch (\Exception $e) { $filterStart = $defaultStart->copy(); }
        try {
            $filterEnd = $request->query('end') ? Carbon::parse($request->query('end'))->endOfDay() : $defaultEnd->copy();
        } catch (\Exception $e) { $filterEnd = $defaultEnd->copy(); }

        $lembur = PengajuanLembur::where(function($q){
                $q->where('status_manager', 'pending')->orWhere('status_manager', 'menunggu')->orWhereNull('status_manager')
                    ->orWhere('status_hrd', 'pending')->orWhere('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                })
                ->whereBetween('tanggal', [$filterStart, $filterEnd])
                ->orderByDesc('tanggal')
                ->take(5)->get();
        $libur = PengajuanLibur::where(function($q){
                $q->where('status_manager', 'pending')->orWhere('status_manager', 'menunggu')->orWhereNull('status_manager')
                    ->orWhere('status_hrd', 'pending')->orWhere('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                })
                ->whereDate('tanggal_mulai', '<=', $filterEnd)
                ->where(function($q) use ($filterStart){
                    $q->whereDate('tanggal_selesai', '>=', $filterStart)->orWhereNull('tanggal_selesai');
                })
                ->orderByDesc('tanggal_mulai')
                ->take(5)->get();
        $ganti = PengajuanGantiShift::where(function($q){
                $q->where('status_manager', 'pending')->orWhere('status_manager', 'menunggu')->orWhereNull('status_manager')
                    ->orWhere('status_hrd', 'pending')->orWhere('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                })
                ->whereBetween('tanggal_shift', [$filterStart, $filterEnd])
                ->orderByDesc('tanggal_shift')
                ->take(5)->get();

        $pendingRows = collect()->concat([$lembur, $libur, $ganti])->flatten(1);

        $data = $pendingRows->map(function($r){
            $emp = $r->employee ?? null;
            $empName = $emp && isset($emp->nama) ? $emp->nama : ($emp && $emp->user ? ($emp->user->name ?? 'Unknown') : 'Unknown');
            // Determine date label by available attributes
            $dateLabel = '-';
            if (isset($r->tanggal)) {
                try { $dateLabel = optional($r->tanggal)->format('Y-m-d'); } catch (\Exception $e) { $dateLabel = '-'; }
            } elseif (isset($r->tanggal_mulai)) {
                try {
                    $from = optional($r->tanggal_mulai)->format('Y-m-d');
                    $to = optional($r->tanggal_selesai)->format('Y-m-d');
                    $dateLabel = trim($from . ' - ' . $to);
                } catch (\Exception $e) { $dateLabel = '-'; }
            } elseif (isset($r->tanggal_shift)) {
                try { $dateLabel = optional($r->tanggal_shift)->format('Y-m-d'); } catch (\Exception $e) { $dateLabel = '-'; }
            }
            return [
                'type' => class_basename($r),
                'employee' => $empName,
                'date_label' => $dateLabel,
                'status_manager' => property_exists($r, 'status_manager') ? $r->status_manager : null,
                'status_hrd' => property_exists($r, 'status_hrd') ? $r->status_hrd : null,
                'avatar' => strtoupper(substr($empName, 0, 1)),
            ];
        })->values();

        // Also return filtered counts for summary cards
        $counts = [
            'employees' => Employee::count(),
            'pending_leaves' => PengajuanLibur::where(function($q){
                    $q->where('status_manager', 'pending')->orWhere('status_manager', 'menunggu')->orWhereNull('status_manager')
                      ->orWhere('status_hrd', 'pending')->orWhere('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                })
                ->whereDate('tanggal_mulai', '<=', $filterEnd)
                ->where(function($q) use ($filterStart){
                    $q->whereDate('tanggal_selesai', '>=', $filterStart)->orWhereNull('tanggal_selesai');
                })
                ->count(),
            'pending_overtime' => PengajuanLembur::where(function($q){
                    $q->where('status_manager', 'pending')->orWhere('status_manager', 'menunggu')->orWhereNull('status_manager')
                      ->orWhere('status_hrd', 'pending')->orWhere('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                })
                ->whereBetween('tanggal', [$filterStart, $filterEnd])
                ->count(),
            'pending_shifts' => PengajuanGantiShift::where(function($q){
                    $q->where('status_manager', 'pending')->orWhere('status_manager', 'menunggu')->orWhereNull('status_manager')
                      ->orWhere('status_hrd', 'pending')->orWhere('status_hrd', 'menunggu')->orWhereNull('status_hrd');
                })
                ->whereBetween('tanggal_shift', [$filterStart, $filterEnd])
                ->count(),
        ];

        return response()->json(['data' => $data, 'counts' => $counts]);
    }
}
