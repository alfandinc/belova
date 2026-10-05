<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HRD\Concerns\HandlesPengajuanApproval;
use App\Http\Controllers\HRD\Concerns\ResolvesDirectManagerApprovals;
use App\Models\HRD\PengajuanLembur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class PengajuanLemburController extends Controller
{
    use ResolvesDirectManagerApprovals;
    use HandlesPengajuanApproval;

    protected function hrdIncludesAdmin(): bool
    {
        return true;
    }

    /**
     * Restrict a query to rows that are waiting for the given approver.
     */
    private function applyNeedsMyAction($query, bool $isHrd, array $subordinateIds)
    {
        return $query->where(function ($q) use ($isHrd, $subordinateIds) {
            $q->where(function ($q) use ($subordinateIds) {
                $q->where('status_manager', 'menunggu')->whereIn('employee_id', $subordinateIds);
            });
            if ($isHrd) {
                $q->orWhere(function ($q) {
                    $q->where('status_manager', 'disetujui')->where('status_hrd', 'menunggu');
                });
            }
        });
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        $isHrd = $this->isHrdApprover($user);
        $subordinateIds = $employee ? $this->getSubordinateEmployeeIds($employee) : [];
        [$filterStart, $filterEnd] = $this->resolveDateFilter($request);

        if ($request->ajax()) {
            if (!$isHrd && !$employee) {
                return DataTables::of(collect())->make(true);
            }

            $query = PengajuanLembur::with('employee');

            // HRD/Admin see everything; others see their own + direct subordinates
            if (!$isHrd) {
                $query->whereIn('employee_id', array_merge($subordinateIds, [$employee->id]));
            }

            // Date-filtered rows, plus anything still waiting for this user's approval
            $query->where(function ($q) use ($filterStart, $filterEnd, $isHrd, $subordinateIds) {
                $q->where(function ($q) use ($filterStart, $filterEnd) {
                    $q->whereDate('tanggal', '>=', $filterStart)->whereDate('tanggal', '<=', $filterEnd);
                });
                $q->orWhere(function ($q) use ($isHrd, $subordinateIds) {
                    $this->applyNeedsMyAction($q, $isHrd, $subordinateIds);
                });
            });

            $canManagerApprove = function ($row) use ($subordinateIds) {
                return $row->status_manager === 'menunggu' && in_array($row->employee_id, $subordinateIds);
            };
            $canHrdApprove = function ($row) use ($isHrd) {
                return $isHrd && $row->status_manager === 'disetujui' && $row->status_hrd === 'menunggu';
            };
            $needsAction = function ($row) use ($canManagerApprove, $canHrdApprove) {
                return $canManagerApprove($row) || $canHrdApprove($row);
            };

            $data = $this->sortNeedsActionFirst($query->get(), $needsAction);

            return DataTables::of($data)
                ->addIndexColumn()
                ->setRowClass(function ($row) use ($needsAction) {
                    return $needsAction($row) ? 'row-needs-action' : '';
                })
                ->addColumn('employee_nama', function ($row) {
                    return e($row->employee->nama ?? '-');
                })
                ->addColumn('tanggal', function ($row) {
                    $tgl = $row->tanggal->locale('id')->translatedFormat('l, j F Y');
                    $mulai = substr((string) $row->jam_mulai, 0, 5);
                    $selesai = substr((string) $row->jam_selesai, 0, 5);
                    $lewatHari = $selesai <= $mulai ? ' <span class="badge badge-light border" title="Selesai keesokan harinya">+1 hari</span>' : '';

                    $diajukan = '';
                    if ($row->jam_mulai_diajukan !== null) {
                        $diajukan = '<div class="small text-muted mt-1" title="Disetujui sebagian"><i class="fas fa-cut mr-1"></i>Diajukan: '
                            . str_replace(':', '.', substr((string) $row->jam_mulai_diajukan, 0, 5)) . ' - '
                            . str_replace(':', '.', substr((string) $row->jam_selesai_diajukan, 0, 5))
                            . ' (' . e($row->total_jam_diajukan_formatted) . ')</div>';
                    }

                    return '<div><i class="fas fa-calendar-alt mr-1 text-muted"></i>' . $tgl . '</div>'
                        . '<div class="mt-1"><i class="fas fa-clock mr-1 text-muted"></i>'
                        . str_replace(':', '.', $mulai) . ' - ' . str_replace(':', '.', $selesai) . $lewatHari
                        . ' <strong>(' . e($row->total_jam_formatted) . ')</strong></div>' . $diajukan;
                })
                // Plain values for the approval modal's "approve fewer hours" inputs
                ->addColumn('jam_mulai_hm', function ($row) {
                    return substr((string) $row->jam_mulai, 0, 5);
                })
                ->addColumn('jam_selesai_hm', function ($row) {
                    return substr((string) $row->jam_selesai, 0, 5);
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
                ->addColumn('action', function ($row) use ($canManagerApprove, $canHrdApprove) {
                    $extra = [];
                    if ($canManagerApprove($row)) {
                        $extra[] = '<button type="button" class="btn btn-warning btn-approve-manager-lembur" data-id="' . $row->id . '"><i class="fas fa-check-circle"></i> Approval</button>';
                    }
                    if ($canHrdApprove($row)) {
                        $extra[] = '<button type="button" class="btn btn-success btn-approve-hrd-lembur" data-id="' . $row->id . '"><i class="fas fa-check-circle"></i> Approval HRD</button>';
                    }

                    return $this->renderActionButtons($row, 'btn-detail-lembur', $extra);
                })
                ->rawColumns(['employee_nama', 'tanggal', 'alasan', 'catatan', 'status_pengajuan', 'action'])
                ->make(true);
        }

        $pendingCount = 0;
        if ($isHrd || !empty($subordinateIds)) {
            $pendingCount = $this->applyNeedsMyAction(PengajuanLembur::query(), $isHrd, $subordinateIds)->count();
        }

        return view('hrd.lembur.index', [
            'canApproveTeam' => !empty($subordinateIds),
            'isHrd' => $isHrd,
            'showEmployeeColumn' => $isHrd || !empty($subordinateIds),
            'hasEmployeeProfile' => (bool) $employee,
            'pendingCount' => $pendingCount,
            'defaultDateStart' => $filterStart->toDateString(),
            'defaultDateEnd' => $filterEnd->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            // jam_selesai earlier than jam_mulai means the overtime ends the next day
            'jam_selesai' => 'required|date_format:H:i|different:jam_mulai',
            'alasan' => 'required|string|max:1000',
        ], [
            'jam_selesai.different' => 'Jam selesai tidak boleh sama dengan jam mulai.',
        ]);

        $employee = Auth::user()->employee;
        if (!$employee) {
            return $this->errorResponse($request, 'Akun Anda belum terhubung dengan data karyawan.');
        }

        // Manager step goes to the direct superior of the employee's position
        $pengajuan = PengajuanLembur::create(array_merge([
            'employee_id' => $employee->id,
            'tanggal' => $request->tanggal,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'alasan' => $request->alasan,
            'status_hrd' => 'menunggu',
        ], $this->initialManagerApproval($employee)));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan lembur berhasil diajukan.',
                'data' => $pengajuan,
            ]);
        }
        return redirect()->route('hrd.lembur.index')->with('success', 'Pengajuan lembur berhasil diajukan.');
    }

    public function persetujuanManager(Request $request, $id)
    {
        $request->validate(array_merge([
            'komentar_manager' => 'nullable|string|max:1000',
            'status' => 'required|in:disetujui,ditolak',
        ], $this->partialApprovalRules(true)));
        $pengajuan = PengajuanLembur::findOrFail($id);

        if (!Auth::user()->employee || !$this->canApproveAsDirectManager(Auth::user()->employee, $pengajuan->employee)) {
            return $this->forbiddenResponse($request, 'Anda bukan atasan langsung untuk pengajuan ini.');
        }

        if ($pengajuan->status_manager !== 'menunggu') {
            return $this->errorResponse($request, 'Pengajuan ini sudah diproses pada approval tingkat 1.');
        }

        return $this->applyApproval($request, $pengajuan, [
            'status_manager' => $request->status,
            'notes_manager' => $request->komentar_manager,
            'tanggal_persetujuan_manager' => now(),
        ]);
    }

    public function persetujuanHRD(Request $request, $id)
    {
        $request->validate(array_merge([
            'komentar_hrd' => 'nullable|string|max:1000',
            'status' => 'required|in:disetujui,ditolak',
        ], $this->partialApprovalRules(true)));

        if (!$this->isHrdApprover(Auth::user())) {
            return $this->forbiddenResponse($request, 'Hanya HRD yang dapat melakukan approval final.');
        }

        $pengajuan = PengajuanLembur::findOrFail($id);

        if ($pengajuan->status_manager !== 'disetujui') {
            return $this->errorResponse($request, 'Approval final HRD hanya bisa dilakukan setelah approval atasan langsung disetujui.');
        }

        if ($pengajuan->status_hrd !== 'menunggu') {
            return $this->errorResponse($request, 'Pengajuan ini sudah diproses pada approval final HRD.');
        }

        return $this->applyApproval($request, $pengajuan, [
            'status_hrd' => $request->status,
            'notes_hrd' => $request->komentar_hrd,
            'tanggal_persetujuan_hrd' => now(),
        ]);
    }

    /**
     * Save an approval decision, optionally approving fewer hours than requested.
     */
    private function applyApproval(Request $request, PengajuanLembur $pengajuan, array $statusAttrs)
    {
        $before = $pengajuan->total_jam_formatted;

        try {
            $adjust = $this->buildTimeAdjustment($pengajuan, $request);
        } catch (\DomainException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        // Model recomputes total_jam from the (possibly reduced) jam_mulai/jam_selesai on save
        $pengajuan->update(array_merge($statusAttrs, $adjust));

        if ($request->status !== 'disetujui') {
            $message = 'Pengajuan lembur ditolak.';
        } elseif ($adjust) {
            $message = 'Disetujui sebagian: ' . $pengajuan->total_jam_formatted . ' dari ' . $before . '.';
        } else {
            $message = 'Pengajuan lembur disetujui.';
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $pengajuan]);
        }
        return redirect()->route('hrd.lembur.index')->with('success', $message);
    }

    public function show(Request $request, $id)
    {
        $pengajuan = PengajuanLembur::with('employee')->findOrFail($id);

        if (!$this->canViewPengajuan(Auth::user(), $pengajuan)) {
            return $this->forbiddenResponse($request);
        }

        return view('hrd.lembur.show', compact('pengajuan'));
    }

    public function getApprovalStatus(Request $request, $id)
    {
        $pengajuan = PengajuanLembur::findOrFail($id);

        if (!$this->canViewPengajuan(Auth::user(), $pengajuan)) {
            return $this->forbiddenResponse($request);
        }

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
