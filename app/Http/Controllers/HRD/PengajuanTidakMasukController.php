<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\HRD\PengajuanTidakMasuk;
use App\Models\HRD\PengajuanLibur;
use App\Models\HRD\JatahLibur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
                    ->addColumn('tanggal_range', function($row) {
                            $mulai = $row->tanggal_mulai->locale('id')->translatedFormat('j F Y');
                            $selesai = $row->tanggal_selesai->locale('id')->translatedFormat('j F Y');
                            return $mulai.' - '.$selesai.' <br><strong>('.($row->total_hari ?? 1).' Hari)</strong>'
                                ." <div class=\"mt-1\"><span class=\"badge badge-info\">".ucfirst($row->jenis)."</span></div>";
                        })
                        ->addColumn('alasan', function($row) {
                            return e($row->alasan);
                        })
                        ->addColumn('catatan', function($row) {
                            $out = '';
                            if (!empty($row->notes_manager)) {
                                $out .= '<div><strong>Manager:</strong> ' . e($row->notes_manager) . '</div>';
                            }
                            if (!empty($row->notes_hrd)) {
                                $out .= '<div><strong>HRD:</strong> ' . e($row->notes_hrd) . '</div>';
                            }
                            return $out;
                        })
                        ->addColumn('status_pengajuan', function($row) {
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
                    ->addColumn('action', function($row) {
                        $btns = '<div class="btn-group btn-group-sm" role="group">';
                        $btns .= '<button class="btn btn-info btn-detail" data-id="'.$row->id.'">Detail</button>';
                        $btns .= '</div>';
                        return $btns;
                    })
                    ->rawColumns(['tanggal_range','status_pengajuan','catatan','action'])
                    ->make(true);
            }
            // Manager: data sendiri (view=personal)
            else if (($viewType == 'personal' || empty($viewType)) && $user->hasRole('Manager')) {
                $data = PengajuanTidakMasuk::where('employee_id', $user->employee->id)
                    ->where(function($q) use ($filterStart, $filterEnd) {
                        $q->whereDate('tanggal_mulai', '<=', $filterEnd)
                          ->whereDate('tanggal_selesai', '>=', $filterStart);
                    })
                    ->latest()
                    ->get();

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('tanggal_range', function($row) {
                        $mulai = $row->tanggal_mulai->locale('id')->translatedFormat('j F Y');
                        $selesai = $row->tanggal_selesai->locale('id')->translatedFormat('j F Y');
                        return $mulai.' - '.$selesai.' <br><strong>('.($row->total_hari ?? 1).' Hari)</strong>'
                            ." <div class=\"mt-1\"><span class=\"badge badge-info\">".ucfirst($row->jenis)."</span></div>";
                    })
                    ->addColumn('alasan', function($row) {
                        return e($row->alasan);
                    })
                    ->addColumn('catatan', function($row) {
                        $out = '';
                        if (!empty($row->notes_manager)) {
                            $out .= '<div><strong>Manager:</strong> ' . e($row->notes_manager) . '</div>';
                        }
                        if (!empty($row->notes_hrd)) {
                            $out .= '<div><strong>HRD:</strong> ' . e($row->notes_hrd) . '</div>';
                        }
                        return $out;
                    })
                    ->addColumn('status_pengajuan', function($row) {
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
                    ->addColumn('action', function($row) {
                        $btns = '<div class="btn-group btn-group-sm" role="group">';
                        $btns .= '<button class="btn btn-info btn-detail" data-id="'.$row->id.'">Detail</button>';
                        $btns .= '</div>';
                        return $btns;
                    })
                    ->rawColumns(['tanggal_range','status_pengajuan','catatan','action'])
                    ->make(true);
            }
            // Manager: data semua employee di divisinya (view=team)
            else if ($viewType == 'team' && $user->hasRole('Manager')) {
                $division = $user->employee->division;
                $employeeIds = $division ? $division->employees->pluck('id')->toArray() : [];
                $data = PengajuanTidakMasuk::whereIn('employee_id', $employeeIds)
                    ->where('employee_id', '!=', $user->employee->id)
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
                    ->addColumn('tanggal_range', function($row) {
                        $mulai = $row->tanggal_mulai->locale('id')->translatedFormat('j F Y');
                        $selesai = $row->tanggal_selesai->locale('id')->translatedFormat('j F Y');
                        return $mulai.' - '.$selesai.' <br><strong>('.($row->total_hari ?? 1).' Hari)</strong>'
                            ." <div class=\"mt-1\"><span class=\"badge badge-info\">".ucfirst($row->jenis)."</span></div>";
                    })
                    ->addColumn('alasan', function($row) {
                        return e($row->alasan);
                    })
                    ->addColumn('catatan', function($row) {
                        $out = '';
                        if (!empty($row->notes_manager)) {
                            $out .= '<div><strong>Manager:</strong> ' . e($row->notes_manager) . '</div>';
                        }
                        if (!empty($row->notes_hrd)) {
                            $out .= '<div><strong>HRD:</strong> ' . e($row->notes_hrd) . '</div>';
                        }
                        return $out;
                    })
                    ->addColumn('status_pengajuan', function($row) {
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
                    ->addColumn('action', function($row) {
                        $btns = '<div class="btn-group btn-group-sm" role="group">';
                        $btns .= '<button class="btn btn-info btn-detail" data-id="'.$row->id.'">Detail</button>';
                        $btns .= '<button class="btn btn-warning btn-approve-manager" title="Approval Manager" data-id="'.$row->id.'"><i class="fas fa-check-circle"></i> Approval</button>';
                        $btns .= '</div>';
                        return $btns;
                    })
                    ->rawColumns(['tanggal_range','status_pengajuan','catatan','action'])
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
                    ->addColumn('tanggal_range', function($row) {
                        $mulai = $row->tanggal_mulai->locale('id')->translatedFormat('j F Y');
                        $selesai = $row->tanggal_selesai->locale('id')->translatedFormat('j F Y');
                        return $mulai.' - '.$selesai.' <br><strong>('.($row->total_hari ?? 1).' Hari)</strong>'
                            ." <div class=\"mt-1\"><span class=\"badge badge-info\">".ucfirst($row->jenis)."</span></div>";
                    })
                    ->addColumn('alasan', function($row) {
                        return e($row->alasan);
                    })
                    ->addColumn('catatan', function($row) {
                        $out = '';
                        if (!empty($row->notes_manager)) {
                            $out .= '<div><strong>Manager:</strong> ' . e($row->notes_manager) . '</div>';
                        }
                        if (!empty($row->notes_hrd)) {
                            $out .= '<div><strong>HRD:</strong> ' . e($row->notes_hrd) . '</div>';
                        }
                        return $out;
                    })
                    ->addColumn('status_pengajuan', function($row) {
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
                    ->addColumn('action', function($row) {
                        $btns = '<div class="btn-group btn-group-sm" role="group">';
                        $btns .= '<button class="btn btn-info btn-detail" data-id="'.$row->id.'">Detail</button>';
                        $btns .= '<button class="btn btn-success btn-approve-hrd" title="Approval HRD" data-id="'.$row->id.'"><i class="fas fa-check-circle"></i> Approval</button>';
                        $btns .= '</div>';
                        return $btns;
                    })
                    ->rawColumns(['tanggal_range','status_pengajuan','catatan','action'])
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
                    ->addColumn('tanggal_range', function($row) {
                        $mulai = $row->tanggal_mulai->locale('id')->translatedFormat('j F Y');
                        $selesai = $row->tanggal_selesai->locale('id')->translatedFormat('j F Y');
                        return $mulai.' - '.$selesai.' <br><strong>('.($row->total_hari ?? 1).' Hari)</strong>'
                            ." <div class=\"mt-1\"><span class=\"badge badge-info\">".ucfirst($row->jenis)."</span></div>";
                    })
                    ->addColumn('alasan', function($row) {
                        return e($row->alasan);
                    })
                    ->addColumn('catatan', function($row) {
                        $out = '';
                        if (!empty($row->notes_manager)) {
                            $out .= '<div><strong>Manager:</strong> ' . e($row->notes_manager) . '</div>';
                        }
                        if (!empty($row->notes_hrd)) {
                            $out .= '<div><strong>HRD:</strong> ' . e($row->notes_hrd) . '</div>';
                        }
                        return $out;
                    })
                    ->addColumn('status_pengajuan', function($row) {
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
                    ->addColumn('action', function($row) {
                        $btn = '<button class="btn btn-info btn-detail" data-id="'.$row->id.'">Detail</button>';
                        return $btn;
                    })
                    ->rawColumns(['tanggal_range','status_pengajuan','catatan','action'])
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

        $user = Auth::user();
        $employee = $user->employee;
        $buktiPath = null;
        if ($request->hasFile('bukti')) {
            $file = $request->file('bukti');
            $buktiPath = $file->store('bukti_tidak_masuk', 'public');
        }
        $payload = [
            'employee_id' => $employee->id,
            'jenis' => $request->jenis,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'total_hari' => $totalHari,
            'alasan' => $request->alasan,
            'bukti' => $buktiPath,
        ];

        // If a Manager submits their own request, auto-approve at manager level
        if ($user->hasRole('Manager')) {
            $payload['status_manager'] = 'disetujui';
            $payload['notes_manager'] = 'Auto-approved (Manager membuat pengajuan)';
            $payload['tanggal_persetujuan_manager'] = now();
        }

        $pengajuan = PengajuanTidakMasuk::create($payload);

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
            'potong_dari_cuti' => 'sometimes|boolean',
        ]);
        $pengajuan = PengajuanTidakMasuk::findOrFail($id);

        DB::transaction(function () use ($request, $pengajuan) {
            $pengajuan->update([
                'status_hrd' => $request->status,
                'notes_hrd' => $request->komentar_hrd,
                'tanggal_persetujuan_hrd' => now(),
            ]);

            $doCut = (bool) $request->boolean('potong_dari_cuti');
            if ($doCut && $request->status === 'disetujui') {
                try {
                    $totalHari = $pengajuan->total_hari ?? 0;
                    if (!$totalHari || $totalHari < 1) {
                        // Recalculate defensively
                        $start = Carbon::parse($pengajuan->tanggal_mulai)->startOfDay();
                        $end = Carbon::parse($pengajuan->tanggal_selesai)->startOfDay();
                        $totalHari = abs((int) round(($end->getTimestamp() - $start->getTimestamp()) / 86400)) + 1;
                        if ($totalHari < 1) { $totalHari = 1; }
                    }

                    // Create PengajuanLibur if not already created for same employee and dates
                    $existingLibur = PengajuanLibur::where('employee_id', $pengajuan->employee_id)
                        ->whereDate('tanggal_mulai', $pengajuan->tanggal_mulai)
                        ->whereDate('tanggal_selesai', $pengajuan->tanggal_selesai)
                        ->where('jenis_libur', 'cuti_tahunan')
                        ->first();

                    if (!$existingLibur) {
                        $notesAuto = 'Otomatis dari Potong Cuti: PTM ID '.$pengajuan->id;
                        $existingLibur = PengajuanLibur::create([
                            'employee_id' => $pengajuan->employee_id,
                            'jenis_libur' => 'cuti_tahunan',
                            'tanggal_mulai' => $pengajuan->tanggal_mulai,
                            'tanggal_selesai' => $pengajuan->tanggal_selesai,
                            'total_hari' => $totalHari,
                            'alasan' => $pengajuan->alasan,
                            'status_manager' => 'disetujui',
                            'notes_manager' => $notesAuto,
                            'tanggal_persetujuan_manager' => now(),
                            'status_hrd' => 'disetujui',
                            'notes_hrd' => $notesAuto,
                            'tanggal_persetujuan_hrd' => now(),
                        ]);
                    }

                    // Reduce leave quota (jatah cuti tahunan) safely
                    $jatah = JatahLibur::firstOrCreate(
                        ['employee_id' => $pengajuan->employee_id],
                        ['jatah_cuti_tahunan' => 0, 'jatah_ganti_libur' => 0]
                    );
                    $newCuti = (int) $jatah->jatah_cuti_tahunan - (int) $totalHari;
                    if ($newCuti < 0) { $newCuti = 0; }
                    $jatah->update(['jatah_cuti_tahunan' => $newCuti]);
                } catch (\Throwable $e) {
                    Log::error('Gagal potong cuti dari PTM', [
                        'ptm_id' => $pengajuan->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Let the PTM approval succeed even if quota adjust fails
                }
            }
        });

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
