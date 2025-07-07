<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\PengajuanCuti;
use App\Models\HRD\SaldoCuti;
use App\Models\HRD\Employee;
use App\Models\HRD\JatahLibur;
use App\Models\HRD\PengajuanLibur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PengajuanLiburController extends Controller
{
    public function index(Request $request)
{
    $user = Auth::user();
    $viewType = $request->input('view', 'personal'); // Default to personal view

    // dd($user->getRoleNames()); // Debugging line to check user roles
    // For debugging role issues
    if ($request->ajax() && $request->has('debug_role')) {
        return response()->json([
            'roles' => $user->getRoleNames(),
            'is_manager' => $user->hasRole('Manager'),
            'is_employee' => $user->hasRole('Employee'),
            'is_hrd' => $user->hasRole('Hrd')
        ]);
    }
    
    if ($request->ajax()) {
        // For employee view - show their own requests
        if (($viewType == 'personal' || empty($viewType)) && $user->hasRole('Employee')) {
            $data = PengajuanLibur::where('employee_id', $user->employee->id)
                ->latest()
                ->get();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('jenis_libur', function($row) {
                    return $row->jenis_libur == 'cuti_tahunan' ? 'Cuti Tahunan' : 'Ganti Libur';
                })
                ->addColumn('tanggal_mulai', function($row) {
                    return $row->tanggal_mulai->format('d/m/Y');
                })
                ->addColumn('tanggal_selesai', function($row) {
                    return $row->tanggal_selesai->format('d/m/Y');
                })
                ->addColumn('status_manager', function($row) {
                    if ($row->status_manager == 'menunggu') {
                        return '<span class="badge badge-warning">Menunggu</span>';
                    } elseif ($row->status_manager == 'disetujui') {
                        return '<span class="badge badge-success">Disetujui</span>';
                    } else {
                        return '<span class="badge badge-danger">Ditolak</span>';
                    }
                })
                ->addColumn('status_hrd', function($row) {
                    if ($row->status_hrd == 'menunggu') {
                        return '<span class="badge badge-warning">Menunggu</span>';
                    } elseif ($row->status_hrd == 'disetujui') {
                        return '<span class="badge badge-success">Disetujui</span>';
                    } else {
                        return '<span class="badge badge-danger">Ditolak</span>';
                    }
                })
                ->addColumn('action', function($row) {
                    $btn = '<button type="button" class="btn btn-sm btn-info btn-detail" data-id="'.$row->id.'"><i class="fas fa-eye"></i></button>';
                    return $btn;
                })
                ->rawColumns(['status_manager', 'status_hrd', 'action'])
                ->make(true);
        } 
        // For manager team view - show team requests
        else if ($viewType == 'team' && $user->hasRole('Manager')) {
            $employee = $user->employee;
            if ($employee) {
                $division = $employee->division;
                if ($division) {
                    $teamEmployeeIds = $division->employees->pluck('id')->toArray();
                    // Show all requests from team, not just 'menunggu'
                    $data = PengajuanLibur::whereIn('employee_id', $teamEmployeeIds)
                        ->with('employee')
                        ->latest()
                        ->get();
                    
                    return DataTables::of($data)
                        ->addIndexColumn()
                        ->addColumn('jenis_libur', function($row) {
                            return $row->jenis_libur == 'cuti_tahunan' ? 'Cuti Tahunan' : 'Ganti Libur';
                        })
                        ->addColumn('tanggal_mulai', function($row) {
                            return $row->tanggal_mulai->format('d/m/Y');
                        })
                        ->addColumn('tanggal_selesai', function($row) {
                            return $row->tanggal_selesai->format('d/m/Y');
                        })
                        ->addColumn('action', function($row) {
                            $btn = '<button type="button" class="btn btn-sm btn-info btn-detail" data-id="'.$row->id.'"><i class="fas fa-eye"></i></button> ';
                            $btn .= '<button type="button" class="btn btn-sm btn-primary btn-approve-manager" data-id="'.$row->id.'"><i class="fas fa-check-circle"></i> Approval</button>';
                            return $btn;
                        })
                        ->addColumn('status_pengajuan', function($row) {
                            // Status logic: show who last approved/rejected and color
                            if ($row->status_hrd == 'disetujui') {
                                return '<span class="badge badge-success">Disetujui HRD</span>';
                            } elseif ($row->status_hrd == 'ditolak') {
                                return '<span class="badge badge-danger">Ditolak HRD</span>';
                            } elseif ($row->status_manager == 'disetujui') {
                                return '<span class="badge badge-warning">Disetujui Manager</span>';
                            } elseif ($row->status_manager == 'ditolak') {
                                return '<span class="badge badge-danger">Ditolak Manager</span>';
                            } else {
                                return '<span class="badge badge-secondary">Menunggu Persetujuan</span>';
                            }
                        })
                        ->rawColumns(['action', 'status_pengajuan'])
                        ->make(true);
                }
            }
            
            return DataTables::of([])->make(true);
        }
        // For manager personal view - show their own requests
        else if ($viewType == 'personal' && $user->hasRole('Manager')) {
            $data = PengajuanLibur::where('employee_id', $user->employee->id)
                ->latest()
                ->get();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('jenis_libur', function($row) {
                    return $row->jenis_libur == 'cuti_tahunan' ? 'Cuti Tahunan' : 'Ganti Libur';
                })
                ->addColumn('tanggal_mulai', function($row) {
                    return $row->tanggal_mulai->format('d/m/Y');
                })
                ->addColumn('tanggal_selesai', function($row) {
                    return $row->tanggal_selesai->format('d/m/Y');
                })
                ->addColumn('status_manager', function($row) {
                    if ($row->status_manager == 'menunggu') {
                        return '<span class="badge badge-warning">Menunggu</span>';
                    } elseif ($row->status_manager == 'disetujui') {
                        return '<span class="badge badge-success">Disetujui</span>';
                    } else {
                        return '<span class="badge badge-danger">Ditolak</span>';
                    }
                })
                ->addColumn('status_hrd', function($row) {
                    if ($row->status_hrd == 'menunggu') {
                        return '<span class="badge badge-warning">Menunggu</span>';
                    } elseif ($row->status_hrd == 'disetujui') {
                        return '<span class="badge badge-success">Disetujui</span>';
                    } else {
                        return '<span class="badge badge-danger">Ditolak</span>';
                    }
                })
                ->addColumn('action', function($row) {
                    $btn = '<button type="button" class="btn btn-sm btn-info btn-detail" data-id="'.$row->id.'"><i class="fas fa-eye"></i></button>';
                    return $btn;
                })
                ->rawColumns(['status_manager', 'status_hrd', 'action'])
                ->make(true);
        }
        // For HRD approval view
        else if (($viewType == 'approval' || empty($viewType)) && $user->hasRole('Hrd')) {
            // Show all requests with status_manager = 'disetujui', regardless of status_hrd
            $data = PengajuanLibur::where('status_manager', 'disetujui')
                ->with('employee')
                ->latest()
                ->get();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('jenis_libur', function($row) {
                    return $row->jenis_libur == 'cuti_tahunan' ? 'Cuti Tahunan' : 'Ganti Libur';
                })
                ->addColumn('tanggal_mulai', function($row) {
                    return $row->tanggal_mulai->format('d/m/Y');
                })
                ->addColumn('tanggal_selesai', function($row) {
                    return $row->tanggal_selesai->format('d/m/Y');
                })
                ->addColumn('action', function($row) {
                    $btn = '<button type="button" class="btn btn-sm btn-info btn-detail" data-id="'.$row->id.'"><i class="fas fa-eye"></i></button> ';
                    $btn .= '<button type="button" class="btn btn-sm btn-primary btn-approve-hrd" data-id="'.$row->id.'"><i class="fas fa-check-circle"></i> Approval</button>';
                    return $btn;
                })
                ->addColumn('status_pengajuan', function($row) {
                    // Status logic: show who last approved/rejected and color
                    if ($row->status_hrd == 'disetujui') {
                        return '<span class="badge badge-success">Disetujui HRD</span>';
                    } elseif ($row->status_hrd == 'ditolak') {
                        return '<span class="badge badge-danger">Ditolak HRD</span>';
                    } elseif ($row->status_manager == 'disetujui') {
                        return '<span class="badge badge-warning">Disetujui Manager</span>';
                    } elseif ($row->status_manager == 'ditolak') {
                        return '<span class="badge badge-danger">Ditolak Manager</span>';
                    } else {
                        return '<span class="badge badge-secondary">Menunggu Persetujuan</span>';
                    }
                })
                ->rawColumns(['action', 'status_pengajuan'])
                ->make(true);
        }
        
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    
    // For non-AJAX requests, gather necessary data based on role and view type
    $pengajuanLibur = null;
    $jatahLibur = null;
    
    if ($user->hasRole('Employee')) {
        $employee = $user->employee;
        if ($employee) {
            $jatahLibur = $employee->ensureJatahLibur();
        }
    } 
    elseif ($user->hasRole('Manager')) {
        if ($viewType == 'team') {
            $employee = $user->employee;
            if ($employee && $employee->division) {
                $teamEmployeeIds = $employee->division->employees->pluck('id')->toArray();
                $pengajuanLibur = PengajuanLibur::whereIn('employee_id', $teamEmployeeIds)
                    ->where('status_manager', 'menunggu')
                    ->with('employee')
                    ->count();
            }
        } else {
            $employee = $user->employee;
            if ($employee) {
                $jatahLibur = $employee->ensureJatahLibur();
            }
        }
    } 
    elseif ($user->hasRole('Hrd')) {
        $pengajuanLibur = PengajuanLibur::where('status_manager', 'disetujui')
            ->where('status_hrd', 'menunggu')
            ->with('employee')
            ->count();
    }
    
    // Determine which view to render based on user role and view type
    if ($user->hasRole('Manager') && $viewType == 'team') {
        return view('hrd.libur.index', [
            'viewType' => 'team',
            'pengajuanLibur' => $pengajuanLibur,
            'jatahLibur' => $jatahLibur
        ]);
    } 
    elseif ($user->hasRole('Hrd') && $viewType == 'approval') {
        return view('hrd.libur.index', [
            'viewType' => 'approval',
            'pengajuanLibur' => $pengajuanLibur,
            'jatahLibur' => $jatahLibur
        ]);
    } 
    else {
        return view('hrd.libur.index', [
            'viewType' => 'personal',
            'pengajuanLibur' => $pengajuanLibur,
            'jatahLibur' => $jatahLibur
        ]);
    }
}

    public function create()
    {
        $employee = Auth::user()->employee;
        $jatahLibur = $employee->ensureJatahLibur();

        return view('hrd.libur.create', compact('jatahLibur'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis_libur' => 'required|in:cuti_tahunan,ganti_libur',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string',
        ]);

        // Parse dates with Carbon to ensure consistent handling
        $tanggalMulai = Carbon::parse($request->tanggal_mulai)->startOfDay();
        $tanggalSelesai = Carbon::parse($request->tanggal_selesai)->startOfDay();

        // Calculate days by counting dates between start and end (inclusive)
        // Manual calculation to ensure accuracy:
        // 1. Get all days between the two dates
        // 2. Count them + 1 to include the start date
        $startTimestamp = $tanggalMulai->getTimestamp();
        $endTimestamp = $tanggalSelesai->getTimestamp();
        $totalHari = (int)round(($endTimestamp - $startTimestamp) / 86400) + 1;
        
        // Force positive value (absolute) to prevent negative days
        $totalHari = abs($totalHari);
        
        // Safety check - ensure at least 1 day
        if ($totalHari < 1) {
            $totalHari = 1;
        }
        
        // Add debug logging to ensure calculation is correct
        Log::info('PengajuanLibur - Day Calculation', [
            'tanggal_mulai' => $tanggalMulai->toDateString(),
            'tanggal_selesai' => $tanggalSelesai->toDateString(),
            'start_timestamp' => $startTimestamp,
            'end_timestamp' => $endTimestamp,
            'diff_seconds' => $endTimestamp - $startTimestamp,
            'diff_days_raw' => ($endTimestamp - $startTimestamp) / 86400,
            'total_hari' => $totalHari
        ]);

        $employee = Auth::user()->employee;
        $jatahLibur = $employee->ensureJatahLibur();

        // Periksa apakah karyawan memiliki jatah libur yang cukup
        if ($request->jenis_libur == 'cuti_tahunan' && ($jatahLibur->jatah_cuti_tahunan < $totalHari)) {
            return redirect()->back()->with('error', 'Jatah cuti tahunan Anda tidak mencukupi.');
        }

        if ($request->jenis_libur == 'ganti_libur' && ($jatahLibur->jatah_ganti_libur < $totalHari)) {
            return redirect()->back()->with('error', 'Jatah ganti libur Anda tidak mencukupi.');
        }

        $pengajuan = PengajuanLibur::create([
            'employee_id' => $employee->id,
            'jenis_libur' => $request->jenis_libur,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'total_hari' => $totalHari,
            'alasan' => $request->alasan,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan libur berhasil diajukan',
                'data' => $pengajuan
            ]);
        }

        return redirect()->route('hrd.libur.index')->with('success', 'Pengajuan libur berhasil diajukan.');
    }

    public function persetujuanManager(Request $request, $id)
    {
        $request->validate([
            'komentar_manager' => 'nullable|string',
            'status' => 'required|in:disetujui,ditolak',
        ]);

        $pengajuanLibur = PengajuanLibur::findOrFail($id);

        $pengajuanLibur->update([
            'status_manager' => $request->status,
            'notes_manager' => $request->komentar_manager,
            'tanggal_persetujuan_manager' => now(),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan libur berhasil diperbarui',
                'data' => $pengajuanLibur
            ]);
        }

        return redirect()->route('hrd.libur.index')->with('success', 'Pengajuan libur berhasil diperbarui.');
    }

    public function persetujuanHRD(Request $request, $id)
    {
        $request->validate([
            'komentar_hrd' => 'nullable|string',
            'status' => 'required|in:disetujui,ditolak',
        ]);

        $pengajuanLibur = PengajuanLibur::findOrFail($id);

        $pengajuanLibur->update([
            'status_hrd' => $request->status,
            'notes_hrd' => $request->komentar_hrd,
            'tanggal_persetujuan_hrd' => now(),
        ]);

        // Jika disetujui oleh HRD, kurangi jatah libur
        if ($request->status == 'disetujui') {
            $employee = $pengajuanLibur->employee;
            $jatahLibur = $employee->jatahLibur;

            if ($pengajuanLibur->jenis_libur == 'cuti_tahunan') {
                $jatahLibur->jatah_cuti_tahunan -= $pengajuanLibur->total_hari;
            } else {
                $jatahLibur->jatah_ganti_libur -= $pengajuanLibur->total_hari;
            }

            $jatahLibur->save();
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan libur berhasil diperbarui',
                'data' => $pengajuanLibur
            ]);
        }

        return redirect()->route('hrd.libur.index')->with('success', 'Pengajuan libur berhasil diperbarui.');
    }

    public function show($id)
    {
        $pengajuanLibur = PengajuanLibur::findOrFail($id);
        return view('hrd.libur.show', compact('pengajuanLibur'));
    }
    
    public function getApprovalStatus($id)
    {
        $pengajuanLibur = PengajuanLibur::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => [
                'status_manager' => $pengajuanLibur->status_manager,
                'komentar_manager' => $pengajuanLibur->notes_manager,
                'status_hrd' => $pengajuanLibur->status_hrd,
                'komentar_hrd' => $pengajuanLibur->notes_hrd,
            ]
        ]);
    }
}