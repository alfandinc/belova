<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HRD\Concerns\HandlesPengajuanApproval;
use App\Http\Controllers\HRD\Concerns\ResolvesDirectManagerApprovals;
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
    use ResolvesDirectManagerApprovals;
    use HandlesPengajuanApproval;

    private function overlapFilter($query, Carbon $filterStart, Carbon $filterEnd)
    {
        return $query->whereDate('tanggal_mulai', '<=', $filterEnd)
            ->whereDate('tanggal_selesai', '>=', $filterStart);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        $isHrd = $this->isHrdApprover($user);
        $subordinateIds = $employee ? $this->getSubordinateEmployeeIds($employee) : [];
        [$filterStart, $filterEnd] = $this->resolveDateFilter($request);
        $viewType = $request->input('view', 'personal');

        if ($request->ajax()) {
            $needsAction = function ($row) {
                return false;
            };

            if ($viewType === 'personal' && $employee) {
                $query = $this->overlapFilter(PengajuanTidakMasuk::where('employee_id', $employee->id), $filterStart, $filterEnd);
            } elseif ($viewType === 'team' && !empty($subordinateIds)) {
                // Team requests in range, plus anything still waiting for this manager
                $query = PengajuanTidakMasuk::whereIn('employee_id', $subordinateIds)
                    ->where(function ($q) use ($filterStart, $filterEnd) {
                        $q->where(function ($q) use ($filterStart, $filterEnd) {
                            $this->overlapFilter($q, $filterStart, $filterEnd);
                        })->orWhere('status_manager', 'menunggu');
                    });
                $needsAction = function ($row) {
                    return $row->status_manager === 'menunggu';
                };
            } elseif ($viewType === 'approval' && $isHrd) {
                // Manager-approved requests in range, plus anything still waiting for HRD
                $query = PengajuanTidakMasuk::where('status_manager', 'disetujui')
                    ->where(function ($q) use ($filterStart, $filterEnd) {
                        $q->where(function ($q) use ($filterStart, $filterEnd) {
                            $this->overlapFilter($q, $filterStart, $filterEnd);
                        })->orWhere('status_hrd', 'menunggu');
                    });
                $needsAction = function ($row) {
                    return $row->status_manager === 'disetujui' && $row->status_hrd === 'menunggu';
                };
            } else {
                return DataTables::of(collect())->make(true);
            }

            $data = $this->sortNeedsActionFirst($query->with('employee')->get(), $needsAction);
            $approveClass = $viewType === 'team' ? 'btn-approve-manager' : 'btn-approve-hrd';
            $potongIds = array_flip($this->potongCutiTidakMasukIds());

            return DataTables::of($data)
                ->addIndexColumn()
                ->setRowClass(function ($row) use ($needsAction) {
                    return $needsAction($row) ? 'row-needs-action' : '';
                })
                ->addColumn('employee_nama', function ($row) {
                    return e($row->employee->nama ?? '-');
                })
                ->addColumn('tanggal_range', function ($row) use ($potongIds) {
                    $mulai = $row->tanggal_mulai->locale('id')->translatedFormat('j M Y');
                    $selesai = $row->tanggal_selesai->locale('id')->translatedFormat('j M Y');
                    $range = $row->tanggal_mulai->isSameDay($row->tanggal_selesai) ? $mulai : $mulai . ' - ' . $selesai;
                    $badgeClass = $row->jenis === 'sakit' ? 'badge-danger' : 'badge-info';
                    $bukti = $row->bukti ? ' <i class="fas fa-paperclip text-muted" title="Ada bukti"></i>' : '';
                    $potong = isset($potongIds[$row->id])
                        ? ' <span class="badge badge-warning" title="Dicatat sebagai cuti tahunan dan saldo cuti dikurangi">Potong cuti</span>'
                        : '';

                    return $range . ' <strong>(' . (int) ($row->total_hari ?? 1) . ' hari)</strong>'
                        . '<div class="mt-1"><span class="badge ' . $badgeClass . '">' . e(ucfirst($row->jenis)) . '</span>' . $potong . $bukti . '</div>'
                        . $this->renderDiajukanDates($row);
                })
                // Plain values for the approval modal's "approve fewer days" inputs
                ->addColumn('tanggal_mulai_ymd', function ($row) {
                    return $row->tanggal_mulai->toDateString();
                })
                ->addColumn('tanggal_selesai_ymd', function ($row) {
                    return $row->tanggal_selesai->toDateString();
                })
                ->addColumn('alasan', function ($row) {
                    return nl2br(e($row->alasan));
                })
                ->addColumn('catatan', function ($row) {
                    return $this->renderCatatan($row);
                })
                ->addColumn('status_pengajuan', function ($row) {
                    return $this->renderStatusBadge($row);
                })
                ->addColumn('action', function ($row) use ($needsAction, $approveClass) {
                    $extra = [];
                    if ($needsAction($row)) {
                        $btnClass = $approveClass === 'btn-approve-manager' ? 'btn-warning' : 'btn-success';
                        $extra[] = '<button type="button" class="btn ' . $btnClass . ' ' . $approveClass . '" data-id="' . $row->id . '"><i class="fas fa-check-circle"></i> Approval</button>';
                    }

                    return $this->renderActionButtons($row, 'btn-detail', $extra);
                })
                ->rawColumns(['employee_nama', 'tanggal_range', 'alasan', 'catatan', 'status_pengajuan', 'action'])
                ->make(true);
        }

        $teamPending = !empty($subordinateIds)
            ? PengajuanTidakMasuk::whereIn('employee_id', $subordinateIds)->where('status_manager', 'menunggu')->count()
            : 0;
        $hrdPending = $isHrd
            ? PengajuanTidakMasuk::where('status_manager', 'disetujui')->where('status_hrd', 'menunggu')->count()
            : 0;

        return view('hrd.tidakmasuk.index', [
            'viewType' => in_array($viewType, ['personal', 'team', 'approval'], true) ? $viewType : 'personal',
            'canApproveTeam' => !empty($subordinateIds),
            'isHrd' => $isHrd,
            'hasEmployeeProfile' => (bool) $employee,
            'teamPending' => $teamPending,
            'hrdPending' => $hrdPending,
            'defaultDateStart' => $filterStart->toDateString(),
            'defaultDateEnd' => $filterEnd->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis' => 'required|in:sakit,izin',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|max:1000',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'bukti.mimes' => 'Bukti harus berupa file JPG, PNG, atau PDF.',
            'bukti.max' => 'Ukuran file bukti maksimal 2MB.',
        ]);

        $user = Auth::user();
        $employee = $user->employee;
        if (!$employee) {
            return $this->errorResponse($request, 'Akun Anda belum terhubung dengan data karyawan.');
        }

        $tanggalMulai = Carbon::parse($request->tanggal_mulai)->startOfDay();
        $tanggalSelesai = Carbon::parse($request->tanggal_selesai)->startOfDay();
        $totalHari = $this->inclusiveDayCount($tanggalMulai, $tanggalSelesai);

        // Prevent duplicate / overlapping requests from the same employee
        $overlap = PengajuanTidakMasuk::where('employee_id', $employee->id)
            ->whereDate('tanggal_mulai', '<=', $tanggalSelesai)
            ->whereDate('tanggal_selesai', '>=', $tanggalMulai)
            ->where(function ($q) {
                $q->whereNull('status_manager')->orWhere('status_manager', '!=', 'ditolak');
            })
            ->where(function ($q) {
                $q->whereNull('status_hrd')->orWhere('status_hrd', '!=', 'ditolak');
            })
            ->first();
        if ($overlap) {
            return $this->errorResponse($request, 'Anda sudah memiliki pengajuan tidak masuk pada tanggal '
                . $overlap->tanggal_mulai->translatedFormat('j F Y') . ' - ' . $overlap->tanggal_selesai->translatedFormat('j F Y')
                . ' yang belum ditolak.');
        }

        $buktiPath = null;
        if ($request->hasFile('bukti')) {
            $buktiPath = $request->file('bukti')->store('bukti_tidak_masuk', 'public');
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

        // Manager step goes to the direct superior of the employee's position
        $pengajuan = PengajuanTidakMasuk::create(array_merge($payload, $this->initialManagerApproval($employee)));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan tidak masuk berhasil diajukan.',
                'data' => $pengajuan,
            ]);
        }
        return redirect()->route('hrd.tidakmasuk.index')->with('success', 'Pengajuan tidak masuk berhasil diajukan.');
    }

    public function persetujuanManager(Request $request, $id)
    {
        $request->validate(array_merge([
            'komentar_manager' => 'nullable|string|max:1000',
            'status' => 'required|in:disetujui,ditolak',
        ], $this->partialApprovalRules(false)));
        $pengajuan = PengajuanTidakMasuk::findOrFail($id);

        if (!Auth::user()->employee || !$this->canApproveAsDirectManager(Auth::user()->employee, $pengajuan->employee)) {
            return $this->forbiddenResponse($request, 'Anda bukan atasan langsung untuk pengajuan ini.');
        }

        if ($pengajuan->status_manager !== 'menunggu') {
            return $this->errorResponse($request, 'Pengajuan ini sudah diproses pada approval tingkat 1.');
        }

        $daysBefore = (int) $pengajuan->total_hari;
        try {
            $adjust = $this->buildDateAdjustment($pengajuan, $request);
        } catch (\DomainException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        $pengajuan->update(array_merge([
            'status_manager' => $request->status,
            'notes_manager' => $request->komentar_manager,
            'tanggal_persetujuan_manager' => now(),
        ], $adjust));

        $message = $this->dateApprovalMessage($request, $adjust, $pengajuan, $daysBefore);
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $pengajuan]);
        }
        return redirect()->route('hrd.tidakmasuk.index')->with('success', $message);
    }

    public function persetujuanHRD(Request $request, $id)
    {
        $request->validate(array_merge([
            'komentar_hrd' => 'nullable|string|max:1000',
            'status' => 'required|in:disetujui,ditolak',
            'potong_dari_cuti' => 'sometimes|boolean',
        ], $this->partialApprovalRules(false)));

        if (!$this->isHrdApprover(Auth::user())) {
            return $this->forbiddenResponse($request, 'Hanya HRD yang dapat melakukan approval final.');
        }

        $doCut = $request->boolean('potong_dari_cuti') && $request->status === 'disetujui';
        $message = '';

        try {
            $pengajuan = DB::transaction(function () use ($request, $id, $doCut, &$message) {
                $pengajuan = PengajuanTidakMasuk::lockForUpdate()->findOrFail($id);

                if ($pengajuan->status_manager !== 'disetujui') {
                    throw new \DomainException('Approval final HRD hanya bisa dilakukan setelah approval atasan langsung disetujui.');
                }
                if ($pengajuan->status_hrd !== 'menunggu') {
                    throw new \DomainException('Pengajuan ini sudah diproses pada approval final HRD.');
                }

                $daysBefore = (int) $pengajuan->total_hari;
                $adjust = $this->buildDateAdjustment($pengajuan, $request);

                // Apply the (possibly shorter) range first so "potong cuti" uses the approved days only
                $pengajuan->update(array_merge([
                    'status_hrd' => $request->status,
                    'notes_hrd' => $request->komentar_hrd,
                    'tanggal_persetujuan_hrd' => now(),
                ], $adjust));

                $message = $this->dateApprovalMessage($request, $adjust, $pengajuan, $daysBefore);
                if ($doCut) {
                    $message = rtrim($message, '.') . '. ' . $this->potongDariCuti($pengajuan);
                }

                return $pengajuan;
            });
        } catch (\DomainException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $pengajuan]);
        }
        return redirect()->route('hrd.tidakmasuk.index')->with('success', $message);
    }

    /**
     * Record an approved tidak masuk request as annual leave: creates an approved PengajuanLibur
     * and deducts the days from jatah cuti tahunan. Must run inside a transaction.
     *
     * @throws \DomainException when it cannot be applied (already on leave, insufficient balance)
     */
    private function potongDariCuti(PengajuanTidakMasuk $pengajuan): string
    {
        $totalHari = (int) ($pengajuan->total_hari ?? 0);
        if ($totalHari < 1) {
            $totalHari = $this->inclusiveDayCount($pengajuan->tanggal_mulai, $pengajuan->tanggal_selesai);
        }

        // Do not deduct twice if annual leave already covers these dates
        $existing = PengajuanLibur::where('employee_id', $pengajuan->employee_id)
            ->where('jenis_libur', 'cuti_tahunan')
            ->whereDate('tanggal_mulai', '<=', $pengajuan->tanggal_selesai)
            ->whereDate('tanggal_selesai', '>=', $pengajuan->tanggal_mulai)
            ->where(function ($q) {
                $q->whereNull('status_manager')->orWhere('status_manager', '!=', 'ditolak');
            })
            ->where(function ($q) {
                $q->whereNull('status_hrd')->orWhere('status_hrd', '!=', 'ditolak');
            })
            ->first();
        if ($existing) {
            throw new \DomainException('Karyawan sudah memiliki pengajuan cuti tahunan pada tanggal '
                . $existing->tanggal_mulai->translatedFormat('j F Y') . ' - ' . $existing->tanggal_selesai->translatedFormat('j F Y')
                . '. Hilangkan centang "Potong dari Cuti Tahunan" agar saldo tidak terpotong dua kali.');
        }

        $pengajuan->loadMissing('employee');
        if (!$pengajuan->employee) {
            throw new \DomainException('Data karyawan untuk pengajuan ini tidak ditemukan.');
        }
        $pengajuan->employee->ensureJatahLibur();
        $jatah = JatahLibur::where('employee_id', $pengajuan->employee_id)->lockForUpdate()->firstOrFail();
        $saldo = (int) $jatah->jatah_cuti_tahunan;

        if ($saldo < $totalHari) {
            throw new \DomainException('Saldo cuti tahunan karyawan tidak mencukupi (sisa ' . $saldo . ' hari, dibutuhkan ' . $totalHari . ' hari). '
                . 'Hilangkan centang "Potong dari Cuti Tahunan" untuk menyetujui tanpa memotong cuti.');
        }

        $marker = $this->potongCutiMarker($pengajuan->id);
        PengajuanLibur::create([
            'employee_id' => $pengajuan->employee_id,
            'jenis_libur' => 'cuti_tahunan',
            'tanggal_mulai' => $pengajuan->tanggal_mulai,
            'tanggal_selesai' => $pengajuan->tanggal_selesai,
            'total_hari' => $totalHari,
            'alasan' => ucfirst($pengajuan->jenis) . ': ' . $pengajuan->alasan,
            'status_manager' => 'disetujui',
            'notes_manager' => $marker,
            'tanggal_persetujuan_manager' => now(),
            'status_hrd' => 'disetujui',
            'notes_hrd' => $marker,
            'tanggal_persetujuan_hrd' => now(),
        ]);

        $jatah->jatah_cuti_tahunan = $saldo - $totalHari;
        $jatah->save();

        Log::info('Tidak masuk dipotong dari cuti tahunan', [
            'ptm_id' => $pengajuan->id,
            'employee_id' => $pengajuan->employee_id,
            'total_hari' => $totalHari,
            'sisa_cuti' => $jatah->jatah_cuti_tahunan,
            'by_user' => Auth::id(),
        ]);

        return 'Cuti tahunan dipotong ' . $totalHari . ' hari (sisa ' . $jatah->jatah_cuti_tahunan . ' hari).';
    }

    public function show(Request $request, $id)
    {
        $pengajuan = PengajuanTidakMasuk::with('employee')->findOrFail($id);

        if (!$this->canViewPengajuan(Auth::user(), $pengajuan)) {
            return $this->forbiddenResponse($request);
        }

        $potongCuti = PengajuanLibur::where('notes_hrd', $this->potongCutiMarker($pengajuan->id))->first();

        return view('hrd.tidakmasuk.show', compact('pengajuan', 'potongCuti'));
    }

    public function getApprovalStatus(Request $request, $id)
    {
        $pengajuan = PengajuanTidakMasuk::with('employee')->findOrFail($id);

        if (!$this->canViewPengajuan(Auth::user(), $pengajuan)) {
            return $this->forbiddenResponse($request);
        }

        $data = [
            'status_manager' => $pengajuan->status_manager,
            'notes_manager' => $pengajuan->notes_manager,
            'status_hrd' => $pengajuan->status_hrd,
            'notes_hrd' => $pengajuan->notes_hrd,
            'total_hari' => (int) ($pengajuan->total_hari ?: $this->inclusiveDayCount($pengajuan->tanggal_mulai, $pengajuan->tanggal_selesai)),
        ];

        // Leave balance is only needed (and only shown) to HRD deciding whether to cut from annual leave
        if ($this->isHrdApprover(Auth::user()) && $pengajuan->employee) {
            $data['saldo_cuti'] = $this->getSaldoSummary($pengajuan->employee)['cuti_tahunan'];
        }

        return response()->json(['success' => true, 'data' => $data]);
    }
}

