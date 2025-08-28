<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\PengajuanLembur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class PengajuanLemburController extends Controller
{
    public function index(Request $request)
    {
        $user = \App\Models\User::find(Auth::id());
        $viewType = $request->input('view', 'personal');

        if ($request->ajax()) {
            $user = \App\Models\User::find(Auth::id());
            // HRD/Admin: all data
            if ($user->hasRole('Hrd') || $user->hasRole('Admin')) {
                $data = PengajuanLembur::with('employee')->latest()->get();
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('employee_nama', function($row) {
                        return $row->employee->nama ?? '-';
                    })
                    ->addColumn('tanggal', function($row) {
                        return $row->tanggal->format('d/m/Y');
                    })
                    ->addColumn('jam_mulai', function($row) {
                        return $row->jam_mulai;
                    })
                    ->addColumn('jam_selesai', function($row) {
                        return $row->jam_selesai;
                    })
                    ->addColumn('total_jam', function($row) {
                        return $row->total_jam_formatted;
                    })
                    ->addColumn('status_manager', function($row) {
                        return $row->status_manager ?? '-';
                    })
                    ->addColumn('status_hrd', function($row) {
                        return $row->status_hrd ?? '-';
                    })
                    ->addColumn('action', function($row) use ($user) {
                        $btn = '<button class="btn btn-info btn-detail-lembur" data-id="'.$row->id.'">Detail</button> ';
                        if ($user->hasRole('Manager')) {
                            $btn .= '<button class="btn btn-primary btn-approve-manager-lembur" data-id="'.$row->id.'"><i class="fas fa-check-circle"></i> Approval</button>';
                        }
                        if ($user->hasRole('Hrd') || $user->hasRole('Admin')) {
                            $btn .= '<button class="btn btn-primary btn-approve-hrd-lembur" data-id="'.$row->id.'"><i class="fas fa-check-circle"></i> Approval HRD</button>';
                        }
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }
            // Manager: own + division
            else if ($user->hasRole('Manager')) {
                $division = $user->employee->division;
                $employeeIds = $division ? $division->employees->pluck('id')->toArray() : [];
                $employeeIds[] = $user->employee->id; // include self
                $data = PengajuanLembur::whereIn('employee_id', $employeeIds)
                    ->with('employee')
                    ->latest()
                    ->get();
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('employee_nama', function($row) {
                        return $row->employee->nama ?? '-';
                    })
                    ->addColumn('tanggal', function($row) {
                        return $row->tanggal->format('d/m/Y');
                    })
                    ->addColumn('jam_mulai', function($row) {
                        return $row->jam_mulai;
                    })
                    ->addColumn('jam_selesai', function($row) {
                        return $row->jam_selesai;
                    })
                    ->addColumn('total_jam', function($row) {
                        return $row->total_jam_formatted;
                    })
                    ->addColumn('status_manager', function($row) {
                        return $row->status_manager ?? '-';
                    })
                    ->addColumn('status_hrd', function($row) {
                        return $row->status_hrd ?? '-';
                    })
                    ->addColumn('action', function($row) use ($user) {
                        $btn = '<button class="btn btn-info btn-detail-lembur" data-id="'.$row->id.'">Detail</button> ';
                        if ($user->hasRole('Manager')) {
                            $btn .= '<button class="btn btn-primary btn-approve-manager-lembur" data-id="'.$row->id.'"><i class="fas fa-check-circle"></i> Approval</button>';
                        }
                        if ($user->hasRole('Hrd') || $user->hasRole('Admin')) {
                            $btn .= '<button class="btn btn-primary btn-approve-hrd-lembur" data-id="'.$row->id.'"><i class="fas fa-check-circle"></i> Approval HRD</button>';
                        }
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }
            // Employee: only own
            else if ($user->hasRole('Employee')) {
                $data = PengajuanLembur::where('employee_id', $user->employee->id)
                    ->latest()
                    ->get();
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('tanggal', function($row) {
                        return $row->tanggal->format('d/m/Y');
                    })
                    ->addColumn('jam_mulai', function($row) {
                        return $row->jam_mulai;
                    })
                    ->addColumn('jam_selesai', function($row) {
                        return $row->jam_selesai;
                    })
                    ->addColumn('total_jam', function($row) {
                        return $row->total_jam_formatted;
                    })
                    ->addColumn('status_manager', function($row) {
                        return $row->status_manager ?? '-';
                    })
                    ->addColumn('status_hrd', function($row) {
                        return $row->status_hrd ?? '-';
                    })
                    ->addColumn('action', function($row) use ($user) {
                        $btn = '<button class="btn btn-info btn-detail-lembur" data-id="'.$row->id.'">Detail</button> ';
                        if ($user->hasRole('Manager')) {
                            $btn .= '<button class="btn btn-primary btn-approve-manager-lembur" data-id="'.$row->id.'"><i class="fas fa-check-circle"></i> Approval</button>';
                        }
                        if ($user->hasRole('Hrd') || $user->hasRole('Admin')) {
                            $btn .= '<button class="btn btn-primary btn-approve-hrd-lembur" data-id="'.$row->id.'"><i class="fas fa-check-circle"></i> Approval HRD</button>';
                        }
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }
            // Default: nothing
            else {
                return DataTables::of([])->make(true);
            }
        }

        // Non-AJAX: render view
        return view('hrd.lembur.index', [
            'viewType' => $viewType
        ]);
    }

    public function create()
    {
        return view('hrd.lembur.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i', // removed after:jam_mulai
            'alasan' => 'required|string',
        ]);

        $employee = Auth::user()->employee;
        $pengajuan = PengajuanLembur::create([
            'employee_id' => $employee->id,
            'tanggal' => $request->tanggal,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'alasan' => $request->alasan,
        ]);

        if ($request->ajax()) {
            return response()->json(['data' => $pengajuan]);
        }
        return redirect()->route('hrd.lembur.index')->with('success', 'Pengajuan lembur berhasil diajukan.');
    }

    public function persetujuanManager(Request $request, $id)
    {
        $request->validate([
            'komentar_manager' => 'nullable|string',
            'status' => 'required|in:disetujui,ditolak',
        ]);
        $pengajuan = PengajuanLembur::findOrFail($id);
        $pengajuan->update([
            'status_manager' => $request->status,
            'notes_manager' => $request->komentar_manager,
            'tanggal_persetujuan_manager' => now(),
        ]);
        if ($request->ajax()) {
            return response()->json(['data' => $pengajuan]);
        }
        return redirect()->route('hrd.lembur.index')->with('success', 'Pengajuan lembur berhasil diperbarui.');
    }

    public function persetujuanHRD(Request $request, $id)
    {
        $request->validate([
            'komentar_hrd' => 'nullable|string',
            'status' => 'required|in:disetujui,ditolak',
        ]);
        $pengajuan = PengajuanLembur::findOrFail($id);
        $pengajuan->update([
            'status_hrd' => $request->status,
            'notes_hrd' => $request->komentar_hrd,
            'tanggal_persetujuan_hrd' => now(),
        ]);
        if ($request->ajax()) {
            return response()->json(['data' => $pengajuan]);
        }
        return redirect()->route('hrd.lembur.index')->with('success', 'Pengajuan lembur berhasil diperbarui.');
    }

    public function show($id)
    {
        $pengajuan = PengajuanLembur::findOrFail($id);
        return view('hrd.lembur.show', compact('pengajuan'));
    }

    public function getApprovalStatus($id)
    {
        $pengajuan = PengajuanLembur::findOrFail($id);
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
}
