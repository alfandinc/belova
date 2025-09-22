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
    public function index()
    {
        if (!Auth::check() || !Auth::user()->hasAnyRole('Hrd', 'Ceo', 'Manager', 'Employee')) {
            return redirect('/')->with('error', 'Unauthorized access.');
        }

        // Counts
        $counts = [
            'employees' => Employee::count(),
            'pending_leaves' => PengajuanLibur::where('status_manager', 'pending')->orWhere('status_hrd', 'pending')->count(),
            'pending_overtime' => PengajuanLembur::where('status_manager', 'pending')->orWhere('status_hrd', 'pending')->count(),
            'pending_shifts' => PengajuanGantiShift::where('status_manager', 'pending')->orWhere('status_hrd', 'pending')->count(),
        ];

        // Pending rows (short list)
        $lembur = PengajuanLembur::where('status_manager', 'pending')->orWhere('status_hrd', 'pending')->take(5)->get();
        $libur = PengajuanLibur::where('status_manager', 'pending')->orWhere('status_hrd', 'pending')->take(5)->get();
        $ganti = PengajuanGantiShift::where('status_manager', 'pending')->orWhere('status_hrd', 'pending')->take(5)->get();
        $pendingRows = collect()->concat([$lembur, $libur, $ganti])->flatten(1);

        // Upcoming birthdays (next 5)
        $today = Carbon::now();
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

        return view('hrd.dashboard', compact('counts', 'pendingRows', 'birthdays'));
    }
}
