<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Belova\NgajiNilai;
use App\Models\HRD\Employee;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\NgajiPerDateExport;

class BelovaMengajiController extends Controller
{
    public function __construct()
    {
        // ensure user is authenticated and only allow Admin or Ustad roles
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!$user) {
                abort(403);
            }
            $roles = $user->roles->pluck('name')->toArray();
            if (!in_array('Admin', $roles) && !in_array('Ustad', $roles)) {
                abort(403, 'Unauthorized. This module is for Ustad and Admin only.');
            }
            return $next($request);
        });
    }
    /**
     * Display a listing of ngaji nilai.
     */
    public function index(Request $request)
    {
        $query = NgajiNilai::with('employee')->orderBy('date', 'desc');

        // Filter by search query
        if ($search = $request->get('q')) {
            $query->where(function($q) use ($search) {
                $q->where('test', 'like', '%'.$search.'%')
                  ->orWhere('catatan', 'like', '%'.$search.'%');
            });
        }

        // Filter by employee id if provided (when clicking from employees table)
        if ($employeeId = $request->get('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        $records = $query->paginate(25)->appends($request->except('page'));

        return view('belova_mengaji.index', compact('records'));
    }

    /**
     * Return employees list for DataTables (AJAX)
     */
    public function employeesData(Request $request)
    {
        $date = $request->get('date') ?: date('Y-m-d');

        $employees = Employee::with(['position', 'division'])->select('id', 'nama', 'no_induk', 'no_hp', 'email', 'position_id', 'division_id');

        // fetch existing ngaji nilai for the given date
        $ngaji = \App\Models\Belova\NgajiNilai::where('date', $date)->get()->keyBy('employee_id');

        return DataTables::of($employees)->addColumn('position', function($e) { return optional($e->position)->nama ?? '-'; })
            ->addColumn('division', function($e) { return optional($e->division)->nama ?? '-'; })
            ->addColumn('nilai_makhroj', function($e) use ($ngaji, $date) {
                $val = isset($ngaji[$e->id]) ? $ngaji[$e->id]->nilai_makhroj : '';
                $html = '<select class="form-control ngaji-select" data-employee="'.$e->id.'" data-field="nilai_makhroj" data-date="'.$date.'">';
                $html .= '<option value="">-</option>';
                for ($i = 1; $i <= 5; $i++) {
                    $selected = ($val !== '' && floatval($val) == $i) ? ' selected' : '';
                    $html .= '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
                }
                $html .= '</select>';
                return $html;
            })
            ->addColumn('nilai_tajwid', function($e) use ($ngaji, $date) {
                $val = isset($ngaji[$e->id]) ? $ngaji[$e->id]->nilai_tajwid : '';
                $html = '<select class="form-control ngaji-select" data-employee="'.$e->id.'" data-field="nilai_tajwid" data-date="'.$date.'">';
                $html .= '<option value="">-</option>';
                for ($i = 1; $i <= 5; $i++) {
                    $selected = ($val !== '' && floatval($val) == $i) ? ' selected' : '';
                    $html .= '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
                }
                $html .= '</select>';
                return $html;
            })
            ->addColumn('nilai_panjang_pendek', function($e) use ($ngaji, $date) {
                $val = isset($ngaji[$e->id]) ? $ngaji[$e->id]->nilai_panjang_pendek : '';
                $html = '<select class="form-control ngaji-select" data-employee="'.$e->id.'" data-field="nilai_panjang_pendek" data-date="'.$date.'">';
                $html .= '<option value="">-</option>';
                for ($i = 1; $i <= 5; $i++) {
                    $selected = ($val !== '' && floatval($val) == $i) ? ' selected' : '';
                    $html .= '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
                }
                $html .= '</select>';
                return $html;
            })
            ->addColumn('nilai_kelancaran', function($e) use ($ngaji, $date) {
                $val = isset($ngaji[$e->id]) ? $ngaji[$e->id]->nilai_kelancaran : '';
                $html = '<select class="form-control ngaji-select" data-employee="'.$e->id.'" data-field="nilai_kelancaran" data-date="'.$date.'">';
                $html .= '<option value="">-</option>';
                for ($i = 1; $i <= 5; $i++) {
                    $selected = ($val !== '' && floatval($val) == $i) ? ' selected' : '';
                    $html .= '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
                }
                $html .= '</select>';
                return $html;
            })
            ->addColumn('total_nilai', function($e) use ($ngaji) {
                if (isset($ngaji[$e->id])) {
                    return $ngaji[$e->id]->total_nilai;
                }
                return '';
            })
            ->addColumn('catatan', function($e) use ($ngaji, $date) {
                $val = isset($ngaji[$e->id]) ? $ngaji[$e->id]->catatan : '';
                return '<input class="form-control ngaji-catatan" data-employee="'.$e->id.'" data-date="'.$date.'" value="'.htmlspecialchars($val).'" type="text">';
            })
            ->addColumn('riwayat', function($e) {
                return '<button class="btn btn-sm btn-outline-primary riwayat-btn" data-employee="'.$e->id.'">Riwayat</button>';
            })
            ->rawColumns(['nilai_makhroj','nilai_tajwid','nilai_panjang_pendek','nilai_kelancaran','catatan','riwayat'])
            ->make(true);
    }

    /**
     * Return history (riwayat) of ngaji nilai for an employee (JSON)
     */
    public function history(Request $request)
    {
        $employeeId = $request->get('employee_id');
        if (!$employeeId) {
            return response()->json(['ok' => false, 'message' => 'employee_id required'], 400);
        }

        $records = NgajiNilai::where('employee_id', $employeeId)
            ->orderBy('date', 'desc')
            ->get(['date','test','nilai_makhroj','nilai_tajwid','nilai_panjang_pendek','nilai_kelancaran','total_nilai','catatan']);

        // compute averages and count for this employee
        $avgRow = NgajiNilai::where('employee_id', $employeeId)
            ->select(DB::raw('AVG(nilai_makhroj) as avg_makhroj, AVG(nilai_tajwid) as avg_tajwid, AVG(nilai_panjang_pendek) as avg_panjang, AVG(nilai_kelancaran) as avg_kelancaran, AVG(total_nilai) as avg_total, COUNT(*) as cnt'))
            ->first();

        $meta = [
            'avg_makhroj' => $avgRow->avg_makhroj !== null ? (float) $avgRow->avg_makhroj : null,
            'avg_tajwid' => $avgRow->avg_tajwid !== null ? (float) $avgRow->avg_tajwid : null,
            'avg_panjang' => $avgRow->avg_panjang !== null ? (float) $avgRow->avg_panjang : null,
            'avg_kelancaran' => $avgRow->avg_kelancaran !== null ? (float) $avgRow->avg_kelancaran : null,
            'avg_total' => $avgRow->avg_total !== null ? (float) $avgRow->avg_total : null,
            'count' => (int) ($avgRow->cnt ?? 0),
        ];

        return response()->json(['ok' => true, 'data' => $records, 'meta' => $meta]);
    }

    /**
     * Store or update ngaji nilai for an employee/date (AJAX)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|integer|exists:hrd_employee,id',
            'date' => 'required|date',
            'nilai_makhroj' => 'nullable|integer|min:1|max:5',
            'nilai_tajwid' => 'nullable|integer|min:1|max:5',
            'nilai_panjang_pendek' => 'nullable|integer|min:1|max:5',
            'nilai_kelancaran' => 'nullable|integer|min:1|max:5',
            'catatan' => 'nullable|string',
            'test' => 'nullable|string'
        ]);

        // compute total
        $total = 0;
        foreach (['nilai_makhroj','nilai_tajwid','nilai_panjang_pendek','nilai_kelancaran'] as $f) {
            $total += isset($data[$f]) && $data[$f] !== '' ? floatval($data[$f]) : 0;
        }
        $data['total_nilai'] = $total;

        // Upsert by employee_id + date (assumes one record per employee per date)
        $record = NgajiNilai::updateOrCreate(
            [ 'employee_id' => $data['employee_id'], 'date' => $data['date'] ],
            [ 'test' => $data['test'] ?? null,
              'nilai_makhroj' => $data['nilai_makhroj'] ?? null,
              'nilai_tajwid' => $data['nilai_tajwid'] ?? null,
              'nilai_panjang_pendek' => $data['nilai_panjang_pendek'] ?? null,
              'nilai_kelancaran' => $data['nilai_kelancaran'] ?? null,
              'total_nilai' => $data['total_nilai'],
              'catatan' => $data['catatan'] ?? null
            ]
        );

        return response()->json(['ok' => true, 'data' => $record]);
    }

    /**
     * Show analytics for ngaji nilai over a date range
     */
    public function analytics(Request $request)
    {
        $from = $request->get('from') ?: date('Y-m-d', strtotime('-30 days'));
        $to = $request->get('to') ?: date('Y-m-d');

        // basic aggregates
        $avg_total = NgajiNilai::whereBetween('date', [$from, $to])->avg('total_nilai');
        $avg_makhroj = NgajiNilai::whereBetween('date', [$from, $to])->avg('nilai_makhroj');
        $avg_tajwid = NgajiNilai::whereBetween('date', [$from, $to])->avg('nilai_tajwid');
        $avg_panjang = NgajiNilai::whereBetween('date', [$from, $to])->avg('nilai_panjang_pendek');
        $avg_kelancaran = NgajiNilai::whereBetween('date', [$from, $to])->avg('nilai_kelancaran');

        $total_employees = \App\Models\HRD\Employee::count();

        // presence = number of distinct employees who have at least one record in range
        $present_employees = NgajiNilai::whereBetween('date', [$from, $to])->distinct('employee_id')->count('employee_id');
        $absent_employees = max(0, $total_employees - $present_employees);

        // top active (most records) employees in range
        $top_active = NgajiNilai::select('employee_id', DB::raw('count(*) as cnt'))
            ->whereBetween('date', [$from, $to])
            ->groupBy('employee_id')
            ->orderByDesc('cnt')
            ->with('employee')
            ->take(10)
            ->get();

        // per-employee average total (small sample: top 10 by average)
        $per_employee_avg = NgajiNilai::select('employee_id', DB::raw('AVG(total_nilai) as avg_total'), DB::raw('COUNT(*) as cnt'))
            ->whereBetween('date', [$from, $to])
            ->groupBy('employee_id')
            ->orderByDesc('avg_total')
            ->with('employee')
            ->take(10)
            ->get();

        return view('belova_mengaji.analytics', compact(
            'from','to','avg_total','avg_makhroj','avg_tajwid','avg_panjang','avg_kelancaran',
            'total_employees','present_employees','absent_employees','top_active','per_employee_avg'
        ));
    }

    /**
     * Return analytics data as JSON for AJAX requests
     */
    public function analyticsData(Request $request)
    {
        $from = $request->get('from') ?: date('Y-m-d', strtotime('-30 days'));
        $to = $request->get('to') ?: date('Y-m-d');

        $avg_total = NgajiNilai::whereBetween('date', [$from, $to])->avg('total_nilai');
        $avg_makhroj = NgajiNilai::whereBetween('date', [$from, $to])->avg('nilai_makhroj');
        $avg_tajwid = NgajiNilai::whereBetween('date', [$from, $to])->avg('nilai_tajwid');
        $avg_panjang = NgajiNilai::whereBetween('date', [$from, $to])->avg('nilai_panjang_pendek');
        $avg_kelancaran = NgajiNilai::whereBetween('date', [$from, $to])->avg('nilai_kelancaran');

        $total_employees = \App\Models\HRD\Employee::count();
        $present_employees = NgajiNilai::whereBetween('date', [$from, $to])->distinct('employee_id')->count('employee_id');
        $absent_employees = max(0, $total_employees - $present_employees);

        // top active
        $top_active_raw = NgajiNilai::select('employee_id', DB::raw('count(*) as cnt'))
            ->whereBetween('date', [$from, $to])
            ->groupBy('employee_id')
            ->orderByDesc('cnt')
            ->take(10)
            ->get();

        $employeeIds = $top_active_raw->pluck('employee_id')->unique()->toArray();
        $names = \App\Models\HRD\Employee::whereIn('id', $employeeIds)->pluck('nama', 'id');

        $top_active = $top_active_raw->map(function($r) use ($names) {
            return [
                'employee_id' => $r->employee_id,
                'nama' => $names[$r->employee_id] ?? null,
                'cnt' => $r->cnt
            ];
        });

        // per employee avg total
        $per_employee_raw = NgajiNilai::select('employee_id', DB::raw('AVG(total_nilai) as avg_total'), DB::raw('COUNT(*) as cnt'))
            ->whereBetween('date', [$from, $to])
            ->groupBy('employee_id')
            ->orderByDesc('avg_total')
            ->take(10)
            ->get();

        $employeeIds2 = $per_employee_raw->pluck('employee_id')->unique()->toArray();
        $names2 = \App\Models\HRD\Employee::whereIn('id', $employeeIds2)->pluck('nama', 'id');

        $per_employee_avg = $per_employee_raw->map(function($r) use ($names2) {
            return [
                'employee_id' => $r->employee_id,
                'nama' => $names2[$r->employee_id] ?? null,
                'avg_total' => (float) $r->avg_total,
                'cnt' => $r->cnt
            ];
        });

        return response()->json([
            'from' => $from,
            'to' => $to,
            'avg_total' => $avg_total,
            'avg_makhroj' => $avg_makhroj,
            'avg_tajwid' => $avg_tajwid,
            'avg_panjang' => $avg_panjang,
            'avg_kelancaran' => $avg_kelancaran,
            'total_employees' => $total_employees,
            'present_employees' => $present_employees,
            'absent_employees' => $absent_employees,
            'top_active' => $top_active,
            'per_employee_avg' => $per_employee_avg,
        ]);
    }

    /**
     * Export ngaji nilai for a specific date as PDF
     */
    public function exportPdf(Request $request)
    {
        $date = $request->get('date') ?: date('Y-m-d');
        $rows = NgajiNilai::with('employee')
            ->where('date', $date)
            ->orderBy('employee_id')
            ->get();

        $data = [
            'rows' => $rows,
            'date' => $date,
        ];

        // use barryvdh/laravel-dompdf (installed in composer.json)
        $pdf = Pdf::loadView('belova_mengaji.export_pdf', $data)->setPaper('a4', 'landscape');
        return $pdf->download('belova_mengaji_'.$date.'.pdf');
    }

    /**
     * Export ngaji nilai for a specific date as Excel
     */
    public function exportExcel(Request $request)
    {
        $date = $request->get('date') ?: date('Y-m-d');
        return Excel::download(new NgajiPerDateExport($date), 'belova_mengaji_'.$date.'.xlsx');
    }
}
