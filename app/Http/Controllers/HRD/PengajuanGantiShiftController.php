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
            // Manager: data semua employee di divisinya (view=team)
            else if ($viewType == 'team' && $user->hasRole('Manager')) {
                $division = $user->employee->division;
                $employeeIds = $division ? $division->employees->pluck('id')->toArray() : [];
                $data = PengajuanGantiShift::whereIn('employee_id', $employeeIds)
                    ->with(['employee', 'shiftLama', 'shiftBaru'])
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
                    ->rawColumns(['status_manager', 'action'])
                    ->make(true);
            }
            // HRD: semua data untuk approval (view=approval)
            else if ($viewType == 'approval' && $user->hasRole('Hrd')) {
                $data = PengajuanGantiShift::with(['employee', 'shiftLama', 'shiftBaru'])
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
                    ->addColumn('status_manager', function($row) {
                        return $this->renderStatusBadge($row->status_manager);
                    })
                    ->addColumn('status_hrd', function($row) {
                        return $this->renderStatusBadge($row->status_hrd);
                    })
                    ->addColumn('action', function($row) {
                        $buttons = '<button class="btn btn-info btn-sm btn-detail" data-id="'.$row->id.'">Detail</button>';
                        if ($row->status_manager == 'disetujui' && $row->status_hrd == 'menunggu') {
                            $buttons .= ' <button class="btn btn-warning btn-sm btn-approve-hrd" data-id="'.$row->id.'">Persetujuan HRD</button>';
                        }
                        return $buttons;
                    })
                    ->rawColumns(['status_manager', 'status_hrd', 'action'])
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
        $request->validate([
            'tanggal_shift' => 'required|date|after_or_equal:today',
            'shift_baru_id' => 'required|exists:hrd_shifts,id',
            'alasan' => 'required|string',
        ]);

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

        $pengajuan = PengajuanGantiShift::create([
            'employee_id' => $employee->id,
            'tanggal_shift' => $request->tanggal_shift,
            'shift_lama_id' => $currentSchedule ? $currentSchedule->shift_id : null,
            'shift_baru_id' => $request->shift_baru_id,
            'alasan' => $request->alasan,
        ]);

        if ($request->ajax()) {
            return response()->json(['data' => $pengajuan]);
        }
        return redirect()->route('hrd.gantishift.index')->with('success', 'Pengajuan ganti shift berhasil diajukan.');
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
}
