<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HRD\Concerns\HandlesPengajuanApproval;
use App\Http\Controllers\HRD\Concerns\ResolvesDirectManagerApprovals;
use App\Models\HRD\Employee;
use App\Models\HRD\EmployeeSchedule;
use App\Models\HRD\JatahLibur;
use App\Models\HRD\LiburNasional;
use App\Models\HRD\PengajuanLibur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\HrdConfig;

class PengajuanLiburController extends Controller
{
    use ResolvesDirectManagerApprovals;
    use HandlesPengajuanApproval;

    private const JENIS_LABEL = [
        'cuti_tahunan' => 'Cuti Tahunan',
        'ganti_libur' => 'Ganti Libur',
    ];

    /**
     * Helper: get dates within range that already reached the daily leave capacity
     * (counts any request not explicitly rejected by Manager or HRD)
     */
    private function getBlockedDatesByCapacity(Carbon $start, Carbon $end, $excludeId = null): array
    {
        $blocked = [];
        $cursor = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        $capacity = HrdConfig::getLeaveDailyCapacity();

        while ($cursor->lte($end)) {
            $count = PengajuanLibur::whereDate('tanggal_mulai', '<=', $cursor->toDateString())
                ->whereDate('tanggal_selesai', '>=', $cursor->toDateString())
                ->when($excludeId, function ($q) use ($excludeId) {
                    $q->where('id', '!=', $excludeId);
                })
                ->where(function ($q) {
                    $q->whereNull('status_manager')->orWhere('status_manager', '!=', 'ditolak');
                })
                ->where(function ($q) {
                    $q->whereNull('status_hrd')->orWhere('status_hrd', '!=', 'ditolak');
                })
                ->count();

            if ($count >= $capacity) {
                $blocked[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        return $blocked;
    }

    private function overlapFilter($query, Carbon $filterStart, Carbon $filterEnd)
    {
        return $query->whereDate('tanggal_mulai', '<=', $filterEnd)
            ->whereDate('tanggal_selesai', '>=', $filterStart);
    }

    /**
     * AJAX: check capacity for a date range; returns blocked dates
     */
    public function checkCapacity(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        $start = Carbon::parse($request->input('start'))->startOfDay();
        $end = Carbon::parse($request->input('end'))->startOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        // Guard against huge ranges (one query per day)
        if ($start->diffInDays($end, true) > 366) {
            return response()->json(['success' => false, 'message' => 'Rentang tanggal terlalu panjang.'], 422);
        }

        $blockedDates = $this->getBlockedDatesByCapacity($start, $end);

        return response()->json([
            'success' => true,
            'capacityExceeded' => count($blockedDates) > 0,
            'capacity' => HrdConfig::getLeaveDailyCapacity(),
            'blockedDates' => $blockedDates,
        ]);
    }

    /**
     * Scheduled Sundays and national holidays (up to today) the employee worked that still back the ganti libur
     * balance (see PengajuanLibur::hariMasukBelumDipakai). Newest first: [date => ['date', 'label', 'shift', 'libur']].
     */
    private function availableHariMasuk(Employee $employee, $excludeId = null): array
    {
        $holidays = LiburNasional::namesByDate();
        $tersedia = PengajuanLibur::hariMasukBelumDipakai($employee->id, $holidays, $excludeId);

        $available = [];
        EmployeeSchedule::with('shift')
            ->where('employee_id', $employee->id)
            ->whereIn('date', $tersedia)
            ->whereDate('date', '<=', Carbon::today())
            ->orderByDesc('date')
            ->get()
            ->groupBy(fn($s) => Carbon::parse($s->date)->toDateString())
            ->each(function ($schedules, $date) use (&$available, $holidays) {
                $available[$date] = [
                    'date' => $date,
                    'label' => Carbon::parse($date)->locale('id')->translatedFormat('l, j F Y'),
                    'shift' => $schedules->map(fn($s) => $s->shift->name ?? null)->filter()->unique()->implode(', '),
                    'libur' => $holidays[$date] ?? null,
                ];
            });

        return $available;
    }

    /**
     * AJAX: Sundays the current employee can claim for a ganti libur request.
     */
    public function hariMasukTersedia(Request $request)
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Akun Anda belum terhubung dengan data karyawan.'], 422);
        }

        return response()->json(['success' => true, 'data' => array_values($this->availableHariMasuk($employee))]);
    }

    /**
     * When a ganti libur is approved for fewer days, keep only as many replacement Sundays as approved days
     * (earliest first); the rest become available to claim again.
     */
    private function trimHariMasuk(PengajuanLibur $pengajuan, array $adjust): array
    {
        if (!$adjust || $pengajuan->jenis_libur !== 'ganti_libur' || empty($pengajuan->tanggal_masuk_pengganti)) {
            return $adjust;
        }

        $adjust['tanggal_masuk_pengganti'] = collect($pengajuan->tanggal_masuk_pengganti)
            ->sort()->values()->take((int) $adjust['total_hari'])->all();

        return $adjust;
    }

    private function renderHariMasuk($row): string
    {
        if ($row->jenis_libur !== 'ganti_libur' || empty($row->tanggal_masuk_pengganti)) {
            return '';
        }
        $dates = collect($row->tanggal_masuk_pengganti)->sort()
            ->map(fn($d) => Carbon::parse($d)->locale('id')->translatedFormat('j M Y'))
            ->implode(', ');

        return '<div class="small mt-1"><i class="fas fa-calendar-check text-success mr-1"></i>Pengganti masuk: ' . e($dates) . '</div>';
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
                $query = $this->overlapFilter(PengajuanLibur::where('employee_id', $employee->id), $filterStart, $filterEnd);
            } elseif ($viewType === 'team' && !empty($subordinateIds)) {
                // Team requests in range, plus anything still waiting for this manager
                $query = PengajuanLibur::whereIn('employee_id', $subordinateIds)
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
                $query = PengajuanLibur::where('status_manager', 'disetujui')
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

            return DataTables::of($data)
                ->addIndexColumn()
                ->setRowClass(function ($row) use ($needsAction) {
                    return $needsAction($row) ? 'row-needs-action' : '';
                })
                ->addColumn('employee_nama', function ($row) {
                    return e($row->employee->nama ?? '-');
                })
                ->addColumn('tanggal_range', function ($row) {
                    $mulai = $row->tanggal_mulai->locale('id')->translatedFormat('j M Y');
                    $selesai = $row->tanggal_selesai->locale('id')->translatedFormat('j M Y');
                    $range = $row->tanggal_mulai->isSameDay($row->tanggal_selesai) ? $mulai : $mulai . ' - ' . $selesai;
                    $badgeClass = $row->jenis_libur == 'cuti_tahunan' ? 'badge-info' : 'badge-secondary';

                    return $range . ' <strong>(' . (int) $row->total_hari . ' hari)</strong>'
                        . '<div class="mt-1"><span class="badge ' . $badgeClass . '">' . (self::JENIS_LABEL[$row->jenis_libur] ?? e($row->jenis_libur)) . '</span></div>'
                        . $this->renderHariMasuk($row)
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
                        $extra[] = '<button type="button" class="btn btn-primary ' . $approveClass . '" data-id="' . $row->id . '"><i class="fas fa-check-circle"></i> Approval</button>';
                    }

                    return $this->renderActionButtons($row, 'btn-detail', $extra);
                })
                ->rawColumns(['employee_nama', 'tanggal_range', 'alasan', 'catatan', 'status_pengajuan', 'action'])
                ->make(true);
        }

