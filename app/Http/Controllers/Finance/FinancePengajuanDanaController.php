<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\FinancePengajuanDana;
use App\Models\Finance\FinancePengajuanDanaItem;
use App\Models\Finance\FinancePengajuanDanaApproval;
use App\Models\Finance\FinanceDanaApprover;
use App\Models\ERM\FakturBeli;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class FinancePengajuanDanaController extends Controller
{
    /**
     * Internal helper: perform approval checks and create approval record.
     * Returns [bool success, string message]. Does not check user auth/jenis matching.
     */
    private function attemptApprovePengajuan(FinancePengajuanDana $pengajuan, FinanceDanaApprover $approver): array
    {
        $pengajuanSumber = $pengajuan->sumber_dana ?? '';

        // Check sequencing: higher tingkat must have at least one approval, and no higher-level decline
        $approverTingkat = intval($approver->tingkat ?: 1);
        $higherTingkatValues = FinanceDanaApprover::where('aktif', 1)
            ->where(function($q) use ($pengajuanSumber) {
                if ($pengajuanSumber && trim($pengajuanSumber) !== '') {
                    $q->whereNull('jenis')->orWhere('jenis', '')->orWhere('jenis', $pengajuanSumber);
                } else {
                    $q->whereNull('jenis')->orWhere('jenis', '');
                }
            })
            ->where('tingkat', '>', $approverTingkat)
            ->distinct()
            ->pluck('tingkat')
            ->toArray();

        $higherApproverIds = FinanceDanaApprover::where('aktif', 1)
            ->where(function($q) use ($pengajuanSumber) {
                if ($pengajuanSumber && trim($pengajuanSumber) !== '') {
                    $q->whereNull('jenis')->orWhere('jenis', '')->orWhere('jenis', $pengajuanSumber);
                } else {
                    $q->whereNull('jenis')->orWhere('jenis', '');
                }
            })
            ->whereIn('tingkat', $higherTingkatValues)
            ->pluck('id')
            ->toArray();

        if (!empty($higherApproverIds)) {
            $higherDeclined = FinancePengajuanDanaApproval::where('pengajuan_id', $pengajuan->id)
                ->whereIn('approver_id', $higherApproverIds)
                ->where('status', 'declined')
                ->exists();
            if ($higherDeclined) {
                return [false, 'Pengajuan telah ditolak pada tingkat lebih tinggi.'];
            }
        }

        foreach ($higherTingkatValues as $ht) {
            $approverIdsAtLevel = FinanceDanaApprover::where('aktif', 1)
                ->where(function($q) use ($pengajuanSumber) {
                    if ($pengajuanSumber && trim($pengajuanSumber) !== '') {
                        $q->whereNull('jenis')->orWhere('jenis', '')->orWhere('jenis', $pengajuanSumber);
                    } else {
                        $q->whereNull('jenis')->orWhere('jenis', '');
                    }
                })
                ->where('tingkat', $ht)
                ->pluck('id')
                ->toArray();

            if (empty($approverIdsAtLevel)) continue;

            $hasAnyApproved = FinancePengajuanDanaApproval::where('pengajuan_id', $pengajuan->id)
                ->whereIn('approver_id', $approverIdsAtLevel)
                ->exists();

            if (!$hasAnyApproved) {
                return [false, 'Awaiting approval from higher level approver(s).'];
            }
        }

        // already approved by this approver?
        $existing = FinancePengajuanDanaApproval::where('pengajuan_id', $pengajuan->id)
            ->where('approver_id', $approver->id)
            ->first();
        if ($existing) {
            return [false, 'Already approved by you'];
        }

        FinancePengajuanDanaApproval::create([
            'pengajuan_id' => $pengajuan->id,
            'approver_id' => $approver->id,
            'status' => 'approved',
            'tanggal_approve' => Carbon::now(),
        ]);

        return [true, 'Approved'];
    }
    public function index()
    {
        return view('finance.pengajuan.index');
    }

    // Generate a kode_pengajuan (simple server-side generator)
    public function generateKode()
    {
        // Example format: PJYYYYMMDD0001
        $date = date('Ymd');
        $maxId = FinancePengajuanDana::max('id') ?? 0;
        $next = $maxId + 1;
        $kode = sprintf('PJ%s%04d', $date, $next);
        return response()->json(['kode' => $kode]);
    }

    public function data(Request $request)
    {
        $query = FinancePengajuanDana::with(['employee.user', 'division', 'approvals.approver.user', 'rekening']);
        // apply optional date range filter (tanggal_pengajuan)
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate || $endDate) {
            try {
                $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
                $end = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
                if ($start && $end) {
                    $query->whereBetween('tanggal_pengajuan', [$start, $end]);
                } elseif ($start) {
                    $query->where('tanggal_pengajuan', '>=', $start);
                } elseif ($end) {
                    $query->where('tanggal_pengajuan', '<=', $end);
                }
            } catch (\Exception $e) {
                // if parsing fails, ignore the filter
            }
        }
        // jenis (type) filter: empty or null means show all
        $jenis = $request->input('jenis', null);
        if ($jenis !== null && trim($jenis) !== '') {
            $query->where('jenis_pengajuan', trim($jenis));
        }
        // sumber_dana filter: empty or null means show all
        $sumber = $request->input('sumber_dana', null);
        if ($sumber !== null && trim($sumber) !== '') {
            $query->where('sumber_dana', trim($sumber));
        }
        // Approval status filter: accepted values: 'approved', 'menunggu' (pending), 'declined'
        $approvalStatus = $request->input('approval_status', 'menunggu');
        // build correlated subqueries for level counts and declined checks
        $totalLevelsSql = "(SELECT COUNT(DISTINCT fda.tingkat) FROM finance_dana_approver fda WHERE fda.aktif = 1 AND ( (fda.jenis IS NULL OR fda.jenis = '') OR fda.jenis = finance_pengajuan_dana.sumber_dana ))";
        $approvedLevelsSql = "(SELECT COUNT(DISTINCT fda.tingkat) FROM finance_dana_approver fda JOIN finance_pengajuan_dana_approval ap ON ap.approver_id = fda.id AND ap.pengajuan_id = finance_pengajuan_dana.id AND ap.status = 'approved' WHERE fda.aktif = 1 AND ( (fda.jenis IS NULL OR fda.jenis = '') OR fda.jenis = finance_pengajuan_dana.sumber_dana ))";
        $declinedExistsSql = "(SELECT 1 FROM finance_pengajuan_dana_approval ap2 WHERE ap2.pengajuan_id = finance_pengajuan_dana.id AND (ap2.status = 'declined' OR ap2.status = 'rejected') LIMIT 1)";

        if ($approvalStatus === 'declined') {
            // only those with at least one declined approval
            $query->whereRaw("EXISTS {$declinedExistsSql}");
        } elseif ($approvalStatus === 'approved') {
            // fully approved: have at least one approver level configured and approved levels >= total levels and no decline
            $query->whereRaw("{$totalLevelsSql} > 0 AND {$approvedLevelsSql} >= {$totalLevelsSql} AND NOT EXISTS {$declinedExistsSql}");
        } else {
            // pending (menunggu): not declined and not fully approved
            $query->whereRaw("NOT EXISTS {$declinedExistsSql} AND NOT ({$totalLevelsSql} > 0 AND {$approvedLevelsSql} >= {$totalLevelsSql})");
        }
        $currentUser = Auth::user();
        $currentApprover = null;
        if ($currentUser) {
            $currentApprover = FinanceDanaApprover::where('user_id', $currentUser->id)->first();
        }

        return DataTables::of($query)
            ->filter(function($q) use ($request) {
                // global search: allow matching kode, perusahaan, sumber_dana,
                // employee name (user.name or employee.nama), division.name, and item.nama_item
                $search = $request->input('search.value');
                if ($search && trim($search) !== '') {
                    $s = trim($search);
                    $q->where(function($qq) use ($s) {
                        $qq->where('kode_pengajuan', 'like', "%{$s}%")
                           ->orWhere('perusahaan', 'like', "%{$s}%")
                           ->orWhere('sumber_dana', 'like', "%{$s}%")
                           ->orWhereHas('employee', function($qe) use ($s) {
                               $qe->whereHas('user', function($qu) use ($s) {
                                   $qu->where('name', 'like', "%{$s}%");
                               })->orWhere('nama', 'like', "%{$s}%");
                           })
                           ->orWhereHas('division', function($qd) use ($s) {
                               $qd->where('name', 'like', "%{$s}%");
                           })
                           // Search Rekening fields: bank, no_rekening, atas_nama
                           ->orWhereHas('rekening', function($qr) use ($s) {
                               $qr->where('bank', 'like', "%{$s}%")
                                  ->orWhere('no_rekening', 'like', "%{$s}%")
                                  ->orWhere('atas_nama', 'like', "%{$s}%");
                           })
                           ->orWhereHas('items', function($qi) use ($s) {
                               $qi->where('nama_item', 'like', "%{$s}%");
                           });
                    });
                }
            })
            ->addColumn('employee_display', function($row) {
                // show employee name and division under it
                $name = '';
                if ($row->employee) {
                    if ($row->employee->user) $name = $row->employee->user->name;
                    else $name = $row->employee->nama ?? '';
                }
                $division = $row->division ? $row->division->name : '';
                $html = '<div class="employee-display">' . '<div>' . e($name) . '</div>';
                if ($division) {
                    $html .= '<div><small class="text-muted">' . e($division) . '</small></div>';
                }
                $html .= '</div>';
                return $html;
            })

            ->addColumn('diajukan_ke', function($row) {
                $s = trim($row->sumber_dana ?? '');
                $p = trim($row->perusahaan ?? '');
                $parts = [];
                if ($s !== '') {
                    $parts[] = '<div><strong>' . e($s) . '</strong></div>';
                }
                if ($p !== '') {
                    // choose badge color per perusahaan
                    $label = e($p);
                    $lc = strtolower($p);
                    $badgeStyle = '';
                    if (strpos($lc, 'belia') !== false) {
                        // pink
                        $badgeStyle = 'background-color:#ff69b4;color:#fff;';
                    } elseif (strpos($lc, 'belova') !== false) {
                        // blue (bootstrap primary)
                        $badgeStyle = 'background-color:#007bff;color:#fff;';
                    } elseif (strpos($lc, 'grha') !== false) {
                        // orange
                        $badgeStyle = 'background-color:#fd7e14;color:#fff;';
                    } else {
                        $badgeStyle = 'background-color:#6c757d;color:#fff;';
                    }
                    $parts[] = '<div><small><span class="badge" style="' . $badgeStyle . '">' . $label . '</span></small></div>';
                }
                return implode('', $parts);
            })

            ->addColumn('items_list', function($row) {
                // render a compact list of items (name and qty)
                $parts = [];
                foreach ($row->items as $it) {
                    $itemName = $it->nama_item ?? ($it->name ?? '');
                    $qty = $it->jumlah ?? ($it->qty ?? 0);
                    $parts[] = '<div class="item-line"><div>' . e($itemName) . ' <small class="text-muted">x' . e($qty) . '</small></div></div>';
                }
                return implode('', $parts);
            })

            ->addColumn('actions', function ($row) {
                $btns = '<div class="btn-group" role="group">';

                // PDF/Print button - opens PDF in new tab (text label)
                $btns .= '<a class="btn btn-sm btn-secondary" href="/finance/pengajuan-dana/' . $row->id . '/pdf" target="_blank" title="Cetak PDF">PDF</a>';

                // Only show edit/delete to the employee who created the pengajuan
                $user = Auth::user();
                $currentEmployeeId = null;
                if ($user && isset($user->employee) && $user->employee) {
                    $currentEmployeeId = $user->employee->id;
                }

                    if ($currentEmployeeId !== null && $row->employee_id == $currentEmployeeId) {
                        $btns .= '<button class="btn btn-sm btn-primary edit-pengajuan" data-id="' . $row->id . '" title="Edit"><i class="fa fa-edit"></i></button>';
                        $btns .= '<button class="btn btn-sm btn-danger delete-pengajuan" data-id="' . $row->id . '" title="Delete"><i class="fa fa-trash"></i></button>';
                    }

                    // Upload bukti button: visible to everyone (icon)
                    $btns .= '<button class="btn btn-sm btn-outline-secondary upload-bukti ms-1" data-id="' . $row->id . '" title="Upload Bukti"><i class="fa fa-upload"></i></button>';

                // render approve button only if current user is an approver, it's their jenis, and it's their turn
                if ($user) {
                    $approver = FinanceDanaApprover::where('user_id', $user->id)->first();
                    if ($approver) {
                        $pengajuanSumber = $row->sumber_dana ?? '';
                        // check approver is allowed for this sumber_dana (empty = global)
                        $allowedForSumber = true;
                        if ($approver->jenis && trim($approver->jenis) !== '') {
                            if (strcasecmp(trim($approver->jenis), trim($pengajuanSumber)) !== 0) {
                                // not authorized for this sumber_dana
                                $allowedForSumber = false;
                            }
                        }

                        // only proceed if approver is allowed for this sumber_dana
                        if ($allowedForSumber) {
                            // sequencing: ensure each higher tingkat (level) that has approvers for this sumber_dana
                            // has at least one approval before allowing current approver to act.
                            $approverTingkat = intval($approver->tingkat ?: 1);
                            $higherTingkatValues = FinanceDanaApprover::where('aktif', 1)
                                ->where(function($q) use ($pengajuanSumber) {
                                    if ($pengajuanSumber && trim($pengajuanSumber) !== '') {
                                        $q->whereNull('jenis')->orWhere('jenis', '')->orWhere('jenis', $pengajuanSumber);
                                    } else {
                                        $q->whereNull('jenis')->orWhere('jenis', '');
                                    }
                                })
                                ->where('tingkat', '>', $approverTingkat)
                                ->distinct()
                                ->pluck('tingkat')
                                ->toArray();

                            $canApprove = true;
                            // if any approver at a higher tingkat has declined, do not allow approval
                            $higherApproverIds = FinanceDanaApprover::where('aktif', 1)
                                ->where(function($q) use ($pengajuanSumber) {
                                    if ($pengajuanSumber && trim($pengajuanSumber) !== '') {
                                        $q->whereNull('jenis')->orWhere('jenis', '')->orWhere('jenis', $pengajuanSumber);
                                    } else {
                                        $q->whereNull('jenis')->orWhere('jenis', '');
                                    }
                                })
                                ->whereIn('tingkat', $higherTingkatValues)
                                ->pluck('id')
                                ->toArray();

                            $hasHigherDeclined = false;
                            if (!empty($higherApproverIds)) {
                                $hasHigherDeclined = FinancePengajuanDanaApproval::where('pengajuan_id', $row->id)
                                    ->whereIn('approver_id', $higherApproverIds)
                                    ->where('status', 'declined')
                                    ->exists();
                            }

                            if ($hasHigherDeclined) {
                                $canApprove = false;
                            }

                            foreach ($higherTingkatValues as $ht) {
                                $approverIdsAtLevel = FinanceDanaApprover::where('aktif', 1)
                                    ->where(function($q) use ($pengajuanSumber) {
                                        if ($pengajuanSumber && trim($pengajuanSumber) !== '') {
                                            $q->whereNull('jenis')->orWhere('jenis', '')->orWhere('jenis', $pengajuanSumber);
                                        } else {
                                            $q->whereNull('jenis')->orWhere('jenis', '');
                                        }
                                    })
                                    ->where('tingkat', $ht)
                                    ->pluck('id')
                                    ->toArray();

                                if (empty($approverIdsAtLevel)) continue;

                                $hasAnyApproved = FinancePengajuanDanaApproval::where('pengajuan_id', $row->id)
                                    ->whereIn('approver_id', $approverIdsAtLevel)
                                    ->exists();

                                if (!$hasAnyApproved) { $canApprove = false; break; }
                            }

                            if ($canApprove) {
                                $already = FinancePengajuanDanaApproval::where('pengajuan_id', $row->id)->where('approver_id', $approver->id)->exists();
                                if (!$already) {
                                    // Keep approve button as text per UI requirement; add a decline option
                                    $btns .= '<button class="btn btn-sm btn-success approve-pengajuan ms-1" data-id="' . $row->id . '" title="Approve">Approve</button>';
                                    // Decline button (localized 'Tolak') — shown next to approve
                                    $btns .= '<button class="btn btn-sm btn-danger decline-pengajuan ms-1" data-id="' . $row->id . '" title="Tolak">Tolak</button>';
                                }
                            }
                        }
                    }
                }

                // Show Bayar button when pengajuan is fully approved and not yet paid
                try {
                    $user = Auth::user();
                    $canPay = $user && ($user->hasRole('Finance') || $user->hasRole('Admin'));
                    $pengajuanSumber = $row->sumber_dana ?? '';
                    $approversForJenis = FinanceDanaApprover::where('aktif', 1)
                        ->where(function($q) use ($pengajuanSumber) {
                            if ($pengajuanSumber && trim($pengajuanSumber) !== '') {
                                $q->whereNull('jenis')->orWhere('jenis', '')->orWhere('jenis', $pengajuanSumber);
                            } else {
                                $q->whereNull('jenis')->orWhere('jenis', '');
                            }
                        })
                        ->get();
                    $levels = $approversForJenis->pluck('tingkat')->unique()->sort()->values()->all();
                    $totalLevels = count($levels);
                    $approvedLevels = 0;
                    if ($totalLevels > 0) {
                        foreach ($levels as $lvl) {
                            $idsAtLevel = $approversForJenis->where('tingkat', $lvl)->pluck('id')->toArray();
                            $hasApprovedAtLevel = FinancePengajuanDanaApproval::where('pengajuan_id', $row->id)
                                ->whereIn('approver_id', $idsAtLevel)
                                ->exists();
                            if ($hasApprovedAtLevel) $approvedLevels++;
                        }
                    }
                    $hasDeclined = FinancePengajuanDanaApproval::where('pengajuan_id', $row->id)
                        ->where(function($q){ $q->where('status', 'declined')->orWhere('status', 'rejected'); })
                        ->exists();
                    $fullyApproved = (!$hasDeclined) && ($totalLevels > 0) && ($approvedLevels >= $totalLevels);
                    $isPaid = (isset($row->payment_status) && $row->payment_status === 'paid');
                    if ($fullyApproved && !$isPaid && $canPay) {
                        $btns .= '<button class="btn btn-sm btn-success pay-pengajuan ms-1" data-id="' . $row->id . '" title="Bayar">Bayar</button>';
                    }
                } catch (\Exception $e) {}

                $btns .= '</div>';
                return $btns;
            })
            ->addColumn('approvals_list', function($row) {
                // Simplified: return a single clickable badge indicating approval progress.
                $pengajuanSumber = $row->sumber_dana ?? '';
                $approversForJenis = FinanceDanaApprover::where('aktif', 1)
                    ->where(function($q) use ($pengajuanSumber) {
                        if ($pengajuanSumber && trim($pengajuanSumber) !== '') {
                            $q->whereNull('jenis')->orWhere('jenis', '')->orWhere('jenis', $pengajuanSumber);
                        } else {
                            $q->whereNull('jenis')->orWhere('jenis', '');
                        }
                    })
                    ->get();

                $levels = $approversForJenis->pluck('tingkat')->unique()->sort()->values()->all();
                $totalLevels = count($levels);
                $approvedLevels = 0;
                if ($totalLevels > 0) {
                    foreach ($levels as $lvl) {
                        $idsAtLevel = $approversForJenis->where('tingkat', $lvl)->pluck('id')->toArray();
                        $hasApprovedAtLevel = FinancePengajuanDanaApproval::where('pengajuan_id', $row->id)
                            ->whereIn('approver_id', $idsAtLevel)
                            ->exists();
                        if ($hasApprovedAtLevel) $approvedLevels++;
                    }
                }

                // if any approver has declined, show red 'Ditolak' badge
                $hasDeclined = FinancePengajuanDanaApproval::where('pengajuan_id', $row->id)
                    ->where(function($q){
                        $q->where('status', 'declined')->orWhere('status', 'rejected');
                    })->exists();

                if ($hasDeclined) {
                    $badge = '<small><span class="badge" style="background-color:#dc3545;color:#fff;">Ditolak</span></small>';
                } elseif ($totalLevels > 0 && $approvedLevels >= $totalLevels) {
                    $badge = '<small><span class="badge" style="background-color:#28a745;color:#fff;">Approved</span></small>';
                } elseif ($totalLevels > 0) {
                    $badge = '<small><span class="badge" style="background-color:#ffc107;color:#212529;">Menunggu ' . intval($approvedLevels) . '/' . intval($totalLevels) . '</span></small>';
                } else {
                    // no approvers configured for this sumber_dana
                    $badge = '<small><span class="badge badge-secondary">Belum Dikonfigurasi</span></small>';
                }

                // payment status badge below approvals
                $payBadge = '';
                try {
                    $isPaid = (isset($row->payment_status) && $row->payment_status === 'paid');
                    if ($isPaid) {
                        $payBadge = '<div class="mt-1"><small><span class="badge" style="background-color:#17a2b8;color:#fff;">Paid</span></small></div>';
                    } else {
                        $payBadge = '<div class="mt-1"><small><span class="badge" style="background-color:#6c757d;color:#fff;">Belum Dibayar</span></small></div>';
                    }
                } catch (\Exception $e) {}

                // wrap in a button so user can click to open modal showing full approval list
                $html = '<button type="button" class="btn btn-sm btn-light show-approvals" data-id="' . $row->id . '" title="Lihat Daftar Persetujuan">' . $badge . '</button>' . $payBadge;
                return $html;
            })
            // actions, approvals_list, employee_display, diajukan_ke and items_list contain HTML, mark them as raw so they are not escaped
            ->rawColumns(['actions', 'approvals_list', 'employee_display', 'diajukan_ke', 'items_list'])
            ->make(true);
    }

    // Approve an individual pengajuan (called by approver)
    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $approver = FinanceDanaApprover::where('user_id', $user->id)->first();
        if (!$approver) {
            return response()->json(['success' => false, 'message' => 'You are not configured as an approver'], 403);
        }

        $pengajuan = FinancePengajuanDana::findOrFail($id);
        // ensure this approver is configured for this sumber_dana (or global when approver.jenis empty)
        $pengajuanSumber = $pengajuan->sumber_dana ?? '';
        if ($approver->jenis && trim($approver->jenis) !== '') {
            // Only allow if approver->jenis matches pengajuan sumber_dana
            if (strcasecmp(trim($approver->jenis), trim($pengajuanSumber)) !== 0) {
                return response()->json(['success' => false, 'message' => 'You are not authorized to approve this pengajuan sumber dana'], 403);
            }
        }
        [$ok, $msg] = $this->attemptApprovePengajuan($pengajuan, $approver);
        if (!$ok) {
            // map generic messages to previous phrasing where applicable
            if (strpos($msg, 'ditolak pada tingkat lebih tinggi') !== false) {
                return response()->json(['success' => false, 'message' => 'Pengajuan telah ditolak pada tingkat lebih tinggi. Anda tidak dapat menyetujui.'], 403);
            }
            if (strpos(strtolower($msg), 'awaiting approval') !== false) {
                return response()->json(['success' => false, 'message' => 'Awaiting approval from higher level approver(s) before you can approve.'], 403);
            }
            return response()->json(['success' => false, 'message' => $msg], 400);
        }
        return response()->json(['success' => true, 'message' => 'Pengajuan approved']);
    }

    /**
     * Decline (reject) an individual pengajuan
     */
    public function decline(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $approver = FinanceDanaApprover::where('user_id', $user->id)->first();
        if (!$approver) {
            return response()->json(['success' => false, 'message' => 'You are not configured as an approver'], 403);
        }

        $pengajuan = FinancePengajuanDana::findOrFail($id);
        // ensure this approver is configured for this sumber_dana (or global when approver.jenis empty)
        $pengajuanSumber = $pengajuan->sumber_dana ?? '';
        if ($approver->jenis && trim($approver->jenis) !== '') {
            if (strcasecmp(trim($approver->jenis), trim($pengajuanSumber)) !== 0) {
                return response()->json(['success' => false, 'message' => 'You are not authorized to decline this pengajuan sumber dana'], 403);
            }
        }

        // Check sequencing same as approve: must be allowed to act now
        $approverTingkat = intval($approver->tingkat ?: 1);
        $higherTingkatValues = FinanceDanaApprover::where('aktif', 1)
            ->where(function($q) use ($pengajuanSumber) {
                if ($pengajuanSumber && trim($pengajuanSumber) !== '') {
                    $q->whereNull('jenis')->orWhere('jenis', '')->orWhere('jenis', $pengajuanSumber);
                } else {
                    $q->whereNull('jenis')->orWhere('jenis', '');
                }
            })
            ->where('tingkat', '>', $approverTingkat)
            ->distinct()
            ->pluck('tingkat')
            ->toArray();

        foreach ($higherTingkatValues as $ht) {
            $approverIdsAtLevel = FinanceDanaApprover::where('aktif', 1)
                ->where(function($q) use ($pengajuanSumber) {
                    if ($pengajuanSumber && trim($pengajuanSumber) !== '') {
                        $q->whereNull('jenis')->orWhere('jenis', '')->orWhere('jenis', $pengajuanSumber);
                    } else {
                        $q->whereNull('jenis')->orWhere('jenis', '');
                    }
                })
                ->where('tingkat', $ht)
                ->pluck('id')
                ->toArray();

            if (empty($approverIdsAtLevel)) continue;

            $hasAnyApproved = FinancePengajuanDanaApproval::where('pengajuan_id', $pengajuan->id)
                ->whereIn('approver_id', $approverIdsAtLevel)
                ->exists();

            if (!$hasAnyApproved) {
                return response()->json(['success' => false, 'message' => 'Awaiting approval from higher level approver(s) before you can act.'], 403);
            }
        }

        // check if already acted by this approver
        $existing = FinancePengajuanDanaApproval::where('pengajuan_id', $pengajuan->id)->where('approver_id', $approver->id)->first();
        if ($existing) {
            return response()->json(['success' => false, 'message' => 'You have already acted on this pengajuan']);
        }

        $note = $request->input('note') ?: null;

        $approval = FinancePengajuanDanaApproval::create([
            'pengajuan_id' => $pengajuan->id,
            'approver_id' => $approver->id,
            'status' => 'declined',
            'tanggal_approve' => Carbon::now(),
            'note' => $note,
        ]);

        // mark pengajuan overall status as declined so downstream approvers cannot act
        try {
            $pengajuan->status = 'declined';
            $pengajuan->save();
        } catch (\Exception $e) {
            // ignore save errors to avoid blocking decline action
        }

        return response()->json(['success' => true, 'message' => 'Pengajuan ditolak', 'data' => $approval]);
    }

    /**
     * Upload bukti_transaksi files for an existing pengajuan (available to all users)
     */
    public function uploadBukti(Request $request, $id)
    {
        $pengajuan = FinancePengajuanDana::findOrFail($id);

        $request->validate([
            'bukti_transaksi' => 'required',
            'bukti_transaksi.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $paths = [];
        if ($request->hasFile('bukti_transaksi')) {
            foreach ($request->file('bukti_transaksi') as $file) {
                if ($file && $file->isValid()) {
                    $paths[] = $file->store('finance/pengajuan', 'public');
                }
            }
        }

        // merge with existing bukti_transaksi if any
        $existing = $pengajuan->bukti_transaksi ?: [];
        if (!is_array($existing)) {
            try { $existing = json_decode($existing, true) ?: []; } catch (\Exception $e) { $existing = []; }
        }

        $merged = array_values(array_filter(array_merge($existing, $paths)));
        $pengajuan->bukti_transaksi = json_encode($merged);
        $pengajuan->save();

        return response()->json(['success' => true, 'message' => 'Bukti transaksi berhasil diupload', 'paths' => $merged]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode_pengajuan' => 'required|string|unique:finance_pengajuan_dana,kode_pengajuan',
            'employee_id' => 'nullable|integer',
            'division_id' => 'nullable|integer',
            'sumber_dana' => 'nullable|string',
            'perusahaan' => 'nullable|string',
            'tanggal_pengajuan' => 'nullable|date',
            'jenis_pengajuan' => 'nullable|string',
            'status' => 'nullable|string',
            'rekening_id' => 'nullable|integer|exists:finance_rekening,id',
            'items_json' => 'nullable|json',
            'bukti_transaksi' => 'nullable',
            'bukti_transaksi.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // If division_id wasn't provided, attempt to infer it from selected employee
        if (empty($data['division_id']) && !empty($data['employee_id'])) {
            try {
                $emp = \App\Models\HRD\Employee::find($data['employee_id']);
                if ($emp && !empty($emp->division_id)) {
                    $data['division_id'] = $emp->division_id;
                }
            } catch (\Exception $e) {
                // ignore and proceed with null division_id
            }
        }

        // Handle multiple file uploads if present
        if ($request->hasFile('bukti_transaksi')) {
            $paths = [];
            foreach ($request->file('bukti_transaksi') as $file) {
                if ($file && $file->isValid()) {
                    $paths[] = $file->store('finance/pengajuan', 'public');
                }
            }
            // store as JSON array of paths
            $data['bukti_transaksi'] = json_encode($paths);
        }

        // persist pengajuan and its items in a transaction
        $pengajuan = null;
        $pengajuanId = null;
        DB::transaction(function() use (&$pengajuan, &$pengajuanId, $data, $request) {
            // prepare grand_total from items_json if provided
            $grandTotal = 0;
            $itemsJson = $request->input('items_json');
            if ($itemsJson) {
                $items = json_decode($itemsJson, true);
                if (is_array($items)) {
                    foreach ($items as $it) {
                        $qty = isset($it['qty']) ? intval($it['qty']) : 1;
                        $price = isset($it['price']) ? floatval($it['price']) : 0;
                        $grandTotal += ($qty * $price);
                    }
                }
            }

            // set grand_total into data so it is persisted atomically with pengajuan
            $data['grand_total'] = $grandTotal;

            $pengajuan = FinancePengajuanDana::create($data);
            $pengajuanId = $pengajuan->id;

            // persist items if present
            if (!empty($items) && is_array($items)) {
                foreach ($items as $it) {
                    // guard and normalize
                    $name = isset($it['desc']) ? $it['desc'] : (isset($it['name']) ? $it['name'] : null);
                    $qty = isset($it['qty']) ? intval($it['qty']) : 1;
                    $price = isset($it['price']) ? floatval($it['price']) : 0;
                    $itemEmployeeId = isset($it['employee_id']) && $it['employee_id'] !== '' ? intval($it['employee_id']) : null;
                    $itemNotes = isset($it['notes']) && $it['notes'] !== '' ? $it['notes'] : null;

                    // faktur-type item: server-side authoritative snapshot
                    $fakturbeliId = isset($it['fakturbeli_id']) ? intval($it['fakturbeli_id']) : null;
                    if ($fakturbeliId) {
                        $faktur = FakturBeli::find($fakturbeliId);
                        if ($faktur) {
                            // preserve frontend-provided description (which includes full item list)
                            // only fallback to a default label when no description was provided
                            if (!$name || trim($name) === '') {
                                $name = 'Faktur: ' . ($faktur->no_faktur ?? '');
                            }
                            // snapshot faktur total to harga_total_snapshot and use as price
                            $price = floatval($faktur->total ?? $price);
                            FinancePengajuanDanaItem::create([
                                'pengajuan_id' => $pengajuan->id,
                                'nama_item' => $name,
                                'jumlah' => 1,
                                'harga_satuan' => $price,
                                'employee_id' => $itemEmployeeId,
                                'notes' => $itemNotes,
                                'fakturbeli_id' => $fakturbeliId,
                                'is_faktur' => true,
                                'harga_total_snapshot' => $price,
                            ]);
                            continue; // next item
                        }
                        // if faktur not found, fall back to normal item creation (but still record provided data)
                    }

                    FinancePengajuanDanaItem::create([
                        'pengajuan_id' => $pengajuan->id,
                        'nama_item' => $name,
                        'jumlah' => $qty,
                        'harga_satuan' => $price,
                        'employee_id' => $itemEmployeeId,
                        'notes' => $itemNotes,
                    ]);
                }
            }
        });

    // reload from DB with items for response (avoid null/type issues)
    $pengajuan = FinancePengajuanDana::with('items')->find($pengajuanId);
    return response()->json(['success' => true, 'data' => $pengajuan]);
    }

    public function show($id)
    {
        $pengajuan = FinancePengajuanDana::with(['items', 'approvals', 'employee.user', 'division'])->findOrFail($id);
        return response()->json($pengajuan);
    }

    /**
     * Generate PDF view for a pengajuan dana
     */
    public function pdf($id)
    {
        $pengajuan = FinancePengajuanDana::with(['items', 'approvals.approver.user', 'employee.user', 'division', 'rekening'])->findOrFail($id);
        // collect linked faktur IDs from items
        $fakturIds = collect($pengajuan->items)->pluck('fakturbeli_id')->filter()->unique()->values()->all();
        $fakturs = [];
        if (!empty($fakturIds)) {
            $fakturs = FakturBeli::with(['items.obat', 'items.gudang', 'pemasok'])->whereIn('id', $fakturIds)->get();
        }
        // locate a logo asset if present
        $logoCandidates = [
            'img/logo-belovacorp.png',
            'img/logo-belova-klinik.png',
            'img/logo-belovaskin.png',
            'img/logo-premiere.png',
            'img/logo-belovacorp-bw.png'
        ];
        $logoPath = null;
        foreach ($logoCandidates as $c) {
            $p = public_path($c);
            if ($p && file_exists($p)) { $logoPath = $p; break; }
        }
        // build signature QR codes for approvals and creator (tanda tangan)
        $signatures = [];
        try {
            // creator / pembuat - determine a robust name fallback
            $creatorUser = ($pengajuan->employee && $pengajuan->employee->user) ? $pengajuan->employee->user : $pengajuan->employee;
            $creatorName = $creatorUser ? ($creatorUser->name ?? $creatorUser->nama ?? '') : '';
            $creatorDate = $pengajuan->tanggal_pengajuan ? Carbon::parse($pengajuan->tanggal_pengajuan)->format('d M Y') : '';
            $creatorQr = null;
            if (!empty($creatorName)) {
                try {
                    $png = QrCode::format('png')->size(160)->generate($creatorName);
                    if (!empty($png)) {
                        $creatorQr = 'data:image/png;base64,' . base64_encode($png);
                    }
                } catch (\Exception $e) {
                    // fallback to svg if png generation fails
                    try {
                        $svg = QrCode::format('svg')->size(160)->generate($creatorName);
                        if (!empty($svg)) {
                            $creatorQr = 'data:image/svg+xml;base64,' . base64_encode($svg);
                        }
                    } catch (\Exception $e) {
                        // ignore, leave qr null
                    }
                }
            }
            // try to get creator jabatan/position
            $creatorJabatan = '';
            if ($pengajuan->employee) {
                if (isset($pengajuan->employee->position) && !empty($pengajuan->employee->position->name)) {
                    $creatorJabatan = $pengajuan->employee->position->name;
                } elseif (!empty($pengajuan->employee->jabatan)) {
                    $creatorJabatan = $pengajuan->employee->jabatan;
                } elseif (!empty($creatorUser->jabatan)) {
                    $creatorJabatan = $creatorUser->jabatan;
                }
            }

            $signatures[] = [
                'label' => 'Dibuat oleh',
                'name' => $creatorName,
                'jabatan' => $creatorJabatan,
                'date' => $creatorDate,
                'qr' => $creatorQr,
            ];

            // approvals
            foreach ($pengajuan->approvals as $ap) {
                $approverName = '';
                if ($ap->approver && $ap->approver->user) {
                    $approverName = $ap->approver->user->name;
                }
                $approveDate = $ap->tanggal_approve ? Carbon::parse($ap->tanggal_approve)->format('d M Y') : '';
                $qr = null;
                if (!empty($approverName)) {
                    try {
                        $png = QrCode::format('png')->size(160)->generate($approverName);
                        if (!empty($png)) {
                            $qr = 'data:image/png;base64,' . base64_encode($png);
                        }
                    } catch (\Exception $e) {
                        // fallback svg
                        try {
                            $svg = QrCode::format('svg')->size(160)->generate($approverName);
                            if (!empty($svg)) {
                                $qr = 'data:image/svg+xml;base64,' . base64_encode($svg);
                            }
                        } catch (\Exception $e) {
                            // ignore
                        }
                    }
                }
                // approver jabatan (from FinanceDanaApprover.jabatan)
                $approverJabatan = '';
                if ($ap->approver) {
                    $approverJabatan = $ap->approver->jabatan ?? '';
                }
                $signatures[] = [
                    'label' => 'Disetujui',
                    'name' => $approverName,
                    'jabatan' => $approverJabatan,
                    'date' => $approveDate,
                    'qr' => $qr,
                ];
            }
        } catch (\Exception $e) {
            // QR generation failed for some reason; proceed without QR images
        }

        // load blade and render to PDF
        try {
            $pdf = PDF::loadView('finance.pengajuan.pdf', compact('pengajuan', 'fakturs', 'logoPath', 'signatures'));
            // Render the PDF in landscape orientation to better fit wide item tables
            $pdf->setPaper('a4', 'landscape');
            return $pdf->stream('pengajuan_' . $pengajuan->id . '.pdf');
        } catch (\Exception $e) {
            // fallback to HTML view if PDF generation fails
            return view('finance.pengajuan.pdf', compact('pengajuan', 'fakturs', 'logoPath', 'signatures'));
        }
    }

    public function update(Request $request, $id)
    {
        $pengajuan = FinancePengajuanDana::findOrFail($id);
        $data = $request->validate([
            'employee_id' => 'nullable|integer',
            'division_id' => 'nullable|integer',
            'sumber_dana' => 'nullable|string',
            'perusahaan' => 'nullable|string',
            'tanggal_pengajuan' => 'nullable|date',
            'jenis_pengajuan' => 'nullable|string',
            'status' => 'nullable|string',
            'rekening_id' => 'nullable|integer|exists:finance_rekening,id',
            'items_json' => 'nullable|json',
            'bukti_transaksi' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // If division_id not provided on update, try to infer from employee relation
        if (empty($data['division_id']) && !empty($data['employee_id'])) {
            try {
                $emp = \App\Models\HRD\Employee::find($data['employee_id']);
                if ($emp && !empty($emp->division_id)) {
                    $data['division_id'] = $emp->division_id;
                }
            } catch (\Exception $e) {
                // ignore
            }
        }

        // Handle file upload: delete old file if exists and replace
        if ($request->hasFile('bukti_transaksi')) {
            // delete existing files if stored as JSON array or single string
            if ($pengajuan->bukti_transaksi) {
                $existing = $pengajuan->bukti_transaksi;
                $arr = [];
                try { $arr = json_decode($existing, true) ?: []; } catch (\Exception $e) { $arr = []; }
                if (!empty($arr) && is_array($arr)) {
                    foreach ($arr as $p) { Storage::disk('public')->delete($p); }
                } else {
                    // maybe single string path
                    Storage::disk('public')->delete($existing);
                }
            }

            $paths = [];
            foreach ($request->file('bukti_transaksi') as $file) {
                if ($file && $file->isValid()) {
                    $paths[] = $file->store('finance/pengajuan', 'public');
                }
            }
            $data['bukti_transaksi'] = json_encode($paths);
        }

        DB::transaction(function() use ($pengajuan, $data, $request) {
            // compute grand_total from incoming items_json (if present)
            $grandTotal = 0;
            $itemsJson = $request->input('items_json');
            $items = [];
                if ($itemsJson !== null) {
                $items = json_decode($itemsJson, true) ?: [];
                if (is_array($items)) {
                    foreach ($items as $it) {
                        // handle faktur items too
                        if (isset($it['fakturbeli_id']) && $it['fakturbeli_id']) {
                            $fakt = FakturBeli::find(intval($it['fakturbeli_id']));
                            if ($fakt) {
                                $grandTotal += floatval($fakt->total ?? 0);
                                continue;
                            }
                        }
                        $qty = isset($it['qty']) ? intval($it['qty']) : 1;
                        $price = isset($it['price']) ? floatval($it['price']) : 0;
                        $grandTotal += ($qty * $price);
                    }
                }
                // assign computed grand_total to data for update
                $data['grand_total'] = $grandTotal;
            }

            $pengajuan->update($data);

            // update items: delete existing and recreate from items_json
                if ($itemsJson !== null) {
                $pengajuan->items()->delete();
                if (is_array($items)) {
                    foreach ($items as $it) {
                        $name = isset($it['desc']) ? $it['desc'] : (isset($it['name']) ? $it['name'] : null);
                        $qty = isset($it['qty']) ? intval($it['qty']) : 1;
                        $price = isset($it['price']) ? floatval($it['price']) : 0;
                        $itemEmployeeId = isset($it['employee_id']) && $it['employee_id'] !== '' ? intval($it['employee_id']) : null;
                        $itemNotes = isset($it['notes']) && $it['notes'] !== '' ? $it['notes'] : null;
                        $fakturbeliId = isset($it['fakturbeli_id']) ? intval($it['fakturbeli_id']) : null;
                        if ($fakturbeliId) {
                            $faktur = FakturBeli::find($fakturbeliId);
                            if ($faktur) {
                                // keep provided description (includes item list) when available
                                if (!$name || trim($name) === '') {
                                    $name = 'Faktur: ' . ($faktur->no_faktur ?? '');
                                }
                                $price = floatval($faktur->total ?? $price);
                                FinancePengajuanDanaItem::create([
                                    'pengajuan_id' => $pengajuan->id,
                                    'nama_item' => $name,
                                    'jumlah' => 1,
                                    'harga_satuan' => $price,
                                    'employee_id' => $itemEmployeeId,
                                    'notes' => $itemNotes,
                                    'fakturbeli_id' => $fakturbeliId,
                                    'is_faktur' => true,
                                    'harga_total_snapshot' => $price,
                                ]);
                                continue;
                            }
                        }
                        FinancePengajuanDanaItem::create([
                            'pengajuan_id' => $pengajuan->id,
                            'nama_item' => $name,
                            'jumlah' => $qty,
                            'harga_satuan' => $price,
                            'employee_id' => $itemEmployeeId,
                            'notes' => $itemNotes,
                        ]);
                    }
                }
            }
        });

    // reload from DB with items for response
    $pengajuan = FinancePengajuanDana::with('items')->find($pengajuan->id);
    return response()->json(['success' => true, 'data' => $pengajuan]);
    }

    public function destroy($id)
    {
        $pengajuan = FinancePengajuanDana::findOrFail($id);
        $pengajuan->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Return approvals details for a pengajuan (for modal display)
     */
    public function approvalsDetails($id)
    {
        $pengajuan = FinancePengajuanDana::findOrFail($id);

        // determine applicable approvers for this pengajuan's sumber_dana
        $pengajuanSumber = $pengajuan->sumber_dana ?? '';
        $approvers = FinanceDanaApprover::where('aktif', 1)
            ->where(function($q) use ($pengajuanSumber) {
                if ($pengajuanSumber && trim($pengajuanSumber) !== '') {
                    $q->whereNull('jenis')->orWhere('jenis', '')->orWhere('jenis', $pengajuanSumber);
                } else {
                    $q->whereNull('jenis')->orWhere('jenis', '');
                }
            })
            ->orderBy('tingkat')
            ->get();

        // map approvals by approver_id for quick lookup
        $existingApprovals = FinancePengajuanDanaApproval::where('pengajuan_id', $pengajuan->id)
            ->get()->keyBy('approver_id');

        $list = [];
        foreach ($approvers as $app) {
            $name = '';
            if ($app->user) {
                $name = $app->user->name ?? '';
            } else {
                $name = $app->nama ?? '';
            }
            $jabatan = $app->jabatan ?? '';
            $status = 'waiting';
            $date = '';
            if ($existingApprovals->has($app->id)) {
                $ap = $existingApprovals->get($app->id);
                // Normalize status values so the frontend can render correct icons
                if (isset($ap->status) && $ap->status === 'approved') {
                    $status = 'approved';
                } elseif (isset($ap->status) && ($ap->status === 'declined' || $ap->status === 'rejected')) {
                    $status = 'declined';
                } else {
                    // fallback to the raw status or mark as approved if unknown (preserve behavior)
                    $status = $ap->status ?: 'waiting';
                }
                $date = $ap->tanggal_approve ? Carbon::parse($ap->tanggal_approve)->format('d M Y') : '';
            }
            $list[] = [
                'approver_id' => $app->id,
                'name' => $name,
                'jabatan' => $jabatan,
                'tingkat' => $app->tingkat,
                'status' => $status,
                'date' => $date,
            ];
        }

        return response()->json(['success' => true, 'data' => $list]);
    }

    /**
     * Mark a pengajuan as paid (sets payment_status='paid').
     */
    public function markPaid(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        // Only users with Finance or Admin role can mark as paid
        if (!($user->hasRole('Finance') || $user->hasRole('Admin'))) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }
        $pengajuan = FinancePengajuanDana::findOrFail($id);

        // Only allow marking as paid if fully approved and not declined
        $pengajuanSumber = $pengajuan->sumber_dana ?? '';
        $approversForJenis = FinanceDanaApprover::where('aktif', 1)
            ->where(function($q) use ($pengajuanSumber) {
                if ($pengajuanSumber && trim($pengajuanSumber) !== '') {
                    $q->whereNull('jenis')->orWhere('jenis', '')->orWhere('jenis', $pengajuanSumber);
                } else {
                    $q->whereNull('jenis')->orWhere('jenis', '');
                }
            })
            ->get();
        $levels = $approversForJenis->pluck('tingkat')->unique()->sort()->values()->all();
        $totalLevels = count($levels);
        $approvedLevels = 0;
        if ($totalLevels > 0) {
            foreach ($levels as $lvl) {
                $idsAtLevel = $approversForJenis->where('tingkat', $lvl)->pluck('id')->toArray();
                $hasApprovedAtLevel = FinancePengajuanDanaApproval::where('pengajuan_id', $pengajuan->id)
                    ->whereIn('approver_id', $idsAtLevel)
                    ->exists();
                if ($hasApprovedAtLevel) $approvedLevels++;
            }
        }
        $hasDeclined = FinancePengajuanDanaApproval::where('pengajuan_id', $pengajuan->id)
            ->where(function($q){ $q->where('status', 'declined')->orWhere('status', 'rejected'); })
            ->exists();
        $fullyApproved = (!$hasDeclined) && ($totalLevels > 0) && ($approvedLevels >= $totalLevels);

        if (!$fullyApproved) {
            return response()->json(['success' => false, 'message' => 'Pengajuan belum approved lengkap'], 422);
        }

        if ($pengajuan->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Pengajuan sudah dibayar'], 422);
        }

        $pengajuan->payment_status = 'paid';
        $pengajuan->save();

        return response()->json(['success' => true, 'message' => 'Status pembayaran: paid']);
    }

    /**
     * Bulk approve multiple pengajuan IDs for the current approver.
     * Body: { ids: [1,2,3,...] }
     */
    public function bulkApprove(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $approver = FinanceDanaApprover::where('user_id', $user->id)->first();
        if (!$approver) {
            return response()->json(['success' => false, 'message' => 'You are not configured as an approver'], 403);
        }

        $ids = $request->input('ids');
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided'], 422);
        }

        $approved = [];
        $skipped = [];
        $errors = [];

        foreach ($ids as $rawId) {
            $id = intval($rawId);
            try {
                $pengajuan = FinancePengajuanDana::find($id);
                if (!$pengajuan) {
                    $errors[] = [ 'id' => $id, 'reason' => 'Not found' ];
                    continue;
                }
                // jenis/source check
                $pengajuanSumber = $pengajuan->sumber_dana ?? '';
                if ($approver->jenis && trim($approver->jenis) !== '') {
                    if (strcasecmp(trim($approver->jenis), trim($pengajuanSumber)) !== 0) {
                        $skipped[] = [ 'id' => $id, 'reason' => 'Not authorized for sumber dana' ];
                        continue;
                    }
                }
                [$ok, $msg] = $this->attemptApprovePengajuan($pengajuan, $approver);
                if ($ok) {
                    $approved[] = $id;
                } else {
                    $skipped[] = [ 'id' => $id, 'reason' => $msg ];
                }
            } catch (\Throwable $e) {
                $errors[] = [ 'id' => $id, 'reason' => 'Server error' ];
            }
        }

        return response()->json([
            'success' => true,
            'approved_count' => count($approved),
            'approved' => $approved,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => 'Bulk approval processed',
        ]);
    }
}
