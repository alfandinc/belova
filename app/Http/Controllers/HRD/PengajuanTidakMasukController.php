<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\PengajuanTidakMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PengajuanTidakMasukController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $viewType = $request->input('view', 'personal');

        // Date filter defaults: start of this month to end of next month
        $defaultStart = Carbon::now()->startOfMonth();
        $defaultEnd = Carbon::now()->copy()->addMonthNoOverflow()->endOfMonth();

        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');

        try {
            $filterStart = $dateStart ? Carbon::parse($dateStart)->startOfDay() : $defaultStart->copy()->startOfDay();
        } catch (\Exception $e) {
            $filterStart = $defaultStart->copy()->startOfDay();
        }

        try {
            $filterEnd = $dateEnd ? Carbon::parse($dateEnd)->endOfDay() : $defaultEnd->copy()->endOfDay();
        } catch (\Exception $e) {
            $filterEnd = $defaultEnd->copy()->endOfDay();
        }

        if ($request->ajax()) {
            // Employee: hanya data sendiri
            if (($viewType == 'personal' || empty($viewType)) && $user->hasRole('Employee')) {
                $data = PengajuanTidakMasuk::where('employee_id', $user->employee->id)
                    ->where(function($q) use ($filterStart, $filterEnd) {
                        $q->whereDate('tanggal_mulai', '<=', $filterEnd)
                          ->whereDate('tanggal_selesai', '>=', $filterStart);
                    })
                    ->latest()
                    ->get();
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('jenis', function($row) {
                        return ucfirst($row->jenis);
                    })
                    ->addColumn('tanggal_mulai', function($row) {
                        return $row->tanggal_mulai->format('d/m/Y');
                    })
                    ->addColumn('tanggal_selesai', function($row) {
                        return $row->tanggal_selesai->format('d/m/Y');
                    })
                    ->addColumn('status_manager', function($row) {
                        return $row->status_manager ?? '-';
                    })
                    ->addColumn('status_hrd', function($row) {
                        return $row->status_hrd ?? '-';
                    })
                    ->addColumn('action', function($row) {
                        $btn = '<button class="btn btn-info btn-detail" data-id="'.$row->id.'">Detail</button>';
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }
            // Manager: data semua employee di divisinya (view=team)
            else if ($viewType == 'team' && $user->hasRole('Manager')) {
                $division = $user->employee->division;
                $employeeIds = $division ? $division->employees->pluck('id')->toArray() : [];
                $data = PengajuanTidakMasuk::whereIn('employee_id', $employeeIds)
                    ->where(function($q) use ($filterStart, $filterEnd) {
                        $q->whereDate('tanggal_mulai', '<=', $filterEnd)
                          ->whereDate('tanggal_selesai', '>=', $filterStart);
                    })
                    ->with('employee')
                    ->latest()
                    ->get();
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('employee_nama', function($row) {
                        return $row->employee->nama ?? '-';
                    })
                    ->addColumn('jenis', function($row) {
                        return ucfirst($row->jenis);
                    })
                    ->addColumn('tanggal_mulai', function($row) {
                        return $row->tanggal_mulai->format('d/m/Y');
                    })
                    ->addColumn('tanggal_selesai', function($row) {
                        return $row->tanggal_selesai->format('d/m/Y');
                    })
                    ->addColumn('status_manager', function($row) {
                        return $row->status_manager ?? '-';
                    })
                    ->addColumn('status_hrd', function($row) {
                        return $row->status_hrd ?? '-';
                    })
                    ->addColumn('action', function($row) {
                        $btn = '<button class="btn btn-info btn-detail" data-id="'.$row->id.'">Detail</button> ';
                        $btn .= '<button class="btn btn-primary btn-approve-manager" data-id="'.$row->id.'"><i class="fas fa-check-circle"></i> Approval</button>';
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }
            // HRD: semua data untuk approval (view=approval)
            else if ($viewType == 'approval' && $user->hasRole('Hrd')) {
                $data = PengajuanTidakMasuk::with('employee')
                    ->where(function($q) use ($filterStart, $filterEnd) {
                        $q->whereDate('tanggal_mulai', '<=', $filterEnd)
                          ->whereDate('tanggal_selesai', '>=', $filterStart);
                    })
                    ->latest()
                    ->get();
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('employee_nama', function($row) {
                        return $row->employee->nama ?? '-';
                    })
                    ->addColumn('jenis', function($row) {
                        return ucfirst($row->jenis);
                    })
                    ->addColumn('tanggal_mulai', function($row) {
                        return $row->tanggal_mulai->format('d/m/Y');
                    })
                    ->addColumn('tanggal_selesai', function($row) {
                        return $row->tanggal_selesai->format('d/m/Y');
                    })
                    ->addColumn('status_manager', function($row) {
                        return $row->status_manager ?? '-';
                    })
                    ->addColumn('status_hrd', function($row) {
                        return $row->status_hrd ?? '-';
                    })
                    ->addColumn('action', function($row) {
                        $btn = '<button class="btn btn-info btn-detail" data-id="'.$row->id.'">Detail</button> ';
                        $btn .= '<button class="btn btn-primary btn-approve-hrd" data-id="'.$row->id.'"><i class="fas fa-check-circle"></i> Approval</button>';
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }
            // Default: data sendiri
            else {
                $data = PengajuanTidakMasuk::where('employee_id', $user->employee->id)
                    ->where(function($q) use ($filterStart, $filterEnd) {
                        $q->whereDate('tanggal_mulai', '<=', $filterEnd)
                          ->whereDate('tanggal_selesai', '>=', $filterStart);
                    })
                    ->latest()
                    ->get();
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('jenis', function($row) {
                        return ucfirst($row->jenis);
                    })
                    ->addColumn('tanggal_mulai', function($row) {
                        return $row->tanggal_mulai->format('d/m/Y');
                    })
                    ->addColumn('tanggal_selesai', function($row) {
                        return $row->tanggal_selesai->format('d/m/Y');
                    })
                    ->addColumn('status_manager', function($row) {
                        return $row->status_manager ?? '-';
                    })
                    ->addColumn('status_hrd', function($row) {
                        return $row->status_hrd ?? '-';
                    })
                    ->addColumn('action', function($row) {
                        $btn = '<button class="btn btn-info btn-detail" data-id="'.$row->id.'">Detail</button>';
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }
        }

        // Non-AJAX: render view
        return view('hrd.tidakmasuk.index', [
            'viewType' => $viewType,
            'defaultDateStart' => $filterStart->toDateString(),
            'defaultDateEnd' => $filterEnd->toDateString(),
        ]);
    }

    public function create()
    {
        return view('hrd.tidakmasuk.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis' => 'required|in:sakit,izin',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $tanggalMulai = Carbon::parse($request->tanggal_mulai)->startOfDay();
        $tanggalSelesai = Carbon::parse($request->tanggal_selesai)->startOfDay();
        $startTimestamp = $tanggalMulai->getTimestamp();
        $endTimestamp = $tanggalSelesai->getTimestamp();
        $totalHari = (int)round(($endTimestamp - $startTimestamp) / 86400) + 1;
        $totalHari = abs($totalHari);
        if ($totalHari < 1) {
            $totalHari = 1;
        }

        $employee = Auth::user()->employee;
        $buktiPath = null;
        if ($request->hasFile('bukti')) {
            $file = $request->file('bukti');
            $buktiPath = $file->store('bukti_tidak_masuk', 'public');
        }
        $pengajuan = PengajuanTidakMasuk::create([
            'employee_id' => $employee->id,
            'jenis' => $request->jenis,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'total_hari' => $totalHari,
            'alasan' => $request->alasan,
            'bukti' => $buktiPath,
        ]);

        if ($request->ajax()) {
            return response()->json(['data' => $pengajuan]);
        }
        return redirect()->route('hrd.tidakmasuk.index')->with('success', 'Pengajuan tidak masuk berhasil diajukan.');
    }

    public function persetujuanManager(Request $request, $id)
    {
        $request->validate([
            'komentar_manager' => 'nullable|string',
            'status' => 'required|in:disetujui,ditolak',
        ]);
        $pengajuan = PengajuanTidakMasuk::findOrFail($id);
        $pengajuan->update([
            'status_manager' => $request->status,
            'notes_manager' => $request->komentar_manager,
            'tanggal_persetujuan_manager' => now(),
        ]);
        if ($request->ajax()) {
            return response()->json(['data' => $pengajuan]);
        }
        return redirect()->route('hrd.tidakmasuk.index')->with('success', 'Pengajuan tidak masuk berhasil diperbarui.');
    }

    public function persetujuanHRD(Request $request, $id)
    {
        $request->validate([
            'komentar_hrd' => 'nullable|string',
            'status' => 'required|in:disetujui,ditolak',
        ]);
        $pengajuan = PengajuanTidakMasuk::findOrFail($id);
        $pengajuan->update([
            'status_hrd' => $request->status,
            'notes_hrd' => $request->komentar_hrd,
            'tanggal_persetujuan_hrd' => now(),
        ]);
        if ($request->ajax()) {
            return response()->json(['data' => $pengajuan]);
        }
        return redirect()->route('hrd.tidakmasuk.index')->with('success', 'Pengajuan tidak masuk berhasil diperbarui.');
    }

    public function show($id)
    {
        $pengajuan = PengajuanTidakMasuk::findOrFail($id);
        return view('hrd.tidakmasuk.show', compact('pengajuan'));
    }

    public function getApprovalStatus($id)
    {
        $pengajuan = PengajuanTidakMasuk::findOrFail($id);
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