        $teamPending = !empty($subordinateIds)
            ? PengajuanLibur::whereIn('employee_id', $subordinateIds)->where('status_manager', 'menunggu')->count()
            : 0;
        $hrdPending = $isHrd
            ? PengajuanLibur::where('status_manager', 'disetujui')->where('status_hrd', 'menunggu')->count()
            : 0;

        return view('hrd.libur.index', [
            'viewType' => in_array($viewType, ['personal', 'team', 'approval'], true) ? $viewType : 'personal',
            'canApproveTeam' => !empty($subordinateIds),
            'isHrd' => $isHrd,
            'hasEmployeeProfile' => (bool) $employee,
            'saldo' => $employee ? $this->getSaldoSummary($employee) : null,
            'teamPending' => $teamPending,
            'hrdPending' => $hrdPending,
            'defaultDateStart' => $filterStart->toDateString(),
            'defaultDateEnd' => $filterEnd->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis_libur' => 'required|in:cuti_tahunan,ganti_libur',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|max:1000',
            'tanggal_masuk_pengganti' => 'required_if:jenis_libur,ganti_libur|array',
            'tanggal_masuk_pengganti.*' => 'date',
        ], [
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'tanggal_masuk_pengganti.required_if' => 'Pilih hari Minggu / libur nasional yang Anda masuk sebagai pengganti libur ini.',
        ]);

