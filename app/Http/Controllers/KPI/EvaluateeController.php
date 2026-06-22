<?php

namespace App\Http\Controllers\KPI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Models\KPI\KpiAssessment;
use App\Models\KPI\KpiPeriod;

class EvaluateeController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $employee = \App\Models\HRD\Employee::where('user_id', $userId)->first();
        if (! $employee) {
            return redirect()->back()->with('error', 'Akun ini belum terhubung ke data pegawai.');
        }

        $assessments = []; // data comes from AJAX
        $hasOpenPeriod = KpiPeriod::where('status', 'open')->exists();

        return view('kpi.evaluatees.index', compact('assessments', 'hasOpenPeriod'));
    }

    public function data(Request $request)
    {
        $userId = Auth::id();
        $employee = \App\Models\HRD\Employee::where('user_id', $userId)->first();
        if (! $employee) {
            return DataTables::of(collect([]))->make(true);
        }

        $query = KpiAssessment::with(['evaluateeEmployee', 'evaluateePosition', 'period'])
            ->where('evaluator_employee_id', $employee->id)
            ->where('status', 'pending')
            ->whereHas('period', function ($q) {
                $q->where('status', 'open');
            });

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('evaluatee', function ($r) {
                return optional($r->evaluateeEmployee)->nama ?? optional($r->evaluateeEmployee)->name ?? '-';
            })
            ->addColumn('position', function ($r) {
                return optional($r->evaluateePosition)->name ?? '-';
            })
            ->addColumn('categories', function ($r) {
                // prefer snapshot categories from scores when available
                $cats = \App\Models\KPI\KpiScore::where('assessment_id', $r->id)
                    ->pluck('ss_category_name')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (empty($cats)) {
                    $mappings = \App\Models\KPI\KpiPositionIndicator::where('position_id', $r->evaluatee_position_id)
                        ->with('indicator.category')
                        ->get();
                    $cats = $mappings->map(function ($m) {
                        return optional(optional($m->indicator)->category)->category_name;
                    })->filter()->unique()->values()->all();
                }

                return empty($cats) ? '-' : implode(', ', $cats);
            })
            ->addColumn('period', function ($r) {
                $p = $r->period;
                if (! $p) return '-';
                if (! empty($p->period_name)) return $p->period_name;
                try { return \DateTime::createFromFormat('!m', $p->month)->format('F') . ' ' . $p->year; } catch (\Exception $e) { return ($p->month ?? '-') . ' ' . ($p->year ?? ''); }
            })
            ->addColumn('action', function ($r) {
                $url = route('kpi.kpi_assessments.fill', $r->id);
                return '<button class="btn btn-sm btn-primary btn-fill-assessment" data-url="' . $url . '">Fill</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
