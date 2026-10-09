<?php

namespace App\Http\Controllers\HRD\Concerns;

use App\Models\HRD\Employee;
use App\Models\HRD\PengajuanLibur;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Shared helpers for the pengajuan (lembur / libur / tidak masuk) controllers.
 * Requires ResolvesDirectManagerApprovals on the using class.
 */
trait HandlesPengajuanApproval
{
    /**
     * Resolve the date filter from the request (default: start of this month - end of next month).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveDateFilter(Request $request): array
    {
        $defaultStart = Carbon::now()->startOfMonth()->startOfDay();
        $defaultEnd = Carbon::now()->addMonthNoOverflow()->endOfMonth()->endOfDay();

        try {
            $start = $request->filled('date_start') ? Carbon::parse($request->input('date_start'))->startOfDay() : $defaultStart;
        } catch (\Exception $e) {
            $start = $defaultStart;
        }

        try {
            $end = $request->filled('date_end') ? Carbon::parse($request->input('date_end'))->endOfDay() : $defaultEnd;
        } catch (\Exception $e) {
            $end = $defaultEnd;
        }

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    protected function isHrdApprover($user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasRole('Hrd') || ($this->hrdIncludesAdmin() && $user->hasRole('Admin'));
    }

    /**
     * Whether Admin users act as HRD for this module. Override per controller.
     */
    protected function hrdIncludesAdmin(): bool
    {
        return false;
    }

    /**
     * Owner, HRD, or the requester's direct manager may view a pengajuan.
     */
    protected function canViewPengajuan($user, $pengajuan): bool
    {
        if (!$user || !$pengajuan) {
            return false;
        }

        if ($this->isHrdApprover($user) || $user->hasRole('Admin')) {
            return true;
        }

        $employee = $user->employee;
        if (!$employee) {
            return false;
        }

        if ((int) $pengajuan->employee_id === (int) $employee->id) {
            return true;
        }

        return $this->canApproveAsDirectManager($employee, $pengajuan->employee);
    }