        $user = Auth::user();
        $employee = $user->employee;
        if (!$employee) {
            return $this->errorResponse($request, 'Akun Anda belum terhubung dengan data karyawan.');
        }

        $tanggalMulai = Carbon::parse($request->tanggal_mulai)->startOfDay();
        $tanggalSelesai = Carbon::parse($request->tanggal_selesai)->startOfDay();
        $totalHari = $this->inclusiveDayCount($tanggalMulai, $tanggalSelesai);

        // Ganti libur must name the scheduled Sunday(s) / national holiday(s) worked, one per day off
        $hariMasuk = null;
        if ($request->jenis_libur === 'ganti_libur') {
            $hariMasuk = collect($request->input('tanggal_masuk_pengganti', []))
                ->map(fn($d) => Carbon::parse($d)->toDateString())
                ->unique()->sort()->values()->all();

            if (count($hariMasuk) !== $totalHari) {
                return $this->errorResponse($request, 'Pilih tepat ' . $totalHari . ' hari Minggu / libur nasional pengganti, sesuai jumlah hari libur yang diajukan (dipilih ' . count($hariMasuk) . ').');
            }

            $invalid = array_diff($hariMasuk, array_keys(array_filter(
                $this->availableHariMasuk($employee),
                fn($h) => PengajuanLibur::hariMasukSebelumLibur($h['date'], $tanggalMulai)
            )));
            if ($invalid) {
                return $this->errorResponse($request, 'Tanggal berikut tidak dapat dipakai: '
                    . implode(', ', array_map(fn($d) => Carbon::parse($d)->translatedFormat('j F Y'), $invalid))
                    . '. Hanya hari Minggu atau libur nasional yang sudah lewat, sebelum tanggal libur, ada di jadwal Anda, dan belum dipakai pengajuan ganti libur lain.');
            }
        }

