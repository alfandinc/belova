<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HRD\Concerns\HandlesPengajuanApproval;
use App\Http\Controllers\HRD\Concerns\ResolvesDirectManagerApprovals;
use App\Models\HRD\Employee;
use App\Models\HRD\EmployeeSchedule;
use App\Models\HRD\LiburNasional;
use App\Models\HRD\PengajuanGantiShift;
use App\Models\HRD\PengajuanLibur;
use App\Models\HRD\ScheduleLog;
use App\Models\HRD\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

/**
 * Ganti shift (change own shift) and tukar shift (swap with a colleague on the same date).
 *
 * Approval order: [tukar only] rekan/target -> atasan langsung -> HRD. The schedule is changed in
 * exactly one place, at HRD approval, inside the same transaction (it fails loudly instead of
 * reporting success when the schedule no longer matches the request).
 */
class PengajuanGantiShiftController extends Controller
{
    use ResolvesDirectManagerApprovals;
    use HandlesPengajuanApproval;

    private const RELATIONS = ['employee', 'shiftLama', 'shiftBaru', 'targetEmployee'];

    // ---------------------------------------------------------------- helpers

    private function shiftLabel(?Shift $shift): string
    {
        if (!$shift) {
            return 'Libur / tidak ada shift';
        }

        return $shift->name . ' (' . substr((string) $shift->start_time, 0, 5) . '-' . substr((string) $shift->end_time, 0, 5) . ')';
    }

    private function isRejected(PengajuanGantiShift $row): bool
    {
        return $row->status_manager === 'ditolak'
            || $row->status_hrd === 'ditolak'
            || ($row->is_tukar_shift && $row->target_employee_approval_status === 'ditolak');
    }

    private function targetApproved(PengajuanGantiShift $row): bool
    {
        return !$row->is_tukar_shift || $row->target_employee_approval_status === 'disetujui';
    }

    private function needsTargetAction(PengajuanGantiShift $row, ?Employee $me): bool
    {
        return $me && $row->is_tukar_shift
            && (int) $row->target_employee_id === (int) $me->id
            && $row->target_employee_approval_status === 'menunggu'
            && !$this->isRejected($row);
    }

    private function needsManagerAction(PengajuanGantiShift $row, array $subordinateIds): bool
    {
        return $row->status_manager === 'menunggu'
            && in_array($row->employee_id, $subordinateIds)
            && $this->targetApproved($row)
            && !$this->isRejected($row);
    }

    private function needsHrdAction(PengajuanGantiShift $row, bool $isHrd): bool
    {
        return $isHrd
            && $row->status_manager === 'disetujui'
            && $row->status_hrd === 'menunggu'
            && $this->targetApproved($row)
            && !$this->isRejected($row);
    }

    private function renderGantiShiftStatus(PengajuanGantiShift $row): string
    {
        if ($row->is_tukar_shift && $row->target_employee_approval_status === 'ditolak') {
            return '<span class="badge badge-danger">Ditolak Rekan</span>';
        }
        if ($row->status_hrd === 'disetujui') {
            return '<span class="badge badge-success">Disetujui HRD</span><div class="small text-muted mt-1">Jadwal sudah diperbarui</div>';
        }
        if ($row->status_hrd === 'ditolak') {
            return '<span class="badge badge-danger">Ditolak HRD</span>';
        }
        if ($row->status_manager === 'ditolak') {
            return '<span class="badge badge-danger">Ditolak Atasan</span>';
        }
        if ($row->is_tukar_shift && $row->target_employee_approval_status === 'menunggu') {
            return '<span class="badge badge-secondary">Menunggu Rekan</span><div class="small text-muted mt-1">'
                . e($row->targetEmployee->nama ?? '-') . '</div>';
        }
        if ($row->status_manager === 'disetujui') {
            return '<span class="badge badge-warning">Disetujui Atasan</span><div class="small text-muted mt-1">Menunggu HRD</div>';
        }

        return '<span class="badge badge-secondary">Menunggu Persetujuan</span><div class="small text-muted mt-1">Menunggu Atasan</div>';
    }

