<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\PengajuanGantiShift;
use App\Models\HRD\Shift;
use App\Models\HRD\EmployeeSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PengajuanGantiShiftController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $viewType = $request->input('view', 'personal');

        if ($request->ajax()) {
            // Employee: hanya data sendiri
            if (($viewType == 'personal' || empty($viewType)) && $user->hasRole('Employee')) {
                // Get requests created by this employee OR requests where this employee is the target
                $data = PengajuanGantiShift::where(function($query) use ($user) {
                        $query->where('employee_id', $user->employee->id)
                              ->orWhere('target_employee_id', $user->employee->id);
                    })
                    ->with(['employee', 'shiftLama', 'shiftBaru', 'targetEmployee'])
                    ->latest()
                    ->get();
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('tanggal_shift', function($row) {
                        return $row->tanggal_shift->format('d/m/Y');
                    })
                    ->addColumn('shift_lama', function($row) {
                        return $row->shiftLama ? $row->shiftLama->name . ' (' . $row->shiftLama->start_time . '-' . $row->shiftLama->end_time . ')' : '-';
                    })
                    ->addColumn('shift_baru', function($row) {
                        return $row->shiftBaru->name . ' (' . $row->shiftBaru->start_time . '-' . $row->shiftBaru->end_time . ')';
                    })
                    ->addColumn('jenis', function($row) use ($user) {
                        if ($row->is_tukar_shift) {
                            $targetName = $row->targetEmployee ? $row->targetEmployee->nama : 'N/A';
                            $isCurrentUserTarget = $row->target_employee_id == $user->employee->id;
                            $isCurrentUserRequester = $row->employee_id == $user->employee->id;
                            
                            if ($isCurrentUserTarget) {
                                return '<span class="badge badge-warning">Permintaan Tukar</span><br><small>dari: ' . $row->employee->nama . '</small>';
                            } else if ($isCurrentUserRequester) {
                                return '<span class="badge badge-info">Tukar Shift</span><br><small>dengan: ' . $targetName . '</small>';
                            }
                        }
                        return '<span class="badge badge-secondary">Ganti Shift</span>';
                    })
                    ->addColumn('status_manager', function($row) {
                        return $this->renderStatusBadge($row->status_manager);
                    })
                    ->addColumn('status_hrd', function($row) {
                        return $this->renderStatusBadge($row->status_hrd);
                    })
                    ->addColumn('status_target', function($row) {
                        if ($row->is_tukar_shift) {
                            return $this->renderStatusBadge($row->target_employee_approval_status);
                        }
                        return '<span class="badge badge-light">N/A</span>';
                    })
                    ->addColumn('action', function($row) use ($user) {
                        $buttons = '<button class="btn btn-info btn-sm btn-detail" data-id="'.$row->id.'">Detail</button>';
                        
                        // Add target employee approval button if current user is the target employee
                        if ($row->is_tukar_shift && $row->target_employee_id == $user->employee->id && $row->target_employee_approval_status === 'menunggu') {
                            $buttons .= ' <button class="btn btn-warning btn-sm btn-target-approve" data-id="'.$row->id.'">Persetujuan</button>';
                        }
                        
                        return $buttons;
                    })
                    ->rawColumns(['jenis', 'status_manager', 'status_hrd', 'status_target', 'action'])
                    ->make(true);
            }
            // Manager: data semua employee di divisinya (view=team)
            else if ($viewType == 'team' && $user->hasRole('Manager')) {
                $division = $user->employee->division;
                $employeeIds = $division ? $division->employees->pluck('id')->toArray() : [];
                $data = PengajuanGantiShift::whereIn('employee_id', $employeeIds)
                    ->with(['employee', 'shiftLama', 'shiftBaru', 'targetEmployee'])
                    ->latest()
                    ->get();
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('employee_name', function($row) {
                        return $row->employee->nama;
                    })
                    ->addColumn('tanggal_shift', function($row) {
                        return $row->tanggal_shift->format('d/m/Y');
                    })
                    ->addColumn('shift_lama', function($row) {
                        return $row->shiftLama ? $row->shiftLama->name . ' (' . $row->shiftLama->start_time . '-' . $row->shiftLama->end_time . ')' : '-';
                    })
                    ->addColumn('shift_baru', function($row) {
                        return $row->shiftBaru->name . ' (' . $row->shiftBaru->start_time . '-' . $row->shiftBaru->end_time . ')';
                    })
                    ->addColumn('jenis', function($row) {
                        if ($row->is_tukar_shift) {
                            return '<span class="badge badge-info">Tukar Shift</span><br><small>dengan: ' . ($row->targetEmployee ? $row->targetEmployee->nama : 'N/A') . '</small>';
                        }
                        return '<span class="badge badge-secondary">Ganti Shift</span>';
                    })
                    ->addColumn('status_manager', function($row) {
                        return $this->renderStatusBadge($row->status_manager);
                    })
                    ->addColumn('action', function($row) {
                        $buttons = '<button class="btn btn-info btn-sm btn-detail" data-id="'.$row->id.'">Detail</button>';
                        if ($row->status_manager == 'menunggu') {
                            $buttons .= ' <button class="btn btn-warning btn-sm btn-approve-manager" data-id="'.$row->id.'">Persetujuan</button>';
                        }
                        return $buttons;
                    })
                    ->rawColumns(['jenis', 'status_manager', 'action'])
                    ->make(true);
            }
            // HRD: semua data untuk approval (view=approval)
            else if ($viewType == 'approval' && $user->hasRole('Hrd')) {
                $data = PengajuanGantiShift::with(['employee', 'shiftLama', 'shiftBaru', 'targetEmployee'])
                    ->latest()
                    ->get();
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('employee_name', function($row) {
                        return $row->employee->nama;
                    })
                    ->addColumn('tanggal_shift', function($row) {
                        return $row->tanggal_shift->format('d/m/Y');
                    })
                    ->addColumn('shift_lama', function($row) {
                        return $row->shiftLama ? $row->shiftLama->name . ' (' . $row->shiftLama->start_time . '-' . $row->shiftLama->end_time . ')' : '-';
                    })
                    ->addColumn('shift_baru', function($row) {
                        return $row->shiftBaru->name . ' (' . $row->shiftBaru->start_time . '-' . $row->shiftBaru->end_time . ')';
                    })
                    ->addColumn('jenis', function($row) {
                        if ($row->is_tukar_shift) {
                            return '<span class="badge badge-info">Tukar Shift</span><br><small>dengan: ' . ($row->targetEmployee ? $row->targetEmployee->nama : 'N/A') . '</small>';
                        }
                        return '<span class="badge badge-secondary">Ganti Shift</span>';
                    })
                    ->addColumn('status_manager', function($row) {
                        return $this->renderStatusBadge($row->status_manager);
                    })
                    ->addColumn('status_hrd', function($row) {
                        return $this->renderStatusBadge($row->status_hrd);
                    })
                    ->addColumn('status_target', function($row) {
                        if ($row->is_tukar_shift) {
                            return $this->renderStatusBadge($row->target_employee_approval_status);
                        }
                        return '<span class="badge badge-light">N/A</span>';
                    })
                    ->addColumn('action', function($row) {
                        $buttons = '<button class="btn btn-info btn-sm btn-detail" data-id="'.$row->id.'">Detail</button>';
                        if ($row->status_manager == 'disetujui' && $row->status_hrd == 'menunggu') {
                            $buttons .= ' <button class="btn btn-warning btn-sm btn-approve-hrd" data-id="'.$row->id.'">Persetujuan HRD</button>';
                        }
                        return $buttons;
                    })
                    ->rawColumns(['jenis', 'status_manager', 'status_hrd', 'status_target', 'action'])
                    ->make(true);
            }
            // Default: data sendiri
            else {
                $data = PengajuanGantiShift::where('employee_id', $user->employee->id)
                    ->with(['shiftLama', 'shiftBaru'])
                    ->latest()
                    ->get();
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('tanggal_shift', function($row) {
                        return $row->tanggal_shift->format('d/m/Y');
                    })
                    ->addColumn('shift_lama', function($row) {
                        return $row->shiftLama ? $row->shiftLama->name . ' (' . $row->shiftLama->start_time . '-' . $row->shiftLama->end_time . ')' : '-';
                    })
                    ->addColumn('shift_baru', function($row) {
                        return $row->shiftBaru->name . ' (' . $row->shiftBaru->start_time . '-' . $row->shiftBaru->end_time . ')';
                    })
                    ->addColumn('status_manager', function($row) {
                        return $this->renderStatusBadge($row->status_manager);
                    })
                    ->addColumn('status_hrd', function($row) {
                        return $this->renderStatusBadge($row->status_hrd);
                    })
                    ->addColumn('action', function($row) {
                        return '<button class="btn btn-info btn-sm btn-detail" data-id="'.$row->id.'">Detail</button>';
                    })
                    ->rawColumns(['status_manager', 'status_hrd', 'action'])
                    ->make(true);
            }
        }

        // Non-AJAX: render view
        return view('hrd.gantishift.index', [
            'viewType' => $viewType
        ]);
    }

    private function renderStatusBadge($status)
    {
        if ($status === 'menunggu') {
            return '<span class="badge badge-warning">Menunggu</span>';
        } else if ($status === 'disetujui') {
            return '<span class="badge badge-success">Disetujui</span>';
        } else if ($status === 'ditolak') {
            return '<span class="badge badge-danger">Ditolak</span>';
        } else {
            return '<span class="badge badge-secondary">-</span>';
        }
    }

    public function create()
    {
        return view('hrd.gantishift.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'tanggal_shift' => 'required|date|after_or_equal:today',
            'shift_baru_id' => 'required|exists:hrd_shifts,id',
            'alasan' => 'required|string',
        ];

        // Add validation for tukar shift
        if ($request->filled('is_tukar_shift') && $request->is_tukar_shift) {
            $rules['target_employee_id'] = 'required|exists:hrd_employee,id';
        }

        $request->validate($rules);

        $employee = Auth::user()->employee;
        
        // Check if there's already a request for this date
        $existingRequest = PengajuanGantiShift::where('employee_id', $employee->id)
            ->where('tanggal_shift', $request->tanggal_shift)
            ->whereIn('status_manager', ['menunggu', 'disetujui'])
            ->first();

        if ($existingRequest) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Anda sudah memiliki pengajuan ganti shift untuk tanggal tersebut.'], 422);
            }
            return redirect()->back()->withErrors(['tanggal_shift' => 'Anda sudah memiliki pengajuan ganti shift untuk tanggal tersebut.']);
        }

        // Get current shift for this date if exists
        $currentSchedule = EmployeeSchedule::where('employee_id', $employee->id)
            ->where('date', $request->tanggal_shift)
            ->first();

        $data = [
            'employee_id' => $employee->id,
            'tanggal_shift' => $request->tanggal_shift,
            'shift_lama_id' => $currentSchedule ? $currentSchedule->shift_id : null,
            'shift_baru_id' => $request->shift_baru_id,
            'alasan' => $request->alasan,
        ];

        // Handle tukar shift
        if ($request->filled('is_tukar_shift') && $request->is_tukar_shift) {
            $data['is_tukar_shift'] = true;
            $data['target_employee_id'] = $request->target_employee_id;
            $data['target_employee_approval_status'] = 'menunggu';
        }

        $pengajuan = PengajuanGantiShift::create($data);

        if ($request->ajax()) {
            return response()->json(['data' => $pengajuan]);
        }
        
        $message = $request->filled('is_tukar_shift') && $request->is_tukar_shift 
            ? 'Pengajuan tukar shift berhasil diajukan.'
            : 'Pengajuan ganti shift berhasil diajukan.';
            
        return redirect()->route('hrd.gantishift.index')->with('success', $message);
    }

    public function persetujuanManager(Request $request, $id)
    {
        $request->validate([
            'komentar_manager' => 'nullable|string',
            'status' => 'required|in:disetujui,ditolak',
        ]);

        $pengajuan = PengajuanGantiShift::with(['employee', 'shiftBaru'])->findOrFail($id);
        
        // Update pengajuan status
        $pengajuan->update([
            'status_manager' => $request->status,
            'notes_manager' => $request->komentar_manager,
            'tanggal_persetujuan_manager' => now(),
        ]);

        // If both manager and HRD approved, update the actual schedule
        if ($request->status === 'disetujui' && $pengajuan->status_hrd === 'disetujui') {
            try {
                $scheduleUpdated = EmployeeSchedule::updateOrCreate(
                    [
                        'employee_id' => $pengajuan->employee_id,
                        'date' => $pengajuan->tanggal_shift,
                    ],
                    [
                        'shift_id' => $pengajuan->shift_baru_id,
                    ]
                );

                Log::info('Schedule automatically updated after Manager approval', [
                    'pengajuan_id' => $pengajuan->id,
                    'employee_id' => $pengajuan->employee_id,
                    'employee_name' => $pengajuan->employee->nama,
                    'date' => $pengajuan->tanggal_shift->format('Y-m-d'),
                    'new_shift_id' => $pengajuan->shift_baru_id,
                    'new_shift_name' => $pengajuan->shiftBaru->name,
                    'schedule_id' => $scheduleUpdated->id
                ]);

                $successMessage = 'Pengajuan ganti shift disetujui dan jadwal telah diperbarui otomatis.';
            } catch (\Exception $e) {
                Log::error('Failed to update schedule after Manager approval', [
                    'pengajuan_id' => $pengajuan->id,
                    'error' => $e->getMessage()
                ]);
                $successMessage = 'Pengajuan ganti shift disetujui, namun terjadi kesalahan saat memperbarui jadwal. Silakan perbarui jadwal secara manual.';
            }
        } else {
            $successMessage = $request->status === 'disetujui' 
                ? 'Pengajuan ganti shift disetujui oleh Manager. Menunggu persetujuan HRD.' 
                : 'Pengajuan ganti shift ditolak oleh Manager.';
        }

        if ($request->ajax()) {
            return response()->json([
                'data' => $pengajuan,
                'message' => $successMessage
            ]);
        }
        
        return redirect()->route('hrd.gantishift.index')->with('success', $successMessage);
    }

    public function persetujuanHRD(Request $request, $id)
    {
        $request->validate([
            'komentar_hrd' => 'nullable|string',
            'status' => 'required|in:disetujui,ditolak',
        ]);

        $pengajuan = PengajuanGantiShift::with(['employee', 'shiftBaru'])->findOrFail($id);
        
        // Update pengajuan status
        $pengajuan->update([
            'status_hrd' => $request->status,
            'notes_hrd' => $request->komentar_hrd,
            'tanggal_persetujuan_hrd' => now(),
        ]);

        // If both manager and HRD approved, update the actual schedule
        if ($request->status === 'disetujui' && $pengajuan->status_manager === 'disetujui') {
            try {
                if ($pengajuan->is_tukar_shift && $pengajuan->target_employee_approval_status === 'disetujui') {
                    // Perform schedule swap for tukar shift
                    $this->performScheduleSwap($pengajuan);
                    $successMessage = 'Pengajuan tukar shift disetujui dan jadwal telah ditukar otomatis.';
                } else if (!$pengajuan->is_tukar_shift) {
                    // Regular shift change
                    $scheduleUpdated = EmployeeSchedule::updateOrCreate(
                        [
                            'employee_id' => $pengajuan->employee_id,
                            'date' => $pengajuan->tanggal_shift,
                        ],
                        [
                            'shift_id' => $pengajuan->shift_baru_id,
                        ]
                    );

                    Log::info('Schedule automatically updated after HRD approval', [
                        'pengajuan_id' => $pengajuan->id,
                        'employee_id' => $pengajuan->employee_id,
                        'employee_name' => $pengajuan->employee->nama,
                        'date' => $pengajuan->tanggal_shift->format('Y-m-d'),
                        'new_shift_id' => $pengajuan->shift_baru_id,
                        'new_shift_name' => $pengajuan->shiftBaru->name,
                        'schedule_id' => $scheduleUpdated->id
                    ]);
                    
                    $successMessage = 'Pengajuan ganti shift disetujui dan jadwal telah diperbarui otomatis.';
                } else {
                    // Tukar shift but target employee hasn't approved yet
                    $successMessage = 'Pengajuan tukar shift disetujui HRD. Menunggu persetujuan dari karyawan tujuan.';
                }
            } catch (\Exception $e) {
                Log::error('Failed to update schedule after HRD approval', [
                    'pengajuan_id' => $pengajuan->id,
                    'error' => $e->getMessage()
                ]);
                $successMessage = 'Pengajuan ganti shift disetujui, namun terjadi kesalahan saat memperbarui jadwal. Silakan perbarui jadwal secara manual.';
            }
        } else {
            $successMessage = $request->status === 'disetujui' 
                ? 'Pengajuan ganti shift disetujui oleh HRD. Menunggu persetujuan manager.' 
                : 'Pengajuan ganti shift ditolak oleh HRD.';
        }

        if ($request->ajax()) {
            return response()->json([
                'data' => $pengajuan,
                'message' => $successMessage
            ]);
        }
        
        return redirect()->route('hrd.gantishift.index')->with('success', $successMessage);
    }

    public function show($id)
    {
        $pengajuan = PengajuanGantiShift::with(['employee', 'shiftLama', 'shiftBaru'])->findOrFail($id);
        
        if (request()->ajax()) {
            // Get current actual schedule to verify if it was updated
            $currentSchedule = EmployeeSchedule::where('employee_id', $pengajuan->employee_id)
                ->where('date', $pengajuan->tanggal_shift)
                ->with('shift')
                ->first();
                
            $scheduleInfo = null;
            if ($currentSchedule && $currentSchedule->shift) {
                $scheduleInfo = [
                    'current_shift_name' => $currentSchedule->shift->name,
                    'current_shift_time' => $currentSchedule->shift->start_time . '-' . $currentSchedule->shift->end_time,
                    'is_updated' => $currentSchedule->shift_id == $pengajuan->shift_baru_id
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $pengajuan->id,
                    'employee_name' => $pengajuan->employee->nama,
                    'tanggal_shift' => $pengajuan->tanggal_shift->format('d/m/Y'),
                    'shift_lama' => $pengajuan->shiftLama ? $pengajuan->shiftLama->name . ' (' . $pengajuan->shiftLama->start_time . '-' . $pengajuan->shiftLama->end_time . ')' : 'Tidak ada shift sebelumnya',
                    'shift_baru' => $pengajuan->shiftBaru->name . ' (' . $pengajuan->shiftBaru->start_time . '-' . $pengajuan->shiftBaru->end_time . ')',
                    'alasan' => $pengajuan->alasan,
                    'status_manager' => $pengajuan->status_manager,
                    'notes_manager' => $pengajuan->notes_manager,
                    'tanggal_persetujuan_manager' => $pengajuan->tanggal_persetujuan_manager ? $pengajuan->tanggal_persetujuan_manager->format('d/m/Y H:i') : null,
                    'status_hrd' => $pengajuan->status_hrd,
                    'notes_hrd' => $pengajuan->notes_hrd,
                    'tanggal_persetujuan_hrd' => $pengajuan->tanggal_persetujuan_hrd ? $pengajuan->tanggal_persetujuan_hrd->format('d/m/Y H:i') : null,
                    'schedule_info' => $scheduleInfo
                ]
            ]);
        }
        
        return view('hrd.gantishift.show', compact('pengajuan'));
    }

    public function getApprovalStatus($id)
    {
        $pengajuan = PengajuanGantiShift::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => [
                'status_manager' => $pengajuan->status_manager,
                'notes_manager' => $pengajuan->notes_manager,
                'status_hrd' => $pengajuan->status_hrd,
                'notes_hrd' => $pengajuan->notes_hrd,
            ]
        ]);
    }

    public function getAvailableShifts(Request $request)
    {
        try {
            $date = $request->input('date');
            $user = Auth::user();
            
            if (!$user || !$user->employee) {
                return response()->json([
                    'error' => 'Employee data not found'
                ], 404);
            }
            
            $employeeId = $user->employee->id;
            
            // Get all shifts
            $shifts = Shift::all();
            
            // Get current shift for this date if exists
            $currentSchedule = EmployeeSchedule::where('employee_id', $employeeId)
                ->where('date', $date)
                ->first();
            
            return response()->json([
                'shifts' => $shifts,
                'current_shift_id' => $currentSchedule ? $currentSchedule->shift_id : null
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getAvailableShifts: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load shift data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getEmployeesSameShift(Request $request)
    {
        try {
            $date = $request->input('date');
            $shiftId = $request->input('shift_id');
            $user = Auth::user();
            
            if (!$user || !$user->employee) {
                return response()->json(['error' => 'User not found'], 404);
            }
            
            $currentEmployeeId = $user->employee->id;
            
            // Get employees with the same shift on the same date (excluding current user)
            $schedules = EmployeeSchedule::where('date', $date)
                ->where('shift_id', $shiftId)
                ->where('employee_id', '!=', $currentEmployeeId)
                ->with(['employee.position'])
                ->get();
            
            $employees = $schedules->map(function ($schedule) {
                $position = $schedule->employee->position ? $schedule->employee->position->nama : '';
                return [
                    'id' => $schedule->employee_id,
                    'name' => $schedule->employee->nama,
                    'position' => $position
                ];
            });
            
            return response()->json([
                'success' => true,
                'employees' => $employees
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getEmployeesSameShift: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load employees: ' . $e->getMessage()
            ], 500);
        }
    }

    public function targetEmployeeApproval(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'notes' => 'nullable|string'
        ]);

        $user = Auth::user();
        if (!$user || !$user->employee) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $pengajuan = PengajuanGantiShift::with(['employee', 'targetEmployee'])->findOrFail($id);
        
        // Check if current user is the target employee
        if ($pengajuan->target_employee_id != $user->employee->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update target employee approval
        $pengajuan->update([
            'target_employee_approval_status' => $request->status,
            'target_employee_notes' => $request->notes,
            'target_employee_approval_date' => now(),
        ]);

        // Check if all approvals are complete and perform schedule swap
        if ($pengajuan->isFullyApproved()) {
            try {
                $this->performScheduleSwap($pengajuan);
                $successMessage = 'Pengajuan tukar shift disetujui dan jadwal telah ditukar otomatis.';
            } catch (\Exception $e) {
                Log::error('Error swapping schedules: ' . $e->getMessage());
                $successMessage = 'Pengajuan disetujui tetapi terjadi error saat menukar jadwal.';
            }
        } else {
            $successMessage = $request->status === 'disetujui' 
                ? 'Pengajuan tukar shift disetujui. Menunggu persetujuan HRD.'
                : 'Pengajuan tukar shift ditolak.';
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage
            ]);
        }

        return redirect()->route('hrd.gantishift.index')->with('success', $successMessage);
    }

    private function performScheduleSwap($pengajuan)
    {
        // Get both employees' current schedules for the date
        $requesterSchedule = EmployeeSchedule::where('employee_id', $pengajuan->employee_id)
            ->where('date', $pengajuan->tanggal_shift)
            ->first();
            
        $targetSchedule = EmployeeSchedule::where('employee_id', $pengajuan->target_employee_id)
            ->where('date', $pengajuan->tanggal_shift)
            ->first();

        if ($requesterSchedule && $targetSchedule) {
            // Swap the shifts
            $tempShiftId = $requesterSchedule->shift_id;
            $requesterSchedule->update(['shift_id' => $targetSchedule->shift_id]);
            $targetSchedule->update(['shift_id' => $tempShiftId]);
            
            Log::info("Schedule swapped successfully for date {$pengajuan->tanggal_shift}: Employee {$pengajuan->employee_id} and Employee {$pengajuan->target_employee_id}");
        }
    }
}