        // Prevent duplicate / overlapping requests from the same employee
        $overlap = PengajuanLibur::where('employee_id', $employee->id)
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
            return $this->errorResponse($request, 'Anda sudah memiliki pengajuan libur pada tanggal '
                . $overlap->tanggal_mulai->translatedFormat('j F Y') . ' - ' . $overlap->tanggal_selesai->translatedFormat('j F Y')
                . ' yang belum ditolak.');
        }

        // Capacity check: ensure no date in the range already reached the daily limit
        $blockedDates = $this->getBlockedDatesByCapacity($tanggalMulai, $tanggalSelesai);
        if (!empty($blockedDates)) {
            $cap = HrdConfig::getLeaveDailyCapacity();
            $msg = 'Tidak dapat mengajukan libur. Kuota maksimal ' . $cap . ' orang per hari telah tercapai pada tanggal: ' . implode(', ', array_map(function ($d) {
                return Carbon::parse($d)->translatedFormat('j F Y');
            }, $blockedDates)) . '.';

            return $this->errorResponse($request, $msg);
        }

        // Balance check, counting days already reserved by requests still in process
        $saldo = $this->getSaldoSummary($employee)[$request->jenis_libur];
        if ($saldo['tersedia'] < $totalHari) {
            $msg = 'Saldo ' . $saldo['label'] . ' tidak mencukupi. Diajukan ' . $totalHari . ' hari, tersedia ' . $saldo['tersedia'] . ' hari';
            if ($saldo['pending'] > 0) {
                $msg .= ' (' . $saldo['pending'] . ' hari sedang menunggu persetujuan)';
            }

            return $this->errorResponse($request, $msg . '.');
        }

        // Manager step goes to the direct superior of the employee's position
        $managerApproval = $this->initialManagerApproval($employee);
        $statusHrd = 'menunggu';
        $tglApproveHrd = null;

        // HRD staff's own request skips the HRD step only once the manager step is already approved
        if ($user->hasRole('Hrd') && $managerApproval['status_manager'] === 'disetujui') {
            $statusHrd = 'disetujui';
            $tglApproveHrd = now();
        }

        try {
            $pengajuan = DB::transaction(function () use ($employee, $request, $totalHari, $managerApproval, $statusHrd, $tglApproveHrd, $hariMasuk) {
                if ($hariMasuk) {
                    // Re-check under the balance lock (same lock as HRD re-pairing) so a worked day is never claimed twice
                    $employee->ensureJatahLibur();
                    JatahLibur::where('employee_id', $employee->id)->lockForUpdate()->first();
                    if (array_diff($hariMasuk, array_keys($this->availableHariMasuk($employee)))) {
                        throw new \DomainException('Hari masuk yang dipilih baru saja dipakai pengajuan lain. Muat ulang dan pilih lagi.');
                    }
                }

                $pengajuan = PengajuanLibur::create(array_merge([
                    'employee_id' => $employee->id,
                    'jenis_libur' => $request->jenis_libur,
                    'tanggal_masuk_pengganti' => $hariMasuk,
                    'tanggal_mulai' => $request->tanggal_mulai,
                    'tanggal_selesai' => $request->tanggal_selesai,
                    'total_hari' => $totalHari,
                    'alasan' => $request->alasan,
                    'status_hrd' => $statusHrd,
                    'tanggal_persetujuan_hrd' => $tglApproveHrd,
                ], $managerApproval));

                // If HRD auto-approved, deduct jatah immediately
                if ($statusHrd === 'disetujui') {
                    $this->deductJatah($pengajuan);
                }

                return $pengajuan;
            });
        } catch (\DomainException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan libur berhasil diajukan.',
                'data' => $pengajuan,
                'saldo' => $this->getSaldoSummary($employee->fresh()),
            ]);
        }

        return redirect()->route('hrd.libur.index')->with('success', 'Pengajuan libur berhasil diajukan.');
    }

    /**
     * Deduct the request's days from the employee's balance. Must run inside a transaction.
     *
     * @throws \DomainException when the balance is insufficient
     */
    private function deductJatah(PengajuanLibur $pengajuan): void
    {
        $pengajuan->employee->ensureJatahLibur();
        $jatah = JatahLibur::where('employee_id', $pengajuan->employee_id)->lockForUpdate()->firstOrFail();
        $column = $pengajuan->jenis_libur == 'cuti_tahunan' ? 'jatah_cuti_tahunan' : 'jatah_ganti_libur';

        if ((int) $jatah->{$column} < (int) $pengajuan->total_hari) {
            throw new \DomainException('Saldo ' . (self::JENIS_LABEL[$pengajuan->jenis_libur] ?? 'libur')
                . ' karyawan tidak mencukupi (sisa ' . (int) $jatah->{$column} . ' hari, dibutuhkan ' . (int) $pengajuan->total_hari . ' hari).');
        }

        $jatah->{$column} = (int) $jatah->{$column} - (int) $pengajuan->total_hari;
        $jatah->save();
    }

    public function persetujuanManager(Request $request, $id)
    {
        $request->validate(array_merge([
            'komentar_manager' => 'nullable|string|max:1000',
            'status' => 'required|in:disetujui,ditolak',
        ], $this->partialApprovalRules(false)));

        $pengajuanLibur = PengajuanLibur::findOrFail($id);

        if (!Auth::user()->employee || !$this->canApproveAsDirectManager(Auth::user()->employee, $pengajuanLibur->employee)) {
            return $this->forbiddenResponse($request, 'Anda bukan atasan langsung untuk pengajuan ini.');
        }

        if ($pengajuanLibur->status_manager !== 'menunggu') {
            return $this->errorResponse($request, 'Pengajuan ini sudah diproses pada approval tingkat 1.');
        }

        $daysBefore = (int) $pengajuanLibur->total_hari;
        try {
            $adjust = $this->trimHariMasuk($pengajuanLibur, $this->buildDateAdjustment($pengajuanLibur, $request));
        } catch (\DomainException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        $pengajuanLibur->update(array_merge([
            'status_manager' => $request->status,
            'notes_manager' => $request->komentar_manager,
            'tanggal_persetujuan_manager' => now(),
        ], $adjust));

        $message = $this->dateApprovalMessage($request, $adjust, $pengajuanLibur, $daysBefore);
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $pengajuanLibur
            ]);
        }

        return redirect()->route('hrd.libur.index')->with('success', $message);
    }

    public function persetujuanHRD(Request $request, $id)
    {
        $request->validate(array_merge([
            'komentar_hrd' => 'nullable|string|max:1000',
            'status' => 'required|in:disetujui,ditolak',
        ], $this->partialApprovalRules(false)));

        if (!$this->isHrdApprover(Auth::user())) {
            return $this->forbiddenResponse($request, 'Hanya HRD yang dapat melakukan approval final.');
        }

        $message = '';
        try {
            $pengajuanLibur = DB::transaction(function () use ($request, $id, &$message) {
                $pengajuanLibur = PengajuanLibur::with('employee')->lockForUpdate()->findOrFail($id);

                if ($pengajuanLibur->status_manager !== 'disetujui') {
                    throw new \DomainException('Approval final HRD hanya bisa dilakukan setelah approval atasan langsung disetujui.');
                }
                if ($pengajuanLibur->status_hrd !== 'menunggu') {
                    throw new \DomainException('Pengajuan ini sudah diproses pada approval final HRD.');
                }

                $daysBefore = (int) $pengajuanLibur->total_hari;
                $adjust = $this->trimHariMasuk($pengajuanLibur, $this->buildDateAdjustment($pengajuanLibur, $request));

                // Apply the (possibly shorter) range first so the balance is cut by the approved days only
                $pengajuanLibur->update(array_merge([
                    'status_hrd' => $request->status,
                    'notes_hrd' => $request->komentar_hrd,
                    'tanggal_persetujuan_hrd' => now(),
                ], $adjust));

                // Jika disetujui oleh HRD, kurangi jatah libur
                if ($request->status == 'disetujui') {
                    $this->deductJatah($pengajuanLibur);
                }

                $message = $this->dateApprovalMessage($request, $adjust, $pengajuanLibur, $daysBefore);

                return $pengajuanLibur;
            });
        } catch (\DomainException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $pengajuanLibur
            ]);
        }

        return redirect()->route('hrd.libur.index')->with('success', $message);
    }

    public function show(Request $request, $id)
    {
        $pengajuanLibur = PengajuanLibur::with('employee')->findOrFail($id);

        if (!$this->canViewPengajuan(Auth::user(), $pengajuanLibur)) {
            return $this->forbiddenResponse($request);
        }

        return view('hrd.libur.show', compact('pengajuanLibur'));
    }

    public function getApprovalStatus(Request $request, $id)
    {
        $pengajuanLibur = PengajuanLibur::findOrFail($id);

        if (!$this->canViewPengajuan(Auth::user(), $pengajuanLibur)) {
            return $this->forbiddenResponse($request);
        }

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