    /**
     * Giving away a Sunday / national holiday shift makes the employee off that day, which is not allowed
     * once the day is already claimed as hari masuk for a (non-rejected) ganti libur request.
     */
    private function gantiLiburClaimError($employeeId, string $tanggal, int $shiftCountThatDay): ?string
    {
        if ($shiftCountThatDay !== 1 || !LiburNasional::isHariGantiLibur($tanggal)) {
            return null;
        }

        return PengajuanLibur::claimOf($employeeId, $tanggal)
            ? 'Tanggal ini sudah dipakai sebagai hari masuk pengganti pada pengajuan ganti libur, sehingga tidak bisa digantikan rekan.'
            : null;
    }

    private function canViewGantiShift($user, PengajuanGantiShift $p): bool
    {
        $me = $user->employee;
        if ($me && $p->is_tukar_shift && (int) $p->target_employee_id === (int) $me->id) {
            return true;
        }

        return $this->canViewPengajuan($user, $p);
    }

    // ---------------------------------------------------------------- index

    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        $isHrd = $this->isHrdApprover($user);
        $subordinateIds = $employee ? $this->getSubordinateEmployeeIds($employee) : [];
        [$filterStart, $filterEnd] = $this->resolveDateFilter($request);
        $viewType = $request->input('view', 'personal');

        $inRange = function ($q) use ($filterStart, $filterEnd) {
            $q->whereDate('tanggal_shift', '>=', $filterStart)->whereDate('tanggal_shift', '<=', $filterEnd);
        };

        if ($request->ajax()) {
            if ($viewType === 'personal' && $employee) {
                // Own requests, plus swap requests where I am the colleague
                $query = PengajuanGantiShift::where(function ($q) use ($employee) {
                    $q->where('employee_id', $employee->id)->orWhere('target_employee_id', $employee->id);
                })->where(function ($q) use ($inRange, $employee) {
                    $q->where($inRange)->orWhere(function ($q) use ($employee) {
                        $q->where('is_tukar_shift', true)->where('target_employee_id', $employee->id)
                          ->where('target_employee_approval_status', 'menunggu');
                    });
                });
                $needsAction = fn($row) => $this->needsTargetAction($row, $employee);
            } elseif ($viewType === 'team' && !empty($subordinateIds)) {
                $query = PengajuanGantiShift::whereIn('employee_id', $subordinateIds)
                    ->where(function ($q) use ($inRange) {
                        $q->where($inRange)->orWhere('status_manager', 'menunggu');
                    });
                $needsAction = fn($row) => $this->needsManagerAction($row, $subordinateIds);
            } elseif ($viewType === 'approval' && $isHrd) {
                $query = PengajuanGantiShift::where('status_manager', 'disetujui')
                    ->where(function ($q) use ($inRange) {
                        $q->where($inRange)->orWhere('status_hrd', 'menunggu');
                    });
                $needsAction = fn($row) => $this->needsHrdAction($row, $isHrd);
            } else {
                return DataTables::of(collect())->make(true);
            }

            $data = $this->sortNeedsActionFirst($query->with(self::RELATIONS)->get(), $needsAction);

            return DataTables::of($data)
                ->addIndexColumn()
                ->setRowClass(fn($row) => $needsAction($row) ? 'row-needs-action' : '')
                ->addColumn('employee_nama', fn($row) => e($row->employee->nama ?? '-'))
                ->addColumn('tanggal', function ($row) {
                    $baru = $row->isGantikan() ? 'Libur' : $this->shiftLabel($row->shiftBaru);
                    return '<div><i class="fas fa-calendar-alt mr-1 text-muted"></i>' . $row->tanggal_shift->locale('id')->translatedFormat('l, j F Y') . '</div>'
                        . '<div class="mt-1 small"><span class="text-muted">' . e($this->shiftLabel($row->shiftLama)) . '</span>'
                        . ' <i class="fas fa-arrow-right mx-1 text-primary"></i> <strong>' . e($baru) . '</strong></div>';
                })
                ->addColumn('jenis', function ($row) use ($employee) {
                    if (!$row->is_tukar_shift) {
                        return '<span class="badge badge-secondary">Ganti Shift</span>';
                    }
                    if ($row->isGantikan()) {
                        if ($employee && (int) $row->target_employee_id === (int) $employee->id) {
                            return '<span class="badge badge-warning">Permintaan Menggantikan</span><div class="small mt-1">dari: ' . e($row->employee->nama ?? '-') . '</div>';
                        }
                        return '<span class="badge badge-info">Digantikan Rekan</span><div class="small mt-1">oleh: ' . e($row->targetEmployee->nama ?? '-') . '</div>';
                    }
                    if ($employee && (int) $row->target_employee_id === (int) $employee->id) {
                        return '<span class="badge badge-warning">Permintaan Tukar</span><div class="small mt-1">dari: ' . e($row->employee->nama ?? '-') . '</div>';
                    }

                    return '<span class="badge badge-info">Tukar Shift</span><div class="small mt-1">dengan: ' . e($row->targetEmployee->nama ?? '-') . '</div>';
                })
                ->addColumn('alasan', fn($row) => nl2br(e($row->alasan)))
                ->addColumn('status_pengajuan', fn($row) => $this->renderGantiShiftStatus($row))
                ->addColumn('action', function ($row) use ($employee, $subordinateIds, $isHrd, $viewType) {
                    $extra = [];
                    if ($viewType === 'personal' && $this->needsTargetAction($row, $employee)) {
                        $extra[] = '<button type="button" class="btn btn-warning btn-target-approve" data-id="' . $row->id . '"><i class="fas fa-exchange-alt"></i> Tanggapi</button>';
                    }
                    if ($viewType === 'team' && $this->needsManagerAction($row, $subordinateIds)) {
                        $extra[] = '<button type="button" class="btn btn-warning btn-approve-manager" data-id="' . $row->id . '"><i class="fas fa-check-circle"></i> Approval</button>';
                    }
                    if ($viewType === 'approval' && $this->needsHrdAction($row, $isHrd)) {
                        $extra[] = '<button type="button" class="btn btn-success btn-approve-hrd" data-id="' . $row->id . '"><i class="fas fa-check-circle"></i> Approval</button>';
                    }

                    return $this->renderActionButtons($row, 'btn-detail', $extra);
                })
                ->rawColumns(['employee_nama', 'tanggal', 'jenis', 'alasan', 'status_pengajuan', 'action'])
                ->make(true);
        }