    protected function forbiddenResponse(Request $request, string $message = 'Anda tidak memiliki akses ke pengajuan ini.')
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        abort(403, $message);
    }

    protected function errorResponse(Request $request, string $message, int $status = 422)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return redirect()->back()->with('error', $message)->withInput();
    }

    protected function renderStatusBadge($row): string
    {
        if ($row->status_hrd == 'disetujui') {
            return '<span class="badge badge-success">Disetujui HRD</span>';
        }
        if ($row->status_hrd == 'ditolak') {
            return '<span class="badge badge-danger">Ditolak HRD</span>';
        }
        if ($row->status_manager == 'disetujui') {
            return '<span class="badge badge-warning">Disetujui Manager</span><div class="small text-muted mt-1">Menunggu HRD</div>';
        }
        if ($row->status_manager == 'ditolak') {
            return '<span class="badge badge-danger">Ditolak Manager</span>';
        }

        return '<span class="badge badge-secondary">Menunggu Persetujuan</span><div class="small text-muted mt-1">Menunggu Manager</div>';
    }

    protected function renderCatatan($row): string
    {
        $out = '';
        if (!empty($row->notes_manager)) {
            $out .= '<div><strong>Manager:</strong> ' . e($row->notes_manager) . '</div>';
        }
        if (!empty($row->notes_hrd)) {
            $out .= '<div><strong>HRD:</strong> ' . e($row->notes_hrd) . '</div>';
        }

        return $out ?: '<span class="text-muted">-</span>';
    }

    protected function renderActionButtons($row, string $detailClass, array $extraButtons = []): string
    {
        $buttons = ['<button type="button" class="btn btn-info ' . $detailClass . '" data-id="' . $row->id . '" title="Detail"><i class="fas fa-eye"></i> Detail</button>'];

        return '<div class="btn-group btn-group-sm" role="group">' . implode('', array_merge($buttons, $extraButtons)) . '</div>';
    }

    /**
     * Put rows that need the current user's action first, newest first within each group.
     */
    protected function sortNeedsActionFirst($rows, callable $needsAction)
    {
        return $rows->sort(function ($a, $b) use ($needsAction) {
            $pa = $needsAction($a) ? 0 : 1;
            $pb = $needsAction($b) ? 0 : 1;

            return $pa === $pb ? ($b->id <=> $a->id) : ($pa <=> $pb);
        })->values();
    }

    /**
     * Validation rules for the optional "approve only part of it" fields.
     */
    protected function partialApprovalRules(bool $isTime): array
    {
        return $isTime
            ? ['jam_mulai_disetujui' => 'nullable|date_format:H:i', 'jam_selesai_disetujui' => 'nullable|date_format:H:i']
            : ['tanggal_mulai_disetujui' => 'nullable|date', 'tanggal_selesai_disetujui' => 'nullable|date'];
    }

    /**
     * Attributes for approving a shorter date range (libur / tidak masuk). The approved range must lie
     * inside the current one; the original request is kept in *_diajukan the first time it is reduced.
     * Returns [] when nothing changes.
     *
     * @throws \DomainException
     */
    protected function buildDateAdjustment($pengajuan, Request $request): array
    {
        if ($request->input('status') !== 'disetujui'
            || (!$request->filled('tanggal_mulai_disetujui') && !$request->filled('tanggal_selesai_disetujui'))) {
            return [];
        }

        $curStart = Carbon::parse($pengajuan->tanggal_mulai)->startOfDay();
        $curEnd = Carbon::parse($pengajuan->tanggal_selesai)->startOfDay();
        $start = $request->filled('tanggal_mulai_disetujui') ? Carbon::parse($request->input('tanggal_mulai_disetujui'))->startOfDay() : $curStart->copy();
        $end = $request->filled('tanggal_selesai_disetujui') ? Carbon::parse($request->input('tanggal_selesai_disetujui'))->startOfDay() : $curEnd->copy();

        if ($end->lt($start)) {
            throw new \DomainException('Tanggal selesai yang disetujui tidak boleh sebelum tanggal mulai yang disetujui.');
        }
        if ($start->lt($curStart) || $end->gt($curEnd)) {
            throw new \DomainException('Tanggal yang disetujui harus berada di dalam rentang pengajuan ('
                . $curStart->translatedFormat('j M Y') . ' - ' . $curEnd->translatedFormat('j M Y') . '). Persetujuan tidak dapat menambah hari.');
        }
        if ($start->eq($curStart) && $end->eq($curEnd)) {
            return [];
        }

        $attrs = [
            'tanggal_mulai' => $start->toDateString(),
            'tanggal_selesai' => $end->toDateString(),
            'total_hari' => $this->inclusiveDayCount($start, $end),
        ];
        if ($pengajuan->tanggal_mulai_diajukan === null) {
            $attrs['tanggal_mulai_diajukan'] = $curStart->toDateString();
            $attrs['tanggal_selesai_diajukan'] = $curEnd->toDateString();
            $attrs['total_hari_diajukan'] = (int) ($pengajuan->total_hari ?: $this->inclusiveDayCount($curStart, $curEnd));
        }

        return $attrs;
    }

    /**
     * Attributes for approving fewer overtime hours. The approved window must lie inside the current one
     * (overnight windows supported); total_jam is recomputed by the model. Returns [] when nothing changes.
     *
     * @throws \DomainException
     */
    protected function buildTimeAdjustment($pengajuan, Request $request): array
    {
        if ($request->input('status') !== 'disetujui'
            || (!$request->filled('jam_mulai_disetujui') && !$request->filled('jam_selesai_disetujui'))) {
            return [];
        }

        $toMinutes = function ($hm) {
            [$h, $m] = array_map('intval', explode(':', substr((string) $hm, 0, 5)));
            return $h * 60 + $m;
        };
        $fmt = fn($hm) => str_replace(':', '.', substr((string) $hm, 0, 5));

        $curStartHm = substr((string) $pengajuan->jam_mulai, 0, 5);
        $curEndHm = substr((string) $pengajuan->jam_selesai, 0, 5);
        $startHm = $request->input('jam_mulai_disetujui') ?: $curStartHm;
        $endHm = $request->input('jam_selesai_disetujui') ?: $curEndHm;

        $curStart = $toMinutes($curStartHm);
        $duration = (($toMinutes($curEndHm) - $curStart + 1440) % 1440) ?: 1440;
        // Offsets from the current start, so windows crossing midnight compare correctly
        $offStart = ($toMinutes($startHm) - $curStart + 1440) % 1440;
        $offEnd = (($toMinutes($endHm) - $curStart + 1440) % 1440) ?: 1440;

        if (!($offStart < $offEnd && $offEnd <= $duration)) {
            throw new \DomainException('Jam yang disetujui harus berada di dalam jam pengajuan (' . $fmt($curStartHm) . ' - ' . $fmt($curEndHm)
                . ') dan jam selesai harus setelah jam mulai. Persetujuan tidak dapat menambah jam.');
        }
        if ($startHm === $curStartHm && $endHm === $curEndHm) {
            return [];
        }

        $attrs = ['jam_mulai' => $startHm, 'jam_selesai' => $endHm];
        if ($pengajuan->jam_mulai_diajukan === null) {
            $attrs['jam_mulai_diajukan'] = $curStartHm;
            $attrs['jam_selesai_diajukan'] = $curEndHm;
            $attrs['total_jam_diajukan'] = $pengajuan->total_jam;
        }

        return $attrs;
    }

    protected function dateApprovalMessage(Request $request, array $adjust, $pengajuan, int $daysBefore): string
    {
        if ($request->input('status') !== 'disetujui') {
            return 'Pengajuan ditolak.';
        }
        if ($adjust) {
            return 'Disetujui sebagian: ' . (int) $pengajuan->total_hari . ' dari ' . $daysBefore . ' hari ('
                . Carbon::parse($pengajuan->tanggal_mulai)->translatedFormat('j M') . ' - '
                . Carbon::parse($pengajuan->tanggal_selesai)->translatedFormat('j M Y') . ').';
        }

        return 'Pengajuan disetujui.';
    }

    /**
     * Small "Diajukan: ..." line shown under the approved value when a request was reduced.
     */
    protected function renderDiajukanDates($row): string
    {
        if ($row->tanggal_mulai_diajukan === null) {
            return '';
        }
        $s = Carbon::parse($row->tanggal_mulai_diajukan)->locale('id')->translatedFormat('j M');
        $e = Carbon::parse($row->tanggal_selesai_diajukan)->locale('id')->translatedFormat('j M Y');

        return '<div class="small text-muted mt-1" title="Disetujui sebagian"><i class="fas fa-cut mr-1"></i>Diajukan: '
            . $s . ' - ' . $e . ' (' . (int) $row->total_hari_diajukan . ' hari)</div>';
    }

    /**
     * Initial manager-step fields for a new pengajuan, based on the position hierarchy (not roles):
     * it waits for the employee's direct superior, and is only auto-approved when the employee's
     * position has no direct superior holder (otherwise nobody could ever approve it).
     */
    protected function initialManagerApproval(Employee $employee): array
    {
        if (!empty($this->getDirectManagerEmployeeIds($employee))) {
            return ['status_manager' => 'menunggu', 'notes_manager' => null, 'tanggal_persetujuan_manager' => null];
        }

        return [
            'status_manager' => 'disetujui',
            'notes_manager' => 'Otomatis disetujui (posisi tidak memiliki atasan langsung)',
            'tanggal_persetujuan_manager' => now(),
        ];
    }

    /**
     * Leave balance per type, with the days already reserved by requests still in process.
     */
    protected function getSaldoSummary(Employee $employee): array
    {
        $jatah = $employee->ensureJatahLibur();

        $pending = PengajuanLibur::where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->whereNull('status_manager')->orWhere('status_manager', '!=', 'ditolak');
            })
            ->where(function ($q) {
                $q->whereNull('status_hrd')->orWhere('status_hrd', 'menunggu');
            })
            ->selectRaw('jenis_libur, COALESCE(SUM(total_hari), 0) as total')
            ->groupBy('jenis_libur')
            ->pluck('total', 'jenis_libur');

        $saldo = (int) $jatah->jatah_cuti_tahunan;
        $reserved = (int) ($pending['cuti_tahunan'] ?? 0);
        // Ganti libur is counted from the schedule: pending requests already claimed their worked days
        $gl = PengajuanLibur::ringkasanGantiLibur($employee->id);

        return [
            'cuti_tahunan' => [
                'label' => 'Cuti Tahunan',
                'saldo' => $saldo,
                'pending' => $reserved,
                'tersedia' => max(0, $saldo - $reserved),
            ],
            'ganti_libur' => [
                'label' => 'Ganti Libur',
                'saldo' => $gl['saldo'] + $gl['diajukan'],
                'pending' => $gl['diajukan'],
                'tersedia' => $gl['saldo'],
            ],
        ];
    }

    /**
     * Marker written to the auto-created PengajuanLibur when a tidak masuk request is cut from annual leave.
     */
    protected function potongCutiMarker(int $tidakMasukId): string
    {
        return 'Otomatis dari Potong Cuti: PTM ID ' . $tidakMasukId;
    }

    /**
     * IDs of tidak masuk requests that were cut from annual leave, parsed from the marker notes.
     */
    protected function potongCutiTidakMasukIds(): array
    {
        return PengajuanLibur::where('notes_hrd', 'like', 'Otomatis dari Potong Cuti: PTM ID %')
            ->pluck('notes_hrd')
            ->map(function ($note) {
                return preg_match('/PTM ID (\d+)$/', (string) $note, $m) ? (int) $m[1] : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function inclusiveDayCount($start, $end): int
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        return max(1, (int) $start->diffInDays($end, true) + 1);
    }
}