        $targetPending = $employee
            ? PengajuanGantiShift::where('is_tukar_shift', true)->where('target_employee_id', $employee->id)
                ->where('target_employee_approval_status', 'menunggu')
                ->where('status_manager', '!=', 'ditolak')->where('status_hrd', '!=', 'ditolak')->count()
            : 0;
        $teamPending = !empty($subordinateIds)
            ? PengajuanGantiShift::whereIn('employee_id', $subordinateIds)->where('status_manager', 'menunggu')
                ->where(function ($q) {
                    $q->where('is_tukar_shift', false)->orWhere('target_employee_approval_status', 'disetujui');
                })->count()
            : 0;
        $hrdPending = $isHrd
            ? PengajuanGantiShift::where('status_manager', 'disetujui')->where('status_hrd', 'menunggu')
                ->where(function ($q) {
                    $q->where('is_tukar_shift', false)->orWhere('target_employee_approval_status', 'disetujui');
                })->count()
            : 0;

        return view('hrd.gantishift.index', [
            'viewType' => in_array($viewType, ['personal', 'team', 'approval'], true) ? $viewType : 'personal',
            'canApproveTeam' => !empty($subordinateIds),
            'isHrd' => $isHrd,
            'hasEmployeeProfile' => (bool) $employee,
            'targetPending' => $targetPending,
            'teamPending' => $teamPending,
            'hrdPending' => $hrdPending,
            'defaultDateStart' => $filterStart->toDateString(),
            'defaultDateEnd' => $filterEnd->toDateString(),
        ]);
    }

    // ---------------------------------------------------------------- create

    public function store(Request $request)
    {
        $isGantikan = $request->input('jenis') === 'gantikan';
        $isTukar = $isGantikan || $request->boolean('is_tukar_shift');
        $request->validate([
            // any date allowed (past dates included, per earlier request)
            'tanggal_shift' => 'required|date',
            'shift_lama_id' => 'nullable|exists:hrd_shifts,id',
            'shift_baru_id' => ($isGantikan ? 'nullable' : 'required') . '|exists:hrd_shifts,id,active,1',
            'alasan' => 'required|string|max:1000',
            'is_tukar_shift' => 'nullable|boolean',
            'target_employee_id' => ($isTukar ? 'required' : 'nullable') . '|exists:hrd_employee,id',
        ], [
            'shift_baru_id.exists' => 'Shift baru tidak ditemukan atau sudah tidak aktif.',
            'target_employee_id.required' => 'Pilih rekan yang akan ditukar shiftnya.',
        ]);

        $employee = Auth::user()->employee;
        if (!$employee) {
            return $this->errorResponse($request, 'Akun Anda belum terhubung dengan data karyawan.');
        }

        $tanggal = Carbon::parse($request->tanggal_shift)->toDateString();

        // One open request per employee per date (a rejected one does not block a new request)
        $existing = PengajuanGantiShift::where('employee_id', $employee->id)
            ->whereDate('tanggal_shift', $tanggal)
            ->where('status_manager', '!=', 'ditolak')
            ->where('status_hrd', '!=', 'ditolak')
            ->where(function ($q) {
                $q->where('is_tukar_shift', false)->orWhere('target_employee_approval_status', '!=', 'ditolak');
            })
            ->exists();
        if ($existing) {
            return $this->errorResponse($request, 'Anda sudah memiliki pengajuan ganti shift untuk tanggal tersebut yang belum ditolak.');
        }

        // Which current shift is being replaced (a day can have up to 2 shifts)
        $currentShiftIds = EmployeeSchedule::where('employee_id', $employee->id)->whereDate('date', $tanggal)->pluck('shift_id')->all();
        $shiftLamaId = $request->filled('shift_lama_id') ? (int) $request->shift_lama_id : (count($currentShiftIds) === 1 ? (int) $currentShiftIds[0] : null);

        if (count($currentShiftIds) > 1 && !$shiftLamaId) {
            return $this->errorResponse($request, 'Anda memiliki 2 shift pada tanggal ini. Pilih shift yang ingin diganti.');
        }
        if ($shiftLamaId && !in_array($shiftLamaId, array_map('intval', $currentShiftIds), true)) {
            return $this->errorResponse($request, 'Shift yang ingin diganti tidak ada di jadwal Anda pada tanggal ini.');
        }

        if ($isGantikan) {
            if (!$shiftLamaId) {
                return $this->errorResponse($request, 'Digantikan rekan hanya bisa dilakukan jika Anda memiliki jadwal pada tanggal tersebut.');
            }
            if ((int) $request->target_employee_id === (int) $employee->id) {
                return $this->errorResponse($request, 'Tidak dapat digantikan oleh diri sendiri.');
            }
            if (EmployeeSchedule::where('employee_id', $request->target_employee_id)->whereDate('date', $tanggal)->exists()) {
                return $this->errorResponse($request, 'Rekan yang dipilih sudah memiliki jadwal di tanggal ini. Gunakan Tukar Shift.');
            }
            if ($error = $this->gantiLiburClaimError($employee->id, $tanggal, count($currentShiftIds))) {
                return $this->errorResponse($request, $error);
            }
            // The shift handed over; see PengajuanGantiShift::isGantikan()
            $request->merge(['shift_baru_id' => $shiftLamaId]);
        } elseif ($shiftLamaId === (int) $request->shift_baru_id) {
            return $this->errorResponse($request, 'Shift baru sama dengan shift saat ini.');
        } elseif (in_array((int) $request->shift_baru_id, array_map('intval', $currentShiftIds), true)) {
            return $this->errorResponse($request, 'Anda sudah terjadwal pada shift tersebut di tanggal ini.');
        }

        if ($isTukar && !$isGantikan) {
            if (!$shiftLamaId) {
                return $this->errorResponse($request, 'Tukar shift hanya bisa dilakukan jika Anda memiliki jadwal pada tanggal tersebut.');
            }
            if ((int) $request->target_employee_id === (int) $employee->id) {
                return $this->errorResponse($request, 'Tidak dapat menukar shift dengan diri sendiri.');
            }
            $targetHasShift = EmployeeSchedule::where('employee_id', $request->target_employee_id)
                ->whereDate('date', $tanggal)->where('shift_id', $request->shift_baru_id)->exists();
            if (!$targetHasShift) {
                return $this->errorResponse($request, 'Rekan yang dipilih tidak terjadwal pada shift tersebut di tanggal ini.');
            }
        }

        $data = array_merge([
            'employee_id' => $employee->id,
            'tanggal_shift' => $tanggal,
            'shift_lama_id' => $shiftLamaId,
            'shift_baru_id' => $request->shift_baru_id,
            'alasan' => $request->alasan,
            'is_tukar_shift' => $isTukar,
            'target_employee_id' => $isTukar ? $request->target_employee_id : null,
            'target_employee_approval_status' => 'menunggu',
            'status_hrd' => 'menunggu',
        ], $this->initialManagerApproval($employee));

        $pengajuan = PengajuanGantiShift::create($data);

        $message = $isTukar
            ? 'Pengajuan ' . ($isGantikan ? 'digantikan rekan' : 'tukar shift') . ' berhasil diajukan. Menunggu persetujuan rekan Anda.'
            : 'Pengajuan ganti shift berhasil diajukan.';

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $pengajuan]);
        }
        return redirect()->route('hrd.gantishift.index')->with('success', $message);
    }

    // ---------------------------------------------------------------- approvals

    public function targetEmployeeApproval(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'notes' => 'nullable|string|max:1000',
        ]);

        $me = Auth::user()->employee;
        $pengajuan = PengajuanGantiShift::with(self::RELATIONS)->findOrFail($id);

        if (!$me || !$pengajuan->is_tukar_shift || (int) $pengajuan->target_employee_id !== (int) $me->id) {
            return $this->forbiddenResponse($request, 'Anda bukan rekan yang diminta untuk tukar shift ini.');
        }
        if (!$this->needsTargetAction($pengajuan, $me)) {
            return $this->errorResponse($request, 'Permintaan tukar shift ini sudah diproses.');
        }

        $pengajuan->update([
            'target_employee_approval_status' => $request->status,
            'target_employee_notes' => $request->notes,
            'target_employee_approval_date' => now(),
        ]);

        $message = $request->status === 'disetujui'
            ? 'Anda menyetujui tukar shift. Menunggu persetujuan atasan dan HRD.'
            : 'Anda menolak permintaan tukar shift.';

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()->route('hrd.gantishift.index')->with('success', $message);
    }

    public function persetujuanManager(Request $request, $id)
    {
        $request->validate([
            'komentar_manager' => 'nullable|string|max:1000',
            'status' => 'required|in:disetujui,ditolak',
        ]);

        $pengajuan = PengajuanGantiShift::with(self::RELATIONS)->findOrFail($id);
        $me = Auth::user()->employee;

        if (!$me || !$this->canApproveAsDirectManager($me, $pengajuan->employee)) {
            return $this->forbiddenResponse($request, 'Anda bukan atasan langsung untuk pengajuan ini.');
        }
        if ($pengajuan->status_manager !== 'menunggu' || $this->isRejected($pengajuan)) {
            return $this->errorResponse($request, 'Pengajuan ini sudah diproses pada approval atasan.');
        }
        if (!$this->targetApproved($pengajuan)) {
            return $this->errorResponse($request, 'Tukar shift ini masih menunggu persetujuan rekan (' . ($pengajuan->targetEmployee->nama ?? '-') . ').');
        }

        $pengajuan->update([
            'status_manager' => $request->status,
            'notes_manager' => $request->komentar_manager,
            'tanggal_persetujuan_manager' => now(),
        ]);

        $message = $request->status === 'disetujui'
            ? 'Pengajuan disetujui. Menunggu persetujuan HRD.'
            : 'Pengajuan ditolak.';

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $pengajuan]);
        }
        return redirect()->route('hrd.gantishift.index')->with('success', $message);
    }

    public function persetujuanHRD(Request $request, $id)
    {
        $request->validate([
            'komentar_hrd' => 'nullable|string|max:1000',
            'status' => 'required|in:disetujui,ditolak',
        ]);

        if (!$this->isHrdApprover(Auth::user())) {
            return $this->forbiddenResponse($request, 'Hanya HRD yang dapat melakukan approval final.');
        }

        try {
            $message = DB::transaction(function () use ($request, $id) {
                $pengajuan = PengajuanGantiShift::with(self::RELATIONS)->lockForUpdate()->findOrFail($id);

                if ($pengajuan->status_manager !== 'disetujui') {
                    throw new \DomainException('Approval HRD hanya bisa dilakukan setelah disetujui atasan langsung.');
                }
                if ($pengajuan->status_hrd !== 'menunggu' || $this->isRejected($pengajuan)) {
                    throw new \DomainException('Pengajuan ini sudah diproses pada approval HRD.');
                }
                if (!$this->targetApproved($pengajuan)) {
                    throw new \DomainException('Tukar shift ini masih menunggu persetujuan rekan.');
                }

                $pengajuan->update([
                    'status_hrd' => $request->status,
                    'notes_hrd' => $request->komentar_hrd,
                    'tanggal_persetujuan_hrd' => now(),
                ]);

                if ($request->status !== 'disetujui') {
                    return 'Pengajuan ditolak.';
                }

                // Rolls back the approval too if the schedule cannot be applied; both days go to the audit log
                $tanggal = $pengajuan->tanggal_shift->toDateString();
                $people = array_filter([$pengajuan->employee_id, $pengajuan->target_employee_id]);
                $before = [];
                foreach ($people as $employeeId) {
                    $before[$employeeId] = ScheduleLog::stateOf($employeeId, $tanggal);
                }
                $message = $this->applySchedule($pengajuan);
                foreach ($people as $employeeId) {
                    ScheduleLog::record($employeeId, $tanggal, $before[$employeeId], ScheduleLog::stateOf($employeeId, $tanggal), 'ganti_shift');
                }

                return $message;
            });
        } catch (\DomainException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()->route('hrd.gantishift.index')->with('success', $message);
    }

    /**
     * Apply an approved request to the schedule. Must run inside a transaction.
     *
     * @throws \DomainException when the schedule no longer matches the request
     */
    private function applySchedule(PengajuanGantiShift $p): string
    {
        $tanggal = $p->tanggal_shift->toDateString();
        $changed = 'Jadwal karyawan pada ' . $p->tanggal_shift->translatedFormat('j F Y') . ' sudah berubah sejak pengajuan dibuat';

        if ($p->isGantikan()) {
            $mine = EmployeeSchedule::where('employee_id', $p->employee_id)->whereDate('date', $tanggal)
                ->where('shift_id', $p->shift_lama_id)->lockForUpdate()->first();
            if (!$mine) {
                throw new \DomainException($changed . ' (shift yang akan digantikan tidak ditemukan). Tolak pengajuan ini dan minta karyawan mengajukan ulang.');
            }
            if (EmployeeSchedule::where('employee_id', $p->target_employee_id)->whereDate('date', $tanggal)->exists()) {
                throw new \DomainException(($p->targetEmployee->nama ?? 'Rekan') . ' sekarang sudah memiliki jadwal di tanggal ini. Tolak pengajuan ini dan gunakan Tukar Shift.');
            }
            $myShiftCount = EmployeeSchedule::where('employee_id', $p->employee_id)->whereDate('date', $tanggal)->count();
            if ($error = $this->gantiLiburClaimError($p->employee_id, $tanggal, $myShiftCount)) {
                throw new \DomainException($error);
            }

            // The colleague takes over my shift row; I am off unless I still have a second shift
            $mine->update(['employee_id' => $p->target_employee_id]);

            // Sunday / national holiday: the ganti libur balance follows the schedule, so it moves to the colleague by itself
            $jatahNote = LiburNasional::isHariGantiLibur($tanggal)
                ? ' Hari masuk ini sekarang dihitung untuk ganti libur ' . ($p->targetEmployee->nama ?? 'rekan') . '.'
                : '';

            Log::info('Ganti shift: digantikan rekan applied', ['pengajuan_id' => $p->id, 'date' => $tanggal,
                'employee_id' => $p->employee_id, 'target_employee_id' => $p->target_employee_id, 'shift_id' => $p->shift_lama_id]);

            return 'Pengajuan disetujui. ' . ($p->targetEmployee->nama ?? '-') . ' menggantikan ' . ($p->employee->nama ?? '-')
                . ' pada ' . $this->shiftLabel($p->shiftLama) . '.' . $jatahNote;
        }

        if ($p->is_tukar_shift) {
            $mine = EmployeeSchedule::where('employee_id', $p->employee_id)->whereDate('date', $tanggal)
                ->where('shift_id', $p->shift_lama_id)->lockForUpdate()->first();
            $theirs = EmployeeSchedule::where('employee_id', $p->target_employee_id)->whereDate('date', $tanggal)
                ->where('shift_id', $p->shift_baru_id)->lockForUpdate()->first();
            if (!$mine || !$theirs) {
                throw new \DomainException($changed . ' (shift yang akan ditukar tidak ditemukan). Tolak pengajuan ini dan minta karyawan mengajukan ulang.');
            }

            $mine->update(['shift_id' => $p->shift_baru_id]);
            $theirs->update(['shift_id' => $p->shift_lama_id]);

            Log::info('Ganti shift: tukar shift applied', ['pengajuan_id' => $p->id, 'date' => $tanggal,
                'employee_id' => $p->employee_id, 'target_employee_id' => $p->target_employee_id]);

            return 'Tukar shift disetujui. Jadwal ' . ($p->employee->nama ?? '-') . ' dan ' . ($p->targetEmployee->nama ?? '-') . ' sudah ditukar.';
        }

        $alreadyHasNew = EmployeeSchedule::where('employee_id', $p->employee_id)->whereDate('date', $tanggal)
            ->where('shift_id', $p->shift_baru_id)->exists();
        if ($alreadyHasNew) {
            throw new \DomainException($changed . ' (karyawan sudah terjadwal pada shift baru).');
        }

        if ($p->shift_lama_id) {
            $row = EmployeeSchedule::where('employee_id', $p->employee_id)->whereDate('date', $tanggal)
                ->where('shift_id', $p->shift_lama_id)->lockForUpdate()->first();
            if (!$row) {
                throw new \DomainException($changed . ' (shift lama tidak ditemukan). Tolak pengajuan ini dan minta karyawan mengajukan ulang.');
            }
            $row->update(['shift_id' => $p->shift_baru_id]);
        } else {
            if (EmployeeSchedule::where('employee_id', $p->employee_id)->whereDate('date', $tanggal)->exists()) {
                throw new \DomainException($changed . ' (karyawan sekarang sudah memiliki jadwal di tanggal ini).');
            }
            EmployeeSchedule::create(['employee_id' => $p->employee_id, 'shift_id' => $p->shift_baru_id, 'date' => $tanggal]);
        }

        Log::info('Ganti shift: schedule updated', ['pengajuan_id' => $p->id, 'date' => $tanggal,
            'employee_id' => $p->employee_id, 'shift_lama_id' => $p->shift_lama_id, 'shift_baru_id' => $p->shift_baru_id]);

        return 'Pengajuan disetujui. Jadwal sudah diperbarui ke ' . $this->shiftLabel($p->shiftBaru) . '.';
    }

    // ---------------------------------------------------------------- read

    public function show(Request $request, $id)
    {
        $pengajuan = PengajuanGantiShift::with(self::RELATIONS)->findOrFail($id);

        if (!$this->canViewGantiShift(Auth::user(), $pengajuan)) {
            return $this->forbiddenResponse($request);
        }

        $currentShifts = EmployeeSchedule::with('shift')
            ->where('employee_id', $pengajuan->employee_id)
            ->whereDate('date', $pengajuan->tanggal_shift)
            ->get()
            ->map(fn($s) => $this->shiftLabel($s->shift));

        return view('hrd.gantishift.show', [
            'pengajuan' => $pengajuan,
            'shiftLama' => $this->shiftLabel($pengajuan->shiftLama),
            'shiftBaru' => $pengajuan->isGantikan() ? 'Libur' : $this->shiftLabel($pengajuan->shiftBaru),
            'currentShifts' => $currentShifts,
        ]);
    }

    public function getApprovalStatus(Request $request, $id)
    {
        $pengajuan = PengajuanGantiShift::findOrFail($id);

        if (!$this->canViewGantiShift(Auth::user(), $pengajuan)) {
            return $this->forbiddenResponse($request);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status_manager' => $pengajuan->status_manager,
                'notes_manager' => $pengajuan->notes_manager,
                'status_hrd' => $pengajuan->status_hrd,
                'notes_hrd' => $pengajuan->notes_hrd,
                'target_employee_approval_status' => $pengajuan->target_employee_approval_status,
            ]
        ]);
    }

    /**
     * AJAX for the create form: active shifts, and the current user's shift(s) on the date.
     */
    public function getAvailableShifts(Request $request)
    {
        $request->validate(['date' => 'required|date']);

        $employee = Auth::user()->employee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Akun Anda belum terhubung dengan data karyawan.'], 422);
        }

        $format = fn(Shift $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'start_time' => substr((string) $s->start_time, 0, 5),
            'end_time' => substr((string) $s->end_time, 0, 5),
            'label' => $this->shiftLabel($s),
        ];

        $current = EmployeeSchedule::with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('date', $request->input('date'))
            ->get()
            ->filter(fn($s) => $s->shift)
            ->map(fn($s) => $format($s->shift))
            ->values();

        return response()->json([
            'success' => true,
            'shifts' => Shift::where('active', true)->orderBy('start_time')->get()->map($format)->values(),
            'current_shifts' => $current,
            'is_hari_ganti_libur' => LiburNasional::isHariGantiLibur($request->input('date')),
            // kept for backward compatibility
            'current_shift_id' => $current->first()['id'] ?? null,
        ]);
    }

    /**
     * AJAX for tukar shift: colleagues scheduled on $shift_id at $date.
     * With mode=libur (digantikan rekan): active colleagues with no schedule at all on $date.
     */
    public function getEmployeesSameShift(Request $request)
    {
        $offMode = $request->input('mode') === 'libur';
        $request->validate(['date' => 'required|date', 'shift_id' => ($offMode ? 'nullable' : 'required') . '|integer']);

        $employee = Auth::user()->employee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Akun Anda belum terhubung dengan data karyawan.'], 422);
        }

        if ($offMode) {
            $scheduledIds = EmployeeSchedule::whereDate('date', $request->input('date'))->pluck('employee_id')->unique();
            $employees = Employee::active()
                ->where('id', '!=', $employee->id)
                ->whereNotIn('id', $scheduledIds)
                ->with('position')
                ->orderBy('nama')
                ->get()
                ->map(fn($e) => ['id' => $e->id, 'name' => $e->nama, 'position' => $e->position->name ?? ''])
                ->values();

            return response()->json(['success' => true, 'employees' => $employees]);
        }

        $employees = EmployeeSchedule::whereDate('date', $request->input('date'))
            ->where('shift_id', $request->input('shift_id'))
            ->where('employee_id', '!=', $employee->id)
            ->whereHas('employee', fn($q) => $q->active())
            ->with('employee.position')
            ->get()
            ->unique('employee_id')
            ->map(fn($s) => [
                'id' => $s->employee_id,
                'name' => $s->employee->nama,
                'position' => $s->employee->position->name ?? '',
            ])
            ->sortBy('name')
            ->values();

        return response()->json(['success' => true, 'employees' => $employees]);
    }
}
